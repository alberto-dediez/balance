# Créditos compartidos entre distribuidos — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-16 · **Bounded Contexts:** `Balance`, `Property`, `CarbonFootprint`

## Qué hace y por qué

Antes, el crédito de compensación de una cuenta solo respaldaba las aportaciones de esa misma cuenta: un hotel adherido a una distribuidora, sin lotes propios en un proyecto, no podía ofrecer compensación a quien calculaba su huella. Ahora el crédito **pagado por Bioscore** (`CreditPurchasePaymentType::PaidByBioscore`) de un distribuidor puede respaldar las aportaciones de sus cuentas o propiedades adheridas cuando estas no tienen crédito propio suficiente.

La solución separa dos conceptos en `ProjectContribution`: la cuenta **a la que pertenece** la aportación (`accountId`, sin cambios) y la cuenta **cuyo crédito la respalda** (`creditAccountId`, nuevo, nunca nulo en la práctica; por defecto igual a `accountId`). Todas las consultas de consumo de crédito pasan a agrupar por `creditAccountId`. Un selector (`CreditSourceSelector`) decide, al listar opciones, cotizar y reservar, si se usa el crédito propio o el de un distribuidor. Una aportación se cubre siempre con **una sola cuenta de crédito**.

Decisiones clave (tabla «Decisiones tomadas» de `plan.md`): solo lotes `Validated` y `PaidByBioscore` (no cambia la tesorería, el neto ya va a Bioscore); solo en los tres flujos públicos (calculadora de la propiedad, calculadora de viajes de la cuenta, encuesta de asistentes); el Propietario nunca usa crédito compartido; sin pantallas nuevas.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Domain (Balance) | `src/Balance/Domain/Model/CreditPurchase/SharedCreditProviders.php` | Puerto: cuentas que prestan crédito a una cuenta (y propiedad) | Nuevo |
| Domain | `src/Balance/Domain/Model/ProjectContribution/ProjectContribution.php` | `creditAccountId` (`:29`, getter `:77`), `usesSharedCredit()` (`:82`), `allowedCreditPaymentType()` (`:219`), parámetro final de `register()` (`:388`, `:429`), `apply…` (`:587`) | Modificado |
| Domain | `.../ProjectContribution/ProjectContributionRegistered.php` | Campo `creditAccountId` en constructor (`:50`), payload (`:244`) y `rebuildFromPayload` (`:283`, cae en `accountId`) | Modificado |
| Domain | `.../ProjectContribution/ProjectContributionFilter.php` | Criterio `creditAccountId` (`:30`) | Modificado |
| Domain | `.../ProjectContribution/ProjectContributionRepository.php` | Siete consultas con semántica «cuenta cuyo crédito respalda»; nueva `unallocatedSharedTonnes()` | Modificado |
| Domain | `src/Balance/Domain/Event/CreditPurchase/CheckCreditAlertsOnCreditsAllocatedSubscriber.php` | Comprueba alertas sobre `creditAccountId()` (`:52`) | Modificado |
| Application | `src/Balance/Application/CreditPurchase/Balance/CreditSource.php` | VO: cuenta, toneladas disponibles, `shared` | Nuevo |
| Application | `.../Balance/CreditSourceSelector.php` | `candidates()`, `select()`, `maxAvailableTonnes()`, `sharedProjectIds()` | Nuevo |
| Application | `.../Balance/CreditAvailability.php` | `sharedFor()` (`:39`), cuerpo común `availableByPaymentType()` (`:73`) | Modificado |
| Application | `.../ProjectContribution/Options/ListContributionOptionsService.php` + `…Request.php` | Proyectos propios ∪ compartidos; usa el selector | Modificado |
| Application | `.../ProjectContribution/Quote/QuoteContributionService.php` + `…Request.php`, `…Response.php` | Devuelve `creditAccountId`; `availableTonnes` = máximo de candidatos | Modificado |
| Application | `.../ProjectContribution/Register/RegisterProjectContributionService.php` + `…Request.php` | Cotiza antes del bloqueo, bloquea por `creditAccountId`, recotiza dentro | Modificado |
| Application | `.../Allocate/AllocateContributionCreditsService.php` | Bloqueo, FIFO y tipo de lote por `creditAccountId` | Modificado |
| Application | `.../Reallocate/ReallocateUnderallocatedContributionsService.php`, `ReallocateAllUnderallocatedContributionsService.php` | Reasignación por `creditAccountId` y tipo de lote permitido | Modificado |
| Application (Property) | `src/Property/Application/DistributorAssignment/DistributedPropertiesPolicy.php` | `distributorsOf()` (`:75`) | Modificado |
| Infrastructure | `src/Balance/Infrastructure/SharedCredit/DistributorSharedCreditProviders.php` | Adaptador del puerto sobre `DistributedPropertiesPolicy` | Nuevo |
| Infrastructure | `src/Balance/Infrastructure/Symfony/DependencyInjection/services.yaml:57` | Alias del puerto | Modificado |
| Infrastructure | `.../Persistence/Doctrine/Mapping/ProjectContribution.ProjectContribution.orm.xml` | Columna `credit_account_id` no nula e índice `idx_project_contribution_credit_balance` | Modificado |
| Infrastructure | `…/Doctrine/Repository/DoctrineProjectContributionRepository.php`, `…/InMemory/Repository/InMemoryProjectContributionRepository.php` | Consultas por `creditAccountId` | Modificado |
| UI (CarbonFootprint) | `src/CarbonFootprint/Infrastructure/UI/Web/ProjectContribution/ContributionContext.php` (`:20`), `ContributionCheckoutHandler.php` | Propagan `allowSharedCredit` y `propertyId` | Modificado |
| UI | `…/Web/Public/PropertyTrip/{ResultController,Contribution/{Choose,Project,Checkout}Controller}.php` | `allowSharedCredit: true` y `propertyId: $link->property?->id()` | Modificado |
| UI | `…/Web/Public/AttendeeSurvey/{ResultController,Contribution/{Choose,Project,Checkout}Controller}.php` | `allowSharedCredit: true`, sin propiedad | Modificado |
| UI | `templates/balance/credits/_allocations.html.twig:137` | «Ver» solo si `is_authorized('view')` | Modificado |
| Migración | `migrations/Version20261116121056.php` | Añade, rellena, pasa a no nula e indexa `credit_account_id` | Nuevo |

## Modelo de dominio

- `ProjectContribution::creditAccountId` es `private ?AccountId` (`ProjectContribution.php:29`). `register()` recibe `?AccountId $creditAccountId = null` al final y emite `$creditAccountId ?? $accountId` (`:429`).
- `usesSharedCredit()`: ambas cuentas informadas y distintas (`:82-87`).
- `allowedCreditPaymentType()` (`:219-226`) es la **única fuente** de la regla de tipo de lote: `OwnCredit` → `PaidByClient`; compartida → `PaidByBioscore`; resto → `null` (cualquiera). La usan `AllocateContributionCreditsService.php:120` y `ReallocateUnderallocatedContributionsService.php:97`.
- Evento `ProjectContributionRegistered`: payload `creditAccountId`; los eventos ya almacenados no lo llevan, por eso `rebuildFromPayload` usa `$payload['creditAccountId'] ?? $payload['accountId']` (`:283`) y el getter cae en `accountId` (`:202-205`).
- Invariantes: consumo de una cuenta en un proyecto = Σ toneladas de aportaciones con `creditAccountId` = cuenta, pagadas o pendientes no expiradas, nunca `Imported` (`reservesCredit()`, `ProjectContribution.php:273`). Una aportación compartida solo recibe lotes `PaidByBioscore` de `creditAccountId`, al asignar y al reasignar.

### Disponibilidad

`CreditAvailability` (`src/Balance/Application/CreditPurchase/Balance/CreditAvailability.php`):

- `for()` = comprado − comprometido (consumo por `creditAccountId`).
- `ownFor()` = lotes `PaidByClient` − asignado, acotado por `for()`.
- `sharedFor()` = lotes `PaidByBioscore` − asignado − `unallocatedSharedTonnes()`, acotado por `for()` (`:39-50`, `:73-102`). El término `unallocatedSharedTonnes` (añadido tras la review, hallazgo C1) resta las toneladas de aportaciones compartidas vivas (pagadas o pendientes) que aún no tienen lote, para no sobrevender crédito Bioscore mientras el distribuidor tiene lotes `PaidByClient` (consulta en `DoctrineProjectContributionRepository.php:451`, con `sharedReservingQueryBuilder` `:473`: `creditAccountId` = cuenta y `accountId` ≠ cuenta). Por esta corrección la tabla t1 del modelo conceptual de `plan.md` ya no es exacta: tras reservar 2 t pendientes, `sharedFor` baja a 8.

### Selector de origen

`CreditSourceSelector` (`.../Balance/CreditSourceSelector.php`):

| Método | Comportamiento |
|---|---|
| `candidates()` (`:26`) | Primero la cuenta propia (solo si está adherida al proyecto) con `for()`. Si `$allowShared`: por cada proveedor con `Compensation` activa y adherido al proyecto, `sharedFor()`; omite los de 0. |
| `select()` (`:68`) | Primer candidato con disponible > 0 y, si se pasa `$tonnes`, que las cubra entero. `null` si ninguno. |
| `maxAvailableTonnes()` (`:89`) | Máximo disponible entre candidatos (lo máximo que una sola cuenta puede cubrir). |
| `sharedProjectIds()` (`:106`) | Proyectos adheridos por los proveedores con `sharedFor() > 0`. |

`providers()` (`:132`) filtra el puerto: excluye a la propia cuenta y a los que no tienen `AccountFeature::Compensation`.

### Distribuidores de una cuenta

`DistributedPropertiesPolicy::distributorsOf()` (`src/Property/Application/DistributorAssignment/DistributedPropertiesPolicy.php:75`): une las asignaciones por cuenta (`accountId`) y, si hay propiedad, las de esa propiedad; ordena por `registeredAt` ascendente; descarta eliminadas, la propia cuenta y distribuidores no activos (`DistributorPolicy::isActiveDistributor()`); deduplica conservando la más antigua. No exige `PropertyTripCalculators`.

## Flujo de una operación

Compensación pública de un huésped (calculadora de la propiedad):

1. `Public/PropertyTrip/ResultController` y `Contribution/{Choose,Project}Controller` llaman a `ListContributionOptionsService` con `allowSharedCredit: true` y `propertyId` del enlace de la calculadora (`$link->property?->id()`). En la calculadora de viajes de la cuenta el enlace no tiene propiedad, así que `propertyId` es nulo; en la encuesta de asistentes no se pasa propiedad.
2. `ListContributionOptionsService` (`__invoke`): sin `Compensation` devuelve vacío; `projectIds()` une los proyectos adheridos por la cuenta con `sharedProjectIds()` (marcados `adhered: false`); los proyectos no limitados por crédito solo se ofrecen si la cuenta está adherida; para `EmissionCompensation` llama a `select()` y descarta el proyecto si no hay origen. En `ownerMode` mantiene la rama de siempre (`for()`/`ownFor()`) y no consulta proveedores.
3. `CheckoutController` → `ContributionCheckoutHandler` cotiza (`QuoteContributionRequest` con los dos campos nuevos) y calcula `maxFreeAmount` a partir de `availableTonnes` (el máximo entre candidatos).
4. `QuoteContributionService`: exige `Compensation`; exige adhesión **o** (proyecto limitado por crédito y existe candidato compartido); calcula importes; `select(..., tonnes)` fija `creditAccountId` (cae en la cuenta solicitante si no hay origen); `availableTonnes` = `maxAvailableTonnes()`.
5. `RegisterProjectContributionService::__invoke`: cotiza para conocer `creditAccountId`, y ejecuta `reserve()` dentro de `CreditReservationLock::withLock($creditAccountId, $projectId, …)`. Dentro del bloqueo vuelve a cotizar; si el origen cambió o `tonnes > availableTonnes`, lanza `InsufficientProjectCreditException`. Registra la aportación con `accountId` = cuenta del hotel y `creditAccountId` = origen. El `ContributionCheckoutHandler` captura la excepción y muestra «Este proyecto ya no tiene crédito suficiente para compensar tu huella.» (`:220`).
6. Tras el pago, `AllocateContributionCreditsService` bloquea por `creditAccountId`, asigna FIFO solo sobre lotes validados del tipo `allowedCreditPaymentType()` y registra la asignación. El suscriptor `CheckCreditAlertsOnCreditsAllocatedSubscriber` lanza la comprobación de alertas sobre la cuenta del crédito (distribuidor).
7. La reasignación diaria (`ReallocateAllUnderallocatedContributionsService`, agrupa por `creditAccountId`, `:62`) y la disparada por un lote validado recuperan aportaciones infra-asignadas respetando el tipo de lote.

El bloqueo (`DoctrineCreditReservationLock`) toma la fila de adhesión (cuenta, proyecto) del distribuidor: las reservas de todos sus distribuidos en un proyecto se serializan.

## Autorización y ámbito

- No hay casos de uso ni acciones nuevas. Los servicios públicos de opciones, cotización y registro no reciben `User`.
- El interruptor `allowSharedCredit` (por defecto `false`) en `ListContributionOptionsRequest`, `QuoteContributionRequest` y `RegisterProjectContributionRequest` solo lo activan los controladores `Public/PropertyTrip` y `Public/AttendeeSurvey`. `OwnerContribution/*` no lo fija: ni la compensación gratuita (`CompensateWithOwnCreditService`) ni la de pago del Propietario ven crédito compartido.
- Requisitos: la cuenta distribuida y el distribuidor tienen `AccountFeature::Compensation`; el distribuidor es distribuidora activa; el proveedor está adherido al proyecto (`hasJoined()`).
- Asignación por cuenta aplica a los tres flujos públicos; por propiedad, solo a la calculadora de esa propiedad (`propertyId`).
- Visibilidad: el distribuido no ve el crédito (sus saldos y lotes salen de `creditAccountId` = su cuenta); sus aportaciones siguen listándose por `accountId`. En el detalle de un lote del distribuidor, el enlace «Ver» de una aportación ajena se oculta con `is_authorized('view')` (`_allocations.html.twig:137`); el nombre de origen sigue visible (decisión del usuario en M2).

## Persistencia y migración

- `project_contributions.credit_account_id` `VARCHAR(36) NOT NULL` (tipo `account_id`), índice `idx_project_contribution_credit_balance (credit_account_id, project_id, status)` (mapping `ProjectContribution.ProjectContribution.orm.xml`).
- Migración aplicada: `migrations/Version20261116121056.php`: `ADD … DEFAULT NULL`, `UPDATE project_contributions SET credit_account_id = account_id`, `MODIFY … NOT NULL`, `CREATE INDEX`; `down()` elimina índice y columna. El fichero `migration.sql` de la carpeta del plan es solo el borrador original.
- El código nuevo no puede desplegarse sin la migración (todas las consultas leen la columna).

## Configuración y operación

- Alias del puerto en `src/Balance/Infrastructure/Symfony/DependencyInjection/services.yaml:57`.
- Sin variables de entorno, colas ni comandos nuevos. Se reutilizan: barrido diario de reasignación (`CommandSchedule`, `ReallocateContributionsCommand`) y barrido de alertas de crédito.
- Retirar una asignación de distribuidor no es retroactivo: las aportaciones pagadas conservan sus lotes y las reservas pendientes siguen consumiendo crédito del distribuidor hasta pagarse o expirar.
- Agotamiento silencioso: los distribuidos consumen el saldo del distribuidor; su aviso son las alertas de crédito.

## Cómo extenderlo

- **Otro proveedor de crédito compartido** (p. ej. consultoras): implementar `SharedCreditProviders::for()` en un adaptador nuevo o ampliar `distributorsOf()`; el selector no cambia. Orden de la lista = orden de preferencia.
- **Compartir lotes `PaidByClient`**: exigiría abonar el neto del libro mayor a una cuenta distinta de la de la aportación (`RecordContributionLedgerEntriesService`); hoy es la razón para limitar a `PaidByBioscore`. Habría que tocar `allowedCreditPaymentType()`, `sharedFor()` y tesorería a la vez.
- **Activar el crédito compartido en otro flujo**: pasar `allowSharedCredit: true` (y `propertyId` si procede) en su `ContributionContext` y peticiones; nunca en flujos del Propietario.
- **Trampas**: toda consulta nueva de consumo debe filtrar por `creditAccountId`, no por `accountId`; cualquier lógica de asignación debe pedir el tipo de lote a `allowedCreditPaymentType()`; una aportación nueva se crea siempre vía `RegisterProjectContributionService`.

## Tests

- Unitarios: `tests/Unit/Balance/Application/CreditPurchase/Balance/CreditSourceSelectorTest.php` (+ `CreditSourceSelectorMother`), `CreditAvailabilityTest` (`sharedFor`, reservas compartidas sin lote), `Options/ListContributionOptionsServiceTest`, `Quote/QuoteContributionServiceTest`, `RegisterProjectContributionServiceTest` (incluye origen que cambia entre cotizaciones con `FakeCreditReservationLock::beforeReservation`), `Allocate`/`Reallocate` (incl. `ReallocateAll…`), `ProjectContributionTest`, el test abstracto de repositorio (InMemory y Doctrine), `DistributedPropertiesPolicyTest` (`distributorsOf`), `CheckCreditAlertsOnCreditsAllocatedSubscriberTest`.
- Aceptación (Behat): `tests/Acceptance/Balance/shared-credit-distributor.feature` (11 escenarios: huésped de propiedad adherida, importe libre, crédito propio suficiente, lotes `PaidByClient` no compartidos, asignación por propiedad frente a calculadora de la cuenta, cliente de la calculadora de la cuenta, sin `Compensation` en cualquiera de las dos cuentas, asistente de encuesta, Propietario sin crédito compartido al compensar y en «Créditos», enlace «Ver» ausente en el lote del distribuidor); `credit_ranges_check.feature` ampliado.
- Ejecución (el `Makefile` no define `test-this`): `make test-unit options=<ruta>`, `make test-acceptance options=<ruta>`, `make test-parallel` para todo; calidad con `make cs-fix` y `make phpstan`.

## Desviaciones y deuda

- Declaradas en `## Implementación` de [plan.md](plan.md): `creditAccountId` es `private` (no `protected`); getter del evento siempre devuelve `AccountId`; `QuoteContributionService` ya no inyecta `CreditAvailability`; closure `beforeReservation` en el fake del bloqueo; pasos Behat nuevos.
- [review.md](review.md): 6 hallazgos (C1, A1, A2, M1, M2, M3), todos corregidos; ninguno abierto. Puntos que siguen vigentes: la variante FIFO (aportaciones de pago propias del distribuidor que consumen lotes Bioscore por FIFO antes que reservas compartidas pendientes) queda sin cerrar, como asumía la opción elegida para C1; el plan (tabla t1) debe actualizarse en una iteración futura; en el detalle del lote el distribuidor sigue viendo el nombre de origen de aportaciones ajenas (opción 2 de M2).
- La deuda declarada en el plan (reasignación `OwnCredit` con cualquier lote) está corregida (M3).
