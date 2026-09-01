# ADL — Architecture Decision Log

Krótkie zapiski nietrywialnych decyzji projektowych wraz z uzasadnieniem — dla rzeczy, które nie są oczywiste z samego kodu.

## `trustProxies(at: '*')` w backendzie (Laravel)

**Problem:** ALB terminuje TLS i do poda przekazuje zwykłe HTTP. Bez zaufania nagłówkowi `X-Forwarded-Proto`, `Request::isSecure()` w Laravelu zwraca `false`, mimo że przeglądarka↔ALB jest po HTTPS — Filament generował wtedy linki do assetów jako `http://`, co przeglądarka blokowała jako mixed content na stronie ładowanej po HTTPS.

**Rozwiązanie:** `$middleware->trustProxies(at: '*')` w `bootstrap/app.php` — Laravel zaczyna ufać nagłówkom `X-Forwarded-*` (w tym `Proto`) niezależnie od tego, kto się bezpośrednio połączył.

**Dlaczego to uproszczenie, nie rozwiązanie produkcyjne:** `X-Forwarded-*` może wysłać każdy, kto się połączy — `at` to whitelista adresów, którym ufamy, że te nagłówki nie są podrobione. `'*'` ufa im od każdego, więc w teorii cokolwiek innego w tym samym VPC (inny pod, skompromitowany kontener), łącząc się bezpośrednio z podem backendu z pominięciem ALB, mogłoby podrobić `X-Forwarded-Proto: https`.

**Co robi się naprawdę:** ogranicza się `at` do CIDR-u, z którego faktycznie przychodzi ruch od load balancera — tu byłby to CIDR VPC (`10.0.0.0/16`, `infrastructure/eks/vpc.tf`), bo ALB w trybie `target-type: ip` łączy się z podem bezpośrednio z adresu w tym VPC. To nadal nie chroni przed czymś złośliwym *wewnątrz* VPC, ale odcina ryzyko spoza niego.

**Status:** zostawione jako `'*'` — w tym klastrze (jednoosobowy VPC, brak innych najemców) ryzyko jest niskie, a projekt jest edukacyjny. Zawężenie do CIDR VPC to prosta zmiana jednej linii, do zrobienia gdy będzie zależało na dociągnięciu tego elementu do poziomu produkcyjnego.

## MCP przez HTTP — Entra jako zewnętrzny authorization server (bez Passport)

**Problem:** serwer MCP (`ProductCenterServer`) miał być dostępny zdalnie z Claude Desktop /
claude.ai, z autoryzacją przez Microsoft Entra. `laravel/mcp` natywnie wspiera OAuth tylko
przez **Laravel Passport** (`Mcp::oauthRoutes()` twardo linkuje `route('passport.*')` i wystawia
własny `/.well-known/oauth-authorization-server` + endpoint DCR). To znaczyłoby postawienie
drugiego authorization servera obok Entry — tylko po to, żeby być dla niej proxy.

**Rozwiązanie:** backend jest wyłącznie **OAuth 2.1 resource serverem** (RFC 9728). Nie prowadzi
żadnego OAuth — waliduje token wystawiony przez Entrę i tyle:
- `routes/ai.php`: `Mcp::web('/mcp', …)->middleware('auth:mcp')`; guard `mcp` to `Auth::viaRequest`
  (`entra-mcp`) → `App\Mcp\Http\EntraTokenAuthenticator` (weryfikacja podpisu przez JWKS Entry,
  `iss`/`aud`/`scp`, mapowanie `oid` → `Admin` z tym samym JIT co SSO panelu —
  `MicrosoftAdminResolver::resolveFromClaims`).
- Sami rejestrujemy trasy `/.well-known/oauth-protected-resource[/{path}]` — pod **tymi samymi
  nazwami** (`mcp.oauth.protected-resource[.nested]`), których szuka middleware
  `AddWwwAuthenticateHeader` z `laravel/mcp`, więc na 401 pakiet sam dokłada poprawny nagłówek
  `WWW-Authenticate: … resource_metadata=…`. Metadane wskazują `authorization_servers` = Entra.
- Klient (Claude) robi auth code + PKCE bezpośrednio z Entrą; token trafia do `/mcp` jako Bearer.

**Uproszczenia względem pełnej zgodności ze specyfikacją MCP:**
- **Brak DCR** — Entra nie ma użytecznego dynamic client registration, więc „Claude MCP Client”
  to ręczna rejestracja aplikacji w Entra, a client_id/secret wkleja się w ustawieniach connectora.
- **`resource` param (RFC 8707)** — dwa wymagania trzeba pogodzić: strict-klient MCP
  (`@modelcontextprotocol` SDK / `mcp-remote`) sprawdza, że `metadata.resource` jest prefiksem
  URL-a połączenia; Entra przyjmuje w `resource` tylko zarejestrowane *Application ID URI* pasujące
  do scope (inaczej AADSTS9010010). Rozwiązanie: **zweryfikować domenę `bechta.pl` w Entra** i ustawić
  *Application ID URI* appki API na dokładnie `https://admin.bechta.pl/mcp` — wtedy `resource` w
  metadanych (`= url('/mcp')`, `MCP_ENTRA_RESOURCE` puste) spełnia oba. Skutek uboczny: **pełny flow
  klienta nie działa lokalnie** (`http://localhost` nie może być App ID URI w Entra) — lokalnie
  testuje się backend ręcznym tokenem.
- **`aud` / scope pinowane configiem** (`config/mcp_auth.php`) zamiast negocjowane — jedna para
  rejestracji Entra, jeden dozwolony scope `mcp.use`.
- **Brak scope-based autoryzacji per-tool** — jak w panelu, każdy `Admin` może wszystko
  (`Admin::canAccessPanel()` też zwraca bezwarunkowo `true`).

**Status:** działa; gdyby `laravel/mcp` dodał wsparcie dla zewnętrznego AS, można wyrzucić
ręczne trasy metadanych. Ewentualny problem z odkrywaniem metadanych AS w Entrze (klient buduje
URL z issuera) obszedłby lokalny proxy re-serwujący metadane Entry — na razie niepotrzebny.
