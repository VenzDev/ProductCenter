# GitHub Actions authenticates to AWS through this OIDC provider + role to push images
# to ECR, rather than static access keys stored as repo secrets — same reasoning as the
# IRSA roles in infrastructure/eks/iam.tf. The trust policy is scoped to this one
# repository; the role can only push/pull the four ECR repositories, nothing else.

# GitHub rewrites the OIDC `sub` claim to the immutable `login@id` / `name@id` form for any
# account or repo that has been renamed (this one was), and keeps it that way permanently — so
# the subject is `repo:VenzDev@44263739/ProductCenter@1309212235:...`, not the plain slug. AWS
# also requires the trust policy to constrain `sub` (or `job_workflow_ref`), so we match against
# that exact renamed subject.
variable "github_repository" {
  description = "GitHub repo in OIDC-subject form (login@id/name@id for github.com/VenzDev/ProductCenter) allowed to assume the ECR push role"
  type        = string
  default     = "VenzDev@44263739/ProductCenter@1309212235"
}

# AWS validates GitHub's OIDC token against its own trust store, so thumbprint_list is
# not needed (provider retrieves it).
resource "aws_iam_openid_connect_provider" "github" {
  url            = "https://token.actions.githubusercontent.com"
  client_id_list = ["sts.amazonaws.com"]
}

data "aws_iam_policy_document" "github_actions_ecr_assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = [aws_iam_openid_connect_provider.github.arn]
    }

    condition {
      test     = "StringEquals"
      variable = "token.actions.githubusercontent.com:aud"
      values   = ["sts.amazonaws.com"]
    }

    condition {
      test     = "StringLike"
      variable = "token.actions.githubusercontent.com:sub"
      values   = ["repo:${var.github_repository}:*"]
    }
  }
}

resource "aws_iam_role" "github_actions_ecr" {
  name               = "${var.project}-github-actions-ecr"
  assume_role_policy = data.aws_iam_policy_document.github_actions_ecr_assume_role.json
}

data "aws_iam_policy_document" "github_actions_ecr" {
  statement {
    sid       = "AuthToken"
    effect    = "Allow"
    actions   = ["ecr:GetAuthorizationToken"]
    resources = ["*"] # this action does not support resource-level scoping
  }

  statement {
    sid    = "PushPull"
    effect = "Allow"
    actions = [
      "ecr:BatchCheckLayerAvailability",
      "ecr:BatchGetImage",
      "ecr:GetDownloadUrlForLayer",
      "ecr:InitiateLayerUpload",
      "ecr:UploadLayerPart",
      "ecr:CompleteLayerUpload",
      "ecr:PutImage",
    ]
    resources = [for repo in aws_ecr_repository.this : repo.arn]
  }
}

resource "aws_iam_role_policy" "github_actions_ecr" {
  name   = "ecr-push"
  role   = aws_iam_role.github_actions_ecr.id
  policy = data.aws_iam_policy_document.github_actions_ecr.json
}
