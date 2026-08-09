#!/bin/sh
set -e

# Runs automatically on every LocalStack start (mounted into /etc/localstack/init/ready.d/).
# LocalStack has no persistent state volume, so the bucket needs recreating each time.
awslocal s3api create-bucket --bucket product-files --region us-east-1

# The admin panel's browser origin (localhost:8081) fetches images directly from the S3
# origin (localhost:4566) for previews — without CORS the browser blocks that fetch. Real
# S3 needs the same bucket-level config, so this isn't a LocalStack-only workaround.
awslocal s3api put-bucket-cors --bucket product-files --cors-configuration '{
  "CORSRules": [
    {
      "AllowedOrigins": ["*"],
      "AllowedMethods": ["GET", "HEAD"],
      "AllowedHeaders": ["*"]
    }
  ]
}'
