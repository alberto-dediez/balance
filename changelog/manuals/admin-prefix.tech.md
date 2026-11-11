# Prefijo configurable para la administración web — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-09 · **Bounded Contexts:** `Shared` (Twig, tests) y configuración global (`config/`, `Makefile`)

## Qué hace y por qué

Los proyectos derivados de Hydra con web pública necesitan la raíz `/` libre y sin login. Hasta ahora toda la administración colgaba de `/{_locale}/...` y `access_control` exigía `ROLE_USER` para `^/`.

La variable `ADMIN_PREFIX` (vacía por defecto, `.env:52-54`) mueve las rutas web de administración bajo un prefijo (`/management/{_locale}/...`). Con el valor vacío el comportamiento es idéntico al anterior. Con prefijo, lo que queda fuera es público por defecto. Las decisiones (formato, rutas que se mueven, destino de login/logout, resolución en tests) están en la tabla de `plan.md`.

El valor se resuelve al compilar las rutas y queda en la caché: cambiarlo exige `make cc` (o `make reset-test-cache` en test). El formato (`''` o path con `/` inicial, sin barra final, `[a-z0-9-]` y `/`) no se valida en código.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Config | `.env` (l. 52-54) | `ADMIN_PREFIX=''` | modificado |
| Config | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` (l. 4, 50-52) | parámetro `app.admin_prefix` y argumento `$adminPrefix` de la extensión Twig | modificado |
| Config | `config/routes.yaml` (l. 13, 28, 39, 69, 80, 91, 102, 109-112) | prefijo `'%app.admin_prefix%/{_locale}'` en las 7 importaciones web; `home_fallback` en `%app.admin_prefix%/` hacia `%app.admin_prefix%/%app.locale_default%` | modificado |
| Config | `config/packages/security.yaml` (l. 41, 45, 65-73) | `default_target_path`, `logout.target` y `access_control` | modificado |
| Config | `Makefile` (l. 7-8, 109-126) | parámetro `admin_prefix=` en `test-acceptance` y `test-acceptance-parallel` | modificado |
| Twig | `src/Shared/Infrastructure/Symfony/Twig/AdminPathExtension.php` | función `is_admin_path()` | nuevo |
| Twig | `templates/bundles/TwigBundle/Exception/error.html.twig` | una línea: extiende `admin` o `public` según `is_admin_path()` | modificado |
| Twig | `templates/shared/error/admin.html.twig` | el antiguo `error.html.twig` íntegro (layout de administración) | nuevo |
| Twig | `templates/shared/error/public.html.twig` | HTML autónomo con estilos en línea, sin assets del admin; se sobrescribe por proyecto | nuevo |
| Tests | `tests/Acceptance/Shared/AcceptanceClient.php` (l. 34, 185-227) | `resolveAdminPrefix()`, `adminPrefix()`, `resolvePath()`, `routeExists()`; usados en `request()` y `requestWithFiles()` | modificado |
| Tests | `tests/Acceptance/Shared/WebContext.php` | hook `BeforeScenario` (`@public`), pasos `the response redirects to`, `the path ... resolves to the administration`, `the path ... is used as written`; `I am redirected to` resuelve el path | modificado |
| Tests | `tests/Acceptance/Security/SecurityContext.php` (l. 311, 418) | `resolvePath()` en la aserción de `Location` y en el *target path* | modificado |
| Tests | `tests/Acceptance/Shared/admin-prefix.feature` | escenarios de la funcionalidad | nuevo |
| Tests | `tests/Unit/Shared/Infrastructure/Symfony/Twig/AdminPathExtensionTest.php` | 9 casos de `isAdminPath()` y `getFunctions()` | nuevo |
| Tests | `profile.feature`, `sidebar-menu.feature`, `global-search.feature` | aserciones de `href` relajadas a subcadena sin `/` inicial | modificado |

## Flujo de una operación

**Resolución de rutas.** El router sustituye el parámetro al compilar cada ruta: `/{_locale}/...` pasa a `<prefijo>/{_locale}/...`. Los controladores no cambian: el prefijo entra por la importación. Las URLs se generan con `path()`/`UrlGenerator`, así que todo sigue al prefijo (también `SsoAuthenticator`, que genera `home`).

**Acceso** (`security.yaml:60-73`, el orden importa): reglas de API/openapi, `sign` y `library/public/media` ancladas con el prefijo, `recover-password`, `login`, `logout` y `^/webhook/` sin ancla (siguen casando con el prefijo delante), `^/_components` con `ROLE_USER` (añadida tras la review: con prefijo `/_components` queda fuera y sería público), `^<prefijo>/` con `ROLE_USER` y, al final, `^/` con `PUBLIC_ACCESS`. Con prefijo vacío la regla `ROLE_USER` casa con todo y la pública es inalcanzable.

**Login y logout.** `form_login.default_target_path` y `logout.target` valen `%app.admin_prefix%/` (`security.yaml:41, 45`). Tras el 2FA, `TwoFactorLoginController` usa el *target path* de sesión o el *success handler* de `form_login`, por lo que hereda el destino.

**Raíz de la administración.** `home_fallback` redirige de forma permanente a `<prefijo>/<LANGUAGE_DEFAULT>`. Un anónimo que entre ahí acaba en el login (la regla `ROLE_USER` actúa antes que la redirección).

**Páginas de error.** `error.html.twig` evalúa `is_admin_path()` (`AdminPathExtension.php:25-40`): `true` con prefijo vacío, sin petición principal, o si el path principal es el prefijo o empieza por `<prefijo>/`. Se usa la petición principal porque el error se renderiza en una subpetición. `error403/404/500/503` siguen extendiendo `error.html.twig`; el `error503` del modo mantenimiento pasa por el mismo despacho.

**Tests de aceptación.** `AcceptanceClient::resolvePath()` (l. 195-214) devuelve el path tal cual si la resolución está desactivada, el prefijo es vacío, el path no empieza por `/` o ya lleva el prefijo. Si no, prueba `routeExists(prefijo + path)` (`matchRequest`; `ResourceNotFoundException` es no, `MethodNotAllowedException` es sí) y antepone el prefijo cuando existe. La query se conserva. `WebContext::configureAdminPrefix` desactiva la resolución en escenarios o features con la etiqueta `@public` y la reactiva en todos los demás (el cliente es un singleton).

## Autorización y ámbito

No hay authorizer ni acción nueva. Es solo `access_control` y el firewall `main`. Quedan en la raíz, sin cambios: `/api/*`, `/login/sso/*`, `/webhook/*`, `/openapi/*`, `/health-check`, `/_components` (con login, ver arriba), `/_error`, `/_wdt`, `/_profiler`. `/_error/*` (solo dev) ya no exige login con prefijo y muestra la plantilla pública.

## Persistencia y migración

Ninguna.

## Configuración y operación

- `ADMIN_PREFIX` en `.env` de cada proyecto. Tras cambiarla: `make cc`.
- Cookies de sesión y remember-me siguen en `/`; el modo mantenimiento sigue cerrando todo el sitio (`MaintenanceModeListener`, sin dependencia del prefijo). Ambos fuera de alcance.
- `compose.yaml` inyecta `.env` en el contenedor, así que los tests heredan `ADMIN_PREFIX` sin tocar `TEST_CONT`. `make test-acceptance admin_prefix=/management` (y `test-acceptance-parallel`) añade `-e ADMIN_PREFIX` vía `ADMIN_PREFIX_ENV` (`Makefile:7-8`), limpia la caché de test antes y la restaura después aunque Behat falle (se conserva su código de salida).

## Cómo extenderlo

- **Nueva importación web de administración** en `config/routes.yaml`: usar `prefix: '%app.admin_prefix%/{_locale}'`. Una ruta pública, fuera del prefijo.
- **Web pública en un proyecto derivado:** sobrescribir `templates/shared/error/public.html.twig`; etiquetar con `@public` las features cuyas rutas coincidan con una de administración (`/`, `/es`, `/es/login`...) y, dentro de ellas, entrar en la administración con el prefijo explícito. Si la web pública usa Live Components, relajar la regla `^/_components`.
- **Regla `access_control` nueva de administración con idioma:** anclarla con `^%app.admin_prefix%/...`.
- Trampa: los Live Components comparten la ruta `/_components`; no se pueden separar entre administración y web pública.

## Tests

- Unitarios: `make test-unit options="--filter AdminPathExtensionTest"`.
- Behat: `make test-acceptance options="tests/Acceptance/Shared/admin-prefix.feature"`. Pasada completa con prefijo: `make test-acceptance-parallel admin_prefix=/management` (no se automatiza en `make test`).
- Escenarios: raíz redirige al idioma, anónimo al login, `/_components/x` al login, resolución con y sin equivalente en administración, escenario `@public`, logout vuelve a `/`.
- Huecos: las páginas de error no se renderizan en Behat (`catchExceptions(false)`, `AcceptanceClient.php`), así que `public.html.twig` y el despacho de `error.html.twig` no tienen test (review B2, descartado).

## Desviaciones y deuda

Ver `## Implementación` en `plan.md` y `review.md`:

- El escenario de la raíz inicia sesión antes; el de logout usa el paso nuevo `the response redirects to`.
- `sidebar-menu.feature` y `global-search.feature` se relajaron a subcadena; la del enlace activo (`sidebar-menu.feature:14`) es más débil (B1, descartado).
- No hay paso de aserción sobre `href` independiente del prefijo; las aserciones negativas con path completo pasan por vacuidad si el path cambia.
- Review: M1 corregido (regla `^/_components`); B1 y B2 descartados; 0 abiertos.
