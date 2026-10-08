# Buscador global en la barra superior — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-07 (ajustes de estilo del modal el 2026-10-08) · **Bounded Contexts:** `Shared` (puerto, servicio, controlador, componentes, Stimulus y estilos), `Security` (proveedores de usuarios y cuentas)
- **Revisión:** `review.md` (revisión 1) y `review-fix.md` (B1 aplicado; B2 asumido), misma carpeta
- **Migración:** ninguna

## Qué hace y por qué

Cualquier usuario autenticado para el que exista al menos una categoría de búsqueda ve en la barra superior un botón con una lupa (y dispone del atajo `Ctrl+K` / `⌘K`) que abre un modal con un único campo. Al teclear, el navegador pide al servidor un fragmento HTML con los resultados de todas las categorías a las que ese usuario tiene acceso, agrupados con la misma etiqueta e icono que su entrada del menú lateral. Pulsar un resultado (o `Enter` sobre el resaltado) abre su pantalla de edición.

La pieza es el **núcleo común** para todos los proyectos derivados de Hydra: un puerto `SearchProvider` etiquetado por atributo que el `GlobalSearchService` agrega con `#[AutowireIterator]`. Hydra trae dos proveedores (usuarios y cuentas); cada proyecto derivado añade sus categorías con una clase que implemente la interfaz, sin tocar `Shared`.

Decisiones clave (tabla *Decisiones tomadas* de `plan.md`): el puerto vive en `Shared` (no en un BC propio) y los proveedores en la capa de aplicación del agregado buscado; cada proveedor decide su disponibilidad con el `Authorizer` de su agregado y ejecuta exactamente la consulta del listado (`XxxFilter` + `findByFilter`), sin ampliar filtros ni repositorios; transporte en **HTML renderizado en servidor**, no JSON; el modal se pinta **fuera** del `<header class="topbar">` y la barra baja su `z-index` a `$zindex-fixed` para quedar bajo los modales de Bootstrap.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Shared/Application/Search/SearchProvider.php` | Puerto con `#[AutoconfigureTag('shared.search.provider')]`: `category()`, `isAvailableTo(User)`, `search(SearchTerm, User, int $limit)` | nuevo |
| `src/Shared/Application/Search/SearchCategory.php` | VO `key`, `label` (literal ES del menú), `icon` (UX Icons), `position` | nuevo |
| `src/Shared/Application/Search/SearchResult.php` | DTO `title`, `?subtitle`, `route`, `routeParameters` | nuevo |
| `src/Shared/Application/Search/SearchTerm.php` | VO: `trim`, `MIN_LENGTH = 2`, `isSearchable()` con `mb_strlen` | nuevo |
| `src/Shared/Application/Search/SearchCategoryResults.php` | DTO categoría + `list<SearchResult>` | nuevo |
| `src/Shared/Application/Search/GlobalSearchRequest.php` | `term`, `limitPerCategory = 5` | nuevo |
| `src/Shared/Application/Search/GlobalSearchResponse.php` | `term`, `categories`, `searched` | nuevo |
| `src/Shared/Application/Search/GlobalSearchService.php` | Agrega proveedores; no es `CommandHandler` (no pasa por el bus) | nuevo |
| `src/Security/Application/User/Search/UserSearchProvider.php` | Categoría «Usuarios» (posición 150), modo administrador / propietario | nuevo |
| `src/Security/Application/Account/Search/AccountSearchProvider.php` | Categoría «Cuentas» (posición 140), solo administradores | nuevo |
| `src/Shared/Infrastructure/UI/Web/Search/SearchController.php` | `GET /{_locale}/search?term=…` (ruta `search`), devuelve el fragmento | nuevo |
| `src/Shared/Infrastructure/UI/Web/Component/Topbar/Search.php` | Componente `Topbar:Search` (botón) con `enabled()` | nuevo |
| `src/Shared/Infrastructure/UI/Web/Component/Search/Modal.php` | Componente `Search:Modal` con el mismo `enabled()` | nuevo |
| `templates/components/Topbar/Search.html.twig` | `<li>` con la lupa, `data-bs-toggle="modal" data-bs-target="#global-search"` | nuevo |
| `templates/components/Search/Modal.html.twig` | Modal `#global-search` con el controlador Stimulus, campo, botón «Borrar búsqueda» y cierre | nuevo |
| `templates/shared/search/results.html.twig` | Fragmento: estado inicial, «Sin resultados» o categorías con enlaces | nuevo |
| `templates/components/Topbar.html.twig` | `<twig:Topbar:Search>` como primer elemento de la lista derecha, antes del conmutador de tema (l.34) | modificado |
| `templates/shared/layout/app.html.twig` | `<twig:Search:Modal>` justo después de `<twig:Topbar>` (l.9) | modificado |
| `assets/controllers/global_search_controller.js` | Debounce, secuencia, navegación por teclado, atajo, borrado | nuevo |
| `assets/styles/components/_search.scss` | Estilos BEM `global-search__*` (solo `var(--bs-*)`) | nuevo |
| `assets/styles/app.scss` | `@import "./components/search";` tras `thumbnail` | modificado |
| `assets/styles/layouts/_header.scss` | `.topbar { z-index: $zindex-fixed; }` (antes `9999`) | modificado |
| `assets/icons/solar/buildings-3-line-duotone.svg`, `assets/icons/solar/backspace-line-duotone.svg` | Iconos locales (categoría «Cuentas» y botón de borrar) | nuevos |
| `translations/messages+intl-icu.{es,en}.xlf` | `Buscar en la plataforma…`, `Escribe al menos 2 caracteres`, `Sin resultados`, `Borrar búsqueda` (`Buscar`, `Cerrar`, `Cuentas`, `Usuarios` ya existían) | modificados |
| `tests/Unit/Shared/Application/Search/{SearchTermTest,FakeSearchProvider,GlobalSearchServiceTest}.php` | Unitarios del núcleo | nuevos |
| `tests/Unit/Security/Application/User/Search/UserSearchProviderTest.php`, `tests/Unit/Security/Application/Account/Search/AccountSearchProviderTest.php` | Unitarios de los proveedores | nuevos |
| `tests/Acceptance/Shared/global-search.feature` | 7 escenarios Behat | nuevo |
| `tests/Acceptance/Shared/WebContext.php` | Pasos `I go to :path as an ajax request`, `I see a text :first before a text :second`, `I see :count times the text :text` | modificado |

Sin cambios: `UserFilter`, `AccountFilter`, repositorios, mappings, `services.yaml`, `NodeLister`, `importmap.php`, `assets/controllers.json`.

## Modelo

No hay agregados, eventos ni persistencia nuevos. El "modelo" es el contrato de aplicación:

- `SearchProvider` (`src/Shared/Application/Search/SearchProvider.php:10-19`). Al llevar `#[AutoconfigureTag]` en la interfaz y tener todos los `services.yaml` `autoconfigure: true`, cualquier clase que la implemente queda registrada sin declararla.
- `SearchTerm` (`SearchTerm.php:13-21`): recorta espacios en el constructor; es buscable con `mb_strlen >= 2`.
- `SearchCategory::$key` se emite como `data-search-category` en el fragmento (lo usan los tests Behat); `$label` es el literal en español del menú y se traduce en la plantilla con `| trans`, por lo que no hay claves nuevas por categoría.
- `SearchResult` devuelve **nombre de ruta + parámetros**, no URL: la capa de aplicación no depende del router; la plantilla genera la URL con `path()`.

## Flujo de una operación

```mermaid
sequenceDiagram
    participant B as Navegador (global_search_controller)
    participant C as SearchController
    participant S as GlobalSearchService
    participant P as SearchProvider[*]
    B->>C: GET /{_locale}/search?term=… (X-Requested-With, tras 300 ms)
    C->>S: __invoke(GlobalSearchRequest(term), LoggedUser())
    S->>P: isAvailableTo(user) (alguno)
    alt ninguno disponible
        S-->>C: UnauthorizedActionException
        C-->>B: 403
    else término < 2 caracteres
        S-->>C: GlobalSearchResponse(searched: false)
    else
        S->>P: search(term, user, 5) por cada disponible, ordenados por position
        S-->>C: categorías con resultados
    end
    C-->>B: results.html.twig (Cache-Control: no-store)
    B->>B: innerHTML si la secuencia sigue vigente; activa el primer resultado
```

1. **Pintado inicial.** `Topbar:Search` y `Search:Modal` (`src/Shared/Infrastructure/UI/Web/Component/Topbar/Search.php:20-29`, `.../Component/Search/Modal.php:20-29`) solo se pintan si hay usuario (`LoggedUser::getLoggedUserOrNull()`) y `GlobalSearchService::isAvailableTo($user)`. El botón abre el modal con la API de datos de Bootstrap; el modal incluye `results.html.twig` con `response: null` (estado «Escribe al menos 2 caracteres»).
2. **Stimulus** (`assets/controllers/global_search_controller.js`):
   - `connect()` (l.12-17) guarda el HTML inicial y crea la instancia `Modal`; `disconnect()` la destruye.
   - `shortcut()` (l.25-31), enganchado a `keydown@window`: `Ctrl`/`Meta` + `k` → `preventDefault()` y `modal.show()`.
   - `focus()` en `shown.bs.modal`; `reset()` (l.37-44) en `hidden.bs.modal`: cancela el temporizador, vacía el campo, incrementa la secuencia (descarta respuestas en vuelo) y restaura el HTML inicial.
   - `search()` (l.51-56): debounce de 300 ms y muestra/oculta el botón «Borrar búsqueda» (`#toggleClear`, l.115-117). `clear()` (l.46-49) = `reset()` + foco.
   - `#fetch()` (l.84-113): `++sequence`; errores de red o `!response.ok` (incluido el 403) se ignoran en silencio; si la secuencia ya no es la última, se descarta; si no, `innerHTML` del fragmento y se activa el primer resultado.
   - `navigate()` (l.58-82): `↑`/`↓` mueven la clase `active` de forma circular con `scrollIntoView`; `Enter` con un activo → `window.location.assign(href)`.
3. **Controlador** (`src/Shared/Infrastructure/UI/Web/Search/SearchController.php`): `#[Route('/search', name: 'search', methods: ['GET'])]` + `#[IsGranted('ROLE_USER')]` (l.17-18), queda bajo `/{_locale}` por el recurso `shared-web` de `config/routes.yaml`. `term` llega con `#[MapQueryParameter]` (por defecto `''`). Traduce `UnauthorizedActionException` a 403 propagando el mensaje (l.34-35, corrección B1 de `review-fix.md`) y fija `Cache-Control: no-store` (l.41).
4. **Servicio** (`src/Shared/Application/Search/GlobalSearchService.php`):
   - l.33-35: si ningún proveedor está disponible lanza `UnauthorizedActionException($this, Action::List)`.
   - l.37-41: término no buscable → respuesta `searched: false` sin consultar proveedores.
   - l.43-44: `iterator_to_array` + `usort` por `category()->position` (el orden del iterable del contenedor no es estable).
   - l.48-60: solo proveedores disponibles; los que devuelven `[]` se omiten.
5. **Fragmento** (`templates/shared/search/results.html.twig`): `response` nulo o `searched` falso → «Escribe al menos 2 caracteres»; sin categorías → «Sin resultados»; si no, por categoría un bloque `.global-search__category[data-search-category]` con cabecera (icono + etiqueta traducida) y enlaces `a.list-group-item[data-global-search-target="result"]` con título y subtítulo opcional (truncados con `text-truncate`). Todo el contenido variable se escapa con el autoescape de Twig.

### Proveedores de `Security`

- **`UserSearchProvider`** (`src/Security/Application/User/Search/UserSearchProvider.php`):
  - `isAdministrator()` (l.76-81): `canDoThat(new User(id: UserId::generate()), Action::List, $user)`; un sujeto sin cuenta solo lo pueden listar `Admin`/`SuperAdmin` (`UserAuthorizer::canList`, `src/Security/Application/User/UserAuthorizer.php:24-35`).
  - `ownAccountId()` (l.83-97): cuenta actual de `CurrentAccountIdProvider` (con fallback a `$user->accountId()` si el provider es `null`, l.85) y `canDoThat(new User(..., accountId: $currentAccountId), Action::List)` — cierto para un `Owner` de esa cuenta.
  - `search()` (l.41-60): administrador → `UserFilter(username:, limit:)` y ruta `admin_users_edit`; propietario → carga la `Account` (si no existe, `[]`), `UserFilter(username:, account:, limit:)` y ruta `users_edit`. Título `username()`, subtítulo `account()?->name()`, parámetro `userId`.
- **`AccountSearchProvider`** (`src/Security/Application/Account/Search/AccountSearchProvider.php`): disponible con `canDoThat(new Account(AccountId::generate()), Action::List)` (l.31-34; solo `Admin`/`SuperAdmin`, `AccountAuthorizer::canList`); `AccountFilter(name:, limit:)`, ruta `admin_accounts_edit`, parámetro `accountId`, sin subtítulo (l.36-51).

Las consultas son las de los listados: `LIKE '%término%'` sobre `u.username` / `a.name`, excluyendo eliminados, orden por defecto ascendente por `username` / `name` (`DoctrineUserRepository.php:130-178`, `DoctrineAccountRepository.php:64-101`, ambos en `src/Security/Infrastructure/Persistence/Doctrine/Repository/`). Las cuentas inactivas se incluyen (`AccountFilter::$active` queda `null`).

## Autorización y ámbito

| Rol efectivo | Botón y modal | Categorías | Destino de los resultados |
|---|---|---|---|
| `SuperAdmin` / `Admin` | Sí | Cuentas (todas) + Usuarios (todas las cuentas) | `admin_accounts_edit`, `admin_users_edit` |
| `Owner` | Sí | Usuarios de su cuenta actual | `users_edit` |
| `User` | No; `GET /{_locale}/search` → 403 | — | — |
| Administrador suplantando una cuenta | Sí | Como el propietario de la cuenta suplantada (roles efectivos `Owner` + `User` vía `CurrentRolesProvider`; cuenta vía `LoggedAccount`) | `users_edit` |

- Doble barrera: `#[IsGranted('ROLE_USER')]` en el controlador y autorización por proveedor con el `Authorizer` de su agregado **antes** de consultar. Un proveedor nunca lanza `UnauthorizedActionException`; la lanza el servicio solo si ninguno está disponible.
- `isAvailableTo()` se evalúa en cada render de página dos veces (botón y modal) y de nuevo en cada búsqueda; con los proveedores actuales son comprobaciones en memoria, salvo la carga de la `Account` en `UserSearchProvider::search()`.
- No hay feature flag: el buscador está siempre activo para quien tenga alguna categoría disponible.

## Persistencia y migración

Sin cambios de esquema ni de datos. Cada búsqueda efectiva ejecuta como mucho una consulta `LIKE … LIMIT 5` por proveedor disponible (más un `find` de la cuenta en el modo propietario).

## Configuración y operación

- Sin variables de entorno, colas, comandos ni tareas programadas.
- Sin cambios en `services.yaml`: la etiqueta la pone el atributo de la interfaz.
- Assets: compilar el SCSS con `make develop` tras tocar `_search.scss`. Los iconos de categoría deben existir en `assets/icons/` (`make icon-import p="solar:<nombre>"`) para no depender de la descarga de UX Icons en producción.
- **`z-index` de la barra** (`assets/styles/layouts/_header.scss`): pasa de `9999` a `$zindex-fixed` (1030), por debajo del modal (1055) y su backdrop (1050) de Bootstrap. Afecta a toda la plataforma; `.card-fullscreen` (`z-index: 9999`) queda sobre la barra. Los proyectos derivados que ya tengan la barra en `$zindex-fixed` no deben volver a tocarla.

## Cómo extenderlo

Para añadir una categoría (p. ej. «Clientes» en un proyecto derivado):

1. Crear `src/<BC>/Application/<Aggregate>/Search/<Aggregate>SearchProvider.php` implementando `App\Shared\Application\Search\SearchProvider`. No hace falta tocar `services.yaml`.
2. `category()`: `new SearchCategory('<key-kebab>', '<literal del menú en ES>', '<icono del menú>', <posición>)`. Usar exactamente la etiqueta e icono de la entrada de `NodeLister` para no crear claves de traducción. Hydra reserva 140 (cuentas) y 150 (usuarios); las entidades de negocio deben usar posiciones menores para aparecer antes. Con posiciones repetidas el orden queda al arbitrio del contenedor.
3. `isAvailableTo()`: `canDoThat(<sujeto vacío>, Action::List, $user)` construyendo el sujeto **igual que el `List<Aggregate>Service`** del agregado; un sujeto distinto haría que la regla de cuenta se evaluara de otra forma que en el listado.
4. `search()`: devolver `[]` si no está disponible; si lo está, usar el `XxxFilter` con su campo de texto y `limit: $limit`, filtrar por la cuenta actual (`CurrentAccountIdProvider`, nunca `$user->accountId()` directamente) cuando el agregado sea por cuenta, y mapear a `SearchResult` con nombre de ruta y parámetros de la pantalla de destino (`*_edit` o `*_show`). Si administradores y propietarios tienen pantallas distintas, decidir el modo como `UserSearchProvider`.
5. Test unitario con el `InMemoryXxxRepository`, el `XxxMother` y el `XxxAuthorizerMother` (plantillas: `UserSearchProviderTest`, `AccountSearchProviderTest`) y, si procede, un escenario en `global-search.feature`.
6. Importar el icono a `assets/icons/` si no está.

Trampas conocidas:

- El término se pasa tal cual al `LIKE`: `%` y `_` actúan como comodines (heredado de los filtros de listado).
- El fragmento se inserta con `innerHTML`, una excepción consciente a la convención de `ARCHITECTURE.md` (clonar `<template>` + `textContent`) igual que el widget de notificaciones: solo es aceptable porque el HTML lo renderiza el servidor con autoescape. No insertar nunca HTML construido en JS con datos del usuario.
- El modal debe seguir fuera de `<header class="topbar">` (contexto de apilamiento propio).
- El `title` del botón anuncia solo «Ctrl+K», aunque el atajo funciona también con `⌘K` (B2, asumido).

## Tests

- **Unitarios** (`tests/Unit/Shared/Application/Search/`): `SearchTermTest` (recorte, 1 carácter no buscable, 2 sí, espacios no cuentan); `GlobalSearchServiceTest` con `FakeSearchProvider::withResults(...)` (orden por `position`, omite categorías vacías y proveedores no disponibles, término corto no llama a nadie, pasa `limitPerCategory`, 403 sin proveedores disponibles, `isAvailableTo`).
- `tests/Unit/Security/Application/User/Search/UserSearchProviderTest.php`: administrador por subcadena en cualquier cuenta con `admin_users_edit` y subtítulo de cuenta; propietario solo su cuenta con `users_edit`; `User` no disponible; límite; eliminados excluidos.
- `tests/Unit/Security/Application/Account/Search/AccountSearchProviderTest.php`: subcadena con `admin_accounts_edit`; inactivas incluidas; eliminadas excluidas; límite; propietario no disponible.
- **Aceptación** (`tests/Acceptance/Shared/global-search.feature`): botón y modal para administrador; agrupación y orden Cuentas → Usuarios; propietario limitado a su cuenta y sin «Cuentas»; `User` sin botón y con 403; término corto y sin resultados; máximo 5 por categoría; suplantación de cuenta. El JavaScript (debounce, teclado, atajo) no tiene cobertura automática.

Ejecución:

```bash
make test-unit options="--filter Search"
make test-acceptance options="tests/Acceptance/Shared/global-search.feature"
```

## Desviaciones y deuda

Ver `## Implementación` de `plan.md` y `review-fix.md`:

- Icono importado con `make icon-import p="solar:buildings-3-line-duotone"` (no con `p=… s=…`).
- `UserSearchProvider` acepta `?CurrentAccountIdProvider = null` con fallback a `$user->accountId()` (contextos sin request).
- Estilos del modal rehechos con clases BEM `global-search__*`; el botón nativo de borrado del `input type="search"` se oculta y se sustituye por «Borrar búsqueda» (`solar:backspace-line-duotone`).
- B1 (mensaje del 403) corregido; B2 (pista solo «Ctrl+K») asumido.
- Pendiente para el usuario: comprobación manual del apilamiento tras el cambio de `z-index` (modales sobre la barra, desplegables, toasts, menú móvil, tema claro y oscuro).
- Deuda preexistente detectada, fuera de alcance: `AccountAuthorizer::canAutocomplete` usa `$user->hasRole()`; orden de parámetros de `SecurityContext::userWithRoleWithIdOfAccountExists`; `style=` inline en `templates/components/Topbar/Notifications.html.twig`.
