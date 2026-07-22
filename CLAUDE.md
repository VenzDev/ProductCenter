# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project purpose

This is an **educational project** for learning microservice system design and delivery — not a product with real users. Two rules override default instincts:

- **Minimal code only** — implement exactly what's needed for the current step. No speculative abstractions, no unused config, no "for later" scaffolding. The system grows one small, explained step at a time.
- **Production-realistic quality** — despite being for learning, code/structure/practices should mirror what a real production system would do (this is why there's Prometheus monitoring, Helm charts, and Terraform instead of shortcuts).

When making changes, prefer the smallest change that satisfies the current ask, and explain *what* and *why* rather than silently doing more.

The user's experience level differs per stack — calibrate explanations accordingly:

| Stack | Level |
|---|---|
| PHP | High (less familiar with Laravel specifically) |
| Go | Medium |
| React / TypeScript | Medium |
| Python / AI | Lowest |

## Repository structure

Monorepo, one directory per independently deployable service, plus infra:

```
services/
  ai/         Python, FastAPI — AI-related functionality
  backend/    PHP, Laravel (FrankenPHP) — main business logic / API
  frontend/   TypeScript, React — not yet implemented (empty placeholder)
  payment/    Go, Gin — payment handling
infrastructure/eks/  Terraform — EKS cluster (VPC, node group, ECR, addons)
k8s/chart/    single reusable Helm chart for all services (see below)
k8s/monitoring/  Grafana dashboard-as-code (ConfigMap)
docker-compose.yaml  local dev environment for all services
```

Target cloud is AWS. AWS services in the design (DynamoDB, S3, SQS) are simulated locally via LocalStack — not yet wired into `docker-compose.yaml`.

Inter-service communication is deliberately mixed: **REST** for request/response paths (e.g. frontend → backend), **SQS** for inherently event-driven flows (e.g. payment-related processes).

Read `DESIGN.md` first for architecture/rationale, `RUNBOOK.md` for the EKS deploy procedure and a log of every real error hit and its fix, `MINIKUBE.md` for the local-cluster equivalent of the runbook, and `MONITORING.md` for the Prometheus/Grafana setup.

## Local development

Each service has `dev` and `prod` Docker build targets. `docker-compose.yaml` runs all three implemented services in `dev` mode with source bind-mounts (live reload):

```bash
docker compose up          # ai:8000, payment:8080, backend:8081(→80)
docker compose up ai       # single service
```

- **ai**: `uvicorn --reload`, source mounted at `./services/ai/app`.
- **payment**: `air` (hot reload via `.air.toml`), full source mounted.
- **backend**: FrankenPHP dev entrypoint (`docker/dev-entrypoint.sh`) runs `php artisan migrate --force` then starts the server; full source + a named `backend_vendor` volume mounted so container-installed vendor deps aren't clobbered by the host bind mount.

Every service exposes `GET /health` (liveness/readiness) and `GET /metrics` (Prometheus text format) — both are load-bearing conventions used by the Helm chart's probes and `ServiceMonitor`, so any new service must implement both before it can be deployed with the shared chart.

## Per-service commands

**backend** (`services/backend`, Laravel 13 / PHP 8.3):
```bash
composer install
php artisan test              # or: composer test  — runs full suite (Unit + Feature)
php artisan test --filter=ExampleTest   # single test
./vendor/bin/pint              # code style (Laravel Pint)
```
Test env config lives inline in `phpunit.xml` (sqlite `:memory:`, array drivers) — no separate `.env.testing` needed.

**payment** (`services/payment`, Go 1.26 / Gin):
```bash
go run .
go test ./...
go build -o payment .
```

**ai** (`services/ai`, Python / FastAPI):
```bash
pip install -r requirements.txt
uvicorn app.main:app --reload
```
No test suite exists yet.

**frontend**: not started — empty directory, no scaffolding yet.

## Kubernetes deployment (Helm)

One reusable chart (`k8s/chart`) serves all services; per-service differences (name, image, port, env, whether metrics are exposed) live entirely in `k8s/chart/values/<service>.yaml` — adding a new service means adding a values file, not copying manifests.

```bash
helm install <service> k8s/chart -f k8s/chart/values/<service>.yaml
helm upgrade  <service> k8s/chart -f k8s/chart/values/<service>.yaml
helm template <service> k8s/chart -f k8s/chart/values/<service>.yaml   # render only, no cluster
```

Key conventions baked into the chart:
- `Service.metadata.labels.app` must be set (not just `spec.selector.app`) — `ServiceMonitor` matches on the Service object's own labels, not its selector. Getting this wrong produces zero errors and a silently missing scrape target.
- Any `ServiceMonitor` needs the label `release: kube-prometheus-stack` or the in-cluster Prometheus ignores it (`serviceMonitorSelector` is scoped to that label).
- `backend` needs `SERVER_NAME: ":80"` (FrankenPHP auto-HTTPS otherwise breaks the readiness probe) and an `APP_KEY` sourced from a Kubernetes Secret named `backend-secrets` — that secret is **not** managed by Terraform or the chart and must be recreated by hand after every fresh cluster (`RUNBOOK.md` §1 step 4 / `MINIKUBE.md` §3).

Two target environments use the same chart:
- **EKS** (`infrastructure/eks`, real deploy): images pushed to ECR, built with `--platform linux/amd64` (nodes are x86_64 even when building from Apple Silicon). Full sequence in `RUNBOOK.md`.
- **Minikube** (local, no AWS cost): images built directly into Minikube's Docker daemon (`eval $(minikube docker-env)`) tagged `:local` (any non-`latest` tag defaults to `imagePullPolicy: IfNotPresent`, avoiding a pull attempt), everything scoped to a `product-center` namespace to coexist with unrelated Minikube workloads. Full sequence in `MINIKUBE.md`. Minikube does **not** emulate anything AWS-specific (vpc-cni, NAT, ECR, IAM) — it's for iterating on the Kubernetes layer only; final verification still requires real EKS.

## Terraform (`infrastructure/eks`)

Provisions VPC + EKS + managed node group + ECR repos for `ai`/`payment`/`backend`, via the `terraform-aws-modules/eks` and `.../vpc` modules. Non-obvious choices, each backed by a real incident logged in `RUNBOOK.md` §4:
- Nodes live in **private subnets** behind a single NAT Gateway (no direct internet access otherwise).
- `vpc-cni` addon has `before_compute = true` — without it, node group creation and `vpc-cni` installation deadlock waiting on each other.
- `coredns`/`kube-proxy`/`vpc-cni` addons are explicit — the EKS module does not install them by default, and nodes stay `NotReady` forever without them.
- ECR repos use `force_delete = true` so `terraform destroy` doesn't get blocked by leftover images.
- Kubernetes version pinned to `1.35` (avoids extended-support pricing on an aging version).

Nothing inside the cluster (pods, Services, the `backend-secrets` Secret) survives `terraform destroy` — only what Terraform itself manages does. ECR images are destroyed too (via `force_delete`) and must be rebuilt/pushed after every fresh `apply`.

## Monitoring (kube-prometheus-stack)

Installed via the upstream `prometheus-community/kube-prometheus-stack` Helm chart into a `monitoring` namespace — not part of `k8s/chart`. Application metrics per service:

| Service | Library |
|---|---|
| `ai` | `prometheus-fastapi-instrumentator` (auto RED metrics, label is `handler` not `path`) |
| `payment` | `prometheus/client_golang` + a hand-written Gin middleware (`metricsMiddleware` in `main.go`) |
| `backend` | `promphp/prometheus_client_php` backed by APCu (survives across FrankenPHP requests) + `App\Http\Middleware\PrometheusMetrics`, registered globally in `bootstrap/app.php` |

Dashboards are defined as code: `k8s/monitoring/dashboard-services.yaml` is a `ConfigMap` labeled `grafana_dashboard: "1"`, auto-loaded by the Grafana sidecar — dashboards are never hand-built in the Grafana UI since that state wouldn't survive a cluster rebuild. See `MONITORING.md` for full install/verify/teardown commands and the CRD cleanup caveat (`helm uninstall` does not remove Prometheus Operator CRDs).
