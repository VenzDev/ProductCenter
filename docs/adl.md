# ADL — Architecture Decision Log

Krótkie zapiski nietrywialnych decyzji projektowych wraz z uzasadnieniem — dla rzeczy, które nie są oczywiste z samego kodu.

## `trustProxies(at: '*')` w backendzie (Laravel)

**Problem:** ALB terminuje TLS i do poda przekazuje zwykłe HTTP. Bez zaufania nagłówkowi `X-Forwarded-Proto`, `Request::isSecure()` w Laravelu zwraca `false`, mimo że przeglądarka↔ALB jest po HTTPS — Filament generował wtedy linki do assetów jako `http://`, co przeglądarka blokowała jako mixed content na stronie ładowanej po HTTPS.

**Rozwiązanie:** `$middleware->trustProxies(at: '*')` w `bootstrap/app.php` — Laravel zaczyna ufać nagłówkom `X-Forwarded-*` (w tym `Proto`) niezależnie od tego, kto się bezpośrednio połączył.

**Dlaczego to uproszczenie, nie rozwiązanie produkcyjne:** `X-Forwarded-*` może wysłać każdy, kto się połączy — `at` to whitelista adresów, którym ufamy, że te nagłówki nie są podrobione. `'*'` ufa im od każdego, więc w teorii cokolwiek innego w tym samym VPC (inny pod, skompromitowany kontener), łącząc się bezpośrednio z podem backendu z pominięciem ALB, mogłoby podrobić `X-Forwarded-Proto: https`.

**Co robi się naprawdę:** ogranicza się `at` do CIDR-u, z którego faktycznie przychodzi ruch od load balancera — tu byłby to CIDR VPC (`10.0.0.0/16`, `infrastructure/eks/vpc.tf`), bo ALB w trybie `target-type: ip` łączy się z podem bezpośrednio z adresu w tym VPC. To nadal nie chroni przed czymś złośliwym *wewnątrz* VPC, ale odcina ryzyko spoza niego.

**Status:** zostawione jako `'*'` — w tym klastrze (jednoosobowy VPC, brak innych najemców) ryzyko jest niskie, a projekt jest edukacyjny. Zawężenie do CIDR VPC to prosta zmiana jednej linii, do zrobienia gdy będzie zależało na dociągnięciu tego elementu do poziomu produkcyjnego.
