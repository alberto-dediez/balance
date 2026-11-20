# Marcar como leída desde el widget de notificaciones — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-20 · **Bounded Contexts:** `Notifications`

## Qué hace y por qué

Cada notificación del desplegable de la campana (componente `Topbar:Notifications`) lleva a su derecha un botón "Marcar como leída". Al pulsarlo, el navegador llama por AJAX a la ruta que ya existía (`notification_mark_read`), recibe `204 No Content`, y repinta badge y menú con el HTML de `notifications_widget`. El desplegable no se cierra, no hay toast y, si el usuario está en "Mis notificaciones", la fila afectada se actualiza sin recargar.

No hay cambios de dominio, aplicación, persistencia, configuración ni traducciones: se reutiliza `MarkNotificationAsReadService` (plan `2026-11-06-marcar-desmarcar-notificaciones`). El cambio es de infraestructura web y JavaScript. Las decisiones (ruta GET reutilizada con rama AJAX, repintado con el HTML del widget en vez de tocar el DOM a mano, módulo JS compartido, sin `List:Action:MarkAsRead` por el tooltip de Bootstrap) están en la tabla de decisiones de `plan.md`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Notifications/Infrastructure/UI/Web/User/Notification/MarkAsReadController.php` | Rama `isXmlHttpRequest()` que devuelve `204` (`:45-47`) | Modificado |
| `templates/components/Topbar/Notifications.html.twig` | `data-notifications-widget` en el `<li>` raíz (`:3`); cada notificación pasa a `div` con enlace al detalle + enlace-botón de marcar (`:33-54`) | Modificado |
| `assets/controllers/notification_mark_read_controller.js` | Controlador Stimulus del botón | Nuevo |
| `assets/js/notifications_widget.js` | `applyNotificationsWidget(root, html)`: sustituye badge y contenido del menú | Nuevo |
| `assets/controllers/notifications_live_controller.js` | Pasa a usar `applyNotificationsWidget` (`:102`) | Modificado |
| `importmap.php` | Entrada `notifications-widget` (`:33-35`) | Modificado |
| `assets/styles/components/_topbar-notifications.scss` + `assets/styles/app.scss` | Clases `topbar-notifications__link` / `__body` (en vez de estilos inline) y el import | Nuevo / modificado |
| `tests/Acceptance/Notifications/notification-widget.feature` | Escenarios del botón y de la ruta AJAX | Modificado |
| `tests/Acceptance/Notifications/notification-list.feature:74` | Pasa a `I see 2 links with title "Marcar como leída"` | Modificado |
| `tests/Acceptance/Shared/WebContext.php` | Pasos `I request :path by ajax with csrf token :tokenId` (`:92`) e `I see :count links with title :title` (`:260`) | Modificado |

`_topbar-notifications.scss` contiene también `.topbar-notifications__badge`, usada ya por la plantilla.

## Flujo de una operación

1. La campana pinta, por cada notificación sin leer (`this.latest`, 5 como máximo), el enlace `notification_mark_read` con `_token = csrf_token('mark_as_read')` y los values `id` y `widgetUrl` del controlador `notification-mark-read` (`Notifications.html.twig:43-51`). El `href` real hace que, sin JavaScript, el botón funcione como un enlace normal (marca, flash y redirección al listado).
2. `mark(event)` (`notification_mark_read_controller.js:11`): `preventDefault` y `stopPropagation` (este último evita que Bootstrap cierre el desplegable, que solo ignora clics en `input|select|option|textarea|form`). Un flag `busy` descarta dobles clics.
3. `fetch(href, X-Requested-With: XMLHttpRequest)`. Si el `fetch` lanza o la respuesta no es exactamente `204` (`:36`), hace `window.location.assign(href)`. El `204` estricto es deliberado: con la sesión caducada `fetch` sigue la redirección al login y recibe un 200 que parecería éxito.
4. En servidor, `MarkAsReadController` aplica `#[CsrfProtected('mark_as_read')]` (token stateless, ver `config/packages/csrf.yaml:14`) e `#[IsGranted('ROLE_USER')]`; 404 si `INTERNAL_NOTIFICATIONS_ENABLED` es `false`; `UnauthorizedActionException` pasa a 403 y `NotificationNotFoundException` a 404. Con `X-Requested-With` responde `204` sin flash ni redirección (`:45-47`).
5. `#syncList()` (`:46`): si hay una fila del listado con `[data-notification-mark-read][href*="/{id}/read"]`, la deja como leída (quita `[data-notification-unread]`, `fw-semibold` del asunto, cambia el icono a gris, oculta `mark-read` y muestra `mark-unread`). Mismos hooks que `mark_all_read_controller.js`.
6. `#refreshWidget(root)` (`:72`): pide `widgetUrl` (`notifications_widget`, `WidgetController`, `no-store`) y llama a `applyNotificationsWidget(root, html)`. Con un número de secuencia por widget (`root.dataset.markSequence`) descarta respuestas obsoletas si se marcan dos seguidas. Si `next === 0`, oculta todos los `[data-notifications-mark-all]` del documento (también el del listado).
7. Si el repintado falla (respuesta no `ok`, excepción o `applyNotificationsWidget` devuelve `null`), `#removeItem()` quita solo el elemento (`parentElement.remove()`); el contador se corrige en el siguiente refresco o carga.

`applyNotificationsWidget` (`assets/js/notifications_widget.js:1-25`) devuelve `null` si falta `root` o algún marcador, y `{previous, next}` (lee `data-notifications-count`) si repinta. Sustituye el badge con `replaceWith` y el menú con `innerHTML`: es la excepción admitida a "no usar `innerHTML`" para fragmentos renderizados por Twig (`.claude/ARCHITECTURE.md`, convenciones de interfaz).

`notifications_live_controller.js` usa la misma función y lanza el toast solo si `next > previous` (`:102-106`). Como el botón vive en el módulo y no en el controlador en vivo, funciona con `LIVE_NOTIFICATIONS_ENABLED=false`.

## Autorización y ámbito

La del servicio existente: `Action::Edit`, solo el destinatario (`NotificationAuthorizer`). El widget solo lista notificaciones del usuario autenticado, así que el botón no se condiciona. No hay scoping por cuenta ni efecto de la impersonación. Sin el token (cabecera `Sec-Fetch-Site: same-origin` o `_token`) la respuesta es 403 y no cambia el estado.

## Persistencia y migración

Sin cambios. Migración: no.

## Configuración y operación

Sin variables nuevas. Afectan: `INTERNAL_NOTIFICATIONS_ENABLED` (404 en la ruta y componente deshabilitado) y `LIVE_NOTIFICATIONS_ENABLED` (solo el polling; el botón funciona igual). Hay que compilar/servir assets como siempre; el importmap ya incluye el módulo nuevo. Sin claves de traducción nuevas ("Marcar como leída" ya existía).

## Cómo extenderlo

- Otra acción por notificación en el widget: añadir un enlace-botón en el `div` de `Notifications.html.twig` con su controlador Stimulus y reutilizar `applyNotificationsWidget` para repintar.
- Cambiar el tamaño del widget (`latest(5)`): el repintado ya trae las nuevas, no hay que tocar JS.
- No usar `List:Action:*` dentro del desplegable: llevan `data-bs-toggle="tooltip"` y el tooltip quedaría flotando al eliminar el elemento (`assets/js/theme.js`).
- Un enlace no puede contener a otro: por eso el elemento es un `div` con dos enlaces hermanos.

## Tests

- Behat (`notification-widget.feature`): botón presente en el widget con notificaciones y ausente cuando está vacío; `204` y estado leído al llamar por AJAX con token; las demás se conservan; 403 sin token, 403 con otro usuario, 404 con id desconocido. `notification-list.feature:74` cuenta 2 enlaces "Marcar como leída" (fila + widget).
- Ejecutar: `make test-acceptance options="tests/Acceptance/Notifications/notification-widget.feature"` (y `notification-list.feature`).
- Sin cobertura automática del JavaScript (Stimulus): repintado, desplegable abierto, sincronización de la fila, sesión caducada y doble clic se prueban a mano.

## Desviaciones y deuda

- Paso de test añadido `I see :count links with title :title` y cambio en `notification-list.feature:74` (declarado en `## Implementación` de `plan.md`).
- Respecto al plan, el código final incluye correcciones de `review.md` (todas aplicadas, 0 abiertas): `204` estricto (M1), secuencia anti-carrera al marcar dos seguidas (M2), eliminación del target `menu` sin uso (B1) y clases SCSS en vez de estilos inline (B2). Ver `review.md`.
