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

Dokładne przypadki użycia i zakres odpowiedzialności poszczególnych serwisów opisane są w sekcji [Use case'y](#use-casey) poniżej.

## Use case'y

Projekt to sklep e-commerce. System ma dwóch typów użytkowników: `user` (klient sklepu) i `admin` (zarządza katalogiem).

### Backend (Laravel)

- Logowanie — dwie role: `user`, `admin`, każda z innym mechanizmem uwierzytelniania:
  - **User** — standardowa rejestracja i logowanie (email/hasło).
  - **Admin** — logowanie przez **OpenID Connect (Microsoft / Entra ID)**, SSO bez lokalnego hasła; brak publicznej rejestracji — konto zakładane ręcznie albo automatycznie (JIT) przy pierwszym logowaniu, jeśli e-mail należy do skonfigurowanej domeny tenantu. W Laravelu naturalny wybór to `Laravel Socialite` z providerem Microsoft/Azure.
- **User** — przegląda i kupuje produkty (typowy e-commerce).
- **Admin** — CRUD produktów: nazwa, opis, cena (podstawowy pricing w MVP), dynamiczne cechy (np. waga, szerokość, wysokość), zdjęcie główne, załączniki (np. instrukcje obsługi w PDF), przypisanie do kategorii.
- System dwujęzyczny (PL/EN) z możliwością rozszerzenia o kolejne języki bez migracji schematu.
- Panel admina prawdopodobnie renderowany bezpośrednio w Laravel (nie w React) — sklep dla klientów to osobna aplikacja React.
- Właściciel danych produktów, użytkowników, zamówień i cen.

### AI (Python, RAG)

- Czyta PDF-y z instrukcjami obsługi powiązanymi z produktami.
- Generuje opis produktu na podstawie treści PDF-a oraz danych produktu wprowadzonych przez admina.
- Wywoływane **asynchronicznie przez SQS**: backend publikuje zdarzenie po dodaniu/aktualizacji produktu (z odnośnikiem do PDF-a w S3), AI przetwarza w tle (RAG może potrwać) i zwraca wygenerowany opis do backendu.

### Frontend (React)

- Sklep e-commerce dla `user` — przeglądanie, koszyk, checkout.
- Integracja ze Stripe (UI checkout) razem z serwisem `payment`.

### Payment (Go)

- Obsługa płatności Stripe.
- Przepływ: backend inicjuje płatność (REST → `payment`) po założeniu zamówienia, `payment` zakłada sesję Stripe; po potwierdzeniu płatności (webhook Stripe → `payment`) serwis publikuje zdarzenie do SQS, które konsumuje backend i aktualizuje status zamówienia.
- Backend orkiestruje cały przepływ (stan zamówienia żyje w jednym miejscu) zamiast frontendu integrującego się bezpośrednio z `payment` — bliżej wzorca produkcyjnego (saga) i łatwiej o spójny status zamówienia.

### Dane i storage

- **Backend**: PostgreSQL — użytkownicy, produkty, ceny, zamówienia.
- **AI**: vector store do embeddingów z RAG — konkretna technologia (np. pgvector) do ustalenia przy budowie serwisu.
- **Payment**: DynamoDB — rozważane jako storage własny serwisu (np. transakcje, idempotency), decyzja do podjęcia przy jego implementacji.
- **S3**: pliki — zdjęcia produktów i dokumentacja PDF.
- **SQS**: zdarzenia async — `product-updated`/PDF → AI (generowanie opisu), `payment-completed` → backend (status zamówienia).

### Schemat produktów (PostgreSQL) — plan

To jest plan schematu (dokumentacja decyzji projektowej), nie gotowe migracje — migracje Laravela powstaną dopiero przy implementacji backendu.

Założenia:
- **i18n** (PL/EN, rozszerzalne) — pola tłumaczone jako `JSONB` w formacie `{"pl": "...", "en": "..."}` na tej samej tabeli (wzorzec `spatie/laravel-translatable`), zamiast osobnej tabeli `product_translations` — nowy język nie wymaga migracji.
- **Dynamiczne cechy** (waga, szerokość, wysokość itd.) — kolumna `attributes JSONB` zamiast sztywnych kolumn lub klasycznego EAV — nowa cecha nie wymaga migracji; `GIN` index umożliwia filtrowanie po cechach, gdyby było potrzebne.
- **Kategorie** — płaska lista na MVP (produkt należy do jednej kategorii). Hierarchia kategorii (drzewo, wiele kategorii na produkt) odłożona do momentu, aż będzie faktycznie potrzebna.
- **Warianty produktu** (np. inny kolor/rozmiar z własną ceną/SKU) — **poza zakresem MVP**, świadomie nie tworzymy tabeli `product_variants` teraz. Zaplanowane rozszerzenie: osobna tabela z FK do `products`, własnym SKU, ceną i podzbiorem `attributes`, gdy będzie na to konkretne zapotrzebowanie.
- Zdjęcie główne i załączniki to referencje do plików w S3, nie dane binarne w bazie.
- **Załączniki mogą być per język** (np. osobna instrukcja obsługi PL i EN dla tego samego produktu) — `product_attachments` ma kolumnę `locale`; `NULL` oznacza załącznik niezależny od języka (np. karta gwarancyjna wspólna dla obu wersji).

```sql
CREATE TABLE categories (
    id          BIGSERIAL PRIMARY KEY,
    name        JSONB NOT NULL,           -- {"pl": "Elektronika", "en": "Electronics"}
    slug        VARCHAR(255) NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE products (
    id           BIGSERIAL PRIMARY KEY,
    category_id  BIGINT NOT NULL REFERENCES categories(id),
    name         JSONB NOT NULL,          -- {"pl": "...", "en": "..."}
    description  JSONB,                   -- {"pl": "...", "en": "..."}, może być uzupełniane przez AI (RAG)
    price_cents  INTEGER NOT NULL,        -- cena w najmniejszej jednostce waluty
    currency     CHAR(3) NOT NULL DEFAULT 'PLN',
    attributes   JSONB,                   -- dynamiczne cechy: {"weight_kg": 1.2, "width_cm": 30, "height_cm": 15}
    main_image   VARCHAR(255),            -- klucz S3 zdjęcia głównego
    created_at   TIMESTAMP NOT NULL DEFAULT now(),
    updated_at   TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX products_attributes_gin_idx ON products USING GIN (attributes);
CREATE INDEX products_category_id_idx ON products (category_id);

CREATE TABLE product_attachments (
    id          BIGSERIAL PRIMARY KEY,
    product_id  BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    path        VARCHAR(255) NOT NULL,    -- klucz S3 (np. instrukcja obsługi PDF)
    label       VARCHAR(255),             -- np. "Instrukcja obsługi"
    locale      VARCHAR(5),               -- np. "pl"/"en"; NULL = załącznik niezależny od języka
    created_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX product_attachments_product_id_locale_idx ON product_attachments (product_id, locale);

-- Poza MVP, w planach:
-- CREATE TABLE product_variants (
--     id          BIGSERIAL PRIMARY KEY,
--     product_id  BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
--     sku         VARCHAR(255) NOT NULL UNIQUE,
--     price_cents INTEGER NOT NULL,
--     attributes  JSONB                   -- cechy specyficzne dla wariantu, np. {"color": "red", "size": "L"}
-- );
```

### Schemat zamówień (PostgreSQL) — plan

Plan, nie gotowe migracje — analogicznie do schematu produktów powyżej.

Założenia:
- **Bez adresu dostawy w MVP** — zamówienie i płatność są w zakresie, logistyka wysyłki to świadomie odłożony kolejny krok.
- **Zamówienie ma pozycje** (`order_items`) — koszyk może zawierać kilka produktów.
- **Snapshot ceny i nazwy w `order_items`** — `product_name`/`unit_price_cents` to zamrożony zapis z momentu zakupu (zwykły string, nie tłumaczony `JSONB`), nie odczyt na żywo z `products` — cena i nazwa produktu mogą się zmienić po fakcie, a historia zamówienia musi pokazywać dokładnie to, co klient kupił i za ile.
- **`product_id` bez `ON DELETE CASCADE`** — usunięcie produktu nie może skasować historii zamówień. Sugeruje to "miękkie" usuwanie produktów (np. `archived_at`) zamiast realnego `DELETE` — decyzja do podjęcia przy budowie CRUD-a produktów.
- **Status jako `VARCHAR` + `CHECK`, nie natywny Postgresowy `ENUM`** — dodanie nowego statusu w przyszłości to zwykły `ALTER TABLE ... CHECK`, a nie `ALTER TYPE`.
- **`payment_reference`** — id sesji/payment intent ze Stripe, żeby backend mógł skorelować przychodzące zdarzenie SQS `payment-completed` z konkretnym zamówieniem.
- **Idempotencja konsumpcji SQS** — SQS gwarantuje *at-least-once delivery*, to samo zdarzenie `payment-completed` może przyjść dwa razy. Na MVP wystarczy prosta ochrona przy aktualizacji (`UPDATE ... WHERE status = 'pending'`) zamiast osobnej tabeli z logiem przetworzonych zdarzeń — tę dodamy dopiero, gdyby duplikaty faktycznie były problemem.

```sql
CREATE TABLE orders (
    id                 BIGSERIAL PRIMARY KEY,
    user_id            BIGINT NOT NULL REFERENCES users(id),
    status             VARCHAR(20) NOT NULL DEFAULT 'pending'
                       CHECK (status IN ('pending', 'paid', 'failed', 'cancelled')),
    total_cents        INTEGER NOT NULL,
    currency           CHAR(3) NOT NULL DEFAULT 'PLN',
    payment_reference  VARCHAR(255) UNIQUE,   -- Stripe session/payment intent id
    created_at         TIMESTAMP NOT NULL DEFAULT now(),
    updated_at         TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX orders_user_id_idx ON orders (user_id);
CREATE INDEX orders_status_idx ON orders (status);

CREATE TABLE order_items (
    id                BIGSERIAL PRIMARY KEY,
    order_id          BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id        BIGINT NOT NULL REFERENCES products(id),
    product_name      VARCHAR(255) NOT NULL,  -- snapshot nazwy w locale kupującego, nie JSONB
    quantity          INTEGER NOT NULL DEFAULT 1,
    unit_price_cents  INTEGER NOT NULL,       -- cena z momentu zakupu, nie live products.price_cents
    created_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX order_items_order_id_idx ON order_items (order_id);
```

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

## Status dokumentu

To jest dokument ogólny, opisujący założenia i architekturę na wysokim poziomie. Szczegółowe przypadki użycia, kontrakty API między serwisami oraz decyzje projektowe dla konkretnych funkcjonalności będą dodawane sukcesywnie, w miarę postępu prac.
