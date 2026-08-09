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
      # k8s/chart/values/backend.yaml (serviceAccount.name) and the "default" namespace
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
