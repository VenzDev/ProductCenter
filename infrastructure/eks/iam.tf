# IRSA (IAM role for a Kubernetes ServiceAccount) — the backend authenticates to S3 via
# this role instead of static access keys. The EKS module creates the OIDC provider by
# default (enable_irsa defaults to true), so it just needs referencing here.

data "aws_iam_policy_document" "backend_s3_assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = [module.eks.oidc_provider_arn]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:sub"
      # Must match the ServiceAccount the backend actually runs as — see
      # infrastructure/k8s/chart/values/backend.yaml (serviceAccount.name) and the "default" namespace
      # used by `helm install backend ...` in docs/runbook.md.
      values = ["system:serviceaccount:default:backend"]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:aud"
      values   = ["sts.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "backend_s3" {
  name               = "${var.cluster_name}-backend-s3"
  assume_role_policy = data.aws_iam_policy_document.backend_s3_assume_role.json
}

data "aws_iam_policy_document" "backend_s3_access" {
  statement {
    effect    = "Allow"
    actions   = ["s3:PutObject", "s3:PutObjectAcl", "s3:GetObject", "s3:DeleteObject"]
    resources = ["${aws_s3_bucket.product_files.arn}/*"]
  }

  statement {
    effect    = "Allow"
    actions   = ["s3:ListBucket"]
    resources = [aws_s3_bucket.product_files.arn]
  }
}

resource "aws_iam_role_policy" "backend_s3" {
  name   = "s3-access"
  role   = aws_iam_role.backend_s3.id
  policy = data.aws_iam_policy_document.backend_s3_access.json
}

# IRSA for the AWS Load Balancer Controller — a cluster-wide k8s controller that watches
# Ingress resources and provisions/manages ALBs for them. Installed via `helm install`
# (docs/runbook.md), not by this Terraform — only its permissions are managed here, same
# split as kube-prometheus-stack. Policy is AWS's own published one, not hand-written:
# https://raw.githubusercontent.com/kubernetes-sigs/aws-load-balancer-controller/main/docs/install/iam_policy.json

data "aws_iam_policy_document" "aws_load_balancer_controller_assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = [module.eks.oidc_provider_arn]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:sub"
      # Must match the ServiceAccount the controller's Helm chart creates for itself —
      # see the `helm install aws-load-balancer-controller` step in docs/runbook.md.
      values = ["system:serviceaccount:kube-system:aws-load-balancer-controller"]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:aud"
      values   = ["sts.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "aws_load_balancer_controller" {
  name               = "${var.cluster_name}-aws-load-balancer-controller"
  assume_role_policy = data.aws_iam_policy_document.aws_load_balancer_controller_assume_role.json
}

resource "aws_iam_policy" "aws_load_balancer_controller" {
  name   = "${var.cluster_name}-aws-load-balancer-controller"
  policy = file("${path.module}/policies/aws-load-balancer-controller-policy.json")
}

resource "aws_iam_role_policy_attachment" "aws_load_balancer_controller" {
  role       = aws_iam_role.aws_load_balancer_controller.name
  policy_arn = aws_iam_policy.aws_load_balancer_controller.arn
}

data "aws_iam_policy_document" "cloudwatch_observability_assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = [module.eks.oidc_provider_arn]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:sub"
      # Fixed name/namespace the addon creates its ServiceAccount under — not
      # configurable, unlike the backend's own ServiceAccount above.
      values = ["system:serviceaccount:amazon-cloudwatch:cloudwatch-agent"]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:aud"
      values   = ["sts.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "cloudwatch_observability" {
  name               = "${var.cluster_name}-cloudwatch-observability"
  assume_role_policy = data.aws_iam_policy_document.cloudwatch_observability_assume_role.json
}

resource "aws_iam_role_policy_attachment" "cloudwatch_observability" {
  role       = aws_iam_role.cloudwatch_observability.name
  policy_arn = "arn:aws:iam::aws:policy/CloudWatchAgentServerPolicy"
}

# IRSA for External Secrets Operator — a cluster-wide k8s controller that reads backend
# app secrets (app-key, azure-*, stripe-*), the OpenSearch admin password, and
# notification's Mailgun credentials from SSM Parameter Store, plus db-password from
# RDS's own Secrets Manager entry, syncing all of it into the `backend-secrets`,
# `opensearch-secrets` and `notification-secrets` k8s Secrets (SecretStore/ExternalSecret
# pairs shipped in infrastructure/k8s/backend/templates,
# infrastructure/k8s/opensearch/templates and infrastructure/k8s/notification/templates).
# Installed via `helm install`
# (docs/runbook.md), not by this Terraform — same split as the AWS Load Balancer
# Controller above. No per-SecretStore auth is configured — each SecretStore relies on
# the controller pod's own IRSA identity, so this one role covers all of them, scoped
# tightly to just those sources.

data "aws_caller_identity" "current" {}

data "aws_iam_policy_document" "external_secrets_assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = [module.eks.oidc_provider_arn]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:sub"
      # Must match the ServiceAccount created by the `helm install external-secrets`
      # step in docs/runbook.md (--set serviceAccount.name=external-secrets, namespace
      # external-secrets).
      values = ["system:serviceaccount:external-secrets:external-secrets"]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(module.eks.cluster_oidc_issuer_url, "https://", "")}:aud"
      values   = ["sts.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "external_secrets" {
  name               = "${var.cluster_name}-external-secrets"
  assume_role_policy = data.aws_iam_policy_document.external_secrets_assume_role.json
}

data "aws_iam_policy_document" "external_secrets_access" {
  statement {
    effect  = "Allow"
    actions = ["ssm:GetParameter", "ssm:GetParameters", "ssm:GetParametersByPath", "ssm:DescribeParameters"]
    resources = [
      "arn:aws:ssm:${var.region}:${data.aws_caller_identity.current.account_id}:parameter/product-center/backend/*",
      "arn:aws:ssm:${var.region}:${data.aws_caller_identity.current.account_id}:parameter/product-center/opensearch/*",
      "arn:aws:ssm:${var.region}:${data.aws_caller_identity.current.account_id}:parameter/product-center/notification/*"
    ]
  }

  statement {
    # Parameters are SecureString, encrypted with the default AWS-managed SSM key —
    # narrowed via ViaService instead of a specific key ARN, since that key has no
    # single predictable ARN/alias resource to reference here.
    effect    = "Allow"
    actions   = ["kms:Decrypt"]
    resources = ["*"]

    condition {
      test     = "StringEquals"
      variable = "kms:ViaService"
      values   = ["ssm.${var.region}.amazonaws.com"]
    }
  }

  statement {
    # db-password (the other key merged into backend-secrets, see
    # infrastructure/k8s/backend/templates/externalsecret.yaml) — RDS's own
    # Secrets Manager entry, not something this project writes to, so scoped to
    # exactly that one secret rather than the SSM path pattern above.
    effect    = "Allow"
    actions   = ["secretsmanager:GetSecretValue"]
    resources = [aws_db_instance.this.master_user_secret[0].secret_arn]
  }
}

resource "aws_iam_role_policy" "external_secrets" {
  name   = "ssm-access"
  role   = aws_iam_role.external_secrets.id
  policy = data.aws_iam_policy_document.external_secrets_access.json
}
