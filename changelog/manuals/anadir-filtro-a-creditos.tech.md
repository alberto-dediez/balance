# Filtro «Con toneladas disponibles» en el listado de créditos — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-11 · **Bounded Contexts:** `Balance`

## Qué hace y por qué

El listado de administración `/admin/credits` (menú Proyectos → Créditos de CO2) mostraba todos los créditos de todas las cuentas. Se añade el filtro «Con toneladas disponibles» (Todos / Sí / No), con «Sí» por defecto: muestra las solicitudes pendientes y los validados con toneladas libres; «No» muestra el complemento (validados agotados, rechazados, cancelados).

Las toneladas libres no son un dato del agregado `CreditPurchase`: lo compensado vive en `ProjectContributionCreditAllocation` (agregado `ProjectContribution`). Para que el filtro conviva con la paginación se resuelve en la consulta del repositorio con una subconsulta, sin columnas nuevas ni migración. La regla se duplica deliberadamente: DQL en Doctrine y `CreditPurchase::hasTonnesToManage()` en InMemory, y un test abstracto de repositorio corre contra ambos adaptadores (decisiones «Dónde vive la regla» y «Cómo se filtra» del plan).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Balance/Domain/Model/CreditPurchase/CreditPurchase.php:205` | `hasTonnesToManage(float $allocatedTonnes)`: regla de «con toneladas por gestionar» | modificado |
| Dominio | `src/Balance/Domain/Model/CreditPurchase/CreditPurchaseFilter.php:15,25,28` | Propiedad `?bool $withAvailableTonnes` y último parámetro del constructor | modificado |
| Infraestructura | `src/Balance/Infrastructure/Persistence/Doctrine/Repository/DoctrineCreditPurchaseRepository.php:133-162` | Condición DQL con subconsulta de asignaciones | modificado |
| Infraestructura | `src/Balance/Infrastructure/Persistence/InMemory/Repository/InMemoryCreditPurchaseRepository.php:104-127` | Filtro en memoria; recibe `ProjectContributionRepository` opcional | modificado |
| Infraestructura | `src/Balance/Infrastructure/Symfony/Form/ListCreditPurchasesType.php:45` | Campo `withAvailableTonnes` (`BooleanType`, placeholder «Todos») | modificado |
| Infraestructura | `src/Balance/Infrastructure/UI/Web/Admin/CreditPurchase/ListController.php:44` | Valor por defecto `true` en el closure de `FilterTrait::filter()` | modificado |
| i18n | `translations/messages+intl-icu.{es,en,tr}.xlf` | «Con toneladas disponibles» (EN «With available tonnes», TR «Kullanılabilir tonu olan») | modificado |
| Tests | `tests/Acceptance/Balance/credit_purchase_list_filter.feature` (nuevo), `BalanceContext.php`, `CreditPurchaseRepositoryTest`, `CreditPurchaseTest`, `InMemoryCreditPurchaseRepositoryTest`, `DoctrineCreditPurchaseRepositoryTest`, `ListCreditPurchasesServiceTest` | Cobertura | nuevo/modificado |

Sin cambios: `list.html.twig`, el servicio `ListCreditPurchasesService`, `ListCreditPurchasesRequest` (extiende `CreditPurchaseFilter`, por lo que hereda la propiedad), el puerto `CreditPurchaseRepository` y el mapping Doctrine.

## Modelo de dominio

Regla (`CreditPurchase.php:205-213`):

- `Pending` → siempre `true`.
- `Validated` → `availableTonnes() - allocatedTonnes > CreditBalance::TONNE_TOLERANCE` (`CreditBalance.php:11`, 0,000001).
- `Rejected`, `Cancelled` (y cualquier otro estado) → `false`.

`availableTonnes()` (`CreditPurchase.php:196`) es `max(0, purchased - offset - upperOffset - voided)`. Las toneladas asignadas son la suma de todas las asignaciones del crédito, sin mirar el estado de la aportación (igual que `ListCreditPurchaseAllocationsService`). `purchasedTonnes` es nullable en el mapping, pero un crédito validado siempre lo tiene (`validate()` exige > 0).

No hay eventos ni excepciones nuevas.

## Flujo de una operación

1. `GET /admin/credits` → `ListController::__invoke` (`ListController.php`).
2. `FilterTrait::filter()` (`src/Shared/.../FilterTrait.php:39`) recupera el filtro de sesión o lo crea con el closure `new ListCreditPurchasesRequest(withAvailableTonnes: true)` (`ListController.php:44`). «Resetear filtro» recrea el filtro con el mismo closure, así que vuelve a «Sí».
3. `ListCreditPurchasesService` autoriza con `Action::List` y llama a `findByFilter` y `totalResultsByFilter` con la request (que es un `CreditPurchaseFilter`).
4. Doctrine: `createQueryBuilderFromFilter` (`DoctrineCreditPurchaseRepository.php:83`) añade, si `withAvailableTonnes !== null` (`:133`):
   - `$unallocatedTonnes = cp.purchasedTonnes - cp.offsetTonnes - cp.upperOffsetTonnes - cp.voidedTonnes - :tonneTolerance` y subconsulta `(SELECT COALESCE(SUM(a.tonnes), 0) FROM ProjectContributionCreditAllocation a WHERE a.creditPurchaseId = cp.id)`.
   - `true`: `status = pending OR (status = validated AND unallocated > allocated)`.
   - `false`: `status != pending AND (status != validated OR unallocated <= allocated)`.
   - Parámetros: `pendingStatus`, `validatedStatus`, `tonneTolerance`. La tolerancia está restada en el lado de las toneladas (equivalente algebraico a `libres > tol`; desviación declarada en el plan).
5. InMemory: `matches` (`:104-107`) compara `withAvailableTonnes` con `hasTonnesToManage(allocatedTonnes())`; `allocatedTonnes()` suma `findAllocationsByCreditPurchase()` del repositorio de aportaciones, o `0.0` si no se inyectó.

## Autorización y ámbito

Sin cambios: el listado sigue autorizando con `Action::List` en el servicio y la ruta es de administración. El filtro solo afecta a esa pantalla.

El valor por defecto `true` está en el controlador y **no** en `CreditPurchaseFilter` porque el filtro lo construyen también `CreditAvailability`, `AllocateContributionCreditsService`, `ReallocateUnderallocatedContributionsService`, `AssignCreditPurchaseService`, `CheckCreditRangesService`, `ListAccountCreditsService`, `BalanceController` y los controladores de tesorería, que deben seguir viendo todos los créditos (`null` = sin filtro). Ninguna otra construcción de `CreditPurchaseFilter` es posicional, por lo que el parámetro nuevo al final del constructor no rompe a nadie.

## Persistencia y migración

Sin migración ni columnas nuevas. La consulta usa la subconsulta correlacionada sobre la tabla de asignaciones `ProjectContributionCreditAllocation`. El filtro se serializa en sesión; en sesiones antiguas la propiedad no promovida se inicializa a `null` al deserializar (equivale a «Todos»).

## Configuración y operación

Sin variables de entorno, colas, comandos ni tareas programadas. El `InMemoryCreditPurchaseRepository` solo existe en entorno de test (`#[When('test')]`).

## Cómo extenderlo

- Para otra condición de «toneladas por gestionar», cambiar **a la vez** `CreditPurchase::hasTonnesToManage()` y la DQL de `DoctrineCreditPurchaseRepository.php:133-162`; el test abstracto de repositorio detecta la divergencia entre adaptadores.
- Para usar el filtro desde otro listado, pasar `withAvailableTonnes` al construir `CreditPurchaseFilter` (el controlador decide el valor por defecto, nunca el filtro).
- Trampa: en tests InMemory hay que inyectar `projectContributionRepository` en `InMemoryCreditPurchaseRepository`; sin él las asignaciones cuentan 0 y un crédito totalmente asignado aparece como disponible. `InMemoryCreditPurchaseRepositoryTest.php:19` lo hace.

## Tests

- Unitarios: `CreditPurchaseTest` (regla en dominio), `CreditPurchaseRepositoryTest` abstracto con variantes InMemory y Doctrine (SQLite) — incluye `testAvailableTonnesFilterHonoursOffsetsTransfersAndTolerance`, que cubre offsets, transferencia más asignación y tolerancia — y `ListCreditPurchasesServiceTest`.
- Aceptación: `tests/Acceptance/Balance/credit_purchase_list_filter.feature` (8 escenarios: oculto por defecto agotado/transferido, parcial visible, pendiente y rechazada, «No», «Todos», persistencia en sesión y reseteo), con el paso `I filter the admin credits list by available tonnes :choice` de `BalanceContext.php:454`.
- Ejecución: `make test-unit options="--filter CreditPurchaseRepositoryTest"` y `make test-acceptance options="tests/Acceptance/Balance/credit_purchase_list_filter.feature"`; `make phpstan`, `make cs`.
- `ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriberTest.php` se tocó (reset del publisher en `tearDown`) por un efecto de aislamiento de tests, ajeno a la funcionalidad.

## Desviaciones y deuda

- Desviación declarada en `## Implementación` del plan (`plan.md`): la tolerancia se resta en el lado de las toneladas de la DQL; equivalente algebraicamente.
- La regla está duplicada (DQL y método de dominio) por decisión del plan.
- `review.md`: 0 hallazgos abiertos; B1, B2 y B3 (cobertura de repositorio, escenarios Behat y claves turcas `NEW_`) corregidos.
- Las claves turcas de este cambio incluyen también las de la funcionalidad de tokens de API (`translations/apiTokenStatus+intl-icu.tr.xlf` nuevo), por el extractor.
