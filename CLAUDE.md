# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project purpose

This is an **educational project** for learning microservice system design and delivery — not a product with real users. Two rules override default instincts:

- **Minimal code only** — implement exactly what's needed for the current step. No speculative abstractions, no unused config, no "for later" scaffolding. The system grows one small, explained step at a time.
- **Production-realistic quality** — despite being for learning, code/structure/practices should mirror what a real production system would do (this is why there's Prometheus monitoring, Helm charts, and Terraform instead of shortcuts).

When making changes, prefer the smallest change that satisfies the current ask, and explain *what* and *why* rather than silently doing more.

When creating git commits, keep the message concise — 1-2 sentences, focused on why.

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

Read `docs/design.md` first for architecture/rationale, `docs/runbook.md` for the EKS deploy procedure and a log of every real error hit and its fix, `docs/minikube.md` for the local-cluster equivalent of the runbook, and `docs/monitoring.md` for the Prometheus/Grafana setup.

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

Run everything — installs, tests, linters, one-off artisan/go/pip commands — **inside the service's Docker container**, via `docker compose exec <service> <command>` (start it first with `docker compose up -d <service>` if it isn't running). The host has no PHP/Go/Python toolchain installed; any per-service `.venv` or similar on the host is IDE-only (autocomplete/type-checking), never used to actually run or test the service.

**backend** (`services/backend`, Laravel 13 / PHP 8.5):
```bash
docker compose exec backend composer install
docker compose exec backend php artisan test              # or: composer test — runs full suite (Unit + Feature), via Pest
docker compose exec backend php artisan test --filter=ExampleTest   # single test
docker compose exec backend ./vendor/bin/pint              # code style (Laravel Pint)
docker compose exec backend ./vendor/bin/phpstan analyse --memory-limit=512M   # static analysis (Larastan, level 8)
```
Test env config lives inline in `phpunit.xml` (sqlite `:memory:`, array drivers) — no separate `.env.testing` needed. Test runner is Pest (PHPUnit-compatible — existing PHPUnit test classes run unmodified).

`phpmd/phpmd` was tried and dropped — `pdepend` (its dependency) only supports `symfony/dependency-injection` up to `^7.0`, while Laravel 13's `symfony/http-kernel` requires `^8.0`; no compatible version combination exists. Larastan (PHPStan + Laravel rules) was added instead, configured via `phpstan.neon`.

**payment** (`services/payment`, Go 1.26 / Gin):
```bash
docker compose exec payment go run .
docker compose exec payment go test ./...
docker compose exec payment go build -o payment .
```

**ai** (`services/ai`, Python / FastAPI):
```bash
docker compose exec ai pip install -r requirements.txt
docker compose exec ai uvicorn app.main:app --reload
docker compose exec ai pytest
```

**frontend**: not started — empty directory, no scaffolding yet.
