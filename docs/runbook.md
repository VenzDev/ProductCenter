# Runbook — EKS + deploy serwisów

Praktyczna ściąga: jak postawić klaster, wdrożyć serwisy i sprawdzić, że wszystko działa. Kontekst architektury patrz `design.md`.

## 0. Struktura `infrastructure/k8s/`

Osobny Helm chart per serwis — bez wspólnych/warunkowych szablonów, każdy chart ma tylko te zasoby, których faktycznie potrzebuje:

```
infrastructure/k8s/backend/
  Chart.yaml
  values.yaml           # obraz, port, env, ingress host/cert, IRSA role ARN
  templates/
    deployment.yaml
    service.yaml
    serviceaccount.yaml  # IRSA — dostęp do S3
    servicemonitor.yaml  # scrape dla Prometheusa
    migrate-job.yaml     # Helm hook, patrz błąd #10
    ingress.yaml         # ALB przez AWS Load Balancer Controller
infrastructure/k8s/payment/
  Chart.yaml
  values.yaml            # obraz, port
  templates/
    deployment.yaml
    service.yaml
    servicemonitor.yaml
infrastructure/k8s/frontend/
  Chart.yaml
  values.yaml            # obraz, port, ingress host/cert
  templates/
    deployment.yaml
    service.yaml
    servicemonitor.yaml
    ingress.yaml          # ALB przez AWS Load Balancer Controller — osobny od backendu
```

Nowy serwis to nowy katalog chartu, nie nowy plik values do istniejącego wspólnego szablonu.

## 1. Kolejność: terraform apply → deploy

```bash
cd infrastructure/eks

# 1. Infrastruktura (VPC, EKS, node group, addony, ECR, S3, RDS, rola IRSA)
terraform apply

# 2. Podłącz kubectl do nowego klastra (to też przełącza current-context na EKS)
aws eks update-kubeconfig --name product-center --region eu-central-1

# Sprawdź, że kubectl faktycznie wskazuje na EKS, nie na Minikube ani inny klaster —
# aws eks update-kubeconfig ustawia current-context, ale coś uruchomione PO nim
# (np. `minikube start`) po cichu je nadpisze
kubectl config current-context   # powinno pokazać arn:aws:eks:...:cluster/product-center

# 3. Build + push obrazów (dla payment, backend i frontend)
docker build --platform linux/amd64 --target prod \
  -t 222634367938.dkr.ecr.eu-central-1.amazonaws.com/<serwis>:latest \
  services/<serwis>
docker push 222634367938.dkr.ecr.eu-central-1.amazonaws.com/<serwis>:latest

# 4. Wypełnij placeholder `<TERRAFORM_OUTPUT:rds_endpoint>` w infrastructure/k8s/backend/values.yaml
#    (host RDS jest generowany przez AWS, nie da się przewidzieć przed apply — nazwa
#    bucketu S3 i ARN roli IRSA są deterministyczne, więc są już wpisane na sztywno)
terraform output rds_endpoint

# 5. Sekrety backendu — NIE są zarządzane przez Terraform (poza samym hasłem RDS
#    w Secrets Manager), trzeba je stworzyć od nowa po każdym świeżym klastrze.
#    azure-client-id/tenant-id/client-secret to te same wartości co lokalnie w .env
#    (AZURE_OPENID_*) — osobna rejestracja aplikacji w Entra, nie generowane tutaj.
#    azure-redirect-uri wymaga publicznego, HTTPS URL-a backendu — od kroku 6a niżej
#    to https://admin.bechta.pl/auth/microsoft/callback.
DB_PASSWORD=$(aws secretsmanager get-secret-value \
  --secret-id "$(terraform output -raw rds_master_user_secret_arn)" \
  --query SecretString --output text | jq -r .password)

kubectl create secret generic backend-secrets \
  --from-literal=app-key="base64:$(openssl rand -base64 32)" \
  --from-literal=db-password="$DB_PASSWORD" \
  --from-literal=azure-client-id="<z Azure App Registration>" \
  --from-literal=azure-tenant-id="<z Azure App Registration>" \
  --from-literal=azure-client-secret="<z Azure App Registration>" \
  --from-literal=azure-redirect-uri="https://admin.bechta.pl/auth/microsoft/callback"

# 6. Monitoring: Prometheus + Grafana. Musi być PRZED krokiem 8 — każdy serwis ma
#    metrics.enabled: true domyślnie (values/<serwis>.yaml), czyli renderuje
#    ServiceMonitor; bez wcześniej zainstalowanego kube-prometheus-stack (które
#    dostarcza ten CRD) `helm install` serwisu od razu się wywali. Więcej w monitoring.md.
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update prometheus-community
kubectl create namespace monitoring
helm install kube-prometheus-stack prometheus-community/kube-prometheus-stack -n monitoring
kubectl wait --for=condition=Ready pods --all -n monitoring --timeout=300s

# Nasz dashboard (Request rate / Error rate / Latency p95, per serwis)
kubectl apply -f infrastructure/k8s/monitoring/dashboard-services.yaml -n monitoring

# 7. AWS Load Balancer Controller — cluster-wide kontroler, który zamienia Ingress
#    backendu (infrastructure/k8s/backend/templates/ingress.yaml) w prawdziwy ALB. Rola IRSA i jej
#    uprawnienia są zarządzane przez Terraform (infrastructure/eks/iam.tf), sam
#    kontroler instalowany tak samo imperatywnie jak kube-prometheus-stack.
helm repo add eks https://aws.github.io/eks-charts
helm repo update eks
helm install aws-load-balancer-controller eks/aws-load-balancer-controller \
  -n kube-system \
  --set clusterName=product-center \
  --set region=eu-central-1 \
  --set vpcId=$(terraform output -raw vpc_id) \
  --set serviceAccount.annotations."eks\.amazonaws\.com/role-arn"=$(terraform output -raw aws_load_balancer_controller_irsa_role_arn)
kubectl wait --for=condition=Available deployment/aws-load-balancer-controller -n kube-system --timeout=120s

# Wypełnij placeholder `<TERRAFORM_OUTPUT:acm_certificate_arn>` w
# infrastructure/k8s/backend/values.yaml (ARN certu ACM znany dopiero po walidacji DNS,
# tak jak rds_endpoint w kroku 4 — nie da się przewidzieć przed apply)
terraform output acm_certificate_arn

# To samo dla frontendu — osobny cert, osobny placeholder
# `<TERRAFORM_OUTPUT:frontend_acm_certificate_arn>` w infrastructure/k8s/frontend/values.yaml
terraform output frontend_acm_certificate_arn

# 8. Zainstaluj serwisy (osobny chart per serwis)
helm install payment infrastructure/k8s/payment
helm install backend infrastructure/k8s/backend
helm install frontend infrastructure/k8s/frontend

# 9. admin.bechta.pl → ALB. AWS Load Balancer Controller tworzy ALB dopiero z Ingressu
#    backendu (krok 8), więc jego DNS name nie jest znany Terraformowi — rekord Route53
#    jest tworzony tu, imperatywnie, tak jak backend-secrets w kroku 5.
kubectl wait --for=jsonpath='{.status.loadBalancer.ingress[0].hostname}' ingress/backend --timeout=180s
ALB_DNS=$(kubectl get ingress backend -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')
ALB_ZONE_ID=$(aws elbv2 describe-load-balancers \
  --query "LoadBalancers[?DNSName=='$ALB_DNS'].CanonicalHostedZoneId" --output text)

aws route53 change-resource-record-sets \
  --hosted-zone-id "$(terraform output -raw route53_zone_id)" \
  --change-batch '{
    "Changes": [{
      "Action": "UPSERT",
      "ResourceRecordSet": {
        "Name": "admin.bechta.pl",
        "Type": "A",
        "AliasTarget": {
          "HostedZoneId": "'"$ALB_ZONE_ID"'",
          "DNSName": "'"$ALB_DNS"'",
          "EvaluateTargetHealth": false
        }
      }
    }]
  }'

# To samo dla shop.bechta.pl → ALB frontendu (osobny Ingress = osobny ALB, ten sam
# powód co wyżej: nazwa DNS ALB nie jest znana Terraformowi przed jego utworzeniem)
kubectl wait --for=jsonpath='{.status.loadBalancer.ingress[0].hostname}' ingress/frontend --timeout=180s
FRONTEND_ALB_DNS=$(kubectl get ingress frontend -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')
FRONTEND_ALB_ZONE_ID=$(aws elbv2 describe-load-balancers \
  --query "LoadBalancers[?DNSName=='$FRONTEND_ALB_DNS'].CanonicalHostedZoneId" --output text)

aws route53 change-resource-record-sets \
  --hosted-zone-id "$(terraform output -raw route53_zone_id)" \
  --change-batch '{
    "Changes": [{
      "Action": "UPSERT",
      "ResourceRecordSet": {
        "Name": "shop.bechta.pl",
        "Type": "A",
        "AliasTarget": {
          "HostedZoneId": "'"$FRONTEND_ALB_ZONE_ID"'",
          "DNSName": "'"$FRONTEND_ALB_DNS"'",
          "EvaluateTargetHealth": false
        }
      }
    }]
  }'
```

Przy zmianie w templatce/values (bez nowego klastra): `helm upgrade <nazwa> infrastructure/k8s/<nazwa>`. Renderowanie manifestów do podglądu bez dotykania klastra: `helm template <nazwa> infrastructure/k8s/<nazwa>`.

**Uwaga:** `--platform linux/amd64` jest obowiązkowe przy buildzie na Macu z Apple Silicon — node'y EKS to x86_64 (`ami_type = AL2023_x86_64_STANDARD`). Domyślny `node_instance_type` (`t3.large`) mieści oba stacki naraz bez zmian.

Dostęp do Grafany:

```bash
kubectl get secret --namespace monitoring kube-prometheus-stack-grafana \
  -o jsonpath="{.data.admin-password}" | base64 --decode; echo
kubectl port-forward -n monitoring svc/kube-prometheus-stack-grafana 3000:80
```

Potem: http://localhost:3000/d/product-center-services (login `admin` + hasło z komendy wyżej).

## 2. Jak sprawdzić, że klaster jest zdrowy

```bash
# Node musi być Ready
kubectl get nodes -o wide

# Systemowe pody (CNI, DNS, kube-proxy) muszą być Running
kubectl get pods -n kube-system

# Node group i addony bez błędów po stronie AWS
aws eks describe-nodegroup --cluster-name product-center \
  --nodegroup-name $(aws eks list-nodegroups --cluster-name product-center --region eu-central-1 --query 'nodegroups[0]' --output text) \
  --region eu-central-1 --query 'nodegroup.{status:status,health:health}'

for a in coredns kube-proxy vpc-cni; do
  aws eks describe-addon --cluster-name product-center --addon-name $a \
    --region eu-central-1 --query 'addon.{status:status,health:health}'
done
```

## 3. Jak sprawdzić, że serwisy działają

```bash
# Pody serwisów muszą być 1/1 Running
kubectl get pods -o wide

# Test /health przez Service (DNS + routing wewnątrz klastra, nie tylko sam pod)
kubectl run curl-test --image=curlimages/curl --rm -i --restart=Never -- sh -c '
  curl -s http://payment:8080/health; echo
  curl -s http://backend:80/health; echo
  curl -s http://frontend:3000/health; echo
'
```

Jeśli pod restartuje się w pętli: `kubectl describe pod <pod>` (sekcja `Events`) i `kubectl logs <pod> --previous`.

## 4. Napotkane błędy i poprawki (już w kodzie repo)

| # | Objaw | Przyczyna | Poprawka | Gdzie |
|---|---|---|---|---|
| 1 | Node nigdy nie wychodzi z `NotReady` | Brak `addons` (coredns/kube-proxy/vpc-cni) — moduł EKS ich nie instaluje sam | Dodać blok `addons` | `infrastructure/eks/eks.tf` |
| 2 | `terraform apply` wisi 20-30+ min na tworzeniu node group, `aws eks describe-addon vpc-cni` zwraca `ResourceNotFoundException` cały czas | Błędne koło: node group czeka aż node będzie `Ready`, node czeka na `vpc-cni`, `vpc-cni` czeka aż node group się utworzy (domyślny `depends_on` w module) | `before_compute = true` na addonie `vpc-cni` | `infrastructure/eks/eks.tf` |
| 3 | Node'y bez dostępu do internetu | Brak NAT / `map_public_ip_on_launch` przy node'ach w public subnecie | Private subnety + NAT Gateway (`single_nat_gateway = true`) | `infrastructure/eks/vpc.tf` |
| 4 | `ImagePullBackOff`, event: `no match for platform in manifest` | Obraz zbudowany na Macu (arm64) bez `--platform`, node x86_64 | Zawsze `docker build --platform linux/amd64 ...` | komenda buildu (patrz sekcja 1) |
| 5 | `backend` restart w pętli, `Readiness probe failed: ... tls: internal error` | FrankenPHP bez `SERVER_NAME` domyślnie włącza auto-HTTPS z self-signed certem; port 80 tylko przekierowuje na HTTPS, sonda idzie za redirectem i wpada na zły cert | env `SERVER_NAME: ":80"` | `infrastructure/k8s/backend/values.yaml` |
| 6 | `backend` HTTP 500, log: `Class "Laravel\Pail\PailServiceProvider" not found` (czasem ukryte pod wtórnym błędem `Target class [translator] does not exist`) | Stary lokalny cache `bootstrap/cache/packages.php` (wygenerowany gdy były zainstalowane dev-deps z Pailem) trafiał do obrazu prod przez `COPY . .`; `composer install --no-scripts --no-dev` w prod stage go nie odświeża | Dodać `bootstrap/cache/*.php` do `.dockerignore` | `services/backend/.dockerignore` |
| 7 | `backend` HTTP 500 (po naprawieniu #6) | Brak `APP_KEY` — `.env` celowo poza obrazem (`.dockerignore`), Laravel wywala się na `EncryptCookies` | Kubernetes Secret `backend-secrets` (imperatywnie, nigdy nie commitować realnej wartości do gita), referencja przez `secretKeyRef` | `infrastructure/k8s/backend/values.yaml` + krok 5 w sekcji 1 |
| 8 | `terraform destroy` odmawia usunąć repozytoria ECR | Repozytoria zawierają obrazy, domyślnie `force_delete = false` | `force_delete = true` na `aws_ecr_repository` | `infrastructure/eks/ecr.tf` |
| 9 | Kubernetes 1.33 zbliżało się do końca standard support (koszt x6 na extended support) | — | Wersja `1.35` | `infrastructure/eks/eks.tf` |
| 10 | `backend` restart w pętli na świeżym RDS, `HTTP probe failed with statuscode: 500` na `/health` mimo że handler nic nie robi z DB | Obraz `prod` nigdy nie uruchamia migracji (tylko `dev-entrypoint.sh` to robi, i to tylko w `dev`) — świeża baza nie ma tabeli `sessions`, a domyślna grupa middleware `web` (którą dostaje KAŻDA trasa, łącznie z `/health`) startuje sesję, więc wywala się na każdym requeście | Helm hook `pre-install,pre-upgrade` (`Job` uruchamiający `php artisan migrate --force` przed rollout Deployment) | `infrastructure/k8s/backend/templates/migrate-job.yaml` |
| 11 | (przy naprawianiu #10) Świeży `helm install` wisi, `job-controller` event: `serviceaccount "backend" not found` | Hooki (`pre-install`) wykonują się PRZED zwykłymi zasobami release'u — `ServiceAccount` (`serviceaccount.yaml`, zwykły szablon, nie hook) jeszcze nie istnieje, gdy Job próbuje go użyć | Job migracji nie ustawia `serviceAccountName` — używa domyślnego SA namespace'u; i tak nie potrzebuje uprawnień S3/IRSA, tylko łączności z DB | `infrastructure/k8s/backend/templates/migrate-job.yaml` |
| 12 | `helm install payment/backend` wywala się od razu: `no matches for kind "ServiceMonitor" in version "monitoring.coreos.com/v1"` | Każdy chart zawsze renderuje `ServiceMonitor` — ten CRD dostarcza dopiero `kube-prometheus-stack` | Monitoring instalowany PRZED serwisami w kolejności runbooka (krok 6 przed 7), nie jako osobny opcjonalny dodatek na końcu | `docs/runbook.md` |
| 13 | `terraform destroy` wywala się na `DependencyViolation` przy subnetach/IGW i `ResourceInUseException` przy certyfikacie ACM | Klaster (a razem z nim AWS Load Balancer Controller) zniknął, zanim kontroler zdążył usunąć ALB, który sam utworzył dla Ingressu — ALB (z ENI trzymającymi publiczne IP w subnetach publicznych) i jego security groupy nie są zarządzane przez Terraform, więc `destroy` o nich nie wie i nie potrafi ich sprzątnąć | Przed `terraform destroy`: `helm uninstall backend` (albo `kubectl delete ingress backend`), poczekać aż kontroler usunie ALB, dopiero potem `destroy`. Jeśli już się wywaliło: ręcznie `aws elbv2 delete-load-balancer` + `delete-target-group`, poczekać aż znikną ENI, usunąć osierocone security groupy (`aws ec2 delete-security-group`), potem ponowić `terraform destroy` | `docs/runbook.md` |

## 5. Rzeczy, które NIE przetrwają `terraform destroy`

- **Wszystko w klastrze** (pody, Service'y, Secrets) — Kubernetes żyje tylko wewnątrz klastra, destroy usuwa cały klaster.
- **`backend-secrets`** — musi być stworzony ręcznie na nowo po każdym świeżym `terraform apply` (krok 5 w sekcji 1). Nie jest zarządzany przez Terraform ani przez pliki w `infrastructure/k8s/`.
- **Obrazy w ECR** — repozytoria (`payment`, `backend`, `frontend`) są usuwane razem z resztą dzięki `force_delete = true`. Po kolejnym `apply` trzeba je zbudować i wypchnąć na nowo.
- **Pliki w S3** (`product-files`) — bucket ma `force_destroy = true`, więc `terraform destroy` kasuje go razem z zawartością (uploadowane zdjęcia produktów). Nie ma osobnego backupu.
- **Baza RDS** (`skip_final_snapshot = true`) i jej hasło w Secrets Manager (`manage_master_user_password`, zarządzane przez `aws_db_instance`) — obie znikają bez śladu przy `destroy`, żadnego snapshotu na wyjściu.
- **Rekordy Route53 `admin.bechta.pl` i `shop.bechta.pl`** — tworzone imperatywnie (krok 9), nie przez Terraform, więc `terraform destroy` ich nie usuwa; wskazują na ALB, które znikną razem z klastrem, więc zostają jako martwe aliasy dopóki nie zrobi się `aws route53 change-resource-record-sets` z `"Action": "DELETE"` ręcznie dla każdego.
- **Same ALB** — tworzone imperatywnie przez AWS Load Balancer Controller (nie Terraform) w reakcji na Ingress. Trzeba je usunąć PRZED `terraform destroy` (`helm uninstall backend`, `helm uninstall frontend` i poczekać aż kontroler je sprzątnie), inaczej `destroy` wywali się próbując usunąć subnety/IGW, na których ALB nadal ma ENI — patrz błąd #13.
