# Runbook — EKS + deploy serwisów

Praktyczna ściąga: jak postawić klaster, wdrożyć serwisy i sprawdzić, że wszystko działa. Kontekst architektury patrz `design.md`.

## 0. Struktura `k8s/`

Jeden reużywalny Helm chart zamiast osobnych manifestów per serwis:

```
k8s/chart/
  Chart.yaml
  values.yaml          # domyślne (puste) wartości
  templates/
    deployment.yaml    # wspólny szablon Deployment
    service.yaml       # wspólny szablon Service
  values/
    ai.yaml
    payment.yaml
    backend.yaml
```

Różnice między serwisami (nazwa, obraz, port, zmienne env) żyją tylko w `values/<serwis>.yaml` — nowy serwis to nowy plik values, nie kopiowanie manifestów.

## 1. Kolejność: terraform apply → deploy

```bash
cd infrastructure/eks

# 1. Infrastruktura (VPC, EKS, node group, addony, ECR)
terraform apply

# 2. Podłącz kubectl do nowego klastra (to też przełącza current-context na EKS)
aws eks update-kubeconfig --name product-center --region eu-central-1

# Sprawdź, że kubectl faktycznie wskazuje na EKS, nie na Minikube ani inny klaster —
# aws eks update-kubeconfig ustawia current-context, ale coś uruchomione PO nim
# (np. `minikube start`) po cichu je nadpisze
kubectl config current-context   # powinno pokazać arn:aws:eks:...:cluster/product-center

# 3. Build + push obrazów (dla każdego serwisu: ai, payment, backend)
docker build --platform linux/amd64 --target prod \
  -t 222634367938.dkr.ecr.eu-central-1.amazonaws.com/<serwis>:latest \
  services/<serwis>
docker push 222634367938.dkr.ecr.eu-central-1.amazonaws.com/<serwis>:latest

# 4. Sekret backendu — NIE jest zarządzany przez Terraform,
#    trzeba go stworzyć od nowa po każdym świeżym klastrze
kubectl create secret generic backend-secrets \
  --from-literal=app-key="base64:$(openssl rand -base64 32)"

# 5. Zainstaluj serwisy (jeden reużywalny Helm chart, różne values per serwis)
helm install ai      k8s/chart -f k8s/chart/values/ai.yaml
helm install payment k8s/chart -f k8s/chart/values/payment.yaml
helm install backend k8s/chart -f k8s/chart/values/backend.yaml
```

Przy zmianie w templatce/values (bez nowego klastra): `helm upgrade <nazwa> k8s/chart -f k8s/chart/values/<nazwa>.yaml`. Renderowanie manifestów do podglądu bez dotykania klastra: `helm template <nazwa> k8s/chart -f k8s/chart/values/<nazwa>.yaml`.

**Uwaga:** `--platform linux/amd64` jest obowiązkowe przy buildzie na Macu z Apple Silicon — node'y EKS to x86_64 (`ami_type = AL2023_x86_64_STANDARD`).

## 1a. Monitoring: Prometheus + Grafana (opcjonalnie, po kroku 1)

Szczegóły i debugowanie w `monitoring.md` — tu tylko sekwencja komend.

**Zanim odpalisz `terraform apply` w sekcji 1:** domyślny node group (`t3.medium`, `desired_size: 1`) nie pomieści obok siebie 3 serwisów i całego `kube-prometheus-stack`. Podnieś zasoby: `terraform apply -var node_instance_type=t3.large`.

```bash
# Stack (Prometheus, Grafana, Alertmanager, kube-state-metrics, node-exporter)
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update prometheus-community
kubectl create namespace monitoring
helm install kube-prometheus-stack prometheus-community/kube-prometheus-stack -n monitoring
kubectl wait --for=condition=Ready pods --all -n monitoring --timeout=300s

# Nasz dashboard (Request rate / Error rate / Latency p95, per serwis)
kubectl apply -f k8s/monitoring/dashboard-services.yaml -n monitoring

# Dostęp do Grafany
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
  curl -s http://ai:8000/health; echo
  curl -s http://payment:8080/health; echo
  curl -s http://backend:80/health; echo
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
| 5 | `backend` restart w pętli, `Readiness probe failed: ... tls: internal error` | FrankenPHP bez `SERVER_NAME` domyślnie włącza auto-HTTPS z self-signed certem; port 80 tylko przekierowuje na HTTPS, sonda idzie za redirectem i wpada na zły cert | env `SERVER_NAME: ":80"` | `k8s/chart/values/backend.yaml` |
| 6 | `backend` HTTP 500, log: `Class "Laravel\Pail\PailServiceProvider" not found` (czasem ukryte pod wtórnym błędem `Target class [translator] does not exist`) | Stary lokalny cache `bootstrap/cache/packages.php` (wygenerowany gdy były zainstalowane dev-deps z Pailem) trafiał do obrazu prod przez `COPY . .`; `composer install --no-scripts --no-dev` w prod stage go nie odświeża | Dodać `bootstrap/cache/*.php` do `.dockerignore` | `services/backend/.dockerignore` |
| 7 | `backend` HTTP 500 (po naprawieniu #6) | Brak `APP_KEY` — `.env` celowo poza obrazem (`.dockerignore`), Laravel wywala się na `EncryptCookies` | Kubernetes Secret `backend-secrets` (imperatywnie, nigdy nie commitować realnej wartości do gita), referencja przez `secretKeyRef` | `k8s/chart/values/backend.yaml` + krok 4 w sekcji 1 |
| 8 | `terraform destroy` odmawia usunąć repozytoria ECR | Repozytoria zawierają obrazy, domyślnie `force_delete = false` | `force_delete = true` na `aws_ecr_repository` | `infrastructure/eks/ecr.tf` |
| 9 | Kubernetes 1.33 zbliżało się do końca standard support (koszt x6 na extended support) | — | Wersja `1.35` | `infrastructure/eks/eks.tf` |

## 5. Rzeczy, które NIE przetrwają `terraform destroy`

- **Wszystko w klastrze** (pody, Service'y, Secrets) — Kubernetes żyje tylko wewnątrz klastra, destroy usuwa cały klaster.
- **`backend-secrets`** — musi być stworzony ręcznie na nowo po każdym świeżym `terraform apply` (krok 4 w sekcji 1). Nie jest zarządzany przez Terraform ani przez pliki w `k8s/`.
- **Obrazy w ECR** — repozytoria (`ai`, `payment`, `backend`) są usuwane razem z resztą dzięki `force_delete = true`. Po kolejnym `apply` trzeba je zbudować i wypchnąć na nowo.
