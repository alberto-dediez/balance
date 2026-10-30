# Gestión de notificaciones en bloque — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-30 · **Bounded Contexts:** `Notifications` (más un cambio en `Shared`)

## Qué hace y por qué

El listado "Mis notificaciones" (`/{_locale}/notifications`) permite seleccionar varias filas y aplicarles de una vez **Marcar como leída** o **Eliminar**. Reutiliza el patrón de acciones en bloque de `Shared` (`BulkType` + `BulkRequest` + `BulkTrait`), igual que Usuarios, Cuentas, Solicitudes de firma, Biblioteca y Novedades: un comando por notificación seleccionada, despachado con `CommandBusDispatcher`.

Para poder despachar los casos de uso `MarkNotificationAsRead` y `RemoveNotification` por el bus se adaptaron al contrato `Command`/`CommandHandler` (devuelven ahora un `CommandResponse`). Los controladores existentes (`ShowController`, `RemoveController`) siguen invocando `__invoke` directamente.

Decisiones clave (tabla "Decisiones tomadas" de `plan.md`): solo se actúa sobre las notificaciones de la página actual; un flash por notificación usando el **asunto** (no el UUID); sin confirmación previa; "Marcar todas como leídas" se mantiene; sin migración.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/Notifications/Application/Notification/Read/MarkNotificationAsReadRequest.php` | Ahora `implements Command` | modificado |
| Aplicación | `src/Notifications/Application/Notification/Read/MarkNotificationAsReadResponse.php` | `CommandResponse` con `notificationId` | nuevo |
| Aplicación | `src/Notifications/Application/Notification/Read/MarkNotificationAsReadService.php` | `CommandHandler` (`RegisterHandlerTrait`, `CommandInvokeHandlerTrait`, `isHandled()` en l. 49); `__invoke` devuelve la response | modificado |
| Aplicación | `src/Notifications/Application/Notification/Remove/RemoveNotificationRequest.php` | Ahora `implements Command` | modificado |
| Aplicación | `src/Notifications/Application/Notification/Remove/RemoveNotificationResponse.php` | `CommandResponse` con `notificationId` | nuevo |
| Aplicación | `src/Notifications/Application/Notification/Remove/RemoveNotificationService.php` | Idem para eliminar | modificado |
| Infraestructura | `src/Notifications/Infrastructure/UI/Web/User/Notification/Bulk/MarkAsReadAction.php` | `BulkAction` id `mark-as-read` | nuevo |
| Infraestructura | `src/Notifications/Infrastructure/UI/Web/User/Notification/Bulk/RemoveAction.php` | `BulkAction` id `remove` | nuevo |
| Infraestructura | `src/Notifications/Infrastructure/UI/Web/User/Notification/ListController.php` | `use BulkTrait`, construye `BulkType` (l. 70-80) y pasa `bulkForm` a la vista | modificado |
| Shared | `src/Shared/Infrastructure/UI/Web/CRUD/BulkTrait.php` | Gestiona el formulario enviado pero inválido (l. 48-58) | modificado |
| Vista | `templates/notifications/user/list.html.twig` | `List:Table` con `:bulkForm="bulkForm"` (l. 20) | modificado |
| Traducciones | `translations/messages+intl-icu.{es,en}.xlf` | Siete mensajes nuevos | modificado |
| Tests | `tests/Acceptance/Notifications/notification-bulk.feature`, `NotificationsContext.php`, `notification-list.feature` | Escenarios y pasos nuevos | nuevo / modificado |
| Tests | `tests/Unit/Notifications/Application/Notification/{Read,Remove}/…ServiceTest.php`, `tests/Unit/Notifications/Infrastructure/UI/Web/User/Notification/Bulk/{MarkAsRead,Remove}ActionTest.php` | Unitarios | nuevo / modificado |

Dominio y persistencia: sin cambios (`Notification::markAsRead()` es idempotente; `remove()` es borrado lógico).

## Modelo de dominio

Sin cambios. Se reutilizan los eventos `NotificationRead` y `NotificationRemoved`. Marcar como leída una ya leída no publica evento ni cambia `readAt`, por lo que la acción en bloque la informa como éxito.

## Flujo de una operación

1. El usuario envía el formulario `bulk` por POST a la misma URL del listado (con sus filtros y página).
2. `ListController::__invoke` (`ListController.php:37`) calcula el listado con `recipientId` y canal `Internal` forzados (l. 62-63) y crea `BulkType` con las notificaciones de **esa página** como opciones de `elements` y las acciones `MarkAsReadAction` y `RemoveAction`, en ese orden (l. 70-76).
3. `BulkTrait::bulkActions()` (`BulkTrait.php:17`) hace `handleRequest`. Si es válido: `BulkRequest::commands()` delega en `BulkAction::commands()`. Sin comandos, flash `danger` "Debes seleccionar algún elemento…". Con comandos, por cada uno `CommandBusDispatcher::instance()->dispatch($command, $user)` y flash `success` o `danger` (si salta cualquier `\Exception`).
4. `MarkAsReadAction::commands()` / `RemoveAction::commands()` crean un `MarkNotificationAsReadRequest` / `RemoveNotificationRequest` por elemento `instanceof Notification` y guardan `subjects[id] = subject()` para los mensajes.
5. El bus localiza el handler con `isHandled()`; el servicio busca la notificación (`NotificationNotFoundException`), autoriza con `NotificationAuthorizer` (`View` para leer, `Remove` para eliminar), aplica el cambio, hace `add()` y devuelve la response.
6. El controlador redirige a `$request->getRequestUri()` (l. 79).

Formulario enviado pero inválido (`BulkTrait.php:48-58`, cambio en `Shared` posterior a la review): si el campo `elements` es inválido (un id fuera de la página actual, ajeno o inexistente) se añade el flash `danger` "La selección ha cambiado; revisa los elementos y vuelve a intentarlo"; cualquier otro fallo (p. ej. acción desconocida) da "No se ha podido realizar la acción; revisa el formulario y vuelve a intentarlo". En ambos casos devuelve `true` (redirección) y no se procesa nada. Este cambio afecta a **todos** los listados que usan `BulkTrait`, no solo a notificaciones (el plan original pedía no tocar `Shared`; ver `review.md`, M1 y B3).

## Autorización y ámbito

- Acceso al listado: `#[IsGranted('ROLE_USER')]` y flag `INTERNAL_NOTIFICATIONS_ENABLED` (404 si está a `false`, `ListController.php:43`).
- El destinatario puede ver y eliminar sus notificaciones; `Admin`/`SuperAdmin` también pueden eliminar (`NotificationAuthorizer.php`, `canRemove`, usa `$user->hasRole()` y no `CurrentRolesProvider`: deuda preexistente).
- Una notificación ajena no se puede procesar por el formulario: no es opción válida de `elements`. Y aunque lo fuera, el servicio vuelve a autorizar.

## Persistencia y migración

Sin cambios de esquema; sin migración.

## Configuración y operación

Sin variables ni servicios nuevos: el registro del handler es automático (`Kernel.php` autoconfigura `CommandHandler`). Traducciones: `make translation` regeneró también el `id` del trans-unit "Sistema" (`sYsTeM1` → `jfbi5SU`), inocuo porque la clave es `resname`.

## Cómo extenderlo

Para añadir otra acción en bloque (p. ej. "marcar como no leída", que requeriría antes una transición en el agregado):
1. Hacer el caso de uso `Command`/`CommandHandler` con su `Response`.
2. Crear una clase `BulkAction` en `Bulk/` (patrón de `MarkAsReadAction`).
3. Añadirla a `actions` en `ListController.php:72`.
4. Añadir las traducciones.

Trampas: las casillas se asocian por posición (`bulkForm.elements[loop.index0]` en `templates/components/List/Table.html.twig`), así que el orden de `$response->notifications` debe coincidir con el pintado. La fila final de acciones suma una fila al recuento de filas en Behat. `BulkAction` guarda estado (`subjects`) entre `commands()` y `success()/failure()`: no reutilizar la instancia entre peticiones.

## Tests

- Unitarios: servicios de lectura y eliminación (respuesta, `isHandled`, `handle`, no encontrada, acceso denegado) y las dos acciones en bloque. Ejecutar: `make test-unit options="--filter Notification"`.
- Aceptación: `tests/Acceptance/Notifications/notification-bulk.feature` (marcar varias, marcar todas las pendientes oculta el botón, eliminar varias, campana vacía tras eliminar, envío sin selección, id ajeno, id fuera de página, acción inválida, marcar una ya leída). Ejecutar: `make test-acceptance options="tests/Acceptance/Notifications/notification-bulk.feature"`.
- Quirks: el paso `I submit a form with` asigna casillas por posición; el paso `I send the bulk form with elements … and action …` (`NotificationsContext.php`) permite enviar ids no ofrecidos por el formulario.

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md` (paso POST propio para id ajeno, orden posicional de casillas, `I go to` extra tras el redirect, `id` regenerado de "Sistema").
- `review.md`: 0 hallazgos abiertos (M1, B1, B2, B3 corregidos). M1 y B3 modifican `BulkTrait` pese a la restricción del plan.
- Deuda: `NotificationAuthorizer::canRemove` con `hasRole`; `WebContext` asigna casillas por posición.
