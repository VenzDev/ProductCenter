resource "aws_ecr_repository" "this" {
  for_each = toset(["payment", "backend", "frontend"])

  name         = each.key
  force_delete = true
}
