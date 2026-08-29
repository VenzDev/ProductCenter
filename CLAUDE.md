# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project purpose

This is an **educational project** for learning microservice system design and delivery — not a product with real users. Two rules override default instincts:

- **Minimal code only** — implement exactly what's needed for the current step. No speculative abstractions, no unused config, no "for later" scaffolding. The system grows one small, explained step at a time.
- **Production-realistic quality** — despite being for learning, code/structure/practices should mirror what a real production system would do (this is why there's Prometheus monitoring, Helm charts, and Terraform instead of shortcuts).

When making changes, prefer the smallest change that satisfies the current ask, and explain *what* and *why* rather than silently doing more.

When creating git commits, keep the message concise — 1-2 sentences, focused on why.

After changing any service, run its tests and static analysis (whatever tooling that service currently has — see Per-service commands below) before considering the change done. This applies to every service.

The user's experience level differs per stack — calibrate explanations accordingly:

| Stack | Level |
|---|---|
| PHP | High (less familiar with Laravel specifically) |
| Go | Medium |
| React / TypeScript | Medium |

## Repository structure

Monorepo, one directory per independently deployable service, plus infra:

```
services/
  backend/    PHP, Laravel (FrankenPHP) — main business logic / API
  frontend/   TypeScript, Next.js (App Router) + shadcn/ui — skeleton, no pages/features yet
  payment/    Go, Gin — payment handling
infrastructure/ecr/  Terraform — ECR repos + GitHub Actions OIDC push role (separate root module, stands up on its own)
infrastructure/eks/  Terraform — EKS cluster (VPC, node group, addons)
infrastructure/k8s/backend/  Helm chart for the backend service
infrastructure/k8s/payment/  Helm chart for the payment service
infrastructure/k8s/monitoring/  Grafana dashboard-as-code (ConfigMap)
e2e/  Playwright end-to-end tests, driving the frontend against the real stack
docker-compose.yaml  local dev environment for all services
docker-compose.test.yaml  fully separate copy of the stack (_test-suffixed services, own project), for backend tests and e2e/
```

Target cloud is AWS. AWS services in the design (DynamoDB, S3, SQS) are simulated locally via LocalStack. S3 (product images, via the backend's `s3` filesystem disk) is wired into `docker-compose.yaml`; DynamoDB/SQS are not yet.

Inter-service communication is deliberately mixed: **REST** for request/response paths (e.g. frontend → backend), **SQS** for inherently event-driven flows (e.g. payment-related processes).

Read `docs/design.md` first for architecture/rationale, `docs/runbook.md` for the EKS deploy procedure and a log of every real error hit and its fix, `docs/minikube.md` for the local-cluster equivalent of the runbook, and `docs/monitoring.md` for the Prometheus/Grafana setup.

## Local development

Each service has `dev` and `prod` Docker build targets. `docker-compose.yaml` runs both implemented services in `dev` mode with source bind-mounts (live reload), plus `postgres` and `localstack`:

```bash
docker compose up          # frontend:3000, payment:8080, backend:8081(→80), localstack:4566
docker compose up payment  # single service
```

- **frontend**: `next dev` (Turbopack, hot reload), full source bind-mounted including `node_modules` — `docker compose exec frontend npm ci` installs straight onto the host, so it's visible to your editor. After changing dependencies, delete `.next` (Turbopack's persistent cache can otherwise keep referencing the old `node_modules` state and throw module-resolution errors like `Cannot find module 'picocolors'`) and restart the container.
- **payment**: `air` (hot reload via `.air.toml`), full source mounted.
- **localstack**: simulates S3 locally; the `product-files` bucket is created automatically on every start via `services/backend/docker/localstack-init-s3.sh` (mounted into LocalStack's init hooks — there's no persistent volume, so it needs recreating each time).
- **backend**: FrankenPHP dev entrypoint (`docker/dev-entrypoint.sh`) runs `php artisan migrate --force` then starts the server; full source bind-mounted including `vendor` — `docker compose exec backend composer install` installs straight onto the host. Note: the entrypoint ignores any command passed via `docker compose run backend <cmd>` (it always runs migrate + serve) — if the container isn't already running (so `exec` isn't an option), use `docker compose run --rm --entrypoint sh backend -c "<cmd>"` instead. `docker compose exec backend php artisan demo:seed --products=24` populates categories, filterable attributes, products, and blog posts with images fetched from picsum.photos and pushed through the real upload pipeline (`WebpImageObserver` → `RelocateUploadedImageJob` → `GenerateWebpImageJob`) — categories/attributes/blog posts are idempotent (safe to rerun), each run adds `--products` more products. Refuses to run outside `local`/`testing` (`app/Console/Commands/SeedDemoData.php`). Needs the `backend-worker` container running to actually process the queued image jobs in dev (`QUEUE_CONNECTION=database`); under `docker-compose.test.yaml` the queue is `sync` so it's immediate.

Every service exposes `GET /health` (liveness/readiness) and `GET /metrics` (Prometheus text format) — both are load-bearing conventions used by the Helm chart's probes and `ServiceMonitor`, so any new service must implement both before it can be deployed with the shared chart.

## Per-service commands

Run everything — installs, tests, linters, one-off artisan/go/pip commands — **inside the service's Docker container**, via `docker compose exec <service> <command>` (start it first with `docker compose up -d <service>` if it isn't running). The host has no PHP/Go/Python toolchain installed; any per-service `.venv` or similar on the host is IDE-only (autocomplete/type-checking), never used to actually run or test the service.

**backend** (`services/backend`, Laravel 13 / PHP 8.5) — tests and static analysis run against the isolated `docker-compose.test.yaml` stack, not the dev one (see below), so they never touch real dev data:
```bash
docker compose -f docker-compose.test.yaml build backend_test
docker compose -f docker-compose.test.yaml up -d --wait postgres_test redis_test opensearch_test localstack_test
docker compose -f docker-compose.test.yaml run --rm --entrypoint sh backend_test -c "composer install --no-interaction"   # once per session
docker compose -f docker-compose.test.yaml up -d --wait backend_test
docker compose -f docker-compose.test.yaml exec backend_test php artisan test              # or: composer test — runs full suite (Unit + Feature), via Pest
docker compose -f docker-compose.test.yaml exec backend_test php artisan test --filter=ExampleTest   # single test
docker compose -f docker-compose.test.yaml exec backend_test ./vendor/bin/pint              # code style (Laravel Pint)
docker compose -f docker-compose.test.yaml exec backend_test ./vendor/bin/phpstan analyse --memory-limit=512M   # static analysis (Larastan, level 8)
docker compose -f docker-compose.test.yaml down -v   # tear down when done — drops its own volumes only, dev stack is untouched
```
`backend_test` bind-mounts the same `./services/backend` dev's own `backend` does, but must never share dev's `.env` (which holds a real `APP_KEY`/`JWT_SECRET` — overwriting it would invalidate dev's sessions). `services/backend/.env.testing` is a separate, **committed** file (no real secrets, so unlike `.env` there's nothing to protect) that Laravel loads instead, because `APP_ENV: testing` is set on `backend_test` in `docker-compose.test.yaml` and Laravel's `LoadEnvironmentVariables` bootstrapper loads `.env.{APP_ENV}` when that variable is set (see `vendor/laravel/framework/.../Bootstrap/LoadEnvironmentVariables.php`). `.env.testing` — not `.env.test` — because `APP_ENV` must be exactly `testing` for Laravel's own framework-level test-mode detection (e.g. `VerifyCsrfToken::runningUnitTests()`, which every Filament/Livewire admin-panel test depends on) to kick in; a same-idea-but-differently-named `.env.test`/`APP_ENV=test` silently breaks CSRF handling and fails every Livewire form test with a misleading "field is required" error. `.env.testing` is now also the **only** place test env config lives — it replaced a duplicate set of overrides that used to live inline in `phpunit.xml`, since the container running Pest also runs `composer install`/phpstan, which need a real env file regardless. Test runner is Pest (PHPUnit-compatible — existing PHPUnit test classes run unmodified). `postgres_test`/`redis_test`/`opensearch_test`/`localstack_test` are each their own dedicated instance rather than dev's, because unlike Postgres (which could just get a second `backend_test` database on the same server) Redis/OpenSearch/LocalStack have no equivalent per-tenant isolation.

`phpmd/phpmd` was tried and dropped — `pdepend` (its dependency) only supports `symfony/dependency-injection` up to `^7.0`, while Laravel 13's `symfony/http-kernel` requires `^8.0`; no compatible version combination exists. Larastan (PHPStan + Laravel rules) was added instead, configured via `phpstan.neon`.

**payment** (`services/payment`, Go 1.26 / Gin):
```bash
docker compose exec payment go run .
docker compose exec payment go test ./...
docker compose exec payment go build -o payment .
```

**frontend** (`services/frontend`, Next.js 16 / TypeScript, App Router, shadcn/ui):
```bash
docker compose exec frontend npm run lint       # ESLint
docker compose exec frontend npx next typegen   # generate .next/types (needed before tsc on a clean checkout)
docker compose exec frontend npx tsc --noEmit   # type check
docker compose exec frontend npm run build      # production build
```
`tsc --noEmit` alone fails on a clean checkout with `Cannot find name 'LayoutProps'` — that global type is generated into `.next/types` by `next dev`/`next build`/`next typegen`, not shipped statically. Always run `next typegen` first if `.next/types` isn't already present (e.g. from a prior `next dev`/`build` in the same container).
Skeleton only — no pages/features, no `/health` or `/metrics` yet, so it isn't deployable with the shared Helm chart pattern.

**e2e** (`e2e`, Playwright, run via `docker-compose.test.yaml` — the same isolated stack backend tests use, see above, not a profile on the main file):
```bash
docker compose -f docker-compose.test.yaml build backend_test frontend_test
docker compose -f docker-compose.test.yaml up -d --wait postgres_test redis_test opensearch_test localstack_test
docker compose -f docker-compose.test.yaml run --rm --entrypoint sh backend_test -c "composer install --no-interaction"   # once per session
docker compose -f docker-compose.test.yaml run --rm frontend_test npm ci   # once per session
docker compose -f docker-compose.test.yaml up -d --wait frontend_test backend_test
docker compose -f docker-compose.test.yaml exec backend_test php artisan demo:seed --products=12   # categories, attributes, products, blog posts (incl. the "Hello World" / hello-world post blog.spec.ts browses to) for e2e to browse
docker compose -f docker-compose.test.yaml run --rm e2e   # npm ci + playwright test, headless chromium
docker compose -f docker-compose.test.yaml down -v        # tear down — drops its own volumes only, dev stack is untouched
```
Uses the official `mcr.microsoft.com/playwright` image (Debian-based) rather than the frontend's own Alpine image, since Playwright's browser binaries aren't officially supported on musl libc. `docker-compose.test.yaml` (`name: product-center-test`) is its own compose project, entirely disjoint from the main `docker-compose.yaml` project — so `down -v` here can never touch dev data, and it can run concurrently with the dev stack without port clashes (nothing here publishes a host port; everything talks over the project's own internal network). `backend_test` and `frontend_test` bind-mount the same `./services/backend` and `./services/frontend` host directories as dev's `backend`/`frontend` — including `vendor`/`node_modules`, so they share whatever's already installed there rather than reinstalling. They can still collide on writes to that shared filesystem: `frontend_test` gets a dedicated `.next` build-cache volume to avoid that (see `frontend_test_next`). Runs against `http://frontend_test:3000` — this requires `allowedDevOrigins: ["frontend_test"]` in `next.config.ts`, because Next's dev server otherwise 403s `/_next/*` asset requests from any origin other than localhost.

On a fresh checkout (no local `vendor`/`node_modules` yet — this is what CI does, see `.github/workflows/test-e2e.yaml` and `test-backend.yaml`) no `.env` setup step is needed first, unlike the main dev stack — `services/backend/.env.testing` is already committed.
