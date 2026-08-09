resource "aws_s3_bucket" "product_files" {
  bucket        = "${var.cluster_name}-product-files"
  force_destroy = true # educational cluster — no need to protect against accidental data loss
}

resource "aws_s3_bucket_ownership_controls" "product_files" {
  bucket = aws_s3_bucket.product_files.id

  rule {
    # ObjectWriter (not the newer BucketOwnerEnforced default) so the uploader can set a
    # per-object public-read ACL — same mechanism the backend already uses against
    # LocalStack locally (Filament's ->visibility('public')), kept identical here.
    object_ownership = "ObjectWriter"
  }
}

resource "aws_s3_bucket_public_access_block" "product_files" {
  bucket = aws_s3_bucket.product_files.id

  block_public_acls       = false
  ignore_public_acls      = false
  block_public_policy     = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_cors_configuration" "product_files" {
  bucket = aws_s3_bucket.product_files.id

  cors_rule {
    # GET/HEAD-only on public, non-sensitive product images — a wildcard origin is the
    # normal pattern for this (same as a public CDN asset bucket), not a security hole:
    # <img> tags aren't CORS-gated at all, this only matters for fetch()-based previews
    # (e.g. the admin panel's Filament image preview).
    allowed_origins = ["*"]
    allowed_methods = ["GET", "HEAD"]
    allowed_headers = ["*"]
  }
}
