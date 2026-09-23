# Novedades para usuarios — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-22 (refactorizado el 2026-09-23) · **Bounded Contexts:** `Changelog` (agregado nuevo `Announcement`), `Shared` (navegación, `CommandSchedule`, zona horaria del navegador, `DoctrineTranslatableRepositoryTrait`), `Notifications` (consumido a través de `SystemNotifier`, sin cambios)
- **Revisión:** `review.md` (revisión 1) y `review-fix.md` (M1, B1 y B2 aplicados; C1 descartado; Q1 asumido), misma carpeta

## Qué hace y por qué

La sección **Novedades** anterior (`ChangelogEntry`) solo llega a los administradores y se alimenta de
ficheros Markdown versionados. Esta funcionalidad añade un segundo tipo de novedad, **`Announcement`**,
que los administradores redactan, traducen y publican desde la plataforma, que se guarda en base de
datos y que leen **todos los usuarios autenticados** de todas las cuentas. Cada novedad tiene
categoría (se reutiliza `ChangelogCategory`), fecha, versión opcional, título, resumen, contenido
Markdown y, opcionalmente, un vídeo de YouTube embebido y un manual PDF. Título, resumen, contenido,
vídeo y manual son **traducibles** (vídeo y manual también van por idioma).

El ciclo de vida es **Borrador → (Programada) → Publicada → Borrador**. Publicar abre un formulario
con fecha y hora opcionales: vacía o pasada publica al instante; futura deja la novedad programada y
una tarea periódica (`CommandSchedule`, cada 5 minutos) la publica cuando llega la hora. Opcionalmente
se avisa a todos los usuarios con una notificación interna, despachada siempre **después** de publicar
(nunca antes de que la novedad sea visible).

Decisiones clave (ver la tabla *Decisiones tomadas* del plan y las secciones *Refactorización del
patrón de traducciones* y *Revisión de convenciones `create-entity*`* de `## Implementación`):

- `Announcement` es la **implementación de referencia del patrón de traducciones** del proyecto
  (mismo patrón que `events`): valores del idioma por defecto en columnas del agregado,
  `AnnouncementTranslation` solo para los demás idiomas, formularios planos y traducción cambiando el
  idioma de la URL. Sustituyó al diseño inicial del plan (pestañas por idioma, traducción por defecto
  en tabla aparte).
- Almacenamiento propio del manual (`AnnouncementManualStorage`), no la Biblioteca.
- Programación en el agregado + tarea periódica, en lugar de un `DeferredCommand` con `executeAt`
  (cancelable y reprogramable sin mensajes huérfanos).
- Todo el acceso de usuarios está tras el flag **`ANNOUNCEMENTS_ENABLED`** (añadido después del plan;
  en `.env` vale `false`).

## Mapa de piezas

### Dominio — `src/Changelog/Domain/`

| Fichero | Rol | |
|---|---|---|
| `DomainEvent.php` | Base de los eventos del contexto (`$context = 'changelog'`) | nuevo |
| `Model/Announcement/Announcement.php` | Agregado, `Translatable`, trait `AggregateRoot` | nuevo |
| `Model/Announcement/AnnouncementTranslation.php` | Traducción (sin id propio) | nuevo |
| `Model/Announcement/AnnouncementId.php` | UUID | nuevo |
| `Model/Announcement/AnnouncementStatus.php` | Enum derivado `Draft`/`Scheduled`/`Published` (no se persiste) | nuevo |
| `Model/Announcement/YouTubeVideo.php` + `InvalidYouTubeUrlException.php` | Value object del vídeo | nuevo |
| `Model/Announcement/AnnouncementFilter.php` | Filtro (`text`, `category`, `status`, orden `date DESC`) | nuevo |
| `Model/Announcement/AnnouncementRepository.php` | Puerto de persistencia | nuevo |
| `Model/Announcement/AnnouncementManualStorage.php` | Puerto de almacenamiento del PDF | nuevo |
| `Model/Announcement/AnnouncementNotFoundException.php` | Excepción | nuevo |
| `Model/Announcement/Announcement{Registered,Updated,Translated,PublicationScheduled,Published,Unpublished,Removed}.php` | Eventos | nuevos |

### Aplicación — `src/Changelog/Application/Announcement/`

| Fichero | Rol | |
|---|---|---|
| `AnnouncementAuthorizer.php` | Autorización (con flag) | nuevo |
| `Register/`, `Update/` | Alta y edición/traducción | nuevos |
| `Publish/` | Publicar ahora o programar | nuevo |
| `PublishScheduled/` | Tarea periódica que publica las programadas vencidas | nuevo |
| `Unpublish/`, `Remove/` | Despublicar / cancelar programación; borrado lógico | nuevos |
| `List/`, `View/`, `DownloadManual/` | Lectura | nuevos |
| `Notify/` | Handler diferido del aviso masivo | nuevo |

### Infraestructura

| Fichero | Rol | |
|---|---|---|
| `src/Changelog/Infrastructure/Persistence/Doctrine/Mapping/Announcement.Announcement.orm.xml`, `Announcement.AnnouncementTranslation.orm.xml` | Mappings (tablas `announcements`, `announcement_translations`) | nuevos |
| `src/Changelog/Infrastructure/Persistence/Doctrine/Type/{AnnouncementIdType,YouTubeVideoType}.php` | Tipos DBAL | nuevos |
| `src/Changelog/Infrastructure/Persistence/Doctrine/Repository/DoctrineAnnouncementRepository.php` | Adaptador Doctrine | nuevo |
| `src/Changelog/Infrastructure/Persistence/InMemory/Repository/InMemoryAnnouncementRepository.php` | Adaptador de tests | nuevo |
| `src/Changelog/Infrastructure/Storage/{Local,InMemory}AnnouncementManualStorage.php` | Adaptadores del PDF | nuevos |
| `src/Changelog/Infrastructure/Symfony/Form/{Register,Update,Publish}AnnouncementType.php`, `ListAnnouncementsType.php`, `ListUserAnnouncementsType.php` | Formularios | nuevos |
| `src/Changelog/Infrastructure/UI/Web/Admin/Announcement/*` | Controladores de administración (`List`, `Register`, `Edit`, `Publish`, `Unpublish`, `Remove`, `Bulk/RemoveAction`, traits) | nuevos |
| `src/Changelog/Infrastructure/UI/Web/Announcement/*` | Controladores de usuario (`List`, `Show`, `Manual`, `StreamAnnouncementManualTrait`) | nuevos |
| `src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros, flag, alias de puertos | modificado |
| `templates/changelog/admin/announcements/*.html.twig` | Pantallas de administración y `_status_badge` | nuevos |
| `templates/changelog/announcements/{list,show,published.notification}.html.twig` | Pantallas de usuario y cuerpo del aviso | nuevos |
| `templates/components/List/Action/{Publish,Unpublish}.html.twig` | Acciones de fila (M1) | nuevos |
| `templates/changelog/translations/{announcement,announcementStatusType}.html.twig`, `translations/announcementStatusType+intl-icu.{es,en}.xlf` | Extracción y catálogo del estado | nuevos |
| `templates/security/admin/events/detail/changelog.announcement*.html.twig` (7) | Detalle de los eventos en el registro de auditoría | nuevos |
| `templates/changelog/admin/{list,show}.html.twig` | `date('d/m/Y', false)` para no desplazar fechas sin hora | modificados |

### Transversal

| Fichero | Rol | |
|---|---|---|
| `src/Shared/Application/CommandBus/CommandSchedule.php:38-42` | Tarea `changelog:publish-scheduled-announcements` cada 5 min | modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | Grupo **Novedades** para usuarios/propietarios (con flag) e ítems nuevos para administradores | modificado |
| `src/Shared/Application/CurrentTimezone/CurrentTimezoneProvider.php` | Puerto: zona horaria del usuario actual | nuevo |
| `src/Shared/Infrastructure/Timezone/CookieTimezoneProvider.php` | Lee la cookie `timezone` (validada con `DateTimeZone::listIdentifiers()`), fallback a `date_default_timezone_get()` | nuevo |
| `src/Shared/Infrastructure/Timezone/RequestTimezoneSetter.php` | Listener que fija la zona de Twig (`CoreExtension::setTimezone`) en cada petición principal | nuevo |
| `assets/js/timezone.js` (+ `importmap.php`, `assets/app.js`) | Escribe la cookie `timezone` con la zona del navegador | nuevo |
| `src/Shared/Infrastructure/Persistence/Doctrine/Repository/DoctrineTranslatableRepositoryTrait.php` | PHPDoc `class-string`; primer consumidor real | modificado |
| `config/packages/doctrine.yaml` | Tipos `announcement_id`, `youtube_video` y mapping `Changelog` | modificado |
| `config/packages/csrf.yaml` | `unpublish` en `stateless_token_ids` | modificado |
| `phpstan.dist.neon` | Ignora `doctrine.associationType` de `iterable|Translations` | modificado |
| `.env`, `.env.test`, `Makefile` (`TEST_CONT`) | `ANNOUNCEMENTS_ENABLED` | modificados |
| `migrations/Version20260923090142.php` | Migración de las dos tablas | nuevo |

## Modelo de dominio

### `Announcement`

`src/Changelog/Domain/Model/Announcement/Announcement.php`. Campos propios: `category`, `date`,
`version`, los traducibles del **idioma por defecto** (`title`, `summary`, `body`, `youtubeVideo`,
`manualFileName`, `manualPath`), el estado (`published`, `publishedAt`, `scheduledAt`,
`notifyOnPublish`), `registeredBy`, `removed`/`removedAt`, `registeredAt`, `updatedAt` y
`translations` (`iterable<AnnouncementTranslation>|Translations`, envuelta una sola vez en
`translations()`, `:163-172`).

- **Colisión de nombres:** `AggregateRoot` ya define `publish(DomainEvent)`; se importa como
  `publish as protected publishEvent` (`:18-20`) y el agregado redefine `recordApplyAndPublish()`
  (`:412-417`).
- **Getters con idioma:** `title()`, `summary()`, `body()`, `youtubeVideo()`, `manualFileName()`,
  `manualPath()`, `hasVideo()`, `hasManual()` aceptan `?Locale`; devuelven la traducción del idioma
  pedido (o de `LocaleGetter::locale()`) y, si no existe, el valor base (`translationIn()`, `:419-424`).
  Ojo: para vídeo y manual, si existe la traducción se devuelve **su** valor aunque sea `null` (no hay
  fallback campo a campo, `:89-108`).
- **Estado derivado** `status()` (`:130-141`): `Published` ⇔ `published`; `Scheduled` ⇔
  `!published && scheduledAt !== null`; `Draft` en el resto.

| Comando | Evento (`eventName`) | Reglas |
|---|---|---|
| `register(..., ?Locale)` `:194` | `AnnouncementRegistered` | Escribe los campos base; con locale además llama a `translate()` |
| `update(..., ?Locale)` `:228` | `AnnouncementUpdated` | Con locale, el evento conserva los valores base actuales y después `translate()`; categoría, fecha y versión siempre se actualizan (son comunes) |
| `translate(..., Locale)` `:260` | `AnnouncementTranslated` | `Translations::add()` inserta o actualiza el idioma |
| `schedule(at, notify)` `:283` | `AnnouncementPublicationScheduled` | `\LogicException` si ya está publicada; sobre una programada reprograma |
| `publish(notify)` `:298` | `AnnouncementPublished` | Idempotente: no registra nada si ya está publicada; limpia `scheduledAt` y `notifyOnPublish` |
| `unpublish()` `:313` | `AnnouncementUnpublished` | No-op sobre un borrador sin programación; limpia `scheduledAt` y `notifyOnPublish` |
| `remove()` `:326` | `AnnouncementRemoved` | Borrado lógico; no borra ficheros |

No hay evento de traducción eliminada: una traducción no se puede quitar. Todos los eventos quedan en
el registro de auditoría por `RecordEventSubscriber` (plantillas de detalle en
`templates/security/admin/events/detail/changelog.*.html.twig`).

### `AnnouncementTranslation`

`src/Changelog/Domain/Model/Announcement/AnnouncementTranslation.php`: repite los seis campos
traducibles, `locale` y `translatable`; getters con el mismo nombre que las propiedades (los usa por
reflexión `DoctrineTranslatableRepositoryTrait`); `update()` copia los seis campos.

### `YouTubeVideo`

`src/Changelog/Domain/Model/Announcement/YouTubeVideo.php`. Guarda solo el id (11 caracteres
`[A-Za-z0-9_-]`). `fromUrl()` acepta `http(s)` y hosts `youtube.com`, `www.youtube.com`,
`m.youtube.com`, `youtu.be` y `www.youtube-nocookie.com`, con rutas `/watch?v=`, `youtu.be/ID`,
`/shorts/ID`, `/embed/ID` y `/live/ID` (`:57-89`). `embedUrl()` apunta a `youtube-nocookie.com`;
`watchUrl()` se usa para rellenar el formulario de edición.

## Flujo de una operación

### Alta (`/{_locale}/admin/announcements/register`)

1. `RegisterController` (`#[IsGranted('ROLE_ADMIN')]` + `denyAccessUnlessGranted(Register)`, `:23-32`)
   crea `RegisterAnnouncementType` y, con `RegisterTrait`, al enviar mapea el PDF subido con
   `MapsManualUploadsTrait::mapManualUpload()` (ruta temporal + nombre original).
2. `RegisterAnnouncementService` (`src/Changelog/Application/Announcement/Register/RegisterAnnouncementService.php`):
   autoriza `Register`, resuelve `LocaleGetter::locale()` y si es el por defecto (`:38-39`), convierte
   la URL con `YouTubeVideo::fromUrl()`, guarda el PDF en `{id}/{locale}.pdf` y llama a `register()`
   con `locale = null` si es el idioma por defecto (`:53-65`).
3. `repository->add()`; flash «Novedad registrada correctamente: {title}» y vuelta al listado.

Consecuencia (Q1, asumida): si se registra desde `/en/…`, los campos base quedan en inglés y además se
crea la traducción EN; los lectores en español verán el texto inglés hasta que alguien edite desde
`/es/…`.

### Edición y traducción (`/{_locale}/admin/announcements/edit/{announcementId}`)

1. `EditController::updateRequestFrom()` (`:72-94`) rellena el formulario con los getters del idioma
   de la URL (con fallback al valor base) salvo el nombre del manual, que se toma **solo** de la
   traducción exacta (o de los campos base en el idioma por defecto).
2. `UpdateAnnouncementService` (`src/Changelog/Application/Announcement/Update/UpdateAnnouncementService.php`):
   calcula el manual actual del idioma editado, lo sustituye si llega uno nuevo o lo quita con
   `removeManual` (`:57-64`), llama a `update()` y persiste. Después borra el fichero anterior
   **solo si** ni los campos base ni otra traducción lo siguen referenciando (`isManualInUse()`,
   `:81-101`).
3. `EditTrait`: **Guardar** se queda en la pantalla; **Guardar y salir** vuelve al listado.

Una traducción nueva nunca hereda el manual base (el nombre sale vacío en el formulario), pero sí
arrastra como valor inicial el vídeo base, porque `youtubeUrl` se rellena con el getter con fallback.

### Publicar / programar (`/{_locale}/admin/announcements/publish/{announcementId}`)

1. `PublishController`: si ya está publicada, flash de aviso «La novedad ya estaba publicada» y
   vuelta al listado (`:41-45`). El formulario se precarga con `scheduledAt` y `notifyOnPublish`.
2. `PublishAnnouncementType`: `publishAt` es `DateTimeType` `single_text` + `html5` con
   `model_timezone = date_default_timezone_get()` y `view_timezone` = zona del navegador
   (`CurrentTimezoneProvider`, `:37-38`). La casilla `notifyUsers` solo existe si el flag está activo
   (`:42-48`).
3. `PublishAnnouncementService` (`src/Changelog/Application/Announcement/Publish/PublishAnnouncementService.php`):
   autoriza `Edit`; si ya está publicada devuelve sin cambios; `notifyUsers = flag && casilla`
   (`:46`); con fecha futura `schedule()` y no despacha nada (`:48-54`); si no, `publish()` y, si
   procede, `DeferredCommand(NotifyAnnouncementRequest, Priority::LOW)` con el usuario que actúa
   (`:56-68`).
4. Flash: «Novedad programada para el {date}» (fecha convertida a la zona del navegador), «Novedad
   publicada. Se está notificando a los usuarios» o «Novedad publicada» (`PublishController::flashFor()`).

### Publicación programada

`CommandSchedule` (`src/Shared/Application/CommandBus/CommandSchedule.php:38-42`) registra
`ScheduledCommand::everyMinutes('changelog:publish-scheduled-announcements', new PublishScheduledAnnouncementsRequest(now), 5)`,
que se encola en la cola `low`. `PublishScheduledAnnouncementsService` autoriza `Action::Admin` con un
sujeto vacío (`:35`), recorre `findScheduledToPublishUntil($request->now)` (no eliminadas, no
publicadas, `scheduled_at <= now`), publica cada una y despacha el aviso diferido si
`flag && notifyOnPublish` (`:40-62`). El `now` se fija cuando `app:schedule:run` construye el
registro, no cuando el worker lo procesa.

### Aviso a los usuarios

`NotifyAnnouncementService` (`src/Changelog/Application/Announcement/Notify/NotifyAnnouncementService.php`)
se ejecuta en el worker de la cola `low`: si la novedad ya no existe o no está publicada devuelve 0
sin avisar (`:38-40`); autoriza `Admin`; recorre **todos** los usuarios (`UserFilter(limit: 0)`,
`:46`) y llama a `SystemNotifier` por el canal `Internal` con asunto `Nueva novedad: {title}` y cuerpo
`@changelog/announcements/published.notification.html.twig` (resumen + enlace «Ver la novedad»), en el
idioma preferido de cada destinatario (`preferredLocale()` o el por defecto, `:47`).

### Lectura (usuarios)

- `Announcement\ListController` (`/{_locale}/announcements`) crea el filtro con
  `status: Published` y `ListUserAnnouncementsType` (sin filtro de estado).
  `ListAnnouncementsService` fuerza además `Published` a quien no pueda `Admin` (`:35-37`).
- `ShowController` (`/{_locale}/announcements/{announcementId}`): `AnnouncementNotFoundException` y
  `UnauthorizedActionException` se traducen en **404**, de modo que un usuario no distingue un
  borrador de una novedad inexistente. El administrador ve la previsualización con el aviso «Esta
  novedad no está publicada y solo la ven los administradores».
- `ManualController` (`/{_locale}/announcements/{announcementId}/manual[?download=1]`):
  `DownloadAnnouncementManualService` resuelve el manual del idioma actual con fallback al base y
  responde con `Content-Disposition` `inline` o `attachment`; sin manual, 404.

### Despublicar y eliminar

GET protegidos con `#[CsrfProtected('unpublish')]` y `#[CsrfProtected('remove')]`; los enlaces llevan
`_token` desde los componentes `List:Action:Unpublish` y `List:Action:Delete`. `UnpublishAnnouncementResponse::wasScheduled`
elige el flash «Programación cancelada» o «Novedad despublicada». Eliminar también existe como acción
masiva (`Admin/Announcement/Bulk/RemoveAction.php`).

## Autorización y ámbito

`AnnouncementAuthorizer` (`src/Changelog/Application/Announcement/AnnouncementAuthorizer.php`):

| Acción | Regla |
|---|---|
| `List` | `true` si `ANNOUNCEMENTS_ENABLED`; si no, solo `canAdmin()` (`:37-44`) |
| `View` | publicada **y** flag activo; en otro caso `canAdmin()` (`:28-35`) |
| `Register`, `Edit`, `Remove`, `History` | `canAdmin()` (`Admin`/`SuperAdmin`) |
| `Admin` | heredado |

No usa `CurrentAccountIdProvider`: el contenido es de plataforma, sin segmentación por cuenta; la
impersonación no cambia lo que se ve. Los controladores de administración llevan además
`#[IsGranted('ROLE_ADMIN')]`, así que `User`/`Owner` reciben 403 en `/admin/announcements/*`.

Efecto del flag `ANNOUNCEMENTS_ENABLED=false`:

- `User`/`Owner` no ven el grupo de menú (`NodeLister.php:269-271`, `:299-301`), reciben 403 en el
  listado y 404 en detalle y manual.
- Los administradores conservan todo (menú incluido: `toAdmins()` no está condicionado).
- No aparece la casilla **Notificar a los usuarios** y los servicios de publicación fuerzan
  `notifyUsers = false`; las programadas se siguen publicando, sin aviso.

## Persistencia y migración

- Tabla `announcements`: columnas base traducibles (`title`, `summary`, `body`, `youtube_video_id`,
  `manual_file_name`, `manual_path`) más `category`, `date`, `version`, `published`, `published_at`,
  `scheduled_at`, `notify_on_publish`, `registered_by`, `removed`, `registered_at`, `updated_at`,
  `removed_at`; índices `idx_announcement_published_date (published, date)` e
  `idx_announcement_scheduled (published, scheduled_at)`.
- Tabla `announcement_translations`: PK `(locale, announcement_id)`, los seis campos traducibles y
  FK a `announcements` con `ON DELETE CASCADE`.
- Mapping `translations`: `cascade-persist` + `cascade-remove`, sin `orphan-removal`,
  `fetch="EXTRA_LAZY"`. `DoctrineAnnouncementRepository::add()` hace `persist` + `addTranslations()`
  (inserta las nuevas y hace `UPDATE` por reflexión de las gestionadas) + `flush`.
- Listados: `leftJoin` de la traducción de `LocaleGetter::locale()` (`:122`); la búsqueda por texto
  mira `trans.title`/`trans.summary` o, si no hay traducción, los campos base (`:128-145`). Orden
  `date` y desempate `registeredAt`. Las eliminadas no se cargan nunca, tampoco en `find()`.
- **Migración:** `migrations/Version20260923090142.php` (alineada con el mapping y con
  `migration.sql`; C1 de la review quedó descartado). **Pendiente de ejecutar con `make migrate`**
  según `review-fix.md`.
- Ficheros: `%app.uploads_dir%/announcements/{id}/{locale}.pdf` (`changelog.announcement_manuals_dir`,
  `services.yaml:5`), fuera del repositorio; el despliegue debe conservar ese directorio. Eliminar una
  novedad no borra sus PDF.

## Configuración y operación

- **`ANNOUNCEMENTS_ENABLED`** (`.env`, por defecto `false`): parámetro `app.announcements_enabled` y
  `bind` de `$announcementsEnabled` en `src/Changelog/Infrastructure/Symfony/DependencyInjection/services.yaml:6,13`;
  `NodeLister` lo recibe con `#[Autowire(param: ...)]`. En test está a `true` en `.env.test` **y** en
  `TEST_CONT` del `Makefile` (necesario por el `env_file` de Docker).
- **`INTERNAL_NOTIFICATIONS_ENABLED`**: si está a `false`, la casilla sigue apareciendo pero
  `DispatchingSystemNotifier` no entrega nada.
- **Cron**: `make sf c="app:schedule:run <username>"` cada 5 minutos, con un usuario **`Admin` o
  `SuperAdmin`**; con otro rol `PublishScheduledAnnouncementsService` lanza
  `UnauthorizedActionException` en el worker y las programadas nunca salen. Retraso máximo ≈ 5 minutos
  más lo que tarde la cola.
- **Workers**: la publicación programada y el aviso masivo viajan por la cola `low`
  (`make queues-low` o `make queues`).
- **Zona horaria**: `assets/js/timezone.js` guarda la zona del navegador en la cookie `timezone`;
  `CookieTimezoneProvider` la usa como `view_timezone` del campo de programación, en el flash y, vía
  `RequestTimezoneSetter`, en todos los filtros `date` de Twig. En base de datos se guarda en la zona
  del servidor (`date_default_timezone_get()`). Las fechas sin hora se pintan con `date('d/m/Y', false)`
  para no desplazarlas de día.
- **PDF**: máximo 20 MB y tipo real `application/pdf` (`File` en los formularios); comprobar
  `upload_max_filesize` y `post_max_size` de PHP.
- **CSRF**: `unpublish` añadido a `stateless_token_ids` (`config/packages/csrf.yaml`).

## Cómo extenderlo

- **Nuevo campo traducible:** añadirlo al agregado (propiedad base + getter con `?Locale` y fallback),
  a `AnnouncementTranslation` (propiedad, getter con el **mismo nombre** y `update()`), a los eventos
  `AnnouncementRegistered`/`Updated`/`Translated` (payload y `rebuildFromPayload`), a los dos mappings,
  a los Request/formularios y a `EditController::updateRequestFrom()`. Crear migración a mano.
- **Nuevo campo común (no traducible):** agregado + `Registered`/`Updated` + mapping de
  `announcements`; nada en la traducción.
- **Nuevo agregado traducible:** copiar este patrón (es la referencia de `CLAUDE.md`), incluida la
  regla de no borrar ficheros por idioma mientras otro idioma los referencie.
- **Nueva transición de estado:** método en el agregado con su evento y `apply`, servicio con
  `denyUnlessCanDoThat`, controlador y, si es un GET con efectos, `#[CsrfProtected]` + componente de
  acción de fila en `templates/components/List/Action/` (no inline) + token en `stateless_token_ids`.
- **Trampas:**
  - El aviso se despacha desde el servicio, no desde un suscriptor, porque el `DeferredCommand`
    necesita el `User` que actúa.
  - En Behat, las aserciones sobre el transporte `async_low` deben ir **antes** de `I follow redirect`
    (el transporte in-memory se vacía entre peticiones).
  - Publicar, despublicar y volver a publicar con la casilla marcada avisa dos veces.
  - El aviso carga todos los usuarios en memoria; con muchos usuarios conviene paginar.

## Tests

- **Unitarios** (`tests/Unit/Changelog/`): `AnnouncementTest` (comandos, estados, traducciones),
  `YouTubeVideoTest`, `AnnouncementRepositoryTest` como batería común de `InMemoryAnnouncementRepositoryTest`
  y `DoctrineAnnouncementRepositoryTest` (SQLite), incluido `testAddUpdatesAnExistingTranslation`, que
  cubre la rama `UPDATE` de `DoctrineTranslatableRepositoryTrait` (B2); tests de cada servicio en
  `Application/Announcement/**` (con `SpySystemNotifier` y spy de comandos diferidos),
  `AnnouncementAuthorizerTest` (con y sin flag), `LocalAnnouncementManualStorageTest`,
  `tests/Unit/Shared/Application/CommandBus/CommandScheduleTest.php`,
  `tests/Unit/Shared/Infrastructure/Timezone/CookieTimezoneProviderTest.php` y `NodeListerTest`.
- **Aceptación** (`tests/Acceptance/Changelog/announcement-*.feature`, pasos en `ChangelogContext`):
  listado y filtros de administración, alta (también en inglés y con URL de YouTube inválida), edición
  y traducción desde `/en/…`, publicación inmediata, con aviso, programada y en la zona del navegador,
  tarea programada, despublicación, borrado con y sin token, listado y detalle de usuario (borrador y
  programada → 404, previsualización del administrador, vídeo, manual).
- Ejecución: `make test-unit options="--filter Announcement"` y
  `make test-acceptance options="tests/Acceptance/Changelog/announcement-publish.feature"` (una feature
  por invocación).
- Hueco conocido: no hay prueba automática contra MySQL real; la prueba manual de alta, traducción y
  detalle tras aplicar la migración sigue pendiente (`plan.md` → *Pendiente para el usuario*).

## Desviaciones y deuda

- Patrón de traducciones sustituido el 2026-09-23, convenciones `create-entity*` y reubicación de
  rutas y plantillas: ver `plan.md` → `## Implementación`. Las secciones de dominio y aplicación del
  cuerpo del plan (pestañas por idioma, `AnnouncementTranslationRequest`, `hasTranslation()`, acciones
  de fila inline…) están superadas por el código.
- Añadido después del plan y no recogido en él: flag `ANNOUNCEMENTS_ENABLED` y la zona horaria del
  navegador (`CurrentTimezoneProvider`).
- `review-fix.md`: M1 (componentes `List:Action:Publish`/`Unpublish`), B1 (`hasTranslation()`
  eliminado) y B2 (test de la rama `UPDATE`) aplicados; C1 descartado (migración ya correcta); Q1
  asumido (el alta en otro idioma deja los campos base en ese idioma). `plan.md:576`, `:788` y `:257`
  siguen contradiciendo el código (pendiente de `feature-planner`).
- Deuda declarada en `## Implementación`: dominio `changelogCategory` sin regla lcfirst + `Type` ni
  plantilla de extracción; `WebContext.php` parte valores por `:`; `AcceptanceClient::followRedirect()`
  no refresca el crawler; `RegisterTrait`/`EditTrait` absorben `UnauthorizedActionException` como
  «Error inesperado».
- Migración `Version20260923090142` pendiente de ejecutar; cron de `app:schedule:run` con usuario
  administrador pendiente de configurar.
