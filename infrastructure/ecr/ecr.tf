resource "aws_ecr_repository" "this" {
  for_each = toset(["payment", "backend", "frontend", "opensearch"])

  name         = each.key
  force_delete = true
}

# Cap each repo at 3 images to keep storage cost negligible — every build pushes two
# tags (commit SHA + a moving tag), so ECR expires the oldest images by push date once
# a repo holds more than 3.
resource "aws_ecr_lifecycle_policy" "this" {
  for_each = aws_ecr_repository.this

  repository = each.value.name

  policy = jsonencode({
    rules = [{
      rulePriority = 1
      description  = "Keep only the 3 most recent images"
      selection = {
        tagStatus   = "any"
        countType   = "imageCountMoreThan"
        countNumber = 3
      }
      action = { type = "expire" }
    }]
  })
}
