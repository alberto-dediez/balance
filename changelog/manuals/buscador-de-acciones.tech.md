# Buscador de acciones (entradas del menú en el buscador global) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-08 · **Bounded Contexts:** `Shared`
- **Revisión:** `review.md` (revisión 1) · **Triaje:** `review-fix.md`
- **Migración:** no

## Qué hace y por qué

El buscador global (`Ctrl+K`, plan `2026-10-07-buscador-global`) encontraba solo **registros** (cuentas y usuarios). Esta funcionalidad añade una categoría nueva, `navigation` («Acciones»), siempre la primera, que busca entre las **entradas del menú lateral que ve el usuario conectado**, en el idioma de la petición, y enlaza directamente a cada pantalla.

La fuente de verdad es `NodeLister` (`src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php`), la misma clase que pinta la barra lateral: decide el menú por rol efectivo (respeta la suplantación de cuentas vía `CurrentRolesProvider`), por *feature flags* y genera las URL con el router. Cualquier entrada que se añada, renombre o quite en `NodeLister` aparece o desaparece en el buscador sin tocar el proveedor.

Como `NodeLister` depende de `RouterInterface`, `RequestStack` y `AuthorizationCheckerInterface`, y el proveedor necesita `TranslatorInterface`, el proveedor es un **adaptador de infraestructura** que implementa el puerto de aplicación `SearchProvider` (decisión «Dónde vive el proveedor» de `plan.md`). Consecuencia visible: todo usuario autenticado tiene menú, así que **el buscador pasa a estar disponible para el rol `User`**, que antes no veía la lupa y recibía `403` en `/search`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/Shared/Application/Search/SearchResult.php` | DTO de resultado: `route` pasa a `?string = null`, `routeParameters` por defecto `[]`, nuevos `?string $url` e `?string $icon` | Modificado |
| Infraestructura | `src/Shared/Infrastructure/UI/Web/Navigation/NavigationSearchProvider.php` | Proveedor `navigation`: recorre `NodeLister::nodes()`, normaliza y filtra | Nuevo |
| Infraestructura | `src/Shared/Infrastructure/UI/Web/Navigation/NavigationEntry.php` | Value object interno (`title`, `subtitle`, `url`, `icon`, `haystack`) | Nuevo |
| Plantilla | `templates/shared/search/results.html.twig` | `href` = `result.url ?? path(result.route, result.routeParameters)` (l.13); icono opcional delante del título (l.15) | Modificado |
| Dependencias | `composer.json` | Declara `ext-intl` (uso de `\Normalizer`) | Modificado |
| Tests | `tests/Unit/Shared/Infrastructure/UI/Web/Navigation/NavigationSearchProviderTest.php` | 11 casos unitarios del proveedor | Nuevo |
| Tests | `tests/Acceptance/Shared/global-search.feature` | Escenario de `ROLE_USER` reescrito y 6 escenarios nuevos de menú | Modificado |
| Tests | `tests/Unit/Shared/Infrastructure/UI/Web/Navigation/NodeListerTest.php:5`, `tests/Unit/Shared/Infrastructure/UI/Web/Component/Sidebar/LeftTest.php:5` | Namespace corregido a `App\Tests\Unit\...` (hallazgo B2 de la review) | Modificado |

Sin cambios (consumidos tal cual): `GlobalSearchService`, `SearchCategory`, `SearchTerm`, `SearchProvider`, `SearchController`, componentes `Topbar:Search` y `Search:Modal`, `assets/controllers/global_search_controller.js`, `NodeLister`, `Node`, `services.yaml` y traducciones.

## Modelo

No hay agregados, eventos ni persistencia. Las piezas son DTO/value objects:

- **`SearchResult`** (`src/Shared/Application/Search/SearchResult.php:9-17`): `title`, `subtitle`, `route = null`, `routeParameters = []`, `url = null`, `icon = null`. Regla de uso (no validada en el constructor): un resultado lleva `url` **o** `route`; la plantilla prioriza `url`. Los constructores existentes (`AccountSearchProvider`, `UserSearchProvider`, `FakeSearchProvider`) siguen compilando sin cambios.
- **`NavigationEntry`** (`NavigationEntry.php:7-16`): `final readonly`; `haystack` es la ruta normalizada `"{grupo} {elemento}"`.
- **Categoría**: `new SearchCategory('navigation', 'Acciones', 'solar:hamburger-menu-line-duotone', 0)` (`NavigationSearchProvider.php:24-27`), con `POSITION = 0` como constante (l.17). «Acciones» ya existía en `translations/messages+intl-icu.{es,en}.xlf` («Actions» en inglés).

## Flujo de una operación

`GET /{_locale}/search?term=...` (AJAX desde el modal):

1. `SearchController` (`src/Shared/Infrastructure/UI/Web/Search/SearchController.php:17-44`, `#[IsGranted('ROLE_USER')]`) invoca `GlobalSearchService` con el usuario conectado.
2. `GlobalSearchService::__invoke` (`src/Shared/Application/Search/GlobalSearchService.php:31-63`): comprueba `isAvailableTo` (l.33; `403` si ningún proveedor lo está), descarta términos de menos de 2 caracteres (`SearchTerm::MIN_LENGTH`), ordena proveedores por `category()->position` (l.44) — `navigation` (0) queda antes que cuentas (140) y usuarios (150) — y omite categorías vacías.
3. `NavigationSearchProvider::search()` (`NavigationSearchProvider.php:34-60`):
   - Normaliza el término y lo parte en palabras por espacios (l.36).
   - `entries()` (l.65-84) recorre `NodeLister::nodes()`: un nodo raíz con `target` y sin `items` genera entrada sin subtítulo (l.70-72); cada `item` con `target` genera entrada con título = etiqueta del elemento y subtítulo = etiqueta del grupo (l.74-80). Los grupos sin `target` no son resultados.
   - Las etiquetas (`TranslatableMessage`) se traducen con `TranslatorInterface` sin forzar locale (l.97-100): el idioma es el de la petición.
   - Una entrada coincide si **cada palabra** del término es subcadena del `haystack` (l.44-49).
   - Devuelve `SearchResult(title, subtitle, url, icon)` en el orden del menú. **Ignora `$limit`**: la categoría muestra todas las coincidencias, mientras las demás siguen limitadas a `limitPerCategory = 5` (`GlobalSearchRequest.php:11`).
4. `normalize()` (l.102-108): `\Normalizer::FORM_D`, elimina marcas `\p{Mn}` (acentos), `mb_strtolower`, colapsa espacios y `trim`. Con fallback al valor original si `Normalizer`/`preg_replace` fallan.
5. `templates/shared/search/results.html.twig` pinta cada resultado como `<a href>` con `data-global-search-target="result"`, de modo que la navegación con `↑`/`↓` + `Enter` de `global_search_controller.js` funciona sin cambios.

## Autorización y ámbito

- **Sin `Authorizer` propio**: el ámbito lo decide `NodeLister::nodes()` (`NodeLister.php:30-41`) con el rol del usuario **conectado** (`CurrentRolesProvider::currentLoggedUserRoles()`, con fallback a `AuthorizationChecker`). El parámetro `User $user` del puerto se ignora (cubierto por `testIgnoresTheUserParameter`). En la web coinciden siempre (`SearchController` pasa `$loggedUser()`).
- **Menús por rol** (`NodeLister.php`):
  - Administradores (`toAdmins`, l.152-232): Seguridad (Cuentas, Usuarios, Firma de documentos, Registro), Biblioteca (Archivos, Subir archivo), Notificaciones (Mis notificaciones, Enviar notificación, Historial de envíos), Novedades (Novedades, Gestionar novedades, Changelog).
  - Propietarios (`toOwners`, l.237-274): Seguridad (Usuarios → `/users`), Biblioteca, Notificaciones (Mis notificaciones), Novedades.
  - Usuarios (`toUsers`, l.279-304): Biblioteca, Notificaciones (Mis notificaciones), Novedades.
- **Suplantación**: al suplantar una cuenta, `CurrentRoles` devuelve `[Owner, User]`, así que el administrador obtiene el menú del propietario (escenario *An admin impersonating an account gets the owner menu*).
- **Feature flags**: `app.internal_notifications_enabled` oculta el grupo Notificaciones en los tres menús; `app.announcements_enabled` oculta Novedades solo a propietarios y usuarios (el menú de administradores lo muestra siempre).
- **Disponibilidad**: `isAvailableTo()` = `[] !== $this->entries()` (l.29-32). Como todo rol tiene al menos Biblioteca → Archivos, `GlobalSearchService::isAvailableTo` es cierto para cualquier usuario autenticado en Hydra: `Topbar:Search` y `Search:Modal` (`Modal.php:20-29`) se pintan también para `User`, y `/search` ya no devuelve `403`.

## Persistencia y migración

Ninguna. Sin cambios de esquema ni consultas a base de datos: el menú se construye en memoria.

## Configuración y operación

- **Registro del servicio**: sin declaración explícita. `SearchProvider` lleva `#[AutoconfigureTag('shared.search.provider')]` (`src/Shared/Application/Search/SearchProvider.php:10`) y `App\Shared\` se carga con `autoconfigure: true` (`src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml`). `NodeLister` ya era servicio (`src/Notifications/Infrastructure/Symfony/DependencyInjection/services.yaml:44-46`).
- **Extensión PHP**: `ext-intl` declarada en `composer.json` (necesaria para `\Normalizer`).
- Sin variables de entorno, colas, comandos ni tareas programadas nuevas.
- **Coste**: cada búsqueda construye el menú tres veces (`isAvailableTo` en l.33 y l.49 de `GlobalSearchService` y `search`), y cada página con barra superior una más (`Modal::enabled()`). Es en memoria y sin consultas; aceptado como asumible (B1 en `review-fix.md`). No memoizar `entries()` en el proveedor sin invalidación por petición: el servicio es compartido y en modo worker (FrankenPHP) filtraría el menú de un rol, idioma o suplantación a la siguiente petición.

## Cómo extenderlo

- **Añadir una pantalla al buscador**: añadir el `Node` en `NodeLister` (con `target` generado por el router y etiqueta `TranslatableMessage` con su traducción). No hay que tocar el proveedor.
- **Nodo raíz sin grupo**: un `Node` raíz con `target` y sin `items` aparece como resultado sin subtítulo (rama `NavigationSearchProvider.php:70-72`). **Atención:** esta rama no tiene test (B3, descartado en `review-fix.md`) porque el `NodeLister` de Hydra no tiene nodos así; un proyecto derivado que la use debería añadir el caso (por ejemplo, con una subclase anónima de `NodeLister` que sobrescriba `nodes()`).
- **Nuevos proveedores de entidades**: siguen viviendo en `Application/<Aggregate>/Search/` con posición **positiva**; la posición `0` queda reservada a `navigation`. No copiar este patrón de infraestructura para entidades de dominio (`.claude/ARCHITECTURE.md`, fila «Buscador global»).
- **Resultados con URL ya generada**: cualquier proveedor puede devolver `SearchResult(url: ...)` en lugar de `route`, e `icon` para pintar un icono delante del título. Nunca devolver un resultado sin `url` ni `route`: la plantilla llamaría a `path(null)` (PHPStan no analiza Twig).
- **Trampa**: `NodeLister::nodes()` llama a `isGranted`, que lanza excepción sin token de seguridad; el proveedor no es apto para CLI o contextos sin petición autenticada.

## Tests

- **Unitarios** (`tests/Unit/Shared/Infrastructure/UI/Web/Navigation/NavigationSearchProviderTest.php`): construye un `NodeLister` real con `FakeSecurity::fromRole()`, router simulado (`'/es/' . str_replace('_', '/', $name)` salvo mapa explícito), `RequestStack` con `/es/search` y `FakeTranslator`. Casos: grupo completo para administrador (orden, subtítulo, URL, icono, `route` nulo), mayúsculas y acentos, todas las palabras deben coincidir, menú de propietario, rol `User` disponible y con Biblioteca, grupos sin `target` no son resultado, flag de notificaciones desactivado, `$limit` ignorado (5 resultados con «ar»), traducción al locale con un `Translator` real en inglés, categoría `navigation` en posición 0 y parámetro `$user` ignorado.
- **Aceptación** (`tests/Acceptance/Shared/global-search.feature`): *A user finds its menu entries*, *An admin finds every screen of a menu group*, *The actions category comes first*, *An owner only finds the screens of its menu*, *The menu is searched in the current language*, *The match ignores accents and case*, *An admin impersonating an account gets the owner menu*; los escenarios previos de cuentas y usuarios siguen válidos.
- **Ejecutar**:
  - `make test-unit options="--filter NavigationSearchProviderTest"`
  - `make test-acceptance options="tests/Acceptance/Shared/global-search.feature"`
- **Hueco conocido**: rama de nodo raíz con `target` sin cobertura (B3).

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`), `review.md` y `review-fix.md`:

- `testIgnoresTheLimit` afirma cinco coincidencias para «ar» (no cuatro como decía el plan).
- `ext-intl` añadido a `composer.json`/`composer.lock`.
- B1 (menú construido varias veces por búsqueda): asumido; la restricción «una construcción» de `plan.md` era inalcanzable con el diseño prescrito.
- B2 (namespace `App\Unit\...` en `NodeListerTest` y `LeftTest`): corregido.
- B3 (rama de nodo raíz con `target` sin test): aceptado sin cobertura.
- Q1 (comprobación manual en navegador): realizada por el usuario.
