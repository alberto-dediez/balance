# Asignación directa de créditos de CO2 por el administrador — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-09 · **Bounded Contexts:** `Balance`, `Security`

## Qué hace y por qué

Hasta ahora un crédito (`CreditPurchase`) solo nacía por dos caminos: el Propietario lo **solicita** (`request()`, estado `Pending`, luego un Admin lo valida) o una cuenta lo **recibe** por transferencia (`receive()`, nace `Validated`). Cuando Bioscore compra créditos para un cliente por un acuerdo externo, el Admin no tenía forma de registrarlos sin impersonar al cliente y dejar una solicitud ficticia.

Se añade un tercer camino, `CreditPurchase::assign()`, con su propio evento `CreditPurchaseAssigned`, expuesto en el panel de administración como la acción «Asignar créditos» (`/admin/credits/register`). El crédito nace `Validated`, con la cuenta adherida al proyecto. Si la cuenta ya tenía una solicitud `Pending` para ese proyecto, el servicio no crea nada nuevo: valida la más antigua reutilizando `ValidateCreditPurchaseService`. Las decisiones (por qué no encadenar `request()` + `validate()`, por qué `SecurityAction::Admin`, fecha actual, ambos tipos de pago, etc.) están en la tabla «Decisiones tomadas» de `plan.md`.

Se añade además el atributo **origen** (`CreditPurchaseOrigin`: `requested`, `assigned`, `received`) a todos los créditos, que las pantallas usan para etiquetar («Toneladas asignadas», «Fecha de asignación», columna y filtro «Origen»).

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/Balance/Domain/Model/CreditPurchase/CreditPurchase.php` | `assign()` (l. 357), `applyCreditPurchaseAssigned()` (l. 448), propiedad `origin`, `origin()` (l. 109), `isAssigned()` (l. 114); `applyCreditPurchaseRequested`/`Received` fijan su origen | Modificado |
| Dominio | `.../CreditPurchase/CreditPurchaseAssigned.php` | Evento `creditPurchaseAssigned` | Nuevo |
| Dominio | `.../CreditPurchase/CreditPurchaseOrigin.php` | Enum `requested` / `assigned` / `received` | Nuevo |
| Dominio | `.../CreditPurchase/CompensationNotEnabledForAccountException.php` | Cuenta sin funcionalidad «Compensación» | Nuevo |
| Dominio | `.../CreditPurchase/CreditPurchaseFilter.php` | Criterio `origin` | Modificado |
| Dominio | `src/Security/Domain/Model/Account/AccountFilter.php` | Criterio `feature` (`?AccountFeature`) | Modificado |
| Dominio | `src/Balance/Domain/Event/ProjectContribution/ReallocateContributionsOnCreditPurchaseValidatedSubscriber.php` | Escucha también `CreditPurchaseAssigned` (l. 35, 57) | Modificado |
| Aplicación | `src/Balance/Application/CreditPurchase/Assign/AssignCreditPurchaseRequest.php`, `...Response.php`, `...Service.php` | Caso de uso | Nuevo |
| Persistencia | `src/Balance/Infrastructure/Persistence/Doctrine/Mapping/CreditPurchase.CreditPurchase.orm.xml` | Columna `origin` | Modificado |
| Persistencia | `DoctrineCreditPurchaseRepository.php` (l. 111), `InMemoryCreditPurchaseRepository.php` | Filtro por origen; el InMemory ordena igual que Doctrine | Modificado |
| Persistencia | `DoctrineAccountRepository.php` (l. 125, `featureField()` l. 175), `InMemoryAccountRepository.php` (l. 104) | Filtro por funcionalidad | Modificado |
| Persistencia | `migrations/Version20261109121928.php` | Migración de `origin` con backfill | Nuevo |
| UI | `src/Balance/Infrastructure/UI/Web/Admin/CreditPurchase/RegisterController.php` | Alta, ruta `admin_credit_purchases_register` | Nuevo |
| UI | `src/Balance/Infrastructure/UI/Web/Admin/CreditPurchase/ListController.php` | Pasa `canAssign` (l. 58) | Modificado |
| UI | `src/Balance/Infrastructure/Symfony/Form/AssignCreditPurchaseType.php`, `Type/CreditPurchaseOriginType.php` | Formulario y tipo de enum | Nuevo |
| UI | `src/Balance/Infrastructure/Symfony/Form/ListCreditPurchasesType.php` | Filtro «Origen» | Modificado |
| UI | `src/Security/Infrastructure/Symfony/Form/Type/AccountIdType.php` | Opción `feature` | Modificado |
| UI | `templates/balance/admin/credits/register.html.twig`, `templates/components/Link/AssignCredits.html.twig` | Pantalla y botón de cabecera | Nuevo |
| UI | `templates/balance/admin/credits/list.html.twig`, `templates/balance/credits/list.html.twig`, `templates/balance/credits/_allocations.html.twig` | Columna «Origen», cabecera «Fecha», etiquetas de asignación | Modificado |
| UI | `templates/balance/translations/creditPurchaseOrigin.html.twig`, `creditPurchaseErrors.html.twig`, `translations/creditPurchaseOrigin+intl-icu.{es,en,tr}.xlf` | Extracción de traducciones | Nuevo |
| Notificación | `src/Balance/Infrastructure/Notification/CreditPurchaseAssignedNotifier.php`, `templates/balance/notification/credit-purchase-assigned.notification.html.twig` | Aviso interno a los Propietarios | Nuevo |

## Modelo de dominio

`assign()` (`CreditPurchase.php:357-392`) valida en este orden: el agregado debe ser nuevo (`Pending` y `accountId` nulo, si no `CreditPurchaseNotPendingException`), `purchasedTonnes > 0` y `amount >= 0` (`\InvalidArgumentException`). Después `recordApplyAndPublish(CreditPurchaseAssigned)`.

`applyCreditPurchaseAssigned()` (l. 448) deja: `status = Validated`, `requestedTonnes = purchasedTonnes` (así `isPartiallyFulfilled()` es falso y no sale la insignia «Parcial»), `requestedBy = resolvedBy = assignedBy`, `requestedAt = resolvedAt = occurredOn`, `lotNumber = 1`, `origin = Assigned`.

Evento `CreditPurchaseAssigned`: `creditPurchaseId`, `accountId`, `projectId`, `purchasedTonnes`, `amount?`, `paymentType`, `reference`, `certificate?`, `assignedBy`.

| | Solicitado | Recibido por transferencia | Asignado |
|---|---|---|---|
| `origin` | `requested` | `received` | `assigned` |
| Nace en | `Pending` | `Validated` | `Validated` |
| Evento | `Requested` → `Validated` | `Received` | `Assigned` |
| `lotNumber` | 1 | ≥ 2 | 1 |

Una solicitud pendiente validada por la asignación conserva `origin = requested`.

## Flujo de una operación

`GET|POST /admin/credits/register` → `RegisterController` → `AssignCreditPurchaseService`:

1. El controlador exige `SecurityAction::Admin` sobre un `CreditPurchase` provisional (l. 42), construye las opciones de proyecto (solo `ProjectType::EmissionCompensation`) y el formulario `AssignCreditPurchaseType`. Usa `RegisterTrait`; las excepciones de dominio se capturan dentro de la closure porque el trait convierte cualquier otra en «Error inesperado».
2. `AssignCreditPurchaseService::__invoke` (`Assign/AssignCreditPurchaseService.php:50`):
   - desempaqueta los campos obligatorios (`\InvalidArgumentException` si falta alguno);
   - autoriza `Admin` (l. 61);
   - `AccountFeaturesPolicy::isEnabled(Compensation, $accountId)`; si no, `CompensationNotEnabledForAccountException` (l. 67);
   - el proyecto debe existir y ser de compensación (`ProjectNotCompensableException`, l. 77);
   - busca las compras `Pending` de cuenta y proyecto ordenadas por `requestedAt ASC` (l. 81-90).
3. **Rama con pendientes** (l. 92): asegura la adhesión al proyecto (`ensureAccountAdheresToProject`, l. 145, idempotente vía `SelectProjectService`) y delega en `ValidateCreditPurchaseService` sobre la más antigua con los datos del formulario. Se publica `CreditPurchaseValidated` (notifier de validación existente: aviso interno + correo al solicitante). La respuesta lleva `validatedPendingRequest = true`, `partiallyFulfilled` y `remainingPendingRequests = count - 1`. El identificador duplicado lo comprueba `ValidateCreditPurchaseService`.
4. **Rama sin pendientes** (l. 117): `existsByReference()` (si existe, `DuplicatedPurchaseReferenceException`), adhesión, `new CreditPurchase(id:)` + `assign()` + `add()`. Se publica `CreditPurchaseAssigned`.
5. El controlador traduce las dos excepciones a error de campo (`reference`, `accountId`) y no redirige; en éxito añade el flash (`success`; más un `info` con plural ICU si quedan pendientes) y redirige a `admin_credit_purchases`.

Suscriptores de `CreditPurchaseAssigned`: `CreditPurchaseAssignedNotifier` (una `SystemNotification` interna por Propietario de la cuenta, sin correo; no hace nada si la cuenta no existe o no tiene Propietarios) y `ReallocateContributionsOnCreditPurchaseValidatedSubscriber` (reasigna las aportaciones infra-asignadas de la cuenta y proyecto). `CreditPurchaseRequestedNotifier` y `CreditPurchaseResolvedNotifier` no lo escuchan, por lo que no hay correo «Nueva solicitud» a los Admin ni «Su compra ha sido validada» al Admin en la rama nueva.

## Autorización y ámbito

- Acción `SecurityAction::Admin`, resuelta por `AbstractAuthorizer::canAdmin()` (Admin o SuperAdmin, respeta la impersonación vía `userHasRole`); `CreditPurchaseAuthorizer` no la sobreescribe. Doble barrera: controlador y servicio.
- El Propietario obtiene 403 en la URL (`UnauthorizedActionException` → `createAccessDeniedException`).
- La cuenta destino viene en el formulario; `CurrentAccountIdProvider` no interviene.
- El selector de cuentas (`AccountIdType` con opción `feature = Compensation`) solo ofrece cuentas con «Compensación» activa; el servicio lo vuelve a comprobar.
- Ruta con `priority: 1` porque `/admin/credits/{creditPurchaseId}` (`ViewController`) no tiene `requirements` y capturaría `register`.
- El botón de cabecera «Asignar créditos» se pinta solo si `canAssign` (`ListController.php:58`).

## Persistencia y migración

- Tabla `credit_purchases`: nueva columna `origin VARCHAR(20) NOT NULL` (mapping `CreditPurchase.CreditPurchase.orm.xml`, enum `CreditPurchaseOrigin`).
- `migrations/Version20261109121928.php`: `ADD origin ... DEFAULT 'requested'`, `UPDATE ... SET origin = 'received' WHERE lot_number > 1`, `ALTER ... DROP DEFAULT`; `down()` hace `DROP origin`. Coincide con `migration.sql`. Si una versión anterior sin backfill se ejecutó en algún entorno, hay que hacer `down` o corregir los datos con `UPDATE credit_purchases SET origin = IF(lot_number > 1, 'received', 'requested') WHERE origin = ''` (según `review.md`, A1). La migración no se ejecuta en tests (SQLite genera el esquema desde el mapping).
- Filtro `AccountFilter::$feature`: en Doctrine accede al embebido como `a.features.<propiedad>` mediante un `match` privado (`DoctrineAccountRepository.php:175`); no cambia el esquema (columnas `feature_*` ya existentes).

## Configuración y operación

Sin variables de entorno, colas, comandos de consola ni tareas programadas nuevas. `services.yaml` no cambia (autoregistro de `App\Balance\`). No hay endpoint API. La notificación es solo interna (`NotificationChannel::Internal`) y viaja por el mecanismo habitual de notificaciones.

## Cómo extenderlo

- **Nuevo origen**: añadir el caso a `CreditPurchaseOrigin`, su traducción en `templates/balance/translations/creditPurchaseOrigin.html.twig` (y `make translation`), fijarlo en el `apply...` del evento que cree el crédito y decidir si las plantillas deben etiquetar de forma distinta (`isAssigned`, `lotNumber > 1`).
- **Filtrar cuentas por otra funcionalidad**: usar `AccountIdType` con la opción `feature`; si se añade una `AccountFeature`, ampliar el `match` de `DoctrineAccountRepository::featureField()` (un caso sin rama rompe en ejecución).
- **Asignar desde otro punto (API, importación)**: invocar `AssignCreditPurchaseService`; nunca instanciar `CreditPurchase` ni encadenar `request()` + `validate()`, que dispararía los correos de solicitud y validación.
- Trampas: el servicio valida solo la solicitud más antigua; las demás siguen pendientes. `RegisterTrait` traga excepciones no capturadas.

## Tests

- Unitarios: `tests/Unit/Balance/Domain/Model/CreditPurchase/CreditPurchaseTest.php` (nacimiento, invariantes, transferibilidad según pago, reconstitución, origen de cada camino), `CreditPurchaseRepositoryTest.php` (filtro y orden), `CreditPurchaseMother::assigned()`; `tests/Unit/Balance/Application/CreditPurchase/Assign/AssignCreditPurchaseServiceTest.php`; `CreditPurchaseAuthorizerTest.php`; `tests/Unit/Balance/Domain/Event/ProjectContribution/ReallocateContributionsOnCreditPurchaseAssignedTest.php`; `tests/Unit/Balance/Infrastructure/Notification/CreditPurchaseAssignedNotifierTest.php`; `tests/Unit/Security/Domain/Model/Account/AccountRepositoryTest.php` (`testFindByFilterByFeature`, también sobre Doctrine/SQLite).
- Aceptación: `tests/Acceptance/Balance/credit_purchase_assign.feature` (14 escenarios: alta, saldo del Propietario, etiquetas, validación de pendiente total y parcial, avisos, identificador duplicado, cuenta sin «Compensación», transferencia, 403, lote recibido, filtro por origen). El escenario de cuenta sin «Compensación» comprueba que no aparece en el selector (Behat no puede enviar una opción no ofrecida); la barrera del servicio la cubre un unitario.
- Ejecución (Makefile): `make test-unit options="--filter AssignCreditPurchase"`, `make test-acceptance options="tests/Acceptance/Balance/credit_purchase_assign.feature"`, `make test-parallel`. `make test-this` no existe.

## Desviaciones y deuda

- Declaradas en `## Implementación` de `plan.md`: orden en `InMemoryCreditPurchaseRepository` (luego igualado a Doctrine), adhesión comprobada en ambas ramas, escenario de cuenta sin «Compensación» adaptado, y tests de navegación (`NodeListerTest`, `NavigationSearchProviderTest`, `sidebar-menu.feature`) corregidos a petición del usuario.
- `review.md`: sin hallazgos abiertos (A1 migración y B1-B4 corregidos).
- Deuda heredada, fuera de alcance: `templates/balance/admin/credits/validate.html.twig` no pinta `form.amount` explícitamente.
- `CompensationNotEnabledForAccountException` conserva su mensaje en inglés; solo el texto del formulario está traducido.
