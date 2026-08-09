variable "region" {
  description = "AWS region"
  type        = string
  default     = "eu-central-1"
}

variable "cluster_name" {
  description = "EKS cluster name"
  type        = string
  default     = "product-center"
}

variable "node_instance_type" {
  description = "EC2 instance type for the managed node group"
  type        = string
  default     = "t3.large"
}

variable "rds_instance_class" {
  description = "RDS instance class for the Postgres database"
  type        = string
  default     = "db.t3.micro"
}
