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
