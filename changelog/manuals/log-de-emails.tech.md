# Log de emails enviados con seguimiento del proveedor — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-20 · **Bounded Contexts:** `Security` (agregado `EmailLog`, UI, webhook), `Shared` (puerto `EmailSender` y sus eventos)
- **Revisión:** `review.md` (rev. 4, 0 hallazgos abiertos: 8 corregidos, 1 descartado) · **Entrada previa a la revisión:** `review-input.md`
- **Migración:** `migrations/Version20261020170559.php` (creada por el usuario a partir de `migration.sql`)

## Qué hace y por qué

La plataforma envía emails desde varios flujos (notificaciones, recuperación de contraseña, códigos de segundo factor, firma de documentos, informes de error) y no había forma de saber qué había salido, a quién y si el proveedor lo entregó. Ahora **todo** email que pasa por el puerto `App\Shared\Domain\Model\Email\EmailSender` queda registrado como un agregado `EmailLog` en `Security`, consultable por `SuperAdmin` en *Seguridad → Emails enviados* (`/{_locale}/admin/emails`).

La captura se hace en el único adaptador real del puerto, `SymfonyEmailSender`, sin tocar a los seis emisores: genera un `EmailTrackingId` por envío, lo adjunta al mensaje como cabecera de metadatos `email_log_id`, publica `EmailSent` (ampliado) o el nuevo `EmailSendingFailed` y devuelve el id. Un suscriptor de `Security` (`RecordEmailLogOnEmailSent`) convierte esos eventos en un `EmailLog` con el mismo UUID. Con proveedor SendGrid (transporte API) el estado se enriquece mediante el *Event Webhook* de SendGrid, recibido por un **controlador propio** (`SendgridWebhookController` + `SendgridSignatureVerifier`); el plan preveía `symfony/webhook` + `symfony/remote-event`, pero se sustituyeron en la revisión (hallazgo M1 de `review.md`) porque un evento desconocido rechazaba todo el lote con 406. Con cualquier otro proveedor (SMTP, Mailpit, `null`) el registro se queda en «Enviado» o «Fallido».

Solo se guardan metadatos (destinatario, asunto, plantilla, proveedor, id de mensaje del proveedor); nunca el cuerpo ni los `params`, que contienen OTP, tokens de recuperación y enlaces de firma (decisión «Contenido guardado» del plan). La cronología de cada email la da la auditoría (`RecordedEvent`), consultada desde una pantalla de histórico propia (`admin_emails_history`, hallazgo A2). Los logs se purgan a diario pasados `EMAIL_LOG_RETENTION_DAYS` días.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Shared · Dominio | `src/Shared/Domain/Model/Email/EmailSender.php` | Puerto; `__invoke(...)` devuelve ahora `EmailTrackingId` (antes `void`) | Modificado |
| Shared · Dominio | `src/Shared/Domain/Model/Email/EmailTrackingId.php` | UUID del envío (`AggregateRootIdTrait`); `Security` lo reutiliza como `EmailLogId` | Nuevo |
| Shared · Dominio | `src/Shared/Domain/Model/Email/EmailSent.php` | Payload ampliado con `trackingId`, `provider`, `providerMessageId` | Modificado |
| Shared · Dominio | `src/Shared/Domain/Model/Email/EmailSendingFailed.php` | Evento de fallo del transporte (`emailSendingFailed`): `trackingId`, `recipient`, `subject`, `content`, `provider`, `reason` | Nuevo |
| Shared · Infra | `src/Shared/Infrastructure/Email/SymfonyEmailSender.php` | Usa `TransportInterface` (para obtener el `SentMessage` y su id); cabecera `MetadataHeader('email_log_id')`; publica `EmailSent`/`EmailSendingFailed` | Modificado |
| Shared · Infra | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` | `$mailerDsn: '%env(MAILER_DSN)%'` para `SymfonyEmailSender` | Modificado |
| Shared · Infra | `src/Shared/Infrastructure/Symfony/Event/MaintenanceModeListener.php` | `/webhook` en `ALLOWED_PATHS` | Modificado |
| Shared · App | `src/Shared/Application/CommandBus/CommandSchedule.php` | Tarea diaria `security:purge-email-logs` a las 03:00 | Modificado |
| Shared · Infra | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | Entrada «Emails enviados» en «Seguridad», solo `SuperAdmin` (`:174-180`) | Modificado |
| Shared · UI | `templates/components/List/FilterBar.html.twig` | Traduce valores de filtro con el `choice_translation_domain` del campo (hallazgo B2; afecta a todos los listados) | Modificado |
| Security · Dominio | `src/Security/Domain/Model/EmailLog/EmailLog.php` | Agregado | Nuevo |
| Security · Dominio | `src/Security/Domain/Model/EmailLog/EmailLogId.php`, `EmailLogStatus.php`, `EmailLogFilter.php`, `EmailLogRepository.php` | Id, enum de estado, filtro, puerto | Nuevo |
| Security · Dominio | `src/Security/Domain/Model/EmailLog/EmailLog{Registered,Delivered,Deferred,Bounced,Dropped,Opened,Clicked,SpamReported,Removed}.php` | Eventos de dominio | Nuevo |
| Security · Dominio | `src/Security/Domain/Event/EmailLog/RecordEmailLogOnEmailSent.php` | Suscriptor `EmailSent`/`EmailSendingFailed` → `RegisterEmailLogService` | Nuevo |
| Security · App | `src/Security/Application/EmailLog/EmailLogAuthorizer.php` | `List`/`View`/`History` solo `SuperAdmin` | Nuevo |
| Security · App | `src/Security/Application/EmailLog/Register/*` | Alta del log (sin authorizer) | Nuevo |
| Security · App | `src/Security/Application/EmailLog/Track/*` | `EmailTrackingEventType` + aplicación de un evento del proveedor (sin authorizer) | Nuevo |
| Security · App | `src/Security/Application/EmailLog/List/*` | Listado autorizado | Nuevo |
| Security · App | `src/Security/Application/EmailLog/Purge/*` | Purga programada (`CommandHandler`, sin authorizer) | Nuevo |
| Security · Persistencia | `src/Security/Infrastructure/Persistence/Doctrine/Mapping/EmailLog.EmailLog.orm.xml` | Tabla `email_log` | Nuevo |
| Security · Persistencia | `src/Security/Infrastructure/Persistence/Doctrine/Type/EmailLogIdType.php` | Tipo `email_log_id` (registrado en `config/packages/doctrine.yaml`) | Nuevo |
| Security · Persistencia | `src/Security/Infrastructure/Persistence/Doctrine/Repository/DoctrineEmailLogRepository.php` | Adaptador `dev`/`prod` | Nuevo |
| Security · Persistencia | `src/Security/Infrastructure/Persistence/InMemory/Repository/InMemoryEmailLogRepository.php` | Adaptador `test` | Nuevo |
| Security · Infra | `src/Security/Infrastructure/UI/Web/Webhook/SendgridWebhookController.php` | `POST /webhook/sendgrid` (`webhook_sendgrid`), sin prefijo de locale | Nuevo |
| Security · Infra | `src/Security/Infrastructure/Webhook/SendgridSignatureVerifier.php` | Verificación del *Signed Event Webhook* | Nuevo |
| Security · Infra | `src/Security/Infrastructure/UI/Web/Admin/EmailLog/ListController.php`, `ShowController.php`, `HistoryController.php` | Listado, detalle e histórico | Nuevo |
| Security · Infra | `src/Security/Infrastructure/Symfony/Form/ListEmailLogsType.php` | Filtros del listado | Nuevo |
| Security · Infra | `src/Security/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros `app.email_log_retention_days`, `app.sendgrid_webhook_secret`; binds `$retentionDays`, `$webhookSecret`, `$mailerDsn` | Modificado |
| Plantillas | `templates/security/admin/emails/list.html.twig`, `show.html.twig`, `_status_badge.html.twig` | Pantallas | Nuevo |
| Plantillas | `templates/security/admin/events/detail/security.emailLog*.html.twig` (9) | Frase de cada evento en auditoría/histórico | Nuevo |
| Traducciones | `templates/security/translations/emailLogStatus.html.twig`, `translations/emailLogStatus+intl-icu.{es,en}.xlf`, `translations/messages+intl-icu.{es,en}.xlf` | Dominio `emailLogStatus` y textos nuevos | Nuevo / Modificado |
| Config | `config/routes.yaml` | Excluye `Web/Webhook/` de `security-web`; grupo `security-webhook` sin prefijo | Modificado |
| Config | `config/packages/security.yaml` | `^/webhook/` → `PUBLIC_ACCESS` | Modificado |
| Config | `.env` | `SENDGRID_WEBHOOK_SECRET=`, `EMAIL_LOG_RETENTION_DAYS=180`, nota sobre `sendgrid+smtp` | Modificado |
| Assets | `assets/icons/solar/letter-opened-line-duotone.svg` | Icono del menú | Nuevo |
| Migración | `migrations/Version20261020170559.php` | `CREATE TABLE email_log` | Nuevo |

## Modelo de dominio

### `EmailLog` (`src/Security/Domain/Model/EmailLog/EmailLog.php`)

Agregado con `AggregateRoot` y constructor promovido con valores por defecto (`:15-37`): `recipientEmail`, `recipientName`, `subject`, `template`, `provider`, `providerMessageId`, `status` (`EmailLogStatus`), `statusReason`, `sentAt`, `deliveredAt`, `firstOpenedAt`, `lastOpenedAt`, `openCount`, `firstClickedAt`, `clickCount`, `spamReportedAt`, `lastEventAt`. Derivados: `isTrackable()` (`'sendgrid' === provider`, `:129-132`) y `hasBeenOpened()` (`openCount > 0`).

`EmailLogStatus` (`EmailLogStatus.php`): `Sent`, `Failed`, `Deferred`, `Delivered`, `Bounced`, `Dropped`; `isTerminal()` = `Failed|Bounced|Dropped`; `badgeColor()` = `info` / `warning` / `success` / `danger`.

### Comandos y transiciones

| Comando | Se aplica si el estado actual es | Evento | Efecto |
|---|---|---|---|
| `register(...)` (`:139-168`) | — (solo admite `Sent` o `Failed`; otro → `\InvalidArgumentException`) | `EmailLogRegistered` | Fija todos los campos iniciales |
| `markAsDelivered($at)` (`:170`) | `Sent`, `Deferred` | `EmailLogDelivered` | `status=Delivered`, `deliveredAt`, `statusReason=null` |
| `markAsDeferred($reason, $at)` (`:184`) | `Sent`, `Deferred` | `EmailLogDeferred` | `status=Deferred`, `statusReason` |
| `markAsBounced($reason, $at)` (`:199`) | cualquiera no terminal (`Sent`, `Deferred`, `Delivered`) | `EmailLogBounced` | `status=Bounced`, `statusReason`; conserva `deliveredAt` |
| `markAsDropped($reason, $at)` (`:214`) | `Sent`, `Deferred` | `EmailLogDropped` | `status=Dropped`, `statusReason` |
| `registerOpen($at)` | siempre | `EmailLogOpened` | `firstOpenedAt ??=`, `lastOpenedAt`, `++openCount` |
| `registerClick($at)` | siempre | `EmailLogClicked` | `firstClickedAt ??=`, `++clickCount` |
| `markAsSpamReported($at)` | siempre | `EmailLogSpamReported` | `spamReportedAt` |
| `remove()` | siempre | `EmailLogRemoved` | Nada (el repositorio borra la fila; el evento queda en auditoría) |

Toda transición no permitida **retorna sin registrar evento** (idempotencia ante eventos duplicados o desordenados del proveedor). Todos los `apply*` salvo `Registered` y `Removed` actualizan `lastEventAt` (`:281-329`). Aperturas, clics y spam no cambian el estado y pueden sobrecontar si SendGrid reenvía un evento (riesgo aceptado en el plan).

Todos los eventos extienden `App\Security\Domain\DomainEvent` (contexto `security`), tienen `aggregateRootId()` = `emailLogId` y payload escalar con fechas en `ATOM` (p. ej. `EmailLogRegistered.php:90-104`), de modo que `RecordEventSubscriber` los graba como `RecordedEvent` y la pantalla de histórico los pinta con `templates/security/admin/events/detail/security.emailLog*.html.twig`.

### Eventos de `Shared`

`EmailSent` (`emailSended`) y `EmailSendingFailed` (`emailSendingFailed`) no tienen agregado (`aggregateRootId()` = `null`) y siguen extendiendo `App\Security\Domain\DomainEvent`, como ya hacía `EmailSent`. `rebuildFromPayload` tolera payloads antiguos sin los campos nuevos (`EmailSent.php`, `?? ''` / `?? null`).

## Flujo de una operación

### 1. Envío de un email (cualquier emisor)

1. Un emisor (`DeliverNotificationService`, `RecoverTokenMailer`, `TwoFactorCodeNotifier`, `SignatureOtpNotifier`, `SignatureRequestedMailer`, `EmailErrorReportNotifier`) invoca `EmailSender`; ninguno usa el valor devuelto.
2. `SymfonyEmailSender::__invoke()` (`src/Shared/Infrastructure/Email/SymfonyEmailSender.php:34-80`): `EmailTrackingId::generate()`, construye el `TemplatedEmail`, añade `MetadataHeader('email_log_id', …)` (`:49`; el transporte API de SendGrid lo envía como `custom_args`), y llama a `TransportInterface::send()`.
   - Si el transporte lanza: publica `EmailSendingFailed` con `reason = $e->getMessage()` y **relanza** (`:51-66`): el comportamiento del emisor no cambia.
   - Si no: publica `EmailSent` con `providerMessageId = $sent?->getMessageId()` (`:68-77`) y devuelve el id.
   - El proveedor es el esquema del `MAILER_DSN` sin sufijo de transporte: `explode('+', Dsn::fromString($mailerDsn)->getScheme())[0]` (`:28`) → `sendgrid`, `smtp`, `null`…
3. `RecordEmailLogOnEmailSent::handle()` (`src/Security/Domain/Event/EmailLog/RecordEmailLogOnEmailSent.php:28-46`) construye `RegisterEmailLogRequest` (`Sent` con `providerMessageId`, o `Failed` con `statusReason`; `sentAt = occurredOn` del evento, `:48-64`) y llama a `RegisterEmailLogService`. Cualquier `\Throwable` se captura y va al logger (`:40-45`): registrar nunca rompe el envío.
4. `RegisterEmailLogService` (`src/Security/Application/EmailLog/Register/RegisterEmailLogService.php:17-36`) crea `new EmailLog(EmailLogId::fromString($trackingId))`, `register(...)` y `repository->add()`.

### 2. Evento del proveedor (`POST /webhook/sendgrid`)

`SendgridWebhookController::__invoke()` (`src/Security/Infrastructure/UI/Web/Webhook/SendgridWebhookController.php:26-65`):

1. Cuerpo no JSON (`:29-33`) o primer elemento sin `email`, `timestamp`, `event` y `sg_event_id` (`:35-42`) → **406** `Payload is malformed.`
2. Firma: `SendgridSignatureVerifier::verify(signature, timestamp, rawBody)` con las cabeceras `X-Twilio-Email-Event-Webhook-Signature` y `-Timestamp` (`:44-48`). Si falla → **406** `Signature is wrong.`; si además falta el secreto con proveedor `sendgrid`, `logger->error('SENDGRID_WEBHOOK_SECRET vacío con proveedor sendgrid')` (`:50-56`).
3. Por cada evento del lote, `track()` (`:70-115`): `EmailTrackingEventType::tryFrom($event['event'])` y `email_log_id`; si falta alguno (incluidos `processed`, `unsubscribe`, `group_unsubscribe`…) → `warning` «Evento de SendGrid ignorado» y continúa. Fecha `new \DateTimeImmutable('@' . timestamp)` (UTC), motivo `reason ?? response`.
4. `TrackEmailEventService` (`src/Security/Application/EmailLog/Track/TrackEmailEventService.php:17-40`): `find()`; si no existe → `tracked: false` (el controlador deja un `warning` «Evento de SendGrid sin email registrado»); si existe, `match` al comando del agregado (`bounce`/`deferred`/`dropped` con `reason ?? ''`) y `repository->add()`.
5. Una excepción en un evento se captura y registra (`:98-107`) sin cortar el lote, para que SendGrid no reenvíe el lote y duplique aperturas o clics.
6. Lote válido → **202** aunque haya eventos ignorados (`:64`).

`EmailTrackingEventType` (`Track/EmailTrackingEventType.php`): `delivered`, `deferred`, `bounce`, `dropped`, `open`, `click`, `spamreport`.

### 3. Consulta (UI)

- **Listado** `GET /{_locale}/admin/emails` (`admin_emails`) — `Admin/EmailLog/ListController.php`: `#[IsGranted('ROLE_SUPER_ADMIN')]`, `FilterTrait` con `ListEmailLogsType`, `ListEmailLogsService` (que autoriza `Action::List`, `ListEmailLogsService.php:23`). Plantilla `templates/security/admin/emails/list.html.twig`: columnas Destinatario (ordenable `recipient`), Asunto, Proveedor, Estado (ordenable `status`), Enviado (`default` = `sentAt`), Entregado (`deliveredAt`), Abierto (`firstOpenedAt`, con badge del contador si `openCount > 1`); acciones de fila `List:Action:View` y `List:Action:History` condicionadas a `is_authorized('view'|'history')`. Orden por defecto `sentAt DESC`, 10 por página (`EmailLogFilter.php:13-24`).
- **Detalle** `GET /{_locale}/admin/emails/{emailLogId}` (`admin_emails_show`) — `ShowController.php`: 404 si no existe, `denyAccessUnlessGranted(Action::View)`. Plantilla `show.html.twig`: columna «Email» (Destinatario, Asunto, Origen = plantilla Twig, Proveedor, Id del proveedor) y «Seguimiento» (Estado, Motivo si existe, fechas y contadores); `Alert` «El proveedor configurado no envía información de entrega ni de apertura» si `not trackable`. `TopAction` «Ver auditoría» → `admin_emails_history`.
- **Histórico** `GET /{_locale}/admin/emails/{emailLogId}/history` (`admin_emails_history`) — `HistoryController.php`: `AggregateHistoryTrait` con los nueve `EmailLog*::eventName()` y contexto `security` (`:36-54`), 404 si no existe, `denyAccessUnlessGranted(Action::History)` (`:64-70`), título «Emails enviados > asunto > Histórico», vuelta a `admin_emails`.

Filtros (`ListEmailLogsType.php`; consultas en `DoctrineEmailLogRepository.php:50-117`):

| Campo | Criterio |
|---|---|
| `recipient` | `LIKE %x%` sobre `recipientEmail` **o** `recipientName` |
| `subject` | `LIKE %x%` |
| `status` | igualdad (`EnumType`, dominio `emailLogStatus`) |
| `provider` | **igualdad exacta** (no «contiene») |
| `sentFrom` | `sentAt >= fecha 00:00` |
| `sentUntil` | `sentAt <= fecha 00:00` (los emails enviados durante ese mismo día **no** entran) |

`totalResultsByFilter()` quita el `ORDER BY` antes del `COUNT` (`:135-145`).

### 4. Purga programada

`CommandSchedule` declara `ScheduledCommand::dailyAt(id: 'security:purge-email-logs', command: new PurgeEmailLogsRequest(...), time: '03:00')`. `PurgeEmailLogsService` (`src/Security/Application/EmailLog/Purge/PurgeEmailLogsService.php:24-37`): límite `now - retentionDays` días; por cada log de `findSentBefore($limit)` (`sentAt < límite`, exclusivo, sin paginación) llama a `remove()` (publica `EmailLogRemoved`) y `repository->remove()`. Devuelve `removed`. No llama al authorizer (precedente de `PurgeExpiredSignatureRequestsService`).

## Autorización y ámbito

- `EmailLogAuthorizer` (`src/Security/Application/EmailLog/EmailLogAuthorizer.php`): `canList`, `canView`, `canHistory` → `userHasRole($user, Role::SuperAdmin)` (respeta el rol efectivo con impersonación); `canRegister`, `canEdit`, `canRemove` → `false`. `canHistory` se sobrescribe porque el defecto de `AbstractAuthorizer` concede a `Admin`.
- Los tres controladores llevan además `#[IsGranted('ROLE_SUPER_ADMIN')]`: `Admin`, `Owner` y `User` reciben 403.
- Menú: `NodeLister::toAdmins()` añade «Emails enviados» entre «Firma de documentos» y «Registro» solo si `hasRole(Role::SuperAdmin)` (`NodeLister.php:174-180`).
- Los logs **no** se filtran por cuenta: un email a un firmante externo o un informe de error no pertenecen a ninguna.
- `RegisterEmailLogService` y `TrackEmailEventService` no autorizan: se invocan desde un suscriptor y desde el webhook, sin usuario.
- Los eventos `EmailLog*` también se graban en la auditoría general («Registro», `admin_events`), que sigue siendo accesible a `Admin` (`RecordedEventAuthorizer`); es comportamiento preexistente para cualquier evento (ya ocurría con `emailSended`).
- Webhook: `config/packages/security.yaml` (`^/webhook/` `PUBLIC_ACCESS`), fuera del prefijo `/{_locale}` (`config/routes.yaml`, grupo `security-webhook`) y permitido en modo mantenimiento (`MaintenanceModeListener`, `/webhook`). Su protección es la firma:
  - `SendgridSignatureVerifier` (`src/Security/Infrastructure/Webhook/SendgridSignatureVerifier.php`): proveedor calculado del `MAILER_DSN` como en `SymfonyEmailSender` (`:21`); `isSecretMissing()` = secreto vacío **y** proveedor `sendgrid` (`:24-27`); con secreto vacío y otro proveedor, `verify()` devuelve `true` (local y test); con secreto, exige ambas cabeceras, convierte la clave pública DER en base64 a PEM y verifica `openssl_verify(timestamp . rawBody, base64_decode(signature), key, OPENSSL_ALGO_SHA256)` (`:39-51`).

## Persistencia y migración

Tabla `email_log` (`src/Security/Infrastructure/Persistence/Doctrine/Mapping/EmailLog.EmailLog.orm.xml`; migración `migrations/Version20261020170559.php`):

| Columna | Tipo | Nulo |
|---|---|---|
| `id` | `VARCHAR(36)` (`email_log_id`) | no (PK) |
| `recipient_email`, `recipient_name`, `subject`, `template` | `VARCHAR(255)` | no |
| `provider` | `VARCHAR(50)` | no |
| `provider_message_id` | `VARCHAR(255)` | sí |
| `status` | `VARCHAR(255)` (`enum-type` `EmailLogStatus`) | no |
| `status_reason` | `LONGTEXT` | sí |
| `sent_at` | `DATETIME` (`datetime_immutable`) | no |
| `delivered_at`, `first_opened_at`, `last_opened_at`, `first_clicked_at`, `spam_reported_at`, `last_event_at` | `DATETIME` (`datetime_immutable`) | sí |
| `open_count`, `click_count` | `INT` | no |

Índices: `idx_email_log_sent_at`, `idx_email_log_recipient`, `idx_email_log_status`. Sin backfill: los emails anteriores al despliegue no tienen registro. La migración no lleva el `DEFAULT 0` de `migration.sql` en los contadores (el agregado siempre los inicializa; ver A1 en `review.md`).

## Configuración y operación

| Variable (`.env`) | Defecto | Uso |
|---|---|---|
| `MAILER_DSN` | `smtp://mailer:1025` | Determina el proveedor registrado. Para seguimiento, `sendgrid://KEY@default` o `sendgrid+api://…` (transporte API); con `sendgrid+smtp://` no llegan los `custom_args` y nada se correlaciona |
| `SENDGRID_WEBHOOK_SECRET` | vacío | Clave pública del *Signed Event Webhook*. **Obligatoria con proveedor `sendgrid`**: vacía, todo lote recibe 406 y se registra un error |
| `EMAIL_LOG_RETENTION_DAYS` | `180` | Días de retención de la purga |

Inyección: `src/Security/Infrastructure/Symfony/DependencyInjection/services.yaml` (parámetros `app.email_log_retention_days` con `env(int:…)` y `app.sendgrid_webhook_secret`; binds `$retentionDays`, `$webhookSecret`, `$mailerDsn`) y `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` (`$mailerDsn` de `SymfonyEmailSender`).

Operativa en SendGrid (fuera del código): activar *Event Webhook* hacia `https://<dominio>/webhook/sendgrid` con Delivered, Deferred, Bounced, Dropped, Opened, Clicked y Spam Report; activar *Signed Event Webhook* y copiar la clave pública a `SENDGRID_WEBHOOK_SECRET`; activar *Open Tracking* y *Click Tracking* (sin ellos no llegan `open`/`click`). Activar otros eventos no rompe nada: se ignoran con `warning`.

Purga: la ejecuta `app:schedule:run <username>` (cron existente, `RunScheduledCommandsCommand`) a partir de las 03:00; manualmente, `make sf c="app:schedule:run <username>"` solo la despacha si toca según `ScheduledCommandRecord`.

Logs útiles (canal por defecto): «No se pudo registrar el email enviado» (fallo al persistir el log), «Evento de SendGrid ignorado», «Evento de SendGrid sin email registrado» (p. ej. emails de otro entorno con la misma cuenta de SendGrid, o evento llegado antes del commit del log), «Error al procesar un evento de SendGrid», «SENDGRID_WEBHOOK_SECRET vacío con proveedor sendgrid».

## Cómo extenderlo

- **Nuevo tipo de evento de SendGrid** (p. ej. `unsubscribe`): 1) caso en `EmailTrackingEventType` con el nombre crudo; 2) comando + evento + `apply*` en `EmailLog` (decidir si cambia estado; respetar la idempotencia: retornar sin evento si la transición no procede); 3) rama en el `match` de `TrackEmailEventService`; 4) plantilla `templates/security/admin/events/detail/security.<eventName>.html.twig` y añadir `eventName()` a `HistoryController::allowedEventNames()`; 5) columna y migración si hay dato nuevo; 6) tests en `EmailLogTest`, `TrackEmailEventServiceTest` y `SendgridWebhookControllerTest`.
- **Otro proveedor con webhook** (Mailgun, Brevo…): nuevo controlador en `src/Security/Infrastructure/UI/Web/Webhook/` (ya público, sin locale y permitido en mantenimiento) con su verificador, que mapee sus eventos a `EmailTrackingEventType` y llame a `TrackEmailEventService`; extraer el `email_log_id` de sus metadatos (la cabecera `X-Metadata-email_log_id` la añade siempre `SymfonyEmailSender`; cada bridge de Symfony la traduce a su formato). Ampliar `EmailLog::isTrackable()`, hoy fijo a `sendgrid`.
- **Nuevo emisor de email**: basta con usar `EmailSender`; queda registrado automáticamente. No enviar con `MailerInterface` directamente o no habrá log.
- **Trampas**: `isTrackable()` y `SendgridSignatureVerifier` dependen del esquema del DSN (`sendgrid`); `RecordEmailLogOnEmailSent` traga excepciones a propósito; el contador de aperturas/clics no deduplica `sg_event_id`; la purga no borra la auditoría (`recorded_event`), que crece con cada apertura y clic.

## Tests

Unitarios (`make test-unit options="--filter EmailLog"`, `--filter Sendgrid`, `--filter EmailSender`):

- `tests/Unit/Security/Domain/Model/EmailLog/EmailLogTest.php` (20) — transiciones permitidas y prohibidas, contadores, `register` con estado no inicial.
- `EmailLogRepositoryTest.php` (11, ejecutado por `InMemoryEmailLogRepositoryTest`) — filtros, orden, total, `findSentBefore` exclusivo. Mothers: `EmailLogMother`, `EmailLogRepositoryMother`.
- `tests/Unit/Security/Application/EmailLog/` — `EmailLogAuthorizerTest` (5: `List`/`View`/`History` solo `SuperAdmin`), `RegisterEmailLogServiceTest` (2), `TrackEmailEventServiceTest` (10), `ListEmailLogsServiceTest` (2), `PurgeEmailLogsServiceTest` (1).
- `tests/Unit/Security/Domain/Event/EmailLog/RecordEmailLogOnEmailSentTest.php` (3).
- `tests/Unit/Security/Infrastructure/Webhook/SendgridSignatureVerifierTest.php` (8; par de claves EC P-256 con `openssl_pkey_new`) y `tests/Unit/Security/Infrastructure/UI/Web/Webhook/SendgridWebhookControllerTest.php` (13: tipos, `processed` y `group_unsubscribe` ignorados, lote que sigue tras una excepción, 406 por payload, firma o secreto ausente).
- `tests/Unit/Shared/Domain/Model/Email/EmailSenderTest.php` y `tests/Unit/Shared/Infrastructure/Email/SymfonyEmailSenderFailureTest.php` (2: fallo publica `EmailSendingFailed` y relanza; éxito lleva `providerMessageId` y la cabecera `X-Metadata-email_log_id`). `SpyEmailSender` devuelve `EmailTrackingId::generate()`.

Aceptación (`make test-acceptance options="tests/Acceptance/Security/email-log-list.feature"`, etc.): `email-log-list.feature` (listado, filtro por estado con «Entregado» en la barra, 403 para `Admin` y `User`), `email-log-show.feature` (detalle, histórico, 403, 404), `email-log-webhook.feature` (202/406, aperturas, evento desconocido en el lote), `email-log-tracks-every-sender.feature` (recuperación de contraseña y código de segundo factor quedan registrados), `email-log-purge.feature` (paso directo `When email logs are purged`). Pasos en `tests/Acceptance/Security/SecurityContext.php:739-830`.

Huecos conocidos: no hay escenario Behat con secreto configurado (solo unitario, opción 2 de B3 no aplicada); los `Given` de Behat no graban `RecordedEvent` antes de la primera petición (B4, descartado), por lo que el histórico se prueba con un evento `delivered` del webhook; no hay test del límite del filtro «Enviado hasta».

## Desviaciones y deuda

Respecto a `plan.md` (ver `## Implementación` del plan y `review.md`):

- Webhook con controlador propio y sin `symfony/webhook`/`symfony/remote-event` (M1): las secciones del plan sobre `AsRemoteEventConsumer`, `ConsumeRemoteEventMessage`, `config/packages/webhook.yaml` y `config/routes/webhook.yaml` están obsoletas.
- Secreto del webhook obligatorio con proveedor `sendgrid` (M2).
- Cronología en `admin_emails_history` (A2), no en `/admin/events?aggregateRootId=…`; el botón conserva el texto «Ver auditoría».
- `EmailLogNotFoundException` eliminada (B1); `FilterBar` traduce valores de enum en todos los listados (B2).
- Escenario de envío por notificación sustituido por el código de segundo factor; purga probada con paso directo; `VARCHAR(36)` en el id.
- Deuda abierta fuera de `review.md`: el filtro «Enviado hasta» excluye los emails de ese mismo día (`DoctrineEmailLogRepository.php:98-103`, comparación `<=` contra la fecha a las 00:00) y el filtro «Proveedor» es de igualdad exacta pese a ser un campo de texto libre.
