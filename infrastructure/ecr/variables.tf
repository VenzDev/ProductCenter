# This is a separate Terraform root module from infrastructure/eks so the ECR repos and
# the GitHub Actions push role can be stood up (and torn down) on their own — building
# and pushing images doesn't need the VPC/EKS/RDS stack. EKS nodes still pull from these
# repos: the managed node group's role carries AWS's ECR read-only policy, which is
# account-wide, so no cross-module wiring is required.

variable "region" {
  description = "AWS region"
  type        = string
  default     = "eu-central-1"
}

variable "project" {
  description = "Name prefix for created resources"
  type        = string
  default     = "product-center"
}
