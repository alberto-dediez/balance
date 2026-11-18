# Consulta de guías desde el asistente — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-17 · **Bounded Contexts:** `Changelog`, `Assistant`, `Shared`

## Qué hace y por qué

La skill `guide` mantiene en `guide/<perfil>/…` (`admin`, `owner`, `user`) artículos Markdown con frontmatter, uno por opción de menú. Esta funcionalidad los expone al asistente IA como segundo modelo de lectura del contexto `Changelog` (`GuideArticle`, gemelo de `ChangelogEntry`): dos herramientas de lectura (`search_guide_articles`, `read_guide_article`), una pantalla `guide_article` (página completa o fragmento para modal) y, en el asistente, un bloque «Fuentes» bajo cada respuesta más la apertura de los enlaces a documentos en un modal global.

Decisión central (plan, tabla «Decisiones tomadas»): **el perfil nunca viene de una entrada externa**. Ni las herramientas ni la ruta tienen parámetro de perfil; los dos servicios lo calculan con `GuideArticleAuthorizer::profileOf($user)` a partir del rol efectivo. El asistente sigue siendo solo para `Admin`/`SuperAdmin` sin suplantación, así que hoy el asistente solo lee `guide/admin/`; la ruta web sí sirve a cualquier usuario autenticado con su propio perfil.

Las fuentes no se persisten: se derivan en el servidor de las `ToolCall` ya guardadas. No hay cambios de esquema ni migración. El contrato nuevo `SourceAssistantTool` es una interfaz opcional que extiende `AssistantTool`, de modo que no cambia ninguna firma existente (compatibilidad con la fusión en `dediez-erp`).

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/Changelog/Domain/Model/GuideArticle/GuideProfile.php` | Enum `admin`/`owner`/`user` | nuevo |
| Dominio | `…/GuideArticlePath.php` | VO de ruta validada; `resolve()` de enlaces relativos | nuevo |
| Dominio | `…/GuideArticle.php` | Modelo de lectura: `matches()`, `excerpt()`, `placeholder()` | nuevo |
| Dominio | `…/GuideArticleFilter.php`, `GuideArticleRepository.php`, `GuideArticleNotFoundException.php`, `InvalidGuideArticleException.php` | Filtro, puerto y excepciones | nuevo |
| Aplicación | `src/Changelog/Application/GuideArticle/GuideArticleAuthorizer.php` | `profileOf()` y reglas por acción | nuevo |
| Aplicación | `…/List/ListGuideArticlesService.php` (+ Request/Response/`GuideArticleMatch`) | Índice y búsqueda del perfil | nuevo |
| Aplicación | `…/View/ViewGuideArticleService.php` (+ Request/Response) | Lectura de un artículo del perfil | nuevo |
| Aplicación | `…/Assistant/SearchGuideArticlesTool.php`, `ReadGuideArticleTool.php` | Herramientas del asistente | nuevo |
| Shared | `src/Shared/Application/Assistant/SourceAssistantTool.php` | `sourceHref(array $arguments): ?string` | nuevo |
| Infra Changelog | `src/Changelog/Infrastructure/Markdown/GuideArticleParser.php` | Frontmatter → `GuideArticle` | nuevo |
| Infra Changelog | `…/Persistence/Filesystem/FilesystemGuideArticleRepository.php` | Adaptador de disco (`dev`/`prod`) | nuevo |
| Infra Changelog | `…/Persistence/InMemory/InMemoryGuideArticleRepository.php` | Adaptador de test | nuevo |
| Infra Changelog | `…/UI/Web/GuideArticle/ShowController.php` | Ruta `guide_article` | nuevo |
| Infra Changelog | `…/UI/Web/Twig/GuideMarkdownExtension.php` | Filtro `guide_article_html` | nuevo |
| Infra Changelog | `templates/changelog/guide/{show,_body,_modal}.html.twig` | Página y fragmento | nuevo |
| Infra Changelog | `src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml` | `changelog.guide_dir`, adaptador, alias | modificado |
| Assistant | `src/Assistant/Infrastructure/Symfony/Twig/AssistantSourcesExtension.php` | Función Twig `assistant_sources()` | nuevo |
| Assistant | `…/Twig/AssistantLinkResolver.php` | `documentRoutes` + `isDocument()` | modificado |
| Assistant | `…/Twig/AssistantMarkdownExtension.php` | Atributo `data-assistant-document` en enlaces de documento | modificado |
| Assistant | `src/Assistant/Infrastructure/UI/Web/Component/Assistant/DocumentModal.php`, `templates/components/Assistant/DocumentModal.html.twig` | Modal global | nuevo |
| Assistant | `src/Assistant/Application/Engine/AssistantPrompts.php` | Regla «guía primero, novedades después» | modificado |
| Front | `assets/controllers/assistant_panel_controller.js`, `assets/styles/components/{_assistant,_changelog}.scss`, `templates/assistant/_thread.html.twig`, `templates/shared/layout/app.html.twig` | Fuentes, apertura en modal, estilos | modificado |
| Config | `config/services.yaml`, `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml`, `src/Assistant/Infrastructure/Symfony/DependencyInjection/services.yaml` | `shared.assistant.document_routes` | modificado |
| i18n | `templates/changelog/translations/assistant.html.twig`, `translations/messages+intl-icu.{es,en}.xlf`, `assets/icons/solar/book-2-line-duotone.svg` | Etiquetas e icono | modificado/nuevo |

## Modelo de dominio

- `GuideArticlePath` (`GuideArticlePath.php:9-21`): patrón `^[a-z0-9]+(-[a-z0-9]+)*(/…){0,3}\z`, máximo 200 caracteres y `index` rechazado; si no, `InvalidGuideArticleException`. `resolve(string $relativeLink)` (`:34`) resuelve un enlace relativo `.md` contra el directorio de la ruta propia y devuelve `null` si no acaba en `.md`, contiene `:`, empieza por `/`, sale de la raíz o no cumple el patrón.
- `GuideArticle` (`GuideArticle.php`): `profile`, `path`, `title`, `summary`, `menu`, `body`, `updated`. `matches($filter)` compara perfil y todas las palabras del texto contra título + resumen + menú + cuerpo, sin mayúsculas ni acentos (`fold()`: NFD, quita `\p{Mn}`, minúsculas). `excerpt($text, 150)` pliega el cuerpo carácter a carácter guardando el índice original (`$origins`) para devolver el fragmento del texto original (corrección de la review B2: antes devolvía `null` si el plegado cambiaba la longitud, p. ej. con «⚠️»).
- `GuideArticleFilter`: `use Filter`, `profile`, `text`; `limit` 20 por defecto.
- Sin agregado ni eventos: es de solo lectura, como `ChangelogEntry`.

## Flujo de una operación

**Herramienta `read_guide_article`** (`ReadGuideArticleTool.php:66`): `ToolRunner` ejecuta con el `User` de la conversación → `path()` construye `GuideArticlePath` (inválido → `InvalidToolArgumentsException` ««path»: no es una ruta de artículo válida.») → `ViewGuideArticleService` (`profileOf` → `denyUnlessCanDoThat(placeholder, Action::View)` → `repository->find(perfil, ruta)`; `null` → `GuideArticleNotFoundException` → `InvalidToolArgumentsException` con `NOT_FOUND`) → `ToolResult` con `markdown` truncado a 20 000 caracteres (`ToolResult::truncate`), `references: ['path' => …]` y `link: ToolLink('Ver guía','guide_article',['path'])`, por lo que el modelo recibe `_link.href` = `route:guide_article?path=…`. `search_guide_articles` devuelve por artículo `path`, `title`, `menu`, `summary`, `excerpt` (solo con texto) y `href`; el límite se acota a `[1, 50]` en `ListGuideArticlesService`.

**Fuentes y modal**:
1. `templates/assistant/_thread.html.twig` llama a `assistant_sources(message)` (`AssistantSourcesExtension.php:30`). Por cada `ToolCall` no fallida cuya herramienta (`Toolbox::get`) es `SourceAssistantTool`, obtiene `sourceHref(argumentValues())`, lo resuelve con `AssistantLinkResolver::resolve()` y deduplica por URL; la etiqueta es el `summary` de la llamada (título del artículo).
2. `isDocument($href)` (`AssistantLinkResolver.php`) es `true` si el nombre de la ruta está en `shared.assistant.document_routes`. Si lo es, el enlace lleva `data-assistant-document="1"`; lo mismo hace `AssistantMarkdownExtension::resolveLinks()` para los enlaces que escribe el modelo en el texto.
3. `assistant_panel_controller.js` (`#openDocument`) escucha `click` en `document`; actúa solo con clic primario sin modificadores y sobre un `a[data-assistant-document]` dentro del panel o del modal; hace `fetch` con `X-Requested-With: XMLHttpRequest`, trata sesión perdida con `#sessionLost` (review B1), vuelca el fragmento en `[data-assistant-document-content]` y abre `Modal.getOrCreateInstance`. Cualquier fallo → `window.location.assign(href)`.
4. `ShowController` (`ShowController.php:24`) responde `_modal.html.twig` si `isXmlHttpRequest()`, y `show.html.twig` si no; `Cache-Control: no-store`. `UnauthorizedActionException` → 403; no encontrado o ruta inválida → 404.

**Render del cuerpo** (`GuideMarkdownExtension.php`): CommonMark + GFM, `html_input: escape`, `allow_unsafe_links: false`, tablas `table table-striped`, imágenes sustituidas por su texto alternativo, enlaces externos en pestaña nueva. En `DocumentParsedEvent` se desprende el `# Título` inicial (review B3; contradice la frase del plan «se conserva») y cada enlace se trata así: `http(s):`/`mailto:` se deja; relativo que `GuideArticlePath::resolve()` convierte en ruta → URL de `guide_article` + `data-assistant-document="1"`; cualquier otro → se sustituye por su texto.

## Autorización y ámbito

- `GuideArticleAuthorizer.php:15-26`: `SuperAdmin` o `Admin` → `admin`; `Owner` → `owner`; resto → `user`, con `userHasRole()` (rol efectivo: un administrador que suplanta una cuenta recibe el perfil `owner` en la ruta web). `canList` siempre `true`; `canView` solo si `$subject->profile === profileOf($user)`; el resto de acciones `false`.
- No se reutiliza `ChangelogEntryAuthorizer` (solo administradores). `ShowController` lleva `#[IsGranted('ROLE_USER')]`; no copiar `ROLE_ADMIN` de los controladores de `Admin/`.
- El asistente: `ConversationAuthorizer` y `AssistantAccess` no cambian (`Admin`/`SuperAdmin`, sin suplantación, flag `ASSISTANT_ENABLED`). El modal solo se pinta si `AssistantAccess` lo permite (`DocumentModal::enabled()`).

## Persistencia y migración

Sin base de datos ni migración. `FilesystemGuideArticleRepository` (solo `dev`/`prod` por `#[When]`) lee `guide/<perfil>/<ruta>.md` en cada llamada, sin caché:

- `find()` (`:27`): fichero inexistente → `null`; si `realpath` del fichero no cuelga de `realpath(guide/<perfil>)` (enlace simbólico) → `null`; frontmatter inválido → `warning` y `null`.
- `all()` (`:66`): recorre el perfil con `RecursiveDirectoryIterator` (sin seguir enlaces), omite `index.md` de la raíz, registra y omite rutas que no cumplen `GuideArticlePath`, ordena por `menu` y `path`. Carpeta inexistente → lista vacía.
- `GuideArticleParser`: obligatorios `title` y `summary`; opcional `menu`; `updated` en `Y-m-d H:i` o `Y-m-d`; `profile` y `routes` se ignoran (manda la carpeta).

## Configuración y operación

- `changelog.guide_dir: '%kernel.project_dir%/guide'` (`src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml:5`); en `when@test` el puerto apunta a `InMemoryGuideArticleRepository` (público).
- `shared.assistant.document_routes`: `[]` por defecto en Shared; `['guide_article']` en `config/services.yaml`. Se inyecta como `$documentRoutes` en `AssistantLinkResolver`.
- `guide/` debe estar commiteada y desplegarse con el código. En el momento de documentar **no existe** en el árbol de trabajo: las herramientas devuelven 0 artículos y la ruta web 404; el prompt (`AssistantPrompts.php:51`) hace que el modelo recurra a las novedades.
- Traducciones: `make translation` tras tocar plantillas; textos nuevos `Guía`, `Fuentes`, `Fuente`, `Ver guía`, `Buscar en la guía`, `Leer artículo de la guía`, `Artículo`.
- La ruta usa el prefijo de administración (`changelog-web` importa `UI/Web/` con `%app.admin_prefix%/{_locale}`): `/{_locale}/guide/{path}` o `/management/{_locale}/guide/{path}`.

## Cómo extenderlo

- **Abrir otro documento en el modal**: 1) su herramienta de lectura implementa `SourceAssistantTool` y devuelve `ToolLink::href('mi_ruta', …)`; 2) añadir `mi_ruta` a `shared.assistant.document_routes`; 3) la ruta debe devolver un fragmento Twig completo cuando `isXmlHttpRequest()`. El plan lo deja fuera para novedades y manuales.
- **Abrir el asistente a `Owner`/`User`**: el aislamiento por perfil ya está implementado; el cambio estaría en `ConversationAuthorizer`, `AssistantAccess` y los `#[IsGranted]` del asistente (plan propio).
- **Guía en otros idiomas**: hoy solo español; habría que añadir locale al puerto y al filtro.
- Trampas: `ToolLink::href()` codifica `/` como `%2F`; el resolver lo devuelve como `/` (lo fijan `AssistantSourcesExtensionTest` y el escenario 1 de `assistant-base-guide.feature`). Los seis ficheros del motor del asistente tocados son «copia idéntica» de `dediez-erp`: editarlos solo con cambio mínimo.

## Tests

- Unitarios: `tests/Unit/Changelog/Domain/Model/GuideArticle/` (`GuideArticlePathTest`, `GuideArticleTest`, `GuideArticleMother`), `tests/Unit/Changelog/Application/GuideArticle/` (authorizer, servicios y herramientas, incluido `testItDoesNotAcceptAProfileParameter`), `tests/Unit/Changelog/Infrastructure/{Markdown,Persistence,UI/Web/Twig}/` con fixtures en `tests/Unit/Changelog/Infrastructure/fixtures/guide/`, y en Assistant `AssistantLinkResolverTest`, `AssistantMarkdownExtensionTest`, `AssistantSourcesExtensionTest`, `AssistantPromptsTest`.
- Behat: `tests/Acceptance/Changelog/guide-article.feature` (7 escenarios: administrador, propietario con su perfil, ruta de otro perfil 404, inexistente 404, anónimo 403, «Relacionado», fragmento XHR) y `tests/Acceptance/Assistant/assistant-base-guide.feature` (4: fuente mostrada, artículo de otro perfil, ruta con `..`, búsqueda aislada). Paso nuevo en `ChangelogContext`: `guide article :path for profile :profile with title :title and body:`.
- Ejecución: `make test-unit options="--filter Guide"`, `make test-acceptance options="tests/Acceptance/Changelog/guide-article.feature"`, `make test-acceptance options="tests/Acceptance/Assistant/assistant-base-guide.feature"`, y los mismos con `admin_prefix=/management`.
- Huecos: no hay tests de JavaScript (apertura del modal, sesión caducada).

## Desviaciones y deuda

- El anónimo recibe 403, no redirección al login, en el entorno de test (`plan.md` § Implementación).
- `excerpt()` ya no devuelve `null` por cambio de longitud (corregido en review B2); el título inicial se desprende al pintar (B3, contradice el plan); `#openDocument` trata la sesión perdida (B1). Ver `review.md`: 0 hallazgos abiertos.
- Pendiente de operación: generar y commitear `guide/` con la skill `guide`.
