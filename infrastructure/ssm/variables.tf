# Separate Terraform root module from infrastructure/eks (own state, stood up
# independently — same split as infrastructure/ecr) so these parameters, and the real
# secret values below, survive tearing down and recreating the EKS cluster. Before this
# split, everything under infrastructure/eks/iam.tf's SSM path had to be re-entered by
# hand (docs/runbook.md) every time the cluster was recreated; app-key, azure-* and
# stripe-* only ever need to be set once per external registration (Entra app, Stripe
# dashboard), not once per cluster.
#
# azure-*/stripe-* come from outside this project (Entra app registration, Stripe
# dashboard) so Terraform can't generate them — real values go in a local terraform.tfvars
# (gitignored, same treatment as services/backend/.env), copied from
# terraform.tfvars.example. app-key IS generated here (see main.tf, random_bytes) since
# it has no external source, same shape as `openssl rand -base64 32` used previously.

variable "region" {
  description = "AWS region"
  type        = string
  default     = "eu-central-1"
}

variable "azure_client_id" {
  description = "Entra app registration client ID — same value as AZURE_OPENID_CLIENT_ID in services/backend/.env"
  type        = string
  sensitive   = true
}

variable "azure_tenant_id" {
  description = "Entra app registration tenant ID — same value as AZURE_OPENID_TENANT_ID in services/backend/.env"
  type        = string
  sensitive   = true
}

variable "azure_client_secret" {
  description = "Entra app registration client secret — same value as AZURE_OPENID_CLIENT_SECRET in services/backend/.env"
  type        = string
  sensitive   = true
}

variable "azure_redirect_uri" {
  description = "Public HTTPS callback URL for the backend's Entra SSO — https://admin.bechta.pl/auth/microsoft/callback"
  type        = string
  default     = "https://admin.bechta.pl/auth/microsoft/callback"
}

variable "azure_allowed_domain" {
  # No default — unlike azure_redirect_uri, an empty string can't be stored (AWS SSM
  # rejects a Parameter value of length 0), so there is no way to represent "JIT
  # disabled" here; a real domain is always required.
  description = "Email domain allowed to self-provision as Admin on first SSO login (JIT) — same value as AZURE_OPENID_ALLOWED_DOMAIN in services/backend/.env"
  type        = string
}

variable "stripe_secret" {
  description = "Stripe secret key from dashboard.stripe.com/apikeys"
  type        = string
  sensitive   = true
}

variable "stripe_webhook_secret" {
  description = "Stripe webhook endpoint signing secret (Developers → Webhooks → the admin.bechta.pl endpoint), not the local `stripe listen` secret"
  type        = string
  sensitive   = true
}

variable "stripe_publishable_key" {
  description = "Stripe publishable key from dashboard.stripe.com/apikeys — not secret (Next.js inlines it into the client bundle), unlike everything else in this file, so not marked sensitive"
  type        = string
}

variable "openai_api_key" {
  description = "OpenAI API key (platform.openai.com/api-keys) — used by GenerateAttachmentEmbeddingsJob to embed product manual chunks, same value as services/backend/.env"
  type        = string
  sensitive   = true
}
