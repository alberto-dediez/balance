# Estadísticas de huella organizacional en el panel de control — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-05 · **Bounded Contexts:** `CarbonFootprint` (lee `Property`, `Security`; ajustes menores de traducción en `Materiality`)

## Qué hace y por qué

El panel de control del Propietario (`/`) solo resumía eventos, compensaciones y formularios. Esta funcionalidad añade un bloque **«Huella organizacional»** con las estadísticas de las huellas de propiedad y de empresa de la cuenta: recuentos, emisiones del último año con huella de propiedad completada (con desglose por alcance y variación interanual), serie anual, ranking de propiedades y últimas huellas.

Decisiones clave (tabla «Decisiones tomadas» del plan):

- El cálculo vive en un colaborador invocable, `OrganizationalDashboardCalculator`, inyectado en `GetOwnerDashboardService` (mismo patrón que `DashboardCompensationsCalculator`).
- Se calcula **solo si la cuenta tiene la funcionalidad `OrganizationalCarbonFootprint`**; si no, `GetOwnerDashboardResponse::$organizational` es `null` y no se consulta ninguna huella.
- Las **emisiones salen solo de huellas de propiedad completadas**: la huella de empresa consolida las de sus propiedades y sumarlas duplicaría. Las de empresa solo aportan recuentos y aparecen en «Últimas huellas organizacionales».
- Año de referencia = último año con alguna huella de propiedad completada; el desglose por alcance se calcula (cargando actividades) solo para ese año; la serie anual usa el total persistido.
- El estado vacío de eventos ya no oculta el resto del panel: cada bloque tiene el suyo.

Cambios posteriores a la implementación inicial (hallazgos de `review.md`, ya aplicados, el código manda sobre el plan): «con plan de mitigación» cuenta huellas con al menos una `MitigationAction` (no el flag `isMitigationPlanStarted()` que prescribía el plan); el badge de estado usa `OrganizationalCarbonFootprintStatus::style()`; los años se pasan como cadena (`~ ''` / `(string)`) a los mensajes ICU en esta pantalla y en otras 57 plantillas y 6 puntos de PHP.

## Mapa de piezas

### Aplicación (`src/CarbonFootprint/Application/Dashboard/`)

| Fichero | Rol | Estado |
|---|---|---|
| `OrganizationalDashboardCalculator.php` | Calculador invocable `__invoke(AccountId): OrganizationalDashboard` | nuevo |
| `OrganizationalDashboard.php` | DTO `final readonly` con 16 campos (recuentos, año de referencia, alcances, variación, series, ranking, últimas) | nuevo |
| `GetOwnerDashboard/OrganizationalFootprintOverview.php` | Fila de «Últimas huellas»: huella, `CarbonFootprintReportSubjectType`, nombre del sujeto | nuevo |
| `GetOwnerDashboard/PropertyEmissions.php` | Fila del ranking: `Property` + `co2eq` (kg) | nuevo |
| `GetOwnerDashboard/GetOwnerDashboardService.php` (`:33`, `:157-159`) | Nueva dependencia y cálculo condicionado a la funcionalidad | modificado |
| `GetOwnerDashboard/GetOwnerDashboardResponse.php` (`:41`) | Nueva propiedad `?OrganizationalDashboard $organizational` (última) | modificado |

### Dominio

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatus.php` (`:99`) | `style()`: `Completed` → `bg-success`, resto → `bg-info` | modificado |

### Infraestructura

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Infrastructure/UI/Web/Dashboard/DashboardController.php` (`:76-77`, `:132-149`) | `organizationalParameters()` añade las series de las gráficas; con `null` devuelve `[]` | modificado |
| `templates/carbon_footprint/dashboard/_organizational.html.twig` | Parcial del bloque: cabecera, estado vacío, 4 `Kpi:Tile`, 2 gráficas por fila y tabla | nuevo |
| `templates/carbon_footprint/dashboard/dashboard.html.twig` (`:25-31`, `:32-127`, `:129-131`, `:141`) | `organizationalEnabled`, aviso general, estado vacío de eventos acotado, inclusión del parcial | modificado |

### Correcciones transversales de la review (años en ICU)

`templates/carbon_footprint/kpi/dashboard.html.twig`, `kpi/_base_year.html.twig`, 49 plantillas de `templates/security/admin/events/detail/`, correos y plantillas públicas de aportaciones, `KpiDashboardView.php`, `OperatorContributionNotifier.php`, `OrganizationalEmployeeContributionNotifier.php` y `StartMaterialityAssessmentType.php`. Ver `review.md` (B1, B4, B5).

### Tests

`tests/Unit/CarbonFootprint/Application/Dashboard/OrganizationalDashboardCalculatorTest.php` (nuevo), `GetOwnerDashboardServiceTest.php`, `tests/Unit/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatusTest.php` y `tests/Acceptance/CarbonFootprint/dashboard.feature`.

## Modelo de dominio

No hay agregados ni eventos nuevos: es una proyección de lectura sobre `PropertyCarbonFootprint`, `CompanyCarbonFootprint`, `Property`, `Company` y `MitigationAction`. No hay migración.

Reglas del calculador (`OrganizationalDashboardCalculator.php`):

- **Carga** (`:47-69`): propiedades y empresas de la cuenta (`limit: 0`), huellas de propiedad y de empresa de la cuenta (`findByFilter` ya excluye eliminadas) y todas las `MitigationAction` de la cuenta en una sola consulta; las acciones se indexan por `subjectType|carbonFootprintId`.
- **Completadas** (`:78-100`): `isCompleted()` cuenta en «completadas»; el resto es «en curso» (`inProgressFootprints = total − completadas`, `:173`). Una huella cuenta para «con plan de mitigación» si tiene al menos una acción, esté completada o no.
- **Deduplicación** (`:86-93`): una huella de propiedad completada por (propiedad, año), la de mayor `updatedAt() ?? registeredAt()` (`lastChange()`, misma regla que `SubjectFootprints::completedOf()`).
- **Serie anual** (`:111-119`): suma `totalEmissions()->co2eq()` por año (kg), `ksort`. `referenceYear` = clave máxima o `null`.
- **Desglose por alcance** (`:126-141`): con año de referencia, inicializa los tres alcances a 0 y llama a `EmissionsBreakdownBuilder::build($footprint, Scope::cases())` por cada huella deduplicada de ese año; suma `byStage[$scope->value]`. El filtro «solo alcances > 0» lo aplica el controlador (`DashboardController.php:139`).
- **Ranking** (`:144-146`, `:181`): propiedades con `co2eq > 0` del año de referencia, orden descendente, máximo `TOP_PROPERTIES = 5`.
- **Variación** (`:150-165`): `previousYear` = mayor año con huellas completadas menor que el de referencia (no necesariamente −1); `null` si no hay anterior o su total es 0.
- **Últimas** (`:194-216`): unión de huellas de propiedad y empresa (cualquier estado), orden por `registeredAt()` descendente, máximo `LATEST_FOOTPRINTS = 5`; nombre `null` si el sujeto ya no existe (la plantilla pinta `---`).
- Invariantes: `completed + inProgress = total`; sin año de referencia todas las magnitudes de emisiones son 0 y las colecciones vacías, pero los recuentos siguen calculándose. El total mostrado es el persistido y el desglose se recalcula con las actividades actuales, por lo que pueden no cuadrar si se editaron actividades tras completar.

## Flujo de una operación

`GET /` → `DashboardController::__invoke` (`DashboardController.php:27`):

1. Admin/SuperAdmin → `adminDashboard()` (sin cambios). Sin rol Owner o sin cuenta → `@shared/home.html.twig`.
2. Propietario: `GetOwnerDashboardService` con `GetOwnerDashboardRequest(accountId: $account->id())` (cuenta efectiva vía `LoggedAccount`, compatible con impersonación).
3. El servicio autoriza (`OwnerDashboardAuthorizer`, `Action::List`) y construye la respuesta; en `:157-159` llama al calculador solo si `AccountFeaturesPolicy::isEnabled(AccountFeature::OrganizationalCarbonFootprint, $request->accountId)`.
4. El controlador concatena `organizationalParameters()` (`:77`) al array de la plantilla: `organizationalYearCategories/Values`, `organizationalScopeCategories/Values` (alcances con valor > 0, traducidos con el dominio `scope`) y `organizationalPropertyCategories/Values`.
5. `dashboard.html.twig`: `organizationalEnabled = account_feature_enabled('organizational_carbon_footprint') and dashboard.organizational is not null` (`:25`); si no hay ninguna funcionalidad con bloque, aviso «No hay estadísticas disponibles…» (`:27`); si no, eventos (tarjetas o estado vacío propio), `_organizational.html.twig` (`:129-131`), compensaciones, formularios y, solo con `eventsEnabled and totalEvents > 0` (`:141`), las gráficas y tablas de eventos.
6. `_organizational.html.twig`: cabecera con enlace «Ver indicadores» (`property_carbon_footprint_kpis`); estado vacío si `totalFootprints == 0` (enlace a `property_carbon_footprints`); si no, 4 `Kpi:Tile`, `Chart:Bar` por año, `Chart:Donut` por alcance, `Chart:Bar` del ranking y tabla. Cada fila enlaza a `{property|company}_carbon_footprints_results` si está completada y a `…_edit` si no (la ruta se compone en la plantilla).

Notas de presentación: `Kpi:Tile` pinta el `footer` antes del bloque `content`, por lo que en «Huellas completadas» el texto «en curso» queda encima de la barra de progreso. La variación se pinta con `text-danger` si > 0 y `text-success` en otro caso. El tile «Variación interanual» concatena en su pie el contador «con plan de mitigación».

## Autorización y ámbito

- Una sola autorización, al inicio del servicio: `OwnerDashboardAuthorizer` (`Action::List`/`View`, rol `Owner` de la cuenta actual). El calculador no autoriza: solo lo invoca el servicio.
- Todas las consultas filtran por `$request->accountId`.
- Funcionalidad por cuenta: `AccountFeature::OrganizationalCarbonFootprint` (`organizational_carbon_footprint`, etiqueta «Huella de carbono de propiedades y empresas»). Activa por defecto en `AccountFeatures`.
- Admin/SuperAdmin: su panel no cambia. `User`: ve la página de inicio estándar.

## Persistencia y migración

Sin cambios de esquema ni de repositorios. Se usan `findByFilter` con `accountId` y `limit: 0` de `PropertyCarbonFootprintRepository`, `CompanyCarbonFootprintRepository`, `PropertyRepository`, `CompanyRepository` y `MitigationActionRepository`.

## Configuración y operación

Sin variables de entorno, colas, comandos ni correos nuevos. `services.yaml` de `CarbonFootprint` carga el contexto con autowiring: las clases nuevas no se registran. Para activar o desactivar el bloque, el Administrador usa **Administración → Cuentas → Editar → Funcionalidades**.

Traducciones: las 19 claves nuevas se añadieron con `make translation` (`messages+intl-icu.*.xlf`); el catálogo `tr` las deja con prefijo `NEW_`, como en planes anteriores.

## Cómo extenderlo

- **Añadir un KPI al bloque:** 1) campo en `OrganizationalDashboard.php`; 2) cálculo en `OrganizationalDashboardCalculator::__invoke` (mantener la regla «emisiones solo de huellas de propiedad»); 3) `Kpi:Tile` o gráfica en `_organizational.html.twig`, con series preparadas en `DashboardController::organizationalParameters()`; 4) test en `OrganizationalDashboardCalculatorTest` y claves con `make translation`.
- **Años en mensajes ICU:** pasar el año siempre como cadena (`organizational.referenceYear ~ ''` en Twig, `(string) $year` en PHP); un entero se formatea como «2,025» fuera del español.
- **Trampas conocidas:** (1) `EmissionsBreakdownBuilder::build` carga las actividades por cada huella de propiedad del año de referencia: una consulta por huella; si fuera lento, persistir el desglose al completar la huella. (2) No sumar huellas de empresa a las emisiones. (3) El año de referencia puede tener menos propiedades completadas que el anterior y la variación compara totales de conjuntos distintos (ver «Desviaciones y deuda»).

## Tests

- **Unitarios** — `OrganizationalDashboardCalculatorTest.php` (11): recuentos y año de referencia, huellas con acciones de mitigación (tipo de sujeto distinto, otra cuenta, eliminada), cinco últimas por `registeredAt`, ranking limitado a cinco, huellas de empresa no sumadas, eliminadas y en curso, deduplicación por propiedad y año, variación nula sin año anterior o con total anterior 0, desglose por alcance, cuenta vacía. `GetOwnerDashboardServiceTest.php`: `organizational` presente con la funcionalidad activa y `null` con ella desactivada (`testWithoutOrganizationalCarbonFootprintTheDashboardDoesNotIncludeItsBlock`). `OrganizationalCarbonFootprintStatusTest::testStyle`.
- **Aceptación** — `tests/Acceptance/CarbonFootprint/dashboard.feature` (11 escenarios): estado vacío con la funcionalidad activa, bloque con huella completada, enlaces de resultados de empresa y de asistente de huella en curso, funcionalidad desactivada, sin eventos pero con huella (el estado vacío de eventos coexiste con el bloque y con «CO₂eq compensado»), aviso sin ninguna funcionalidad, y Admin / sin cuenta / `User`.
- **Ejecución:** `make test-unit options="tests/Unit/CarbonFootprint/Application/Dashboard"` y `make test-acceptance options="tests/Acceptance/CarbonFootprint/dashboard.feature"` (el `Makefile` no define `test-this`).
- **Huecos:** no hay test en inglés del formateo del año en ICU (B1/B4/B5 sin test nuevo); la deriva entre total fijado y desglose no está cubierta.

## Desviaciones y deuda

- Declaradas en `## Implementación` de `plan.md`: paso Behat «with employees answered exists» (`complete()` exige `EmployeesAnswered`); los tres alcances a 0 con el filtro `> 0` en el controlador; cabecera en `d-flex`; orden pie/contenido de `Kpi:Tile`; `usleep(1000)` en el test de deduplicación; «Ver indicadores» ya existía.
- Correcciones de `review.md` que contradicen al plan: KPI de mitigación basado en `MitigationAction` (M1) y `style()` en el enum (B2).
- Abierto en `review.md`: **[Q1]**, resuelta como «plan nuevo con `feature-planner`», sin implementar. La tarjeta «Emisiones {año}» y la variación comparan totales de conjuntos de propiedades distintos: si en 2025 completaron 10 propiedades y en 2026 solo 1, el año de referencia pasa a 2026 y la variación muestra una caída engañosa (`OrganizationalDashboardCalculator.php:119-125,150-165`). Review: `review.md`.
- Deuda: las tarjetas y la tabla «Últimas huellas de carbono» de eventos siguen a mano en lugar de `Kpi:Tile`; una carga de actividades por huella del año de referencia.
