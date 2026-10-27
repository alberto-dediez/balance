# Permisos del informe de huella según la cuenta de quien impersona — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-26 (revisado 2026-10-27) · **Bounded Contexts:** `Security` (política de permisos efectivos), `CarbonFootprint` (casos de uso del informe, pantalla de textos y pantallas de Resultados) · **Migración:** no

## Qué hace y por qué

Cada `Account` tiene dos permisos de informe (`AccountReportPermissions`: `canEditTexts`, por defecto `false`, y `autoReview`, por defecto `true`; `src/Security/Domain/Model/Account/AccountReportPermissions.php`). Antes, los casos de uso del informe leían siempre los de la cuenta del informe, de modo que el Propietario de una consultora que impersonaba una cuenta gestionada pasaba por el circuito de revisión de esa cuenta aunque la consultora tuviera los permisos.

Ahora los permisos se resuelven como función pura de (cuenta del informe, usuario que actúa) en `App\Security\Application\Account\ReportPermissionsPolicy`. Si el usuario actúa sobre una cuenta ajena que puede impersonar, **sustituyen** los de su cuenta de origen (la consultora, o la cuenta del Admin) a los de la cuenta del informe, en los dos flags. Decisiones en la tabla «Decisiones tomadas» de `plan.md` (regla de sustitución, Admin con cuenta de origen, ubicación en `Security`, no depender de la sesión).

Además, el botón «Revisar textos del informe» de Resultados solo se muestra a quien tiene permiso efectivo de editar textos (prop `canEditTexts` del componente `ReportStatus`). La redirección con aviso de `TextsController` se mantiene como red de seguridad para accesos por URL.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Security / Application | `src/Security/Application/Account/ReportPermissionsPolicy.php` | Resuelve los permisos efectivos de informe | Nuevo |
| CarbonFootprint / Application | `src/CarbonFootprint/Application/CarbonFootprintReport/ReportTextsTrait.php` | Solo `textsFrom()` (mezcla secciones nuevas con las actuales). Sustituye al antiguo `ReportPermissionsTrait` | Nuevo (el trait anterior se eliminó) |
| CarbonFootprint / Application | `.../GenerateTexts/GenerateCarbonFootprintReportTextsService.php` | Decide borrador / aprobación automática / revisión con la política (`:79`) | Modificado |
| CarbonFootprint / Application | `.../Issue/IssueCarbonFootprintReportService.php` | Exige `canEditTexts && autoReview` (`:44`) | Modificado |
| CarbonFootprint / Application | `.../SubmitForReview/SubmitCarbonFootprintReportForReviewService.php` | Exige `canEditTexts` (`:40`) | Modificado |
| CarbonFootprint / Application | `.../UpdateTexts/UpdateCarbonFootprintReportTextsService.php` | En borrador exige `canEditTexts` (`:42`) | Modificado |
| CarbonFootprint / Application | `.../Approve/ApproveCarbonFootprintReportService.php` | Solo pierde la dependencia de permisos; sigue autorizando `Action::Admin` | Modificado |
| CarbonFootprint / UI Web | `src/CarbonFootprint/Infrastructure/UI/Web/CarbonFootprintReport/TextsController.php` | Usa la política (`:51-54`) para redirigir y para el botón «Emitir informe» | Modificado |
| CarbonFootprint / UI Web | `.../PropertyCarbonFootprint/Step/ResultsController.php`, `.../CompanyCarbonFootprint/Step/ResultsController.php`, `.../EventCarbonFootprint/Step/ResultsController.php` | Pasan `canEditReportTexts` a la plantilla (`:67`, `:69`, `:149`) | Modificado |
| Plantillas | `templates/components/CarbonFootprint/ReportStatus.html.twig` | Prop `canEditTexts` (por defecto `false`); botón de revisar con `canEdit and canEditTexts` (`:13`) | Modificado |
| Plantillas | `templates/carbon_footprint/{property,company,event}_carbon_footprints/step/results.html.twig` | Pasan `:canEditTexts="canEditReportTexts"` | Modificado |
| Tests | ver sección «Tests» | | Nuevos y modificados |

## Modelo de dominio

Sin cambios de dominio. `ReportPermissionsPolicy` es un servicio de aplicación `final` con `AccountRepository` e `ImpersonationPolicy` (autowiring en ambos BCs; no hay definición explícita en `services.yaml`).

Secuencia de `permissionsFor(?AccountId $accountId, User $user)` (`ReportPermissionsPolicy.php:20-40`):

1. `$accountId` nulo: `new AccountReportPermissions()` (`:22-24`).
2. `$own` = permisos de la cuenta del informe, o los por defecto si no existe (`:26-27`).
3. Si `User::accountId()` no es un `AccountId` o coincide con la cuenta del informe: `$own` (`:29-33`).
4. Si la cuenta del informe no existe o `ImpersonationPolicy::canImpersonate($account, $user)` es falso: `$own` (`:35-37`).
5. Permisos de la cuenta de origen del usuario, o `$own` si esa cuenta no existe (`:39`).

| Usuario | Permisos aplicados |
|---|---|
| Propietario de la cuenta del informe | los de esa cuenta |
| Propietario de consultora activa asignada a la cuenta | los de la consultora |
| Propietario de consultora no asignada | los de la cuenta del informe (y el authorizer le deniega igualmente) |
| Admin / SuperAdmin con cuenta de origen, sobre cuenta activa | los de su cuenta de origen |
| Admin / SuperAdmin sin cuenta de origen | los de la cuenta del informe |
| Cuenta del informe inactiva o eliminada (`canImpersonate` falso) | los de la cuenta del informe |
| Rol `User` con otra cuenta de origen | los de la cuenta del informe |

Invariante: no depende de la sesión (no consulta `CurrentImpersonationProvider`), por eso es idéntica en la petición web y en el worker de comandos diferidos. Se usa `User::accountId()` a propósito, porque se necesita la cuenta **no impersonada**; no es scoping por cuenta y **no debe sustituirse** por `CurrentAccountIdProvider`, que devolvería la cuenta impersonada y anularía la funcionalidad (mismo criterio que `ImpersonationPolicy::consultantAccount()`).

## Flujo de una operación

**Generación de textos (asíncrona).** Completar la huella, `Reissue` o `Retry` despachan un `DeferredCommand` de prioridad `LOW` con el usuario que actúa. `DeferredMessageHandler` restaura el usuario y la cuenta impersonada antes de ejecutar `GenerateCarbonFootprintReportTextsService`. Tras generar los textos, el servicio obtiene `permissionsFor($report->accountId(), $user)` (`GenerateCarbonFootprintReportTextsService.php:79`): con `canEditTexts` el informe queda en borrador; si no, con `autoReview` se aprueba automáticamente como el usuario que lo pidió; si no, pasa a pendiente de revisión. El notificador de borrador avisa a quien pidió el informe (la consultora si fue ella), sin cambios.

**Pantalla de Resultados.** Cada `ResultsController` busca el informe (`findBySubject`), calcula `permissionsFor($report?->accountId(), $user)->canEditTexts()` y lo pasa como `canEditReportTexts`; la plantilla lo envía a `ReportStatus`. El botón «Reintentar generación» sigue gobernado solo por `canEdit`. El parcial `_mitigation_plan.html.twig` no pasa la prop nueva, así que ahí el botón de revisar textos nunca aparece.

**Pantalla de textos** (`TextsController`, ruta `carbon_footprint_reports_texts`): `denyAccessUnlessGranted(Edit)` (solo `Owner` cuya cuenta actual es la del informe, `CarbonFootprintReportAuthorizer.php:38-53`), luego `$user = $loggedUser()` y `permissionsFor(...)` (`:51-54`). Sin `canEditTexts` o con informe no editable redirige a Resultados con el aviso «El informe no tiene textos pendientes de revisar.». `autoReview` efectivo decide si el formulario ofrece «Emitir informe». Guardar, solicitar revisión y emitir llaman a sus servicios, que vuelven a resolver la política con el mismo usuario (el orden dentro de cada servicio es authorizer primero, permisos después).

## Autorización y ámbito

- La política no es autorización: `CarbonFootprintReportAuthorizer` sigue siendo la barrera (`Edit` exige cuenta actual = cuenta del informe, de ahí que solo se pueda actuar impersonando). Sin cambios.
- `Approve`, `Reject` y la edición de textos en pendiente de revisión (`Action::Admin`) no cambian.
- Una cuenta de origen inactiva o eliminada impide iniciar sesión, por lo que la política no comprueba su estado (confirmado en `review.md`, Q1).

## Persistencia y migración

Sin cambios de esquema ni migración.

## Configuración y operación

Sin variables, colas ni comandos nuevos. Los permisos de cada cuenta se editan en Administración → Cuentas (campos «Puede editar los textos del informe de huella» y «Autorrevisión del informe de huella», `src/Security/Infrastructure/Symfony/Form/UpdateAccountType.php:50-57`).

Consecuencia operativa: un borrador generado con los permisos de la consultora en una cuenta gestionada sin `canEditTexts` solo puede cerrarlo la consultora (o un Admin cuya cuenta tenga el permiso); el Propietario de la cuenta gestionada ni ve el botón. Un Admin con cuenta de origen con valores por defecto `(false, true)` que impersone ahora hereda esos valores.

## Cómo extenderlo

- Para un tercer permiso de informe: añadirlo a `AccountReportPermissions` y consumirlo desde `permissionsFor()`; la sustitución es del objeto completo, no flag a flag.
- Para otro consumidor de permisos (otro caso de uso o pantalla): inyectar `ReportPermissionsPolicy` y pasar siempre el `User` que actúa; no leer `Account::reportPermissions()` directamente.
- Para mostrar el botón de revisar en otra pantalla: pasar `canEdit` y `canEditTexts` a `ReportStatus`.
- Trampa en tests: al simular consultora o Admin, el authorizer necesita `StubCurrentAccountIdProvider` con la cuenta gestionada y `StubCurrentRolesProvider([Role::Owner])`, o denegará `Edit` antes de llegar a los permisos (`ReportServiceContextTrait::impersonatingAuthorizer()`).

## Tests

- **Unitarios Security:** `tests/Unit/Security/Application/Account/ReportPermissionsPolicyTest.php` (12 casos: propia cuenta, consultora que supervisa, no asignada, inactiva, Admin y SuperAdmin con y sin cuenta, cuenta del informe inactiva, id nulo, cuenta desconocida, usuario con cuenta desconocida, rol `User`) y `ReportPermissionsPolicyMother.php`.
- **Unitarios CarbonFootprint:** `tests/Unit/CarbonFootprint/Application/CarbonFootprintReport/` (`GenerateTexts`, `Issue`, `SubmitForReview`, `UpdateTexts`, `Approve`); helpers en `ReportServiceContextTrait.php` (`givenConsultantAccount`, `givenManagedAccount`, `consultantOwnerUser`, `adminUserOfAccount`, `impersonatingAuthorizer`) y `AccountMother::consultantAccountFromNameAndId()`.
- **Aceptación:** `tests/Acceptance/CarbonFootprint/carbon-footprint-report-texts.feature` (consultora emite o solicita revisión impersonando, Propietario de cuenta gestionada sin acceso a un borrador que solo la consultora puede editar, Propietario sin impersonar conserva los suyos, Admin impersonando) y `carbon-footprint-report-status.feature` (botón oculto sin permiso en las tres huellas).
- La ruta asíncrona no se cubre en Behat porque en `test` los transportes de Messenger son `in-memory://` (`config/packages/messenger.yaml`); la cubren los unitarios de `GenerateTexts`.
- Ejecución: `make test-unit options=tests/Unit/Security/Application/Account/ReportPermissionsPolicyTest.php`, `make test-unit options=tests/Unit/CarbonFootprint/Application/CarbonFootprintReport`, `make test-acceptance options=tests/Acceptance/CarbonFootprint/carbon-footprint-report-texts.feature`. El Makefile no define `test-this`.

## Desviaciones y deuda

- `plan.md` («Implementación»): el Makefile no tiene `test-this`; `impersonatingSut()` por clase de test; id fijo para la consultora de test.
- Tras la revisión (`review.md`, B1 a B3 corregidos): el trait `ReportPermissionsTrait` se sustituyó por `ReportTextsTrait` (solo `textsFrom()`) y los servicios llaman directamente a la política; el plan describe todavía el trait antiguo. Sin hallazgos abiertos.
