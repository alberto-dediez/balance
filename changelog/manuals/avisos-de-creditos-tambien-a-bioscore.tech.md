# Avisos de consumo de crédito también a los Administradores — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-19 · **Bounded Contexts:** `Balance` (consume `Security` y `Notifications`)

## Qué hace y por qué

Cuando el consumo de la última compra validada de una cuenta en un proyecto cruza el 80 %, el 90 % o el 100 %, el dominio publica `CreditThresholdReached` una sola vez por umbral y compra. Hasta ahora solo se avisaba a los Propietarios de la cuenta. Ahora el mismo suscriptor avisa además a los Administradores de la plataforma (rol `Admin` sin rol `SuperAdmin`), con textos propios en tercera persona que nombran la cuenta y enlazan al detalle de la compra en administración.

No cambia la detección: `CreditPurchase::reachThreshold()` y `applyCreditThresholdReached()` (`src/Balance/Domain/Model/CreditPurchase/CreditPurchase.php`) siguen igual y garantizan que cada umbral se publica una vez. Solo se amplía el suscriptor `CreditThresholdReachedNotifier` con un segundo grupo de destinatarios. Decisiones (quiénes, canales, duplicados, cuenta sin Propietarios): tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Balance/Infrastructure/Notification/CreditThresholdReachedNotifier.php` | Suscriptor del evento; notifica a Propietarios y a Administradores | modificado |
| `templates/balance/notification/credit-threshold-reached-admin.email.html.twig` | Cuerpo del correo al Administrador | nuevo |
| `templates/balance/notification/credit-threshold-reached-admin.notification.html.twig` | Cuerpo de la notificación interna al Administrador | nuevo |
| `translations/messages+intl-icu.{es,en,tr}.xlf` | Siete claves nuevas (3 asuntos, 3 párrafos, «Ver compra de crédito») | modificado |
| `tests/Unit/Balance/Infrastructure/Notification/CreditThresholdReachedNotifierTest.php` | Casos de Administradores | modificado |
| `tests/Acceptance/Balance/credit_alerts.feature` | Cuatro escenarios de Administradores | modificado |

Sin cambios: `CreditThresholdReached`, `CreditPurchase`, `CreditAlertThreshold`, `CheckCreditAlertsService`, `CommandSchedule`, plantillas del cliente (`credit-threshold-reached.*.html.twig`) y `services.yaml` (el notificador se autoconfigura; el constructor no cambia).

## Flujo de una operación

1. Disparo: `CheckCreditAlertsService` (comando programado `balance:check-credit-alerts` a las 04:30, `src/Shared/Application/CommandBus/CommandSchedule.php:62-64`; también `app:balance:check-credit-alerts` por consola y al asignar créditos) llama a `reachThreshold()` solo si el umbral no estaba notificado.
2. `CreditThresholdReachedNotifier::handleCreditThresholdReached()` (`CreditThresholdReachedNotifier.php:47`):
   - Carga el proyecto; si no existe, el nombre es el id.
   - Carga la cuenta una vez (`AccountRepository::find`) y resuelve los Propietarios con `owners(?Account)` (`:142`): `UserFilter(role: Owner, account, excludeRole: null, limit: 0)`; sin cuenta devuelve `[]`.
   - Envía a cada Propietario dos `SystemNotification` (`Internal` y `Email`) con las plantillas `credit-threshold-reached.*` y `subjectFor()` (`:123`). Sin cambios respecto a antes.
   - Construye `adminParams` = parámetros del cliente + `accountName` (nombre de la cuenta, o su id si no existe) + `creditPurchaseId`; y `adminSubjectParams` con `{account}` y `{project}`.
   - Para cada usuario de `administratorsExcluding($owners)` (`:158`) envía dos `SystemNotification` (`Internal`, `Email`) con `adminSubjectFor()` (`:132`), `accountId: $admin->accountId()` y las plantillas `credit-threshold-reached-admin.*`.
3. `administratorsExcluding()` hace una sola consulta `UserFilter(role: Role::Admin, excludeRole: Role::SuperAdmin, limit: 0)` (`:163`) y descarta a quien ya recibió el aviso como Propietario (comparando `(string) $user->id()`), de modo que nadie recibe dos avisos por canal.
4. `DispatchingSystemNotifier` renderiza Twig y traduce asunto y plantilla al idioma del destinatario; por eso el asunto se pasa con `TranslatableMessage` y `subjectParams`.

Las plantillas escapan `accountName` y `projectName` con `| e` dentro de `{% trans with %}` (hallazgo B1 de `review.md`, corregido) porque el cuerpo se renderiza sin escape adicional.

## Autorización y ámbito

Un suscriptor de evento no pasa por `Authorizer`; no hay caso de uso nuevo. El enlace apunta a `admin_credit_purchases_view` (`/admin/credits/{creditPurchaseId}`, `src/Balance/Infrastructure/UI/Web/Admin/CreditPurchase/ViewController.php`), pantalla ya restringida a Administradores. Destinatarios por rol:

| Rol del usuario | Aviso de cliente | Aviso de Administrador |
|---|---|---|
| `Owner` de la cuenta dueña de la compra | Sí | No (si además es `Admin`, solo el de cliente) |
| `Admin` sin `SuperAdmin` | No | Sí, para toda compra (`PaidByClient` o `PaidByBioscore`) |
| `SuperAdmin` (con o sin `Admin`) | No | No |
| `User` | No | No |

No se usa `CurrentAccountIdProvider`: el `accountId` del evento es el de la cuenta dueña de la compra (en crédito compartido, el distribuidor). El `accountId` de cada notificación de Administrador es el de su propio usuario.

## Persistencia y migración

Ninguna. No hay cambio de esquema ni backfill: los umbrales ya notificados antes del despliegue no se reenvían a los Administradores.

## Configuración y operación

- Sin variables de entorno, colas nuevas ni servicios. Las notificaciones salen por el flujo habitual de `SystemNotifier`.
- Comprobación manual: `make sf c="app:balance:check-credit-alerts"` (comando `src/Balance/Infrastructure/UI/Console/CreditPurchase/CheckCreditAlertsCommand.php`).
- Volumen: hasta tres correos por compra y por Administrador.
- Traducciones: `make translation` regenera los catálogos; en `tr.xlf` solo se conservaron las 7 claves de la funcionalidad (ver deuda).

## Cómo extenderlo

- Añadir un umbral: se añade a `CreditAlertThreshold` y a los dos `match` (`subjectFor()` y `adminSubjectFor()`), y a la rama `if/elseif` de las cuatro plantillas.
- Cambiar quién recibe el aviso: editar `administratorsExcluding()`. El filtro de rol en Doctrine es una pertenencia exacta a la lista de roles (`DoctrineUserRepository.php:155-170`); `excludeRole` sustituye la exclusión por defecto de `Role::Provider`.
- Trampa: usar `excludeRole: Role::SuperAdmin` deja sin aviso a quien tenga `Admin` y `SuperAdmin` a la vez.
- El método `administrators()` de otros notificadores (`CreditPurchaseRequestedNotifier.php`, etc.) avisa a `SuperAdmin` y `Admin` y no es el mismo criterio; no reutilizarlo sin decidirlo.

## Tests

- Unitarios (`CreditThresholdReachedNotifierTest.php`, con `SpySystemNotifier`, `InMemoryUserRepository`): Propietarios por ambos canales; Administradores por ambos canales con plantillas, parámetros y `subjectParams`; asunto según umbral (`@dataProvider administratorSubjects`); cuenta sin Propietarios; sin destinatarios; `SuperAdmin` y `Admin`+`SuperAdmin` excluidos; Propietario que también es `Admin` (un solo aviso); cuenta inexistente (usa el id).
- Behat (`credit_alerts.feature`, escenarios de las líneas 43-66): el usuario `payment` (`ROLE_ADMIN`) recibe 1 notificación al primer umbral, no se repite al volver a comprobar, 2 tras agotar el crédito y 0 por debajo del 80 %.
- Ejecución: `make test-unit options=tests/Unit/Balance/Infrastructure/Notification/CreditThresholdReachedNotifierTest.php` y `make test-acceptance options=tests/Acceptance/Balance/credit_alerts.feature` (`make test-this` no existe en el `Makefile`).

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: `make test-this` inexistente; `@dataProvider` en lugar del atributo; solo se conservaron 7 claves en `tr.xlf`.
- Deuda no corregida (plan): unas 114 claves usadas en el código faltan en el catálogo turco, y faltan los catálogos TR de `assistantPlanStatusType` y `assistantStepStatusType`.
- `review.md`: sin hallazgos abiertos (B1 y B2 corregidos). Duda Q1 abierta: confirmar los roles reales del equipo de Bioscore en producción, porque quien tenga `Admin` y `SuperAdmin` a la vez no recibirá el aviso.
- Enlaces: [`plan.md`](plan.md) · [`review.md`](review.md).
