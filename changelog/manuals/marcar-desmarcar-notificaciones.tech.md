# Marcar / desmarcar notificaciones como leídas — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-06 · **Bounded Contexts:** `Notifications` (más un cambio de configuración en `config/packages/csrf.yaml`)

## Qué hace y por qué

Hasta ahora una notificación solo podía pasar a leída (al abrir el detalle, con "Marcar todas como leídas" o con la acción en bloque) y nunca volver a no leída. Se añade la transición inversa en el agregado (`Notification::markAsUnread()` + evento `NotificationUnread`), un caso de uso propio, y tres puntos de entrada en "Mis notificaciones": acción de fila alternante (leída/no leída), acción en bloque "Marcar como no leída" y entrada "Marcar como no leída" en el menú "Acciones" del detalle.

Decisiones clave (tabla "Decisiones tomadas" del plan): dos casos de uso y dos controladores explícitos en lugar de un "toggle" (idempotentes ante doble clic o pestaña obsoleta); acciones de fila por GET protegidas con `#[CsrfProtected]`; redirección a `notifications` para conservar filtro y página (guardados en sesión).

Cambio posterior a la implementación inicial (hallazgo M2 de `review.md`): el permiso para cambiar el estado de lectura no es `View` sino `Edit`, y `Edit` solo lo tiene el destinatario. Un Admin/SuperAdmin puede ver una notificación ajena pero no cambiar su estado de lectura.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Notifications/Domain/Model/Notification/Notification.php` | `markAsUnread()` (l.170) y `applyNotificationUnread()` (l.222) | modificado |
| Dominio | `src/Notifications/Domain/Model/Notification/NotificationUnread.php` | Evento `notificationUnread`, payload `{notificationId}` | nuevo |
| Aplicación | `src/Notifications/Application/Notification/Unread/MarkNotificationAsUnread{Request,Response,Service}.php` | Caso de uso (Command + CommandHandler) | nuevo |
| Aplicación | `src/Notifications/Application/Notification/NotificationAuthorizer.php` | `canEdit()` (l.36) pasa de `false` a "es el destinatario" | modificado |
| Aplicación | `src/Notifications/Application/Notification/Read/MarkNotificationAsReadService.php` | Autoriza con `Action::Edit` (antes `View`) | modificado |
| Infraestructura | `.../UI/Web/User/Notification/MarkAsReadController.php` | GET `/notifications/{id}/read` (`notification_mark_read`) | nuevo |
| Infraestructura | `.../UI/Web/User/Notification/MarkAsUnreadController.php` | GET `/notifications/{id}/unread` (`notification_mark_unread`) | nuevo |
| Infraestructura | `.../UI/Web/User/Notification/Bulk/MarkAsUnreadAction.php` | `BulkAction` `mark-as-unread` | nuevo |
| Infraestructura | `.../UI/Web/User/Notification/ListController.php` | Registra la acción en bloque entre "Marcar como leída" y "Eliminar" (l.72-76) | modificado |
| Infraestructura | `.../UI/Web/User/Notification/ShowController.php` | Solo marca como leída si el usuario tiene `Edit`; 404 si no existe | modificado |
| Infraestructura | `.../UI/Web/User/Notification/RemoveController.php` | `NotificationNotFoundException` → 404 | modificado |
| Infraestructura | `src/Notifications/Infrastructure/Symfony/DependencyInjection/services.yaml` | `$internalNotificationsEnabled` para los dos controladores nuevos | modificado |
| Configuración | `config/packages/csrf.yaml:14-15` | `mark_as_read` y `mark_as_unread` en `stateless_token_ids` | modificado |
| UI | `templates/components/List/Action/MarkAsRead.html.twig`, `MarkAsUnread.html.twig` | Acciones de fila con `_token` | nuevos |
| UI | `templates/notifications/user/list.html.twig:46-53` | Las dos acciones en la celda de acciones, con `d-none` según `element.isRead` | modificado |
| UI | `templates/notifications/user/show.html.twig:10-16` | `TopAction` "Marcar como no leída", condicionado a `is_authorized('edit')` | modificado |
| UI | `templates/security/admin/events/detail/notifications.notificationUnread.html.twig` | Detalle del evento en el histórico de eventos | nuevo |
| UI | `assets/controllers/mark_all_read_controller.js:62-66` | Tras "Marcar todas como leídas" (AJAX) oculta las acciones `data-notification-mark-read` y muestra las `data-notification-mark-unread` | modificado |
| i18n | `translations/messages+intl-icu.{es,en}.xlf` | Seis textos nuevos | modificado |

## Modelo de dominio

- `Notification::markAsUnread()` es idempotente: si `readAt` ya es `null` retorna sin registrar nada (`Notification.php:170-173`); si no, `recordApplyAndPublish(new NotificationUnread($this->id()))`.
- `applyNotificationUnread()` deja `readAt = null` (`Notification.php:222-225`). `readAt` ya era `nullable` en el mapping, por lo que no hay migración.
- `NotificationUnread` hereda de `App\Notifications\Domain\DomainEvent` (contexto `notifications`); `rebuildFromPayload()` lo reconstruye desde el audit log.
- Simétrico a `markAsRead()` (l.156) y a `Announcement::unpublish()` de `Changelog`.

## Flujo de una operación

**Acción de fila (leída / no leída).**
1. El listado pinta las dos acciones; la que no corresponde lleva `d-none` (`list.html.twig:46-53`). El componente añade `_token=<csrf_token('mark_as_unread'|'mark_as_read')>` a la URL (`MarkAsUnread.html.twig:3-5`).
2. `MarkAsUnreadController` (`#[CsrfProtected('mark_as_unread')]`, `#[IsGranted('ROLE_USER')]`) devuelve 404 si `internalNotificationsEnabled` es falso (l.31) y llama a `MarkNotificationAsUnreadService`.
3. El servicio busca la notificación (`NotificationNotFoundException` si no existe, l.34-36), autoriza `SecurityAction::Edit` (l.38), llama a `markAsUnread()` y persiste con `add()`.
4. El controlador traduce `UnauthorizedActionException` → 403 y `NotificationNotFoundException` → 404, añade el flash `success` y redirige a la ruta `notifications` (l.43-45). El filtro con página se conserva porque `FilterTrait` lo guarda en sesión.
5. `MarkAsReadController` es idéntico con `MarkNotificationAsReadService` y el token `mark_as_read`.

**Acción en bloque.** `ListController` construye el `BulkType` con `[MarkAsReadAction, MarkAsUnreadAction, RemoveAction]`. `MarkAsUnreadAction::commands()` emite un `MarkNotificationAsUnreadRequest` por notificación e ignora elementos que no sean `Notification`; `success()`/`failure()` usan el asunto (`MarkAsUnreadAction.php:52-64`). Cada comando pasa por el mismo servicio y su autorización.

**Detalle.** `ShowController` carga la notificación (404 si no existe), exige `View` y solo invoca `MarkNotificationAsReadService` si `canDoThat(Edit)`: un administrador que abre una notificación ajena la ve sin marcarla como leída. La plantilla muestra el `TopAction` "Marcar como no leída" solo si `notification | is_authorized('edit')`; apunta a `notification_mark_unread` con `csrf_token('mark_as_unread')` y, tras la acción, el usuario aterriza en el listado.

**Efecto en la campana.** `UnreadNotificationsProvider` consulta `readAt IS NULL`; al marcar como no leída la notificación vuelve a contar en el badge y en el desplegable, y reaparece "Marcar todas como leídas" (`list.html.twig` bloque `list_actions`, `unreadCount > 0`). No hay JS nuevo salvo el ajuste de `mark_all_read_controller.js`.

## Autorización y ámbito

| Acción | Quién puede |
|---|---|
| `View` (`NotificationAuthorizer::canView`, l.19) | Destinatario, Admin y SuperAdmin |
| `Edit` (`canEdit`, l.36) — marcar leída / no leída | Solo el destinatario (`recipientId()->equals($user->id())`) |
| `Remove` | Destinatario, Admin y SuperAdmin |

Un Admin/SuperAdmin que intenta `/read` o `/unread` sobre una notificación ajena recibe 403. Las rutas exigen `ROLE_USER` y el token CSRF (sin token → 403, gestionado por el listener de `CsrfProtected`). Con `INTERNAL_NOTIFICATIONS_ENABLED=false` las dos rutas devuelven 404 (el valor llega por `services.yaml`). Las notificaciones no dependen de la cuenta efectiva, por lo que la impersonación no interviene (el destinatario se compara con `$user->id()`).

## Persistencia y migración

Sin cambios de esquema ni migración: `readAt` es nullable en `Notification.Notification.orm.xml`. El evento se guarda en el audit log como cualquier otro del BC.

## Configuración y operación

- `config/packages/csrf.yaml:14-15`: los ids `mark_as_read` y `mark_as_unread` deben estar en `stateless_token_ids` o el token de los GET no valida (igual que `remove` y `unpublish`).
- Sin comandos de consola, colas ni tareas programadas nuevas.

## Cómo extenderlo

- Otra acción de lectura sobre `Notification`: nuevo evento + método en el agregado con `apply`, caso de uso con `Action::Edit`, controlador GET con `#[CsrfProtected('<id>')]`, id añadido a `stateless_token_ids` y, si procede, `BulkAction` en `ListController`.
- Trampa: el id del token del componente (`csrf_token('mark_as_unread')`) y el del atributo del controlador deben coincidir.
- Trampa: las acciones de fila se alternan con la clase `d-none`; cualquier JS que cambie el estado de lectura sin recargar debe tocar también los atributos `data-notification-mark-read` / `data-notification-mark-unread` (como hace `mark_all_read_controller.js`).

## Tests

- Unitarios: `tests/Unit/Notifications/Domain/Model/Notification/NotificationTest.php` (transición, idempotencia, volver a marcar tras desmarcar); `tests/Unit/Notifications/Application/Notification/Unread/MarkNotificationAsUnreadServiceTest.php` (destinatario, no destinatario, admin ajeno, no encontrada, idempotencia, `isHandled`); `NotificationAuthorizerTest`; `MarkNotificationAsReadServiceTest`; `tests/Unit/Notifications/Infrastructure/UI/Web/User/Notification/Bulk/MarkAsUnreadActionTest.php`.
- Aceptación (Behat): `tests/Acceptance/Notifications/notification-list.feature` (acción de fila, visibilidad según estado, sin token → 403, usuario y admin ajenos → 403, el admin no marca como leída al abrir, detalle, filtro conservado, 404), `notification-bulk.feature` (bloque y idempotencia), `notification-widget.feature` (vuelve a la campana).
- Ejecución: `make test-unit options="--filter Unread"`; `make test-acceptance options="tests/Acceptance/Notifications/notification-list.feature"`; o `make test-parallel`.

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: ids de token añadidos a `csrf.yaml`; paso extra `When I go to "/es/notifications"` tras `I follow redirect` en el escenario del filtro.
- `review.md`: 0 hallazgos abiertos (M1, M2, M3, B1, B2, B3 corregidos). Los cambios de M2 (permiso `Edit`) y M3 (botón del detalle condicionado) están ya reflejados arriba.
- Deuda ajena señalada en el plan: `PublishController` de `Changelog` sin `#[CsrfProtected]`.
