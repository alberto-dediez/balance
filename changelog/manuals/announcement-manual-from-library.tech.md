# El manual de las novedades se elige de la Biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-26 · **Revisado:** 2026-09-28 · **Bounded Contexts:** `Changelog` (dominio, aplicación, persistencia, UI), `Library` (se usa `MediaId`, `MediaPickerType`, `MediaRepository`, `library_media_show`; ajuste menor en `MediaPickerType`)

## Qué hace y por qué

Antes, el manual PDF de cada idioma de una novedad (`Announcement`) se subía con un `FileType` propio y se guardaba en un almacén exclusivo (`AnnouncementManualStorage`, en `%app.uploads_dir%/announcements`), fuera de la Biblioteca: no se podía reutilizar un PDF ya subido y había dos sistemas de almacenamiento para lo mismo.

Ahora el manual es un archivo (`Media`) de la Biblioteca. La novedad solo guarda su `MediaId`, uno por idioma (campo base = idioma por defecto; `AnnouncementTranslation` = resto de idiomas), igual que `User::$pictureId`. El administrador lo elige con `MediaPickerType` (subida por AJAX o selección en el modal "Biblioteca"), que eleva la visibilidad del archivo a `System` para que lo abra cualquier usuario autenticado. La ruta pública `announcements_manual` se conserva: autoriza la vista de la novedad y redirige (302) a `library_media_show`.

Decisiones relevantes (tabla *Decisiones tomadas* de `plan.md`): los manuales existentes se descartan (sin migración de datos); se mantiene la restricción "solo PDF" con un `Callback` en el form type; archivo borrado o con visibilidad rebajada en la Biblioteca se acepta (404/403 al abrirlo, igual que la foto de perfil); sustituir o quitar el manual no borra nada físicamente.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Changelog/Domain/Model/Announcement/Announcement.php` | `?MediaId $manualId` (`:38`), `manualId(?Locale)` (`:96-101`), `hasManual()` (`:108-111`), `register/update/translate` con `?MediaId` (`:187-268`), `apply*` (`:331`, `:345`, `:356`) | Modificado |
| Dominio | `src/Changelog/Domain/Model/Announcement/AnnouncementTranslation.php` | `?MediaId $manualId` (`:18`), getter `manualId()` (`:43`), copia en `update()` (`:66`) | Modificado |
| Dominio | `src/Changelog/Domain/Model/Announcement/AnnouncementRegistered.php`, `AnnouncementUpdated.php`, `AnnouncementTranslated.php` | Campo `manualId`, payload `'manualId'` y `rebuildFromPayload` tolerante a payloads antiguos | Modificado |
| Dominio | `src/Changelog/Domain/Model/Announcement/AnnouncementManualStorage.php` | Puerto del almacén propio | Borrado |
| Aplicación | `src/Changelog/Application/Announcement/Register/RegisterAnnouncementRequest.php`, `RegisterAnnouncementService.php` | `?MediaId $manualId`; sin storage | Modificado |
| Aplicación | `src/Changelog/Application/Announcement/Update/UpdateAnnouncementRequest.php`, `UpdateAnnouncementService.php` | `?MediaId $manualId` (`null` = sin manual en ese idioma); sin storage ni `removeManual` | Modificado |
| Aplicación | `src/Changelog/Application/Announcement/DownloadManual/DownloadAnnouncementManualService.php`, `DownloadAnnouncementManualResponse.php` | Devuelve el `MediaId` del idioma | Modificado |
| Persistencia | `src/Changelog/Infrastructure/Persistence/Doctrine/Mapping/Announcement.Announcement.orm.xml:27`, `Announcement.AnnouncementTranslation.orm.xml:16` | `<field name="manualId" type="media_id" column="manual_id" nullable="true"/>` | Modificado |
| Persistencia | `migrations/Version20260928082439.php` | Añade `manual_id`, elimina `manual_file_name` y `manual_path` en ambas tablas | Nuevo (creado por el usuario) |
| Infraestructura | `src/Changelog/Infrastructure/Storage/LocalAnnouncementManualStorage.php`, `InMemoryAnnouncementManualStorage.php` | Adaptadores del almacén propio | Borrados |
| Infraestructura | `src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml` | Sin parámetro `changelog.announcement_manuals_dir` ni servicios/alias del storage | Modificado |
| Infraestructura | `src/Changelog/Infrastructure/Symfony/Form/AddsManualFieldTrait.php` | Campo `manualId` (`MediaPickerType`) + validación PDF | Nuevo |
| Infraestructura | `src/Changelog/Infrastructure/Symfony/Form/RegisterAnnouncementType.php:35,102`, `UpdateAnnouncementType.php:35,103` | Constructor con `MediaRepository`; `addManualField()` | Modificado |
| Infraestructura | `src/Changelog/Infrastructure/UI/Web/Admin/Announcement/RegisterController.php`, `EditController.php:69-90` | Sin mapeo de subida; la edición precarga `manualId` del idioma | Modificado |
| Infraestructura | `src/Changelog/Infrastructure/UI/Web/Admin/Announcement/MapsManualUploadsTrait.php`, `src/Changelog/Infrastructure/UI/Web/Announcement/StreamAnnouncementManualTrait.php` | Mapeo del `UploadedFile` y streaming del PDF | Borrados |
| Infraestructura | `src/Changelog/Infrastructure/UI/Web/Announcement/ManualController.php` | Redirección a `library_media_show` | Modificado |
| Infraestructura (Library) | `src/Library/Infrastructure/Symfony/Form/Type/MediaPickerType.php:144` | `'error_bubbling' => false` | Modificado |
| Plantillas | `templates/shared/form/custom_types.html.twig:76-78` | Bloque `media_picker_row` que delega en `form_row` | Modificado |
| Plantillas | `templates/changelog/admin/announcements/edit.html.twig:25` | `{{ form_row(form.manualId) }}` | Modificado |

Sin cambios: `templates/changelog/announcements/show.html.twig` (botones "Ver manual"/"Descargar manual" condicionados por `announcement.hasManual`), `templates/changelog/admin/announcements/register.html.twig` (el layout `@shared/layout/register.html.twig` pinta todos los campos), `AnnouncementAuthorizer`, los repositorios y el `ManualStorage` de `ChangelogEntry` (manuales del changelog interno, otra cosa).

## Modelo de dominio

- **Campo base** `Announcement::$manualId` (`Announcement.php:38`) = manual del idioma por defecto. **Traducción** `AnnouncementTranslation::$manualId` = manual de ese idioma. El getter de la traducción se llama exactamente `manualId()` porque `DoctrineTranslatableRepositoryTrait` actualiza las traducciones por reflexión.
- **Resolución por idioma** (`Announcement.php:96-101`): si existe traducción para el locale pedido (o `LocaleGetter::locale()`, `translationIn()` en `:403-408`), se devuelve **su** `manualId`, aunque sea `null`; solo si no hay traducción se cae al campo base. Consecuencia: una traducción guardada sin manual oculta el manual base en ese idioma. `hasManual()` (`:108-111`) = `manualId($locale) instanceof MediaId`.
- **Comandos:**
  - `register(..., ?MediaId $manualId, ?UserId $registeredBy, ?Locale $locale = null)` (`:187-217`): el `AnnouncementRegistered` lleva el manual como valor base y, con locale, además llama a `translate()` con el mismo manual. Registrar en un idioma no por defecto deja el manual en base **y** en esa traducción.
  - `update(..., ?MediaId $manualId, ?Locale $locale = null)` (`:219-247`): con locale, `AnnouncementUpdated` conserva `$this->manualId` (`:240`) y el manual nuevo va a `translate()`.
  - `translate(..., ?MediaId $manualId, Locale $locale)` (`:249-268`) registra `AnnouncementTranslated`; su `apply` añade o actualiza la traducción (`:356`).
- **Eventos:** payload `'manualId' => (string)|null` (`AnnouncementRegistered.php:101`, `AnnouncementUpdated.php:94`, `AnnouncementTranslated.php:80`). `rebuildFromPayload` usa `$payload['manualId'] ?? null` (`AnnouncementRegistered.php:120`, `AnnouncementUpdated.php:112`, `AnnouncementTranslated.php:95`), así que los eventos históricos guardados en `RecordedEvent` con `manualFileName`/`manualPath` se reconstruyen sin manual en vez de fallar.
- **Sin invariantes nuevas en dominio:** que el `MediaId` exista y sea PDF se valida en el formulario, no en el agregado.

## Flujo de una operación

### Registrar o editar con manual (administrador)

1. `GET/POST /{_locale}/admin/announcements/register` (`RegisterController`) o `/{_locale}/admin/announcements/edit/{announcementId}` (`EditController`). En edición, `EditController::updateRequestFrom()` (`EditController.php:69-90`) precarga `manualId`: el del campo base si el locale es el por defecto; si no, el de la traducción existente o `null` si aún no hay traducción (la traducción nueva no hereda el manual base).
2. El campo `manualId` lo añade `AddsManualFieldTrait::addManualField()` (`AddsManualFieldTrait.php:23-50`): `MediaPickerType` con `label: 'Manual (PDF)'`, `required: false`, `types: [MediaType::Document]`, `accept: 'application/pdf'`, `visibility: MediaVisibility::System`.
3. En el navegador (`media_picker_controller.js`): subir el archivo llama a `library_media_ajax_upload` (`src/Library/Infrastructure/UI/Web/Media/Picker/AjaxUploadController.php`), que crea el `Media` ya con visibilidad `System` y rechaza tipos fuera de `Document`; el botón "Biblioteca" busca en `library_media_search` filtrando por `Document`. El `MediaId` queda en un `<input type="hidden">`; "Quitar" lo vacía.
4. Al enviar: `AggregateRootIdTransformer` convierte el hidden en `MediaId`. En `POST_SUBMIT`, `MediaPickerType::shareSubmittedMedia()` (`MediaPickerType.php:79-104`) invoca `ShareMediaService`, que exige `Action::View` sobre el media y sube su visibilidad a `System` si está por debajo (`MediaVisibility::satisfies()`); si el usuario no puede verlo, error "No puedes usar ese archivo en este campo". El `Callback` del trait carga el `Media` con `MediaRepository::find()` y, si no existe o `mimeType() !== 'application/pdf'`, añade "El manual debe ser un PDF".
5. Con `error_bubbling: false` (`MediaPickerType.php:144`) y `media_picker_row` (`custom_types.html.twig:76-78`) el error se pinta bajo el propio campo; antes burbujeaba al raíz, que el layout de alta no muestra.
6. `RegisterAnnouncementService` / `UpdateAnnouncementService`: `denyUnlessCanDoThat(..., Action::Register|Edit, ...)`, resuelve `$isDefaultLocale` con `LocaleGetter` y pasa `manualId: $request->manualId` y `locale: $isDefaultLocale ? null : $locale` al agregado; `repository->add()`.

Nota de orden: la elevación a `System` ocurre en `POST_SUBMIT` del campo, antes de validar el formulario completo; si el formulario falla por otro motivo (p. ej. "El manual debe ser un PDF" o un título vacío), el archivo ya ha quedado con visibilidad `System`.

### Ver o descargar el manual (cualquier usuario)

1. `templates/changelog/announcements/show.html.twig:8-17`: si `announcement.hasManual` (idioma actual), `TopAction` "Ver manual" (`target="_blank"`) y "Descargar manual" (`download: 1`) a `announcements_manual`.
2. `ManualController` (`src/Changelog/Infrastructure/UI/Web/Announcement/ManualController.php`, ruta `/announcements/{announcementId}/manual`, `ROLE_USER`) invoca `DownloadAnnouncementManualService`.
3. El servicio (`DownloadAnnouncementManualService.php:30-47`): busca → `AnnouncementNotFoundException`; `denyUnlessCanDoThat(..., Action::View, ...)`; `manualId($request->locale ?? LocaleGetter::locale())`; si es `null`, `ManualNotFoundException`; devuelve `DownloadAnnouncementManualResponse($mediaId)`.
4. El controlador convierte las tres excepciones en 404 y, si no, redirige a `library_media_show` con `mediaId` y `download=1` cuando venía en la query (`_locale` se hereda de la petición).
5. `library_media_show` (`src/Library/Infrastructure/UI/Web/Media/ShowController.php:20`) aplica `MediaAuthorizer::canView` (`System` es accesible fuera de la cuenta) y sirve el fichero; 404 si el media se ha borrado, 403 si ya no es accesible.

## Autorización y ámbito

- **Gestión** (`Register`, `Edit`): `AnnouncementAuthorizer::canRegister/canEdit` → `canAdmin` (Administrador y Super administrador) (`src/Changelog/Application/Announcement/AnnouncementAuthorizer.php:23-49`).
- **Manual**: `canView` (`:28-35`) = novedad publicada y `ANNOUNCEMENTS_ENABLED`, o administrador. Propietarios y usuarios reciben 404 en el manual de un borrador o programada.
- **Archivo**: el acceso final lo decide `MediaAuthorizer::canView` (`src/Library/Application/Media/MediaAuthorizer.php:21-28`). Como el picker fija `System`, el archivo es visible para cualquier usuario autenticado de cualquier cuenta, sin aparecer en la Biblioteca de otras cuentas.
- **Impersonación**: el administrador que suplanta una cuenta sube el archivo a esa cuenta (`AjaxUploadController` usa `LoggedAccount`); con visibilidad `System` sigue siendo accesible para todos. Un Super administrador sin cuenta crea medias sin cuenta.
- **Feature flag**: `ANNOUNCEMENTS_ENABLED` (sin cambios en este plan).

## Persistencia y migración

- Tablas `announcements` y `announcement_translations`: nueva columna `manual_id VARCHAR(36) DEFAULT NULL COMMENT '(DC2Type:media_id)'`; se eliminan `manual_file_name` y `manual_path`. Sin clave foránea hacia la tabla de medias.
- Migración: `migrations/Version20260928082439.php` (creada a mano por el usuario a partir de `migration.sql`; sin seguimiento en git a fecha de este manual). El `down()` recrea las columnas antiguas vacías; no recupera datos.
- **Pérdida de datos asumida:** los manuales existentes no se migran. Antes de aplicar la migración en un entorno se pueden listar con `SELECT id, manual_file_name FROM announcements WHERE manual_path IS NOT NULL` (y lo mismo en `announcement_translations`). Tras desplegar, `var/uploads/announcements/` queda huérfano y puede borrarse.

## Configuración y operación

- `services.yaml` del BC ya no define `changelog.announcement_manuals_dir` ni el storage de manuales de novedades (tampoco en `when@test`). `MediaRepository` se inyecta por autowiring en los dos form types.
- Sin variables de entorno, colas, comandos ni tareas programadas nuevas.
- Despliegue: aplicar la migración con `make migrate` y reasignar a mano los manuales desde la edición de cada novedad (en cada idioma que lo tuviera).

## Cómo extenderlo

- **Otro campo "archivo de la Biblioteca" en un agregado**: seguir este patrón (y `User::$pictureId`): propiedad `?MediaId` mapeada con `type="media_id"`, payload `string|null` en eventos con `?? null` en `rebuildFromPayload`, y `MediaPickerType` en el form type con `visibility` fija si lo deben abrir usuarios de otras cuentas.
- **Restringir el tipo de archivo**: `types`/`accept` del picker solo filtran en el navegador y en la búsqueda; la garantía está en un `Callback` como el de `AddsManualFieldTrait` que carga el `Media` y valida `mimeType()`.
- **Varios manuales por idioma**: `MediaPickerType` admite `multiple: true` (devuelve `list<MediaId>`); habría que cambiar el tipo del campo y el mapping (no hay colección de `MediaId` en el agregado hoy).
- **Trampas conocidas:**
  - La traducción no hereda el manual base: al traducir sin elegir manual, ese idioma queda sin manual (y deja de ver el base).
  - Si el archivo se borra de la Biblioteca o baja a "Solo mi cuenta", los botones siguen visibles pero `library_media_show` responde 404/403 (decisión aceptada; no hay comprobación cruzada Changelog → Library).
  - Nada impide borrar de la Biblioteca un archivo usado como manual.
  - `edit.html.twig` no debe referenciar `form.manual`, `form.removeManual` ni `manualFileName` (`strict_variables` en test).

## Tests

- **Unitarios** (`make test-unit options="--filter Announcement"`):
  - `tests/Unit/Changelog/Domain/Model/Announcement/AnnouncementTest.php:101` (`testVideoAndManualAreResolvedPerLocale`) y `:140` (`testUpdateInAnotherLocaleKeepsTheBaseManual`).
  - `tests/Unit/Changelog/Application/Announcement/Register/RegisterAnnouncementServiceTest.php:85,98` — manual en el idioma por defecto y en otro idioma.
  - `tests/Unit/Changelog/Application/Announcement/Update/UpdateAnnouncementServiceTest.php:78-124` — quitar, sustituir, traducción nueva sin heredar el base, cambiar el de la traducción conservando el base.
  - `tests/Unit/Changelog/Application/Announcement/DownloadManual/DownloadAnnouncementManualServiceTest.php:29,39,48` — devuelve el `MediaId`, traducción sin manual → `ManualNotFoundException`, borrador → no autorizado.
  - `AnnouncementMother` y `AnnouncementRepositoryTest` adaptados a `MediaId`.
- **Aceptación** (`make test-acceptance options="tests/Acceptance/Changelog"`):
  - `announcement-register.feature:39` (registro con manual de la Biblioteca) y `:49` (documento que no es PDF → "El manual debe ser un PDF").
  - `announcement-edit.feature:32` (quitar el manual desde el selector).
  - `announcement-user-show.feature:15` (botones), `:62` (redirección a `/es/library/media/{id}/show`, 200 y `application/pdf`), `:73` (borrador → 404), `:81` (sin manual → 404).
  - Pasos en `tests/Acceptance/Changelog/ChangelogContext.php:121-131` (`announcement :id has a manual in :locale`, `announcement :id has the manual :mediaId in :locale`) y `document :name with id :id exists` de `LibraryContext`.
- **Hueco conocido** (`review.md`, B1): el escenario de `announcement-user-show.feature:62` usa un PDF sin cuenta con visibilidad `Account` y un usuario sin cuenta, así que no ejercita la ruta de producción (`System` visible desde otra cuenta). Esa ruta la cubre `tests/Acceptance/Library/media-download.feature:26` de forma genérica.

## Desviaciones y deuda

Ver `## Implementación` de `plan.md` y `review.md` / `review-fix.md`:

- `RegisterAnnouncementServiceTest`: el test prescrito que esperaba el manual base a `null` al registrar en otro idioma se sustituyó por `testAManualRegisteredInAnotherLocaleBelongsToItsTranslation`, coherente con el dominio (el registro con locale también rellena la base).
- `MediaPickerType` gana `error_bubbling: false` y `media_picker_row`: afecta también a la imagen de perfil (`ProfileType`), que ahora muestra etiqueta y errores bajo el campo.
- `AddsManualFieldTrait::addManualField()` lleva `@param FormBuilderInterface<mixed>` por PHPStan.
- Review A1 (migración ausente): descartado en `review-fix.md`; la migración ya existe (`migrations/Version20260928082439.php`), pendiente de aplicar en cada entorno.
- Review B1 (cobertura Behat de la visibilidad `System`): confirmado, corrección pendiente de aprobación en `review-fix.md`.
- Deuda previa, fuera de alcance: `RegisterController` instancia `new Announcement(AnnouncementId::generate())` como sujeto del voter fuera del servicio.
