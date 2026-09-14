# Sección de novedades (changelog) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-14 · **Bounded Contexts:** `Changelog` (nuevo), `Shared` (navegación), `Security` (autorización)

## Qué hace y por qué

La plataforma no tenía forma de comunicar a los administradores qué había cambiado en cada
despliegue: esa información vivía solo en `.claude/plans/done/` y en los manuales que genera
`feature-manual`, ambos fuera del control de versiones del código (`.gitignore` ignora `.claude`).
Este plan añade una sección **Novedades**, visible solo para `Admin` y `SuperAdmin`, que lista y
muestra entradas de changelog leídas directamente de ficheros Markdown con frontmatter versionados
en `changelog/entries/`. Cada entrada puede enlazar al manual de usuario (PDF) y al manual técnico
(Markdown) de la funcionalidad que documenta, copiados a `changelog/manuals/`.

La decisión de diseño central es **no persistir nada en base de datos**: el puerto
`ChangelogEntryRepository` (`src/Changelog/Domain/Model/ChangelogEntry/ChangelogEntryRepository.php`)
tiene como adaptador de producción un lector de sistema de ficheros
(`FilesystemChangelogEntryRepository`) que parsea el directorio en cada petición, sin caché ni
comando de sincronización. Así la fuente de verdad es el propio repositorio git y se despliega con
el código, sin riesgo de desincronización entre una tabla y los ficheros.

Como consecuencia, se crea también la skill `feature-changelog`
(`/opt/htdocs/ai/hydra/skills/feature-changelog/SKILL.md`), que copia los manuales de un plan ya
documentado a `changelog/manuals/` y escribe la entrada correspondiente, y `feature-implementer` la
encadena tras `feature-manual` para que implementar un plan lo deje también publicado en la
plataforma.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Changelog/Domain/Model/ChangelogEntry/ChangelogEntry.php` | Modelo de lectura (sin `AggregateRoot`, sin eventos) | Nuevo |
| `src/Changelog/Domain/Model/ChangelogEntry/ChangelogEntrySlug.php` | VO del identificador/URL, valida `^[a-z0-9]+(?:-[a-z0-9]+)*$` | Nuevo |
| `src/Changelog/Domain/Model/ChangelogEntry/ChangelogCategory.php` | Enum cerrado `feature`/`improvement`/`fix`/`security` | Nuevo |
| `src/Changelog/Domain/Model/ChangelogEntry/ChangelogEntryFilter.php` | Filtro de listado (`use Filter;`), `limit=10`, orden por `date DESC` | Nuevo |
| `src/Changelog/Domain/Model/ChangelogEntry/ChangelogEntryRepository.php` | Puerto de lectura | Nuevo |
| `src/Changelog/Domain/Model/ChangelogEntry/ManualStorage.php` | Puerto de almacenamiento de manuales | Nuevo |
| `src/Changelog/Domain/Model/ChangelogEntry/{ChangelogEntryNotFoundException,InvalidChangelogEntryException,ManualNotFoundException}.php` | Excepciones de dominio | Nuevo |
| `src/Changelog/Application/ChangelogEntry/ChangelogEntryAuthorizer.php` | Autorización; todas las acciones delegan en `canAdmin()` | Nuevo |
| `src/Changelog/Application/ChangelogEntry/List/*` | Caso de uso de listado (`ListChangelogEntriesRequest/Response/Service`) | Nuevo |
| `src/Changelog/Application/ChangelogEntry/View/*` | Caso de uso de detalle | Nuevo |
| `src/Changelog/Application/ChangelogEntry/DownloadManual/*` | Caso de uso de descarga/visualización del PDF | Nuevo |
| `src/Changelog/Application/ChangelogEntry/ViewTechnicalManual/*` | Caso de uso del manual técnico renderizado | Nuevo |
| `src/Changelog/Infrastructure/Markdown/ChangelogEntryParser.php` | Parseo de frontmatter + Markdown de cada fichero | Nuevo |
| `src/Changelog/Infrastructure/Persistence/Filesystem/FilesystemChangelogEntryRepository.php` | Adaptador de producción (lee `changelog/entries/*.md`) | Nuevo |
| `src/Changelog/Infrastructure/Persistence/InMemory/InMemoryChangelogEntryRepository.php` | Adaptador de tests | Nuevo |
| `src/Changelog/Infrastructure/Storage/{LocalManualStorage,InMemoryManualStorage}.php` | Adaptadores del puerto `ManualStorage` | Nuevo |
| `src/Changelog/Infrastructure/UI/Web/Twig/MarkdownExtension.php` | Filtro Twig `changelog_markdown` | Nuevo |
| `src/Changelog/Infrastructure/Symfony/Form/ListChangelogEntriesType.php` | Formulario de filtro (texto + categoría) | Nuevo |
| `src/Changelog/Infrastructure/UI/Web/Admin/ChangelogEntry/{ListController,ShowController,ManualController,TechnicalManualController,StreamManualTrait}.php` | Controladores y streaming del PDF | Nuevo |
| `src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros de directorio, alias puerto→adaptador, `when@test` | Nuevo |
| `templates/changelog/admin/{list,show,tech_manual,_category_badge}.html.twig` | Plantillas | Nuevo |
| `config/services.yaml` | Import del `services.yaml` del contexto | Modificado |
| `config/routes.yaml` | Bloque `changelog-web` | Modificado |
| `config/packages/twig.yaml` | Namespace `changelog` | Modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:191-200` | Nodo **Novedades → Changelog** en `toAdmins()` | Modificado |
| `translations/messages+intl-icu.{es,en}.xlf`, `translations/changelogCategory+intl-icu.{es,en}.xlf` | Traducciones de la sección y del enum de categoría | Nuevo/Modificado |
| `behat.yml.dist` | Registro de `ChangelogContext` | Modificado |
| `changelog/README.md`, `changelog/entries/.gitkeep`, `changelog/manuals/.gitkeep`, `changelog/entries/2026-09-14-changelog-section.md` | Contenido versionado, incluida la entrada inicial que documenta este mismo plan | Nuevo |
| `tests/Unit/Changelog/**`, `tests/Acceptance/Changelog/**` | Suite de tests descrita en *Tests* | Nuevo |
| `.claude/skills/feature-changelog/SKILL.md` (enlace a `/opt/htdocs/ai/hydra/skills/`) | Skill nueva de publicación | Nuevo |
| `.claude/skills/feature-implementer/SKILL.md` | Encadena `feature-changelog` tras `feature-manual` | Modificado |

## Modelo de dominio

`ChangelogEntry` (`src/Changelog/Domain/Model/ChangelogEntry/ChangelogEntry.php:9-21`) es una clase
`final` con propiedades `public readonly`, sin el trait `AggregateRoot` y sin eventos de dominio —
mismo criterio que `RecordedEvent` en `Security`, porque no hay escritura desde la aplicación: es un
modelo de lectura sobre ficheros. Los campos son `slug` (`ChangelogEntrySlug`), `title`, `date`
(`\DateTimeImmutable`), `category` (`ChangelogCategory`), `summary`, `body`, y opcionalmente
`version`, `planSlug`, `manualFileName`, `technicalManualFileName` y `published` (por defecto
`true`).

`placeholder()` (líneas 23-33) construye una entrada mínima (slug `placeholder`, categoría
`Improvement`, fecha actual, textos vacíos) que los servicios usan como sujeto de autorización antes
de consultar el repositorio — igual que `ListMediaService` hace con un `Media` vacío.

`matches(ChangelogEntryFilter $filter)` (líneas 45-64) concentra el criterio de filtrado que
comparten los dos adaptadores del repositorio: descarta las no publicadas cuando
`$filter->onlyPublished` es `true`, filtra por categoría exacta y busca el texto (sin distinguir
mayúsculas, vía `mb_strtolower`) en `title` o `summary`.

`ChangelogEntrySlug` (`ChangelogEntrySlug.php:9,13-15`) valida el patrón
`^[a-z0-9]+(?:-[a-z0-9]+)*$` en el constructor y lanza `InvalidChangelogEntryException` si no casa;
es la identidad de la entrada y la parte variable de la URL.

`ChangelogCategory` (`ChangelogCategory.php:7-16`) es un `enum ... : string` cerrado con
`Feature = 'feature'`, `Improvement = 'improvement'`, `Fix = 'fix'`, `Security = 'security'`; no
lleva método de color — ese mapeo vive en la plantilla `_category_badge.html.twig`.

`ChangelogEntryFilter` (`ChangelogEntryFilter.php:9-22`) usa el trait `Filter` de `Shared` y fija en
el constructor `limit = 10`, `orderBy = 'date'`, `orderWay = 'DESC'`.

**Invariantes y dónde se validan:**

- Slug válido → constructor de `ChangelogEntrySlug` (`ChangelogEntrySlug.php:13-15`).
- `title`/`summary` no vacíos, `date` parseable, `category` una de las cuatro permitidas → todas en
  `ChangelogEntryParser` (ver más abajo), que lanza `InvalidChangelogEntryException` con el motivo
  concreto.
- `manual`/`techManual` sin `/`, `\` ni `..`, y con formato de nombre de fichero
  (`ChangelogEntryParser::MANUAL_NAME_PATTERN`, `ChangelogEntryParser.php:18,117-130`) → segunda
  barrera independiente en `LocalManualStorage`, que también rechaza esos caracteres.
- `published` ausente equivale a `true` (`ChangelogEntryParser.php:52`).
- Deduplicación por slug entre ficheros: gana la fecha mayor, se registra un `warning`
  (`FilesystemChangelogEntryRepository.php:70-74`).

**Excepciones**: `ChangelogEntryNotFoundException`, `InvalidChangelogEntryException` (mensaje con el
nombre de fichero y el motivo), `ManualNotFoundException` — las tres extienden `\Exception`.

## Flujo de una operación

### Listar (`GET /{_locale}/admin/changelog`)

`ListController::__invoke` (`ListController.php:25-40`) usa `FilterTrait::filter()` para construir
`ListChangelogEntriesRequest` desde `ListChangelogEntriesType` y la sesión, y llama a
`ListChangelogEntriesService`. El servicio:

1. `denyUnlessCanDoThat(ChangelogEntry::placeholder(), Action::List, $user)`.
2. Fuerza `$request->onlyPublished = true` — el filtro de la interfaz nunca puede pedir borradores.
3. Devuelve `ListChangelogEntriesResponse($this->repository->findByFilter($request), $this->repository->totalResultsByFilter($request))`.

`FilesystemChangelogEntryRepository::all()` (`FilesystemChangelogEntryRepository.php:52-92`) hace
`glob($entriesPath . '/*.md')`, parsea cada fichero con `ChangelogEntryParser`, descarta (con
`$logger?->warning(...)`) los inválidos sin romper el resto, deduplica por slug, ordena por
`date DESC` y slug ascendente como desempate, y `findByFilter`/`totalResultsByFilter` aplican
`matches()` y la paginación en memoria (`array_slice` con `page * limit`).
`UnauthorizedActionException` se captura en el controlador → `createAccessDeniedException` (403).

### Ver detalle (`GET /{_locale}/admin/changelog/{slug}`)

`ShowController::__invoke` (`ShowController.php:23-36`) construye `ChangelogEntrySlug` desde la ruta
(el slug inválido ya es 404 de enrutado por `requirements: ['slug' => '[a-z0-9-]+']`) y llama a
`ViewChangelogEntryService`: autoriza `Action::View`, busca por slug y lanza
`ChangelogEntryNotFoundException` si es `null` o `!$entry->published`
(`ViewChangelogEntryService.php:23-29`). El controlador traduce `ChangelogEntryNotFoundException` e
`InvalidChangelogEntryException` a 404 (`ShowController.php:29-31`).

### Manual de usuario (`GET /{_locale}/admin/changelog/{slug}/manual`)

`ManualController::__invoke` (`ManualController.php:27-38`) llama a `DownloadManualService`
(autoriza `Action::View`, localiza la entrada, comprueba `hasManual()` y `storage->exists()`, y
lanza `ManualNotFoundException` si falta cualquiera de las dos) y entrega el resultado con
`StreamManualTrait::streamManual()` (`StreamManualTrait.php:13-37`), que arma una `StreamedResponse`
con `Content-Type` del `mimeType` de la respuesta y `Content-Disposition` de
`ContentDisposition::inline()` o `::attachment()` según el parámetro de consulta `?download=1`
(`ManualController.php:37`, `$request->query->getBoolean('download')`).

### Manual técnico (`GET /{_locale}/admin/changelog/{slug}/manual-tecnico`)

`TechnicalManualController` (`TechnicalManualController.php:24-38`) sigue el mismo patrón con
`ViewTechnicalManualService` y renderiza `@changelog/admin/tech_manual.html.twig` con `entry` y
`markdown` (el contenido leído con `ManualStorage::readContents()`).

En las cuatro rutas, `UnauthorizedActionException` → 403 (`createAccessDeniedException`) y
`ChangelogEntryNotFoundException` / `InvalidChangelogEntryException` / `ManualNotFoundException` →
404 (`createNotFoundException`), como en `RecordedEvent/ListController.php`.

## Autorización y ámbito

`ChangelogEntryAuthorizer extends AbstractAuthorizer`
(`ChangelogEntryAuthorizer.php:11-42`): `supports()` es `$subject instanceof ChangelogEntry`;
`canRegister`, `canView`, `canList`, `canEdit` y `canRemove` delegan todas en
`$this->canAdmin($subject, $user)`, que en `AbstractAuthorizer` resuelve "es `Admin` o
`SuperAdmin`" respetando la cuenta/rol efectivos de la impersonación. No se usa
`CurrentAccountIdProvider`: el changelog no está segmentado por cuenta, es de plataforma.

Todos los controladores llevan además `#[IsGranted('ROLE_ADMIN')]` a nivel de ruta. El nodo de menú
**Novedades → Changelog** solo se añade en `NodeLister::toAdmins()`
(`NodeLister.php:191-200`); `toOwners()` y `toUsers()` no se tocan, así que un `Owner` o `User` ni ve
el nodo ni puede alcanzar las rutas (403 verificado en los `.feature` de aceptación).

No hay feature flag específico para esta funcionalidad.

## Persistencia y migración

No hay persistencia en base de datos ni migración: `config/packages/doctrine.yaml` no se ha tocado.
Los datos viven en `changelog/entries/*.md` (parseados en caliente por
`FilesystemChangelogEntryRepository`) y `changelog/manuals/*` (servidos por `LocalManualStorage`).
El directorio se inyecta como parámetros de servicio en
`src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml`
(`changelog.dir`, `changelog.entries_dir`, `changelog.manuals_dir`), con el bloque `when@test` que
sustituye ambos puertos por los adaptadores in-memory (`InMemoryChangelogEntryRepository`,
`InMemoryManualStorage`), marcados `public: true` para que `ChangelogContext` los pueble en los
escenarios Behat.

## Configuración y operación

- `config/routes.yaml` — bloque `changelog-web` con `prefix: /{_locale}` como el resto de contextos.
- `config/packages/twig.yaml` — namespace `'templates/changelog': 'changelog'`.
- `composer.json` / `composer.lock` — nueva dependencia `league/commonmark`, instalada con
  `make composer c="req league/commonmark"` (transitivas: `league/config`,
  `dflydev/dot-access-data`, `nette/schema`).
- No hay comandos de consola, colas ni tareas programadas nuevas: los cuatro casos de uso son
  consultas síncronas, ninguno implementa `CommandHandler` ni pasa por el `CommandBus`.
- No hay variables de entorno nuevas ni entradas en `TEST_CONT` del `Makefile`: nada queda detrás de
  un feature flag.

## Cómo extenderlo

- **Añadir una novedad**: crear `changelog/entries/YYYY-MM-DD-<slug>.md` con el frontmatter descrito
  en `changelog/README.md` (o invocar la skill `feature-changelog` sobre un plan ya documentado de
  `.claude/plans/done/`, que automatiza la copia de manuales y la redacción de la entrada). No hace
  falta ningún comando ni reinicio: se lee en la siguiente petición.
- **Añadir una categoría nueva**: ampliar el enum `ChangelogCategory`
  (`ChangelogCategory.php:7-16`), añadir su traducción en
  `translations/changelogCategory+intl-icu.{es,en}.xlf` y su clase de color en
  `templates/changelog/admin/_category_badge.html.twig`. Un valor de `category` en el frontmatter
  que no exista en el enum invalida esa entrada de forma silenciosa (queda fuera del listado; ver
  *Riesgos* en `plan.md`).
- **Cachear la lectura del directorio** si crece por encima de las ~200 entradas: el propio plan
  señala `cache.app` con clave derivada de la lista de ficheros y sus `mtime` como la vía prevista;
  no está implementado.
- No hay pantallas de alta/edición dentro de la plataforma: el contenido se escribe siempre en el
  repositorio.

## Tests

**Unitarios** (`tests/Unit/Changelog/`, 626 tests unitarios en todo el proyecto tras este plan):
cubren el VO de slug y sus validaciones (`ChangelogEntrySlugTest`), `matches()`/`hasManual()`/
`hasTechnicalManual()`/`placeholder()` (`ChangelogEntryTest`), el parser de frontmatter con fixtures
válidas e inválidas en `tests/Unit/Changelog/Infrastructure/fixtures/entries/`
(`ChangelogEntryParserTest`) incluida la fecha como entero (ver *Desviaciones*), la jerarquía
`ChangelogEntryRepositoryTest` ejecutada contra el adaptador in-memory y el de ficheros (orden,
paginación, deduplicación por slug, directorio inexistente → lista vacía), `LocalManualStorageTest`
(incluida la protección ante `/`, `\` y `..`), el `ChangelogEntryAuthorizerTest`, los cuatro tests de
servicio de aplicación y `MarkdownExtensionTest` (escapado de `<script>` y de enlaces `javascript:`).

**Aceptación** (`tests/Acceptance/Changelog/`, 193 escenarios de aceptación en todo el proyecto tras
este plan): `changelog-list.feature` (listado, entradas no publicadas ocultas, filtro por categoría,
403 para `Owner` y `User`), `changelog-show.feature` (detalle renderizado, slug inexistente y entrada
no publicada → 404, enlaces de manual presentes/ausentes según el frontmatter, 403 para `Owner`),
`changelog-manual.feature` (descarga del PDF con 200, 404 si no hay manual, manual técnico
renderizado, 403 para `User` en ambas rutas). El contexto `ChangelogContext.php` escribe directamente
en `InMemoryChangelogEntryRepository`/`InMemoryManualStorage`.

Ejecución dirigida: `make test-unit options="--filter Changelog"` /
`make test-acceptance options="tests/Acceptance/Changelog/changelog-list.feature"`.

No hay `review.md` en esta carpeta, así que no hay hallazgos de revisión pendientes que reflejar
aquí.

## Desviaciones y deuda

Del apartado `## Implementación` de `plan.md`:

- `InMemoryChangelogEntryRepository` añade un método `remove(ChangelogEntrySlug $slug)`, no descrito
  en el plan, para que `ChangelogContext` pueda sustituir una entrada ya cargada por una versión con
  manuales sin vaciar el resto de entradas del escenario.
- `ChangelogEntryParser::parseDate()` (`ChangelogEntryParser.php:135-160`) añade una rama para
  valores `int`: el parser YAML de Symfony interpreta una fecha `YYYY-MM-DD` sin comillas en el
  frontmatter como timestamp Unix, no como cadena ni como `\DateTimeInterface` — algo no
  contemplado explícitamente en el plan. Sin esta rama, las entradas reales del directorio
  `changelog/` fallarían al parsear.
- Validación: `make cs-fix` OK, `make phpstan` OK, `make test` OK (626 tests unitarios, 193
  escenarios de aceptación). Sin pendientes para el usuario (no hay migración) ni deuda técnica
  detectada, según el propio plan.
