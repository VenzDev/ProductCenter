# Monitoring — Prometheus + Grafana (kube-prometheus-stack)

Jak zainstalowaliśmy `kube-prometheus-stack` i jak z nim pracować. Testowane na Minikube (patrz `minikube.md`) — na EKS te same komendy działają identycznie, to zwykły Helm chart bez zależności od AWS.

## Co to jest

Jeden chart Helm, który instaluje naraz cały standardowy stack obserwowalności:

| Komponent | Rola |
|---|---|
| `prometheus-...-0` | Sam serwer Prometheus — zbiera i przechowuje metryki |
| `kube-prometheus-stack-operator` | Prometheus Operator — obserwuje CRD (`ServiceMonitor`, `PrometheusRule`) i na ich podstawie konfiguruje Prometheusa |
| `kube-prometheus-stack-grafana` | Grafana — dashboardy |
| `alertmanager-...-0` | Alertmanager — routing i wysyłka alertów |
| `kube-state-metrics` | Tłumaczy stan obiektów k8s (liczba replik, status podów) na metryki |
| `prometheus-node-exporter` | DaemonSet — metryki systemowe node'a (CPU, RAM, dysk) |

Zero zmian w kodzie serwisów potrzebne na start — `kube-state-metrics` + `node-exporter` od razu dają metryki klastra i gotowe dashboardy w Grafanie (np. "Kubernetes / Compute Resources / Node", ".../Pod").

## 1. Instalacja

```bash
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update prometheus-community

kubectl create namespace monitoring
helm install kube-prometheus-stack prometheus-community/kube-prometheus-stack -n monitoring
```

## 2. Sprawdzenie statusu

```bash
# Poczekaj aż wszystkie pody będą Ready (instalacja obejmuje kilka komponentów, chwilę to trwa)
kubectl wait --for=condition=Ready pods --all -n monitoring --timeout=180s

kubectl get pods -n monitoring -o wide
```

## 3. Dostęp do Grafany

```bash
# Hasło admina (login: admin)
kubectl get secret --namespace monitoring kube-prometheus-stack-grafana \
  -o jsonpath="{.data.admin-password}" | base64 --decode; echo

# Port-forward na localhost:3000
kubectl port-forward -n monitoring svc/kube-prometheus-stack-grafana 3000:80
```

Potem: http://localhost:3000, login `admin` + hasło z komendy wyżej. Gotowe dashboardy klastra widoczne od razu w **Dashboards**, bez żadnej dodatkowej konfiguracji.

## 4. Dostęp do samego Prometheusa (opcjonalnie, do debugowania)

```bash
kubectl port-forward -n monitoring svc/kube-prometheus-stack-prometheus 9090:9090
```

http://localhost:9090 — przydatne do sprawdzenia np. **Status → Targets**, czyli co Prometheus faktycznie scrapuje (na razie tylko komponenty samego stacku + node-exporter/kube-state-metrics — nasze `ai`/`payment`/`backend` pojawią się tam dopiero po dodaniu `/metrics` + `ServiceMonitor`, kolejny krok).

## 5. Sprzątanie — WAŻNE: CRD-y nie znikają same

`helm uninstall` **celowo nie usuwa CRD-ów** (Prometheus Operator ich nie kasuje automatycznie, bo usunięcie CRD kasowałoby kaskadowo wszystkie zasoby tego typu w całym klastrze — Helm się na to nie odważa za ciebie).

```bash
# Usuwa pody/Deploymenty/Service'y stacku
helm uninstall kube-prometheus-stack -n monitoring
kubectl delete namespace monitoring

# CRD-y zostają w klastrze mimo powyższego — trzeba je usunąć osobno, jeśli chcesz pełny czysty stan:
kubectl delete crd \
  alertmanagerconfigs.monitoring.coreos.com \
  alertmanagers.monitoring.coreos.com \
  podmonitors.monitoring.coreos.com \
  probes.monitoring.coreos.com \
  prometheusagents.monitoring.coreos.com \
  prometheuses.monitoring.coreos.com \
  prometheusrules.monitoring.coreos.com \
  scrapeconfigs.monitoring.coreos.com \
  servicemonitors.monitoring.coreos.com \
  thanosrulers.monitoring.coreos.com
```

Jeśli planujesz zainstalować stack ponownie wkrótce, można spokojnie zostawić CRD-y — nie przeszkadzają, następny `helm install` po prostu ich użyje ponownie.

## 6. Metryki aplikacyjne — `/metrics` + `ServiceMonitor` (zrobione)

Dwie rzeczy per serwis:
1. Endpoint `/metrics` w kodzie serwisu — biblioteka inna per stack:
   - `payment` (Go/Gin): `prometheus/client_golang` → `r.GET("/metrics", gin.WrapH(promhttp.Handler()))`
   - `ai` (FastAPI): `prometheus-fastapi-instrumentator` → `Instrumentator().instrument(app).expose(app)`
   - `backend` (Laravel/FrankenPHP): `promphp/prometheus_client_php` + rozszerzenie PHP `apcu` (dodane w Dockerfile: `install-php-extensions apcu`) — liczniki trzymane w APCu, żeby przetrwały między requestami
2. Zasób `ServiceMonitor` (szablon `k8s/chart/templates/servicemonitor.yaml`, włączany przez `metrics.enabled: true` w `values/<serwis>.yaml`) — mówi Prometheusowi, żeby scrapował dany Service.

**Ważne, sprawdzone wymaganie:** Prometheus w tym stacku ma `serviceMonitorSelector: matchLabels: release: kube-prometheus-stack` — każdy `ServiceMonitor` musi mieć etykietę `release: kube-prometheus-stack`, inaczej zostanie zignorowany. Sprawdź komendą:
```bash
kubectl get prometheus -n monitoring -o jsonpath='{.items[0].spec.serviceMonitorSelector}'
```

### Napotkany błąd: `ServiceMonitor` bez trafień, zero błędów

Po dodaniu `ServiceMonitor` z poprawną etykietą `release`, Prometheus **nie zgłaszał żadnego błędu**, ale target po prostu nie istniał (`/api/v1/targets` — brak `ai`/`payment`/`backend`, nawet wśród `dropped`). Przyczyna: `ServiceMonitor.spec.selector.matchLabels: {app: <serwis>}` dopasowuje się do **etykiet samego obiektu Service** (`metadata.labels`), a nie do jego `spec.selector` (to, czego Service używa do znalezienia swoich podów — zupełnie inne pole). Nasz szablon `service.yaml` ustawiał `spec.selector.app`, ale nigdy `metadata.labels.app` — więc `ServiceMonitor` szukał etykiety, której obiekt Service nigdy nie miał.

Fix: dodać `labels: app: {{ .Values.name }}` do `metadata` w `k8s/chart/templates/service.yaml`, obok `spec.selector`.

### Jak zweryfikować, że dany serwis jest faktycznie scrapowany

```bash
kubectl port-forward -n monitoring svc/kube-prometheus-stack-prometheus 9090:9090 &

curl -s http://localhost:9090/api/v1/targets | python3 -c "
import json,sys
data = json.load(sys.stdin)
for t in data['data']['activeTargets']:
    j = t['labels'].get('job','?')
    if j in ('ai','payment','backend'):
        print(j, t['scrapeUrl'], t['health'])
"
```

Jeśli serwis się nie pojawia (ani jako `active`, ani `dropped`) mimo poprawnej etykiety `release` na `ServiceMonitor` — sprawdź najpierw, czy sam obiekt `Service` (nie jego `selector`) ma etykietę, po której filtruje `ServiceMonitor`:
```bash
kubectl get svc <serwis> -n product-center --show-labels
```

### RED metrics (rate/errors/duration) per serwis

`ai` (przez `prometheus-fastapi-instrumentator`) dostaje `http_requests_total`/`http_request_duration_seconds` automatycznie. `payment` i `backend` wymagały własnego middleware, które je liczy ręcznie:
- `payment`: middleware Gin w `main.go` (`metricsMiddleware`, rejestrowane przez `r.Use(...)`)
- `backend`: `app/Http/Middleware/PrometheusMetrics.php`, rejestrowane globalnie w `bootstrap/app.php` (`$middleware->append(...)`), liczniki w APCu (ta sama biblioteka/storage co endpoint `/metrics`)

**Uwaga na nazwy etykiet:** `ai` używa etykiety `handler` (nie `path`) dla trasy — to standard `prometheus-fastapi-instrumentator`. Zapytania PromQL grupujące po `job` działają identycznie dla wszystkich trzech; grupowanie po nazwie endpointu wymagałoby ujednolicenia (`label_replace`), więc na razie dashboard grupuje tylko po `job`.

## 7. Dashboard jako kod

Dashboard **nie** budujemy ręcznie w UI Grafany — nie przetrwałby kolejnego `terraform destroy`/świeżego Minikube (żyje tylko w bazie Grafany, nie w repo). Zamiast tego: plik JSON w `ConfigMap` z etykietą `grafana_dashboard: "1"` — sidecar Grafany (`grafana-sc-dashboard`, wbudowany w ten chart) sam go wykrywa i ładuje, w dowolnym namespace.

```bash
kubectl apply -f k8s/monitoring/dashboard-services.yaml -n monitoring
```

Dashboard `product-center services (RED)` (`k8s/monitoring/dashboard-services.yaml`) ma trzy panele — Request rate, Error rate (5xx), Latency p95 — oraz zmienną `$service` (z `label_values(http_requests_total, job)`) do filtrowania między `ai`/`payment`/`backend`.

Weryfikacja, że sidecar podłapał plik:
```bash
kubectl logs -n monitoring deployment/kube-prometheus-stack-grafana -c grafana-sc-dashboard --tail=20
# szukaj: "Writing /tmp/dashboards/<nazwa>.json" i "Dashboards config reloaded"
```
