output "cluster_name" {
  description = "EKS cluster name"
  value       = module.eks.cluster_name
}

output "cluster_endpoint" {
  description = "EKS cluster API endpoint"
  value       = module.eks.cluster_endpoint
}

output "configure_kubectl" {
  description = "Command to configure kubectl"
  value       = "aws eks update-kubeconfig --name ${module.eks.cluster_name} --region ${var.region}"
}

output "ecr_repository_urls" {
  description = "ECR repository URLs per service"
  value       = { for name, repo in aws_ecr_repository.this : name => repo.repository_url }
}

output "s3_bucket_name" {
  description = "S3 bucket for product files — paste into k8s/chart/values/backend.yaml (AWS_BUCKET)"
  value       = aws_s3_bucket.product_files.bucket
}

output "backend_s3_irsa_role_arn" {
  description = "IAM role ARN for the backend's ServiceAccount — paste into k8s/chart/values/backend.yaml (serviceAccount.annotations)"
  value       = aws_iam_role.backend_s3.arn
}

output "rds_endpoint" {
  description = "RDS Postgres host — paste into k8s/chart/values/backend.yaml (DB_HOST)"
  value       = aws_db_instance.this.address
}

output "rds_master_user_secret_arn" {
  description = "Secrets Manager ARN holding the RDS master password — fetch with: aws secretsmanager get-secret-value --secret-id <this> --query SecretString --output text | jq -r .password"
  value       = aws_db_instance.this.master_user_secret[0].secret_arn
}
