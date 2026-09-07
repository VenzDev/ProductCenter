# Praca z Minikube (lokalny odpowiednik EKS)

Cel: testować charty w `infrastructure/k8s/` (i docelowo `kube-prometheus-stack`) bez kosztów AWS i bez czekania na node group. Kontekst: `runbook.md` opisuje to samo dla prawdziwego EKS — Minikube używa dokładnie tych samych chartów, różni się tylko obrazami i sekretami.

## 1. Start klastra

```bash
minikube start --driver=docker
```

**Uwaga:** ten profil Minikube może już zawierać niepowiązane obciążenia z innych projektów/eksperymentów (namespace `default`, `quality-checker` itd.). Nie ruszamy ich — wszystko dla `product-center` idzie do osobnego namespace.

```bash
kubectl create namespace product-center
```

Sprawdzenie, że `kubectl` faktycznie wskazuje na Minikube (a nie na EKS):

```bash
kubectl config current-context   # powinno być "minikube"
```

`aws eks update-kubeconfig` i `minikube start` zapisują się do tego samego pliku `~/.kube/config`, ale jako osobne konteksty — przełączanie: `kubectl config use-context minikube` / `kubectl config use-context arn:aws:eks:...`.

## 2. Obrazy — bez ECR, bez `--platform`

Node Minikube (na Macu z Apple Silicon) jest `arm64`, czyli tej samej architektury co host — build jest natywny, szybszy niż na EKS (tam wymuszaliśmy `--platform linux/amd64` pod x86_64).

`eval $(minikube docker-env)` przełącza Dockera w bieżącej sesji terminala tak, żeby budował obrazy bezpośrednio do wewnętrznego registry Minikube (nie trzeba pushować nigdzie na zewnątrz):

```bash
eval $(minikube docker-env)

for svc in notification backend; do
  docker build --target prod -t ${svc}:local services/${svc}
done
```

Tag `:local` (nie `:latest`) jest ważny — dla dowolnego tagu innego niż `latest` Kubernetes domyślnie ustawia `imagePullPolicy: IfNotPresent`, więc pody wystartują od razu z lokalnie zbudowanego obrazu, bez próby pullowania z internetu.

**Uwaga:** `eval $(minikube docker-env)` działa tylko w bieżącej sesji shella. Nowy terminal/sesja = trzeba powtórzyć.

## 3. Sekrety — osobne per klaster

Na EKS `backend-secrets`/`notification-secrets` syncuje External Secrets Operator z SSM (`runbook.md`, kroki 5/5b/7a) — na Minikube tego operatora nie ma (nic go tu nie instaluje), więc `externalsecret.yaml` obu chartów by się nie zaaplikował (CRD `ExternalSecret` dostarcza dopiero ten operator). Zamiast tego oba Secrety trzeba stworzyć ręcznie, w namespace `product-center` — wystarczą do samego zbootowania poda, nie do realnej funkcjonalności (bez prawdziwego Mailguna e-mail i tak nie wyjdzie):

```bash
kubectl create secret generic backend-secrets -n product-center \
  --from-literal=app-key="base64:$(openssl rand -base64 32)"

kubectl create secret generic notification-secrets -n product-center \
  --from-literal=mailgun-domain="localhost" \
  --from-literal=mailgun-api-key="dummy" \
  --from-literal=mailgun-sender="test@localhost"
```

## 4. Instalacja serwisów

Te same charty co na EKS, ale z nadpisanym `image` (lokalny tag zamiast adresu ECR):

```bash
helm install notification infrastructure/k8s/notification -n product-center --set image=notification:local
helm install redis infrastructure/k8s/redis -n product-center
helm install backend infrastructure/k8s/backend -n product-center --set image=backend:local
```

## 5. Weryfikacja

```bash
kubectl get pods -n product-center -o wide

kubectl run curl-test -n product-center --image=curlimages/curl --rm -i --restart=Never -- sh -c '
  curl -s http://notification:8080/health; echo
  curl -s http://backend:80/health; echo
'
```

## 6. Sprzątanie

```bash
# Usunąć tylko nasze release'y i namespace (zostawia resztę Minikube nietkniętą)
helm uninstall notification redis backend -n product-center
kubectl delete namespace product-center

# Zatrzymać całego Minikube (zwalnia zasoby hosta, zachowuje stan)
minikube stop

# Usunąć całkowicie profil Minikube (nieodwracalne — kasuje też inne niepowiązane obciążenia!)
minikube delete
```

## 7. Czego Minikube NIE odwzorowuje

Wszystko, co specyficzne dla AWS/EKS: `vpc-cni`, NAT Gateway, ECR, IAM (role node'ów, access entries), a więc też błędy specyficzne dla EKS opisane w `runbook.md` (deadlock `before_compute`, `map_public_ip_on_launch` itd.). Minikube służy do taniego iterowania nad samą warstwą Kubernetesa (manifesty, Helm, `kube-prometheus-stack`) — końcowa weryfikacja "czy to działa naprawdę" i tak wymaga realnego `terraform apply` na EKS.
