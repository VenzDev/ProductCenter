resource "aws_ecr_repository" "this" {
  for_each = toset(["ai", "payment", "backend"])

  name         = each.key
  force_delete = true
}
