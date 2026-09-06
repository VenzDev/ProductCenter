# Read by infrastructure/eks's External Secrets Operator IRSA role (iam.tf, scoped to
# these exact paths) and synced into k8s Secrets by each service chart's own
# ExternalSecret (infrastructure/k8s/backend and infrastructure/k8s/opensearch). Path
# prefixes are literals here, not var.cluster_name — the IAM policy and the Helm charts
# all hardcode them the same way, and none ever need to vary.

resource "random_bytes" "app_key" {
  length = 32
}

resource "aws_ssm_parameter" "app_key" {
  name  = "/product-center/backend/app-key"
  type  = "SecureString"
  value = "base64:${random_bytes.app_key.base64}"
}

resource "aws_ssm_parameter" "azure_client_id" {
  name  = "/product-center/backend/azure-client-id"
  type  = "SecureString"
  value = var.azure_client_id
}

resource "aws_ssm_parameter" "azure_tenant_id" {
  name  = "/product-center/backend/azure-tenant-id"
  type  = "SecureString"
  value = var.azure_tenant_id
}

resource "aws_ssm_parameter" "azure_client_secret" {
  name  = "/product-center/backend/azure-client-secret"
  type  = "SecureString"
  value = var.azure_client_secret
}

resource "aws_ssm_parameter" "azure_redirect_uri" {
  name  = "/product-center/backend/azure-redirect-uri"
  type  = "SecureString"
  value = var.azure_redirect_uri
}

resource "aws_ssm_parameter" "azure_allowed_domain" {
  name  = "/product-center/backend/azure-allowed-domain"
  type  = "SecureString"
  value = var.azure_allowed_domain
}

resource "aws_ssm_parameter" "stripe_secret" {
  name  = "/product-center/backend/stripe-secret"
  type  = "SecureString"
  value = var.stripe_secret
}

resource "aws_ssm_parameter" "stripe_webhook_secret" {
  name  = "/product-center/backend/stripe-webhook-secret"
  type  = "SecureString"
  value = var.stripe_webhook_secret
}

resource "aws_ssm_parameter" "openai_api_key" {
  name  = "/product-center/backend/openai-api-key"
  type  = "SecureString"
  value = var.openai_api_key
}

# OpenSearch admin password — shared by the opensearch chart (sets it,
# OPENSEARCH_INITIAL_ADMIN_PASSWORD) and the backend chart (logs in with it,
# OPENSEARCH_PASSWORD), synced into both via infrastructure/k8s/opensearch's own
# ExternalSecret (opensearch-secrets, read cross-chart by backend's Deployment). No
# external source, same as app-key above — but OpenSearch's security plugin rejects weak
# passwords (needs upper/lower/digit/special, can't resemble "admin"), so min_* is set
# explicitly rather than relying on random_password's default (probabilistic, not
# guaranteed) mix.
resource "random_password" "opensearch_admin_password" {
  length      = 24
  min_upper   = 1
  min_lower   = 1
  min_numeric = 1
  min_special = 1
  # OpenSearch's password validator is picky about which special characters it accepts —
  # narrowed to a known-safe subset rather than random_password's full default set.
  override_special = "!#%&*-_="
}

resource "aws_ssm_parameter" "opensearch_admin_password" {
  name  = "/product-center/opensearch/admin-password"
  type  = "SecureString"
  value = random_password.opensearch_admin_password.result
}

# Frontend's Stripe publishable key — needed at `docker build` time (Next.js inlines
# NEXT_PUBLIC_* into the client bundle then, not at container start), read by
# .github/workflows/build-frontend.yaml's OIDC role (infrastructure/ecr/github-oidc.tf),
# not by External Secrets Operator — this never becomes a k8s Secret, since the value
# has to exist before the image is even built. Was a GitHub Actions repo secret before
# this; String, not SecureString, since it's meant to be public (Stripe's own naming).
resource "aws_ssm_parameter" "frontend_stripe_publishable_key" {
  name  = "/product-center/frontend/stripe-publishable-key"
  type  = "String"
  value = var.stripe_publishable_key
}
