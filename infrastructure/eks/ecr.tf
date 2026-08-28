resource "aws_ecr_repository" "this" {
  for_each = toset(["payment", "backend", "frontend", "opensearch"])

  name         = each.key
  force_delete = true
}
