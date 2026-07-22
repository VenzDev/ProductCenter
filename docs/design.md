# Product Center — Design

## Cel projektu

Projekt edukacyjny — nauka projektowania i budowy systemu opartego o mikroserwisy.

Zasady pracy nad projektem:

- **Kod minimalny** — implementujemy tylko to, co jest aktualnie potrzebne. Brak kodu "na przyszłość", niedokończonych abstrakcji czy funkcji, które nie mają jeszcze zastosowania. Rozszerzamy system stopniowo, w miarę pojawiania się kolejnych wymagań.
- **Jakość zbliżona do produkcyjnej** — mimo że to nauka, kod, struktura i praktyki mają odzwierciedlać podejście stosowane w realnych systemach produkcyjnych.
- **Praca krokowa** — każdy krok implementacji ma być mały i dobrze opisany, tak aby można było zrozumieć *co* i *dlaczego* zostało zrobione, zanim przejdziemy dalej.

### Poziom doświadczenia

Punkt odniesienia przy doborze wyjaśnień i tempa pracy nad poszczególnymi serwisami:

| Technologia | Poziom doświadczenia |
|---|---|
| PHP | Wysoki (mało doświadczenia z Laravel konkretnie) |
| Go | Średni |
| React / TypeScript | Średni |
| Python / AI | Najniższy |

## Architektura systemu

System składa się z czterech niezależnych mikroserwisów:

| Serwis | Technologia | Rola |
|---|---|---|
| `ai` | Python, FastAPI | Funkcjonalności związane z AI |
| `backend` | PHP, Laravel | Główna logika biznesowa / API |
| `frontend` | TypeScript, React.js | Interfejs użytkownika |
| `payment` | Go, Gin | Obsługa płatności |

Dokładne przypadki użycia i zakres odpowiedzialności poszczególnych serwisów zostaną doprecyzowane w kolejnych krokach.

## Infrastruktura

- **Środowisko docelowe:** AWS, wdrożenie na **EKS** lub **ECS** — obie opcje przygotowane równolegle jako alternatywne konfiguracje Terraform.
- **Infrastructure as Code:** Terraform.
- **CI/CD:** GitHub Actions (workflows w `.github/`).
- **Usługi AWS wykorzystywane przez system:**
  - DynamoDB
  - S3
  - SQS

## Struktura repozytorium

Monorepo z podziałem na serwisy:

```
services/
  ai/         # Python, FastAPI
  backend/    # PHP, Laravel
  frontend/   # TypeScript, React
  payment/    # Go, Gin
infrastructure/ # Terraform (EKS/ECS)
.github/        # CI/CD workflows
```

## Komunikacja między serwisami

Mieszany model, dobierany do przypadku:

- **Synchronicznie (REST)** — tam, gdzie odpowiedź jest potrzebna od razu (np. frontend → backend).
- **Asynchronicznie (SQS)** — tam, gdzie proces jest z natury zdarzeniowy (np. przepływy związane z płatnościami).

## Środowisko lokalne

Usługi AWS (DynamoDB, S3, SQS) symulowane lokalnie przez **LocalStack** w ramach `docker-compose.yaml` — bez kosztów i bez zależności od realnego konta AWS na etapie nauki.

## Plan pierwszych kroków

1. Szkielet serwisu **AI** (FastAPI) — endpoint `/health` + `hello world`.
2. Szkielet serwisu **backend** (Laravel) — endpoint `/health` + `hello world`.
3. Szkielet serwisu **payment** (Go/Gin) — endpoint `/health` + `hello world`.
4. Konfiguracja `docker-compose.yaml` i środowiska lokalnego (w tym LocalStack).
5. Terraform — puste/minimalne środowiska na EKS i/lub ECS pod powyższe szkielety.

Frontend oraz szczegółowe kontrakty API dołączymy w kolejnych etapach, po ustaleniu use case'ów.

## Status dokumentu

To jest dokument ogólny, opisujący założenia i architekturę na wysokim poziomie. Szczegółowe przypadki użycia, kontrakty API między serwisami oraz decyzje projektowe dla konkretnych funkcjonalności będą dodawane sukcesywnie, w miarę postępu prac.
