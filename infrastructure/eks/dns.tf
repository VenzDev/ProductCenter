# admin.bechta.pl — public hostname for the backend admin panel, terminated at the ALB
# that AWS Load Balancer Controller provisions from the Ingress (see
# k8s/backend/templates/ingress.yaml). The zone itself already exists in this AWS account
# and is managed outside this Terraform — only referenced here for DNS validation.

data "aws_route53_zone" "bechta_pl" {
  name         = "bechta.pl"
  private_zone = false
}

resource "aws_acm_certificate" "backend_admin" {
  domain_name       = "admin.bechta.pl"
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "backend_admin_cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.backend_admin.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  zone_id = data.aws_route53_zone.bechta_pl.zone_id
  name    = each.value.name
  type    = each.value.type
  records = [each.value.record]
  ttl     = 60
}

resource "aws_acm_certificate_validation" "backend_admin" {
  certificate_arn         = aws_acm_certificate.backend_admin.arn
  validation_record_fqdns = [for record in aws_route53_record.backend_admin_cert_validation : record.fqdn]
}

# shop.bechta.pl — public hostname for the Next.js storefront, terminated at its own
# ALB (see k8s/frontend/templates/ingress.yaml) — separate Ingress from the backend
# admin panel above, so a separate ALB and cert, same pattern otherwise.

resource "aws_acm_certificate" "frontend" {
  domain_name       = "shop.bechta.pl"
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "frontend_cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.frontend.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  zone_id = data.aws_route53_zone.bechta_pl.zone_id
  name    = each.value.name
  type    = each.value.type
  records = [each.value.record]
  ttl     = 60
}

resource "aws_acm_certificate_validation" "frontend" {
  certificate_arn         = aws_acm_certificate.frontend.arn
  validation_record_fqdns = [for record in aws_route53_record.frontend_cert_validation : record.fqdn]
}
