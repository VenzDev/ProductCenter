output "ecr_repository_urls" {
  description = "ECR repository URLs per service"
  value       = { for name, repo in aws_ecr_repository.this : name => repo.repository_url }
}

output "github_actions_ecr_role_arn" {
  description = "IAM role ARN the image-build GitHub workflows assume via OIDC — matches AWS_ROLE_ARN in .github/workflows/*-image.yaml"
  value       = aws_iam_role.github_actions_ecr.arn
}
