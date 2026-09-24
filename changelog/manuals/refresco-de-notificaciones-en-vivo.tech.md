# Refresco de notificaciones en vivo — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-23 (correcciones de review el 2026-09-24) · **Bounded Contexts:** `Notifications` (infraestructura web y assets), `Shared` (reutiliza `CsrfProtected`)
- **Revisión:** `review.md` (revisión 1) y `review-fix.md` (A1, M1, B2 y D1 aplicados; B1 pendiente de prueba manual; B3 asumible; Q1 cerrada), misma carpeta

## Qué hace y por qué

El widget de notificaciones de la barra superior (campana, contador de no leídas y las cinco últimas
no leídas) solo reflejaba el estado del momento en que se cargó la página. Ahora se **refresca solo
mediante polling**: mientras la pestaña está visible, un controlador Stimulus pide cada N segundos
(30 por defecto) el componente `Topbar:Notifications` ya renderizado por el servidor y sustituye en
el DOM el badge y el interior del desplegable. Si el contador de no leídas recibido es mayor que el
actual, muestra un toast "Tienes una nueva notificación".

Decisiones clave (tabla *Decisiones tomadas* del plan):

- **Polling, no Mercure/SSE**: producción no corre en Docker ni tiene hub Mercure.
- **HTML renderizado en servidor**, no JSON: no se duplica maquetación, traducciones ni formato de
  fechas en JavaScript. El endpoint devuelve el mismo componente Twig que pinta la topbar.
- **Solo pestaña visible**: al ocultarse se para el temporizador; al volver se refresca al instante.
  Cada pestaña se gestiona sola, sin sincronización explícita entre pestañas.
- **Nunca con el desplegable abierto**: el refresco se pospone hasta `hidden.bs.dropdown`.
- **Toast por aumento del contador**, no por cambio del id de la última notificación (evita toasts
  falsos al leer en otra pestaña).
- **Sin cambios de dominio, aplicación ni persistencia**: `UnreadNotificationsProvider` ya resolvía
  lo necesario.

Como corrección de la review (`review-fix.md` A1 + D1), el POST "Marcar todas como leídas", que
forma parte del propio widget, pasó a exigir un token CSRF de **sesión** (`mark_all_read`).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Config | `.env:103-104` | `LIVE_NOTIFICATIONS_ENABLED=true`, `LIVE_NOTIFICATIONS_POLLING_SECONDS=30` | modificado |
| Config | `src/Notifications/Infrastructure/Symfony/DependencyInjection/services.yaml:3-4,25-26,40-42` | Parámetros `app.live_notifications_*`, inyección en el componente y en `WidgetController` | modificado |
| Componente | `src/Notifications/Infrastructure/UI/Web/Component/Topbar/Notifications.php` | `liveEnabled()` y `pollingSeconds()` (suelo de 5 s) | modificado |
| Plantilla | `templates/components/Topbar/Notifications.html.twig` | Raíz única `<li>` con `data-controller="notifications-live"` y sus *values*; hooks `data-notifications-count`, `data-notifications-menu`; targets `toggle` y `menu`; `_token` en el formulario de marcar todas | modificado |
| Plantilla | `templates/components/Topbar.html.twig:45` | Quita el `<li>` envolvente (ahora lo pinta el componente) | modificado |
| Controlador | `src/Notifications/Infrastructure/UI/Web/User/Notification/WidgetController.php` | `GET /{_locale}/notifications/widget` (`notifications_widget`) | nuevo |
| Plantilla | `templates/notifications/user/widget.html.twig` | Una línea: `<twig:Topbar:Notifications>` | nuevo |
| Controlador | `src/Notifications/Infrastructure/UI/Web/User/Notification/MarkAllReadController.php:21` | `#[CsrfProtected('mark_all_read')]` (review A1) | modificado |
| Plantilla | `templates/notifications/user/list.html.twig:58` | `_token` en el formulario de marcar todas (review A1) | modificado |
| JS | `assets/controllers/notifications_live_controller.js` | Polling, sustitución de DOM y toast | nuevo |
| JS | `assets/js/toast.js` | `showToast(message, type = 'success')`, extraído de `mark_all_read_controller.js` | nuevo |
| JS | `assets/controllers/mark_all_read_controller.js` | Usa `showToast`; envía `FormData` (con `_token`); sin comentarios (review B2) | modificado |
| Assets | `importmap.php:27-29` | Módulo local `toast` | modificado |
| i18n | `translations/messages+intl-icu.{es,en}.xlf` | Clave `Tienes una nueva notificación` | modificado |
| Tests | `tests/Unit/Notifications/Infrastructure/UI/Web/Component/Topbar/NotificationsTest.php` | Flags y suelo del intervalo | nuevo |
| Tests | `tests/Acceptance/Notifications/notification-widget.feature` | Endpoint del widget | nuevo |
| Tests | `tests/Acceptance/Notifications/notification-list.feature:21-39` | Marcar todas con token real y 403 sin token | modificado |
| Tests | `tests/Acceptance/Shared/WebContext.php:75-79` | Paso `When I press :text` (envía el formulario del botón) | modificado |

## Modelo de dominio

Sin cambios. La lectura se apoya en
`src/Notifications/Application/Notification/Unread/UnreadNotificationsProvider.php`: `count()` y
`latest(5)` consultan el repositorio por el usuario de `LoggedUser` y devuelven `0` / `[]` si no hay
usuario (`:19-42`). Cada polling cuesta, por tanto, dos consultas ligeras (`countUnreadByRecipient` y
`findUnreadByRecipient`).

## Flujo de una operación

### Render inicial (cualquier página autenticada)

1. `templates/components/Topbar.html.twig:45` pinta `<twig:Topbar:Notifications>`.
2. `Notifications::enabled()` (`Notifications.php:21-24`) decide si se pinta algo
   (`INTERNAL_NOTIFICATIONS_ENABLED`). Si es `false`, no queda ningún `<li>` vacío.
3. Si `liveEnabled()` (`:26-29`, ambos flags a `true`), el `<li>` raíz lleva
   `data-controller="notifications-live"` con `url` = `path('notifications_widget')` (lleva el
   `_locale` de la página), `interval` = `pollingSeconds()` (`max(5, …)`, `:31-34`) y `new-message`
   = texto traducido en servidor (`Notifications.html.twig:2-8`).
4. El badge expone el contador exacto en `data-notifications-count` (`:12-18`); el texto visible se
   limita a `99+`.

### Ciclo de refresco (`assets/controllers/notifications_live_controller.js`)

- `connect()` (`:14-26`): inicializa `pending` y `sequence`, escucha `visibilitychange` en `document`
  y `hidden.bs.dropdown` en el `<li>`, y arranca el temporizador.
- `#start()` (`:35-41`): solo si la pestaña está visible; `setInterval` con
  `Math.max(5, intervalValue) * 1000` (segundo suelo, en cliente).
- `#onVisibilityChange()` (`:48-57`): al volver a visible, refresca de inmediato y rearma; al
  ocultarse, `#stop()`.
- `#refresh()` (`:63-119`):
  1. Si el desplegable está abierto (`#isOpen()`, `aria-expanded="true"` en el target `toggle`,
     `:59-61`) marca `pending = true` y sale; `onHidden` lo relanza al cerrarse (`:18-20`).
  2. Incrementa `this.sequence` (`:72`) y hace `fetch` con `X-Requested-With: XMLHttpRequest` y
     `credentials: 'same-origin'` (`:77-80`). Errores de red y respuestas `!ok` se ignoran.
  3. **Guardas tras el `await`** (review M1, `:91-99`): descarta la respuesta si ya no es la última
     petición lanzada; si el desplegable se abrió durante la petición, deja `pending = true` y no
     aplica nada.
  4. Parsea con `DOMParser` y exige `[data-notifications-badge]` y `[data-notifications-menu]`
     (`:101-107`). Si la sesión caducó, `fetch` sigue la redirección al login, esos nodos no existen y
     no se toca nada.
  5. Sustituye el badge (`replaceWith`) y el `innerHTML` del menú (target `menu`), **no** el toggle
     ni el `.dropdown-menu`, porque Bootstrap 5.3 cachea el elemento del menú en su instancia
     `Dropdown` (`:113-114`). Stimulus conecta el `mark-all-read` que llega en el HTML nuevo.
  6. Si `next > previous` (contadores de `data-notifications-count`), `showToast(newMessage,
     'primary')` (`:116-118`).

### Endpoint

`WidgetController.php:12-28`: `GET`, `#[IsGranted('ROLE_USER')]`, 404 si
`INTERNAL_NOTIFICATIONS_ENABLED=false`, renderiza `@notifications/user/widget.html.twig` y fija
`Cache-Control: no-store`. La ruta se registra sola por el recurso `notifications-web` de
`config/routes.yaml` (atributos, prefijo `/{_locale}`). Es un GET sin efectos: no lleva CSRF.

### Marcar todas como leídas (interacción con el widget)

- `MarkAllReadController.php:19-47`: `POST /{_locale}/notifications/read-all`, `ROLE_USER`,
  `#[CsrfProtected('mark_all_read')]`. `CsrfProtectedListener`
  (`src/Shared/Infrastructure/Symfony/Event/CsrfProtectedListener.php:21-32`) lee `_token` de la query
  o del cuerpo y responde 403 si no es válido.
- `mark_all_read` **no** está en `stateless_token_ids` (`config/packages/csrf.yaml`), así que lo valida
  el gestor de tokens de sesión. Motivo (review D1): con el token stateless, Symfony exige
  double-submit en los POST de una sesión cuyo login ya lo usó, y el POST fallaba siempre en
  navegador.
- `mark_all_read_controller.js:12-19` envía `new FormData(this.element)` (incluye `_token`); si el
  `fetch` falla o no es `ok`, hace `submit()` normal del formulario.
- Tras marcar en cliente, el badge se oculta pero su `data-notifications-count` no cambia; en el
  siguiente polling el servidor devuelve `0`, `next > previous` es falso y no hay toast falso. Caso
  límite aceptado en el plan: si entre medias llega una nueva, no se muestra su toast (el widget sí se
  actualiza).

## Autorización y ámbito

- Endpoint del widget: `ROLE_USER`; no hay *subject* que autorizar con `NotificationAuthorizer`. Solo
  devuelve notificaciones del usuario autenticado vía `UnreadNotificationsProvider`.
- Aplica a todos los roles autenticados (Usuario, Propietario, Administrador, Super administrador).
  Las notificaciones son del **usuario**, no de la cuenta: la impersonación de cuenta no cambia lo que
  muestra el widget.
- Anónimo: en la aplicación real, 302 al login (firewall `main`); en Behat (`KernelBrowser`), 403.
- Flags: `INTERNAL_NOTIFICATIONS_ENABLED=false` oculta el widget y el endpoint responde 404;
  `LIVE_NOTIFICATIONS_ENABLED=false` pinta el widget sin `data-controller` (sin polling) y el endpoint
  sigue existiendo.

## Persistencia y migración

Sin cambios de esquema. No hay migración.

## Configuración y operación

| Variable | `.env` | Parámetro | Efecto |
|---|---|---|---|
| `LIVE_NOTIFICATIONS_ENABLED` | `true` | `app.live_notifications_enabled` (`env(bool:…)`) | Activa el polling |
| `LIVE_NOTIFICATIONS_POLLING_SECONDS` | `30` | `app.live_notifications_polling_seconds` (`env(int:…)`) | Intervalo; mínimo efectivo 5 s (servidor y cliente) |

- Ambas sin `default:` en `services.yaml`, igual que `INTERNAL_NOTIFICATIONS_ENABLED`: deben existir en
  el entorno. Producción carga `.env` y después `.env.local` (confirmado en `review-fix.md` Q1).
- Carga: una petición por pestaña visible y por intervalo. Si hiciera falta, subir
  `LIVE_NOTIFICATIONS_POLLING_SECONDS` sin desplegar código.
- Para ver notificaciones nuevas en desarrollo hace falta el worker de la cola donde se entregan
  (p. ej. `make queues-low` al enviar desde `/es/admin/notifications/send`).
- Sin colas, comandos ni tareas programadas nuevas. Sin dependencias nuevas (el módulo `toast` es local
  en `importmap.php`).

## Cómo extenderlo

- **Refrescar otra zona con el mismo patrón**: renderizar el componente en un endpoint GET con
  `Cache-Control: no-store`, dar a la plantilla una raíz única con `data-controller` y *values*, y
  sustituir en cliente solo los nodos que no cachea Bootstrap (marcarlos con `data-*`).
- **Añadir un disparador de refresco** (p. ej. `EventSource` si algún día hay hub Mercure): llamar a
  `#refresh()` desde el nuevo evento; las guardas de desplegable abierto y de `sequence` ya cubren
  solapes y respuestas obsoletas.
- **Mostrar otro toast**: `import { showToast } from 'toast'` y `showToast(texto, tipo)` (tipo = clase
  `text-bg-*` de Bootstrap).
- **Trampas**:
  - No sustituir el `<a data-bs-toggle="dropdown">` ni el `.dropdown-menu`: rompe la instancia
    `Dropdown` de Bootstrap.
  - Mantener exactas las clases del `<li>` (`nav-item nav-icon-hover dropdown`) para no cambiar el
    aspecto del tema.
  - Cualquier cambio en la maqueta del widget debe conservar `data-notifications-badge`,
    `data-notifications-count` y `data-notifications-menu`: si faltan, el JS deja de refrescar en
    silencio.
  - Un POST con efectos en el widget necesita token de **sesión**, no stateless (ver D1).

## Tests

- **Unitarios** — `NotificationsTest.php`: `liveEnabled()` con cada flag y su valor por defecto;
  suelo de 5 s en `pollingSeconds()`.
- **Aceptación** — `notification-widget.feature`: widget con una notificación (200, asunto y "Marcar
  todas como leídas"), widget vacío ("No tienes notificaciones sin leer", sin botón) y anónimo (403
  en Behat). `notification-list.feature`: marcar todas pulsando el botón con el token real del HTML
  (`When I press`) y 403 sin token. Todos los escenarios autenticados renderizan la topbar, así que
  una plantilla rota del componente los rompe.
- **Ejecución**:
  - `make test-unit options="--filter NotificationsTest"`
  - `make test-acceptance options="tests/Acceptance/Notifications/"`
- **Huecos**: no hay runner de tests JS; el controlador Stimulus (intervalo, visibilidad, desplegable
  abierto, toast, `FormData` con token) solo se verifica a mano (`review-fix.md` B1, pendiente). La
  regresión exacta de D1 (login por formulario + POST) no es reproducible en `KernelBrowser`. La
  redirección al login del anónimo no está cubierta en Behat.

## Desviaciones y deuda

Detalle en `plan.md` (`## Implementación`), `review.md` y `review-fix.md`:

- Escenario anónimo con 403 en lugar de redirección al login (limitación del entorno Behat).
- `MarkAllReadController` y `list.html.twig` estaban *Fuera de alcance* en el plan y se modificaron
  por la review (A1/D1); el plan no se ha actualizado.
- Prueba manual en navegador (paso 8 del plan + casos de M1 y A1/D1) pendiente (B1).
- Estilos inline preexistentes en `Notifications.html.twig:13,39` (B3, asumible, pendiente de
  validación del usuario).
