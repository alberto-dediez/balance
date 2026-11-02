# Panel de indicadores de intensidad de la huella organizacional — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-02 · **Bounded Contexts:** `CarbonFootprint` (lee `Property` y `Security`; toca `Shared` en `NodeLister` y `CommandSchedule`)

## Qué hace y por qué

Las huellas de propiedad y de empresa ya calculaban el inventario (alcances 1, 2 y 3). Esta funcionalidad lo convierte en herramienta de gestión: el Propietario declara en cada huella los **datos de actividad del año** (en código, *denominadores*: habitaciones ocupadas, pernoctaciones, m², cubiertos, plantilla media, facturación, clientes) y el **Panel de indicadores** calcula, por propiedad o por empresa, indicadores de intensidad (kg CO₂eq por unidad), serie temporal, descomposición de la variación interanual, comparación entre propiedades (empresa), año base declarado con aviso de deriva y una referencia sectorial anónima.

Decisiones clave (tabla «Decisiones tomadas» del plan):

- El panel es por **sujeto** (`KpiSubject`: propiedad o empresa), no por huella; solo entran huellas **completadas** (únicas con total fijado).
- Los denominadores viven en la huella como value object embebido (columnas planas nulables) y se editan también con la huella completada, porque no alteran el inventario.
- Solo se persisten denominadores, `BaseYearDeclaration` y `SectorBenchmark`. Inventario, indicadores, series y descomposición se **recalculan en cada petición**.
- Autorización con un authorizer propio sobre `KpiSubject`, solo rol `Owner` de la cuenta efectiva (impersonación incluida).

Divergencias del plan relevantes (el código manda, ver «Desviaciones y deuda»): la referencia sectorial se publica con **≥ 10 huellas, ≥ 5 cuentas y ninguna cuenta > 40 %** (el plan decía 5/3), y el total mostrado en el panel es el **fijado al completar** (`totalEmissions()`), con un aviso cuando el desglose recalculado difiere.

## Mapa de piezas

### Dominio (`src/CarbonFootprint/Domain/Model/`)

| Fichero | Rol | Estado |
|---|---|---|
| `OrganizationalCarbonFootprint/IntensityDenominators.php` | VO embebido de 7 campos nulables; `> 0` o `\InvalidArgumentException` (`:20`); `denominatorFor()` (`:90`, facturación / 1 000 000), `equals()`, `sum()` | nuevo |
| `OrganizationalCarbonFootprint/IntensityIndicator.php` | Enum de los 7 indicadores y `denominatorUnitKey()` | nuevo |
| `OrganizationalCarbonFootprint/IntensityIndicatorsCalculator.php` | Servicio de dominio puro: `calculate()` (`:10`) solo devuelve indicadores con denominador; `intensity()` (`:31`) redondea a 2 decimales (4 si `< 1`) | nuevo |
| `OrganizationalCarbonFootprint/IntensityIndicatorValue.php`, `GasBreakdown.php`, `InventoryLine.php`, `InterannualVariation.php` | DTOs readonly de dominio | nuevo |
| `OrganizationalCarbonFootprint/InterannualVariationDecomposer.php` | Descomposición en actividad / factores / eficiencia (`decompose()` `:13`) | nuevo |
| `OrganizationalCarbonFootprint/OrganizationalCarbonFootprintTrait.php` (`:22,49`) | Propiedad `intensityDenominators` y getter | modificado |
| `PropertyCarbonFootprint/PropertyCarbonFootprint.php` (`:58,364,510`), `CompanyCarbonFootprint/CompanyCarbonFootprint.php` | Inicializan a `empty()`, `declareIntensityDenominators()` (no hace nada si `equals`; no exige estado), `apply…Declared` | modificado |
| `PropertyCarbonFootprint/PropertyCarbonFootprintIntensityDenominatorsDeclared.php`, `CompanyCarbonFootprint/CompanyCarbonFootprintIntensityDenominatorsDeclared.php` | Eventos con los siete campos | nuevo |
| `PropertyCarbonFootprintRepository.php:26`, `CompanyCarbonFootprintRepository.php:26` | `findCompletedByYear(int): list<…>` (todas las cuentas, solo lo usa el refresco sectorial) | modificado |
| `BaseYearDeclaration/{BaseYearDeclaration,BaseYearDeclarationId,BaseYearDeclarationRepository,BaseYearDeclared,BaseYearDeclarationRemoved,BaseYearNotCompletedException}.php` | Agregado «año base» por sujeto | nuevo |
| `SectorBenchmark/{SectorBenchmark,SectorBenchmarkId,SectorBenchmarkRepository,SectorBenchmarkComputed,SectorBenchmarkSample,SectorBenchmarkStatistics}.php` | Agregado de referencia sectorial y su cálculo estadístico | nuevo |

### Aplicación (`src/CarbonFootprint/Application/`)

| Fichero | Rol | Estado |
|---|---|---|
| `PropertyCarbonFootprint/DeclareIntensityDenominators/*`, `CompanyCarbonFootprint/DeclareIntensityDenominators/*` | Caso de uso de declarar denominadores (autoriza `Action::Edit` con el authorizer de la huella) | nuevo |
| `CompanyCarbonFootprint/CompanyIntensityDenominatorsDefaults.php` | Suma de denominadores de las huellas de propiedad del mismo año de las propiedades consolidadas | nuevo |
| `CarbonFootprintKpi/{KpiSubject,KpiSubjectResolver,KpiSubjectOption,CarbonFootprintKpiAuthorizer,SubjectFootprints}.php` | Sujeto, resolución, autorización y selección de huellas completadas | nuevo |
| `CarbonFootprintKpi/{InventoryResults,InventoryResultsBuilder,GasBreakdownCalculator,TimeSeriesPoint,PropertyComparisonRow}.php` | Cálculo del inventario del panel | nuevo |
| `CarbonFootprintKpi/ListSubjects/*` | Opciones del selector de sujeto | nuevo |
| `CarbonFootprintKpi/GetDashboard/*` | Caso de uso principal del panel | nuevo |
| `BaseYearDeclaration/{BaseYearDeclarationAuthorizer}.php`, `Declare/*`, `Remove/*` | Declarar / actualizar y eliminar el año base | nuevo |
| `SectorBenchmark/{SectorBenchmarkAuthorizer}.php`, `Refresh/*` | Refresco de la referencia sectorial (sistema) | nuevo |

### Infraestructura

| Fichero | Rol | Estado |
|---|---|---|
| `Infrastructure/Persistence/Doctrine/Mapping/OrganizationalCarbonFootprint.IntensityDenominators.orm.xml` | Embeddable (prefijo `denominator_`) | nuevo |
| `…/Mapping/{BaseYearDeclaration.BaseYearDeclaration,SectorBenchmark.SectorBenchmark}.orm.xml`, `…/Type/{BaseYearDeclarationIdType,SectorBenchmarkIdType}.php` | Mappings y tipos Doctrine (registrados en `config/packages/doctrine.yaml:118-119`) | nuevo |
| `…/Mapping/PropertyCarbonFootprint.PropertyCarbonFootprint.orm.xml`, `CompanyCarbonFootprint.CompanyCarbonFootprint.orm.xml` | Embebido `intensityDenominators` | modificado |
| `…/Repository/Doctrine{BaseYearDeclaration,SectorBenchmark}Repository.php`, `InMemory/InMemory{BaseYearDeclaration,SectorBenchmark}Repository.php`; los cuatro repositorios de huellas | Adaptadores | nuevo / modificado |
| `Infrastructure/Symfony/Form/{IntensityDenominatorsType,DeclareBaseYearType}.php` | Formularios | nuevo |
| `Infrastructure/UI/Web/CarbonFootprintKpi/{DashboardController,DeclareBaseYearController,RemoveBaseYearDeclarationController}.php`, `View/KpiDashboardView.php` | Panel, alta/edición y baja del año base, datos para gráficas y tablas | nuevo |
| `Infrastructure/UI/Web/{PropertyCarbonFootprint,CompanyCarbonFootprint}/Step/DeclareIntensityDenominatorsController.php` | POST de los datos de actividad | nuevo |
| `Infrastructure/UI/Web/{PropertyCarbonFootprint,CompanyCarbonFootprint}/Step/ResultsController.php` | Crean `denominatorsForm`, precarga de empresa y de estancias del certificado | modificado |
| `Infrastructure/UI/Console/RefreshSectorBenchmarksCommand.php` | `carbon-footprint:sector-benchmarks:refresh <username>` | nuevo |
| `src/Shared/Application/CommandBus/CommandSchedule.php:74-78` | Entrada diaria 03:00 | modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:580,602` | Nodos «Indicadores» en «Propiedades» y «Empresas» | modificado |
| `templates/carbon_footprint/kpi/{select,dashboard,_inventory,_lines,_intensity,_evolution,_variation,_comparison,_base_year}.html.twig` | Pantallas | nuevo |
| `templates/carbon_footprint/organizational_carbon_footprints/_intensity_denominators.html.twig` | Tarjeta «Datos de actividad del año» (incluida en `step/results.html.twig:37` de propiedad y empresa) | nuevo |
| `templates/components/Kpi/Tile.html.twig`, `templates/components/List/Action/Statistics.html.twig`, `templates/components/Link.html.twig` (variante `outlineDanger`) | Componentes | nuevo / modificado |
| `templates/carbon_footprint/translations/intensityIndicator.html.twig`, `translations/intensityIndicator+intl-icu.{es,en,tr}.xlf` | Dominio de traducción de indicadores y unidades | nuevo |
| `migrations/Version20261102101743.php` | Migración aplicada | nuevo |

## Modelo de dominio

**`IntensityDenominators`** (`final readonly`): `occupiedRooms`, `stays`, `coversServed`, `customersServed` (`?int`) y `builtAreaM2`, `averageHeadcount`, `revenueEur` (`?string` decimal). Cualquier valor no nulo `≤ 0` lanza `\InvalidArgumentException` en el constructor. `IntensityIndicator` ↔ campo: `PerOccupiedRoom`→`occupiedRooms`, `PerStay`→`stays`, `PerSquareMeter`→`builtAreaM2`, `PerCover`→`coversServed`, `PerEmployee`→`averageHeadcount`, `PerMillionEuroRevenue`→`revenueEur / 1 000 000`, `PerCustomer`→`customersServed`.

**Eventos de huella:** `PropertyCarbonFootprintIntensityDenominatorsDeclared` / `CompanyCarbonFootprintIntensityDenominatorsDeclared`. `declareIntensityDenominators()` es idempotente (mismo valor → no publica) y válido en cualquier estado, incluida la huella completada.

**`BaseYearDeclaration`** (`AggregateRoot`; único por `subjectType + subjectId`): `year`, `justification`, `recalculationThresholdPercent`, `primaryIndicator`, `baseYearTotalKgCo2eq` (foto), `registeredAt`, `updatedAt`. Eventos `BaseYearDeclared` (alta y redeclaración, mismo evento) y `BaseYearDeclarationRemoved`. Invariantes en `BaseYearDeclaration.php:181,185`: justificación no vacía tras `trim`, umbral en `(0, 100]`; ambas lanzan `\InvalidArgumentException`. «El año tiene huella completada» se valida en el servicio (`BaseYearNotCompletedException`). `exceedsThreshold()` (`:64`) compara `|vigente − foto| / foto × 100` con el umbral (foto 0 → cualquier total ≠ 0 excede); `changePercent()` (`:75`) alimenta el texto del aviso.

**`SectorBenchmark`** (`AggregateRoot`): una fila por `subjectType + segmentKey + year + indicator` con `sampleSize`, `accountCount`, `median`, `percentile25`, `percentile75`, `mean`, `computedAt`; evento `SectorBenchmarkComputed`. `isPublishable()` (`SectorBenchmark.php:123`) repite el umbral de anonimato que usa el refresco; el panel comprueba ambos (`GetCarbonFootprintKpiDashboardService.php:214`).

**`SectorBenchmarkStatistics::from()`** (`SectorBenchmarkStatistics.php:22-51`): devuelve `null` si hay menos de `MIN_SAMPLE_SIZE = 10` muestras, menos de `MIN_ACCOUNT_COUNT = 5` cuentas o alguna cuenta aporta más de `MAX_ACCOUNT_SHARE = 0.4` de la muestra. Percentiles por interpolación lineal sobre la muestra ordenada. Las constantes son la única fuente de verdad del anonimato; con n ≥ 10 los percentiles caen en posiciones no enteras y ninguna cuenta puede despejar valores ajenos (hallazgo A1 de `review.md`).

**Descomposición** (`InterannualVariationDecomposer.php:13-56`), con `E` total fijado, `D` denominador del indicador principal y líneas emparejadas por `InventoryLine::matchingKey()` (alcance + fuente + nombre de elemento + unidad; las repetidas se agregan con cantidad sumada y factor medio ponderado):

- `ΔE = E₁ − E₀`
- `A = (D₁ − D₀) × E₀ / D₀` (nulo si falta indicador principal o algún `D`, o `D₀ = 0`)
- `F = Σ Q₁ × (F₁ − F₀)` solo para claves presentes en ambos años
- `X = ΔE − A − F` (eficiencia; «actividad y eficiencia» cuando `A` es nulo). La suma de los efectos siempre cuadra con `ΔE`.

## Flujo de una operación

### Declarar datos de actividad

`POST /property-carbon-footprints/{id}/step/results/activity-data` (`property_carbon_footprints_declare_denominators`; espejo `company_carbon_footprints_declare_denominators`) → `DeclareIntensityDenominatorsController` (`denyAccessUnlessGranted(Edit)`, `IntensityDenominatorsType` con `data_class` del request) → `DeclareIntensityDenominatorsService` (authorizer de la huella `Action::Edit`; construye `IntensityDenominators`; `declareIntensityDenominators()`; `add()`) → flash `Datos de actividad guardados.` y redirección a Resultados. Formulario inválido → flash `danger` con el primer error.

`ResultsController` (propiedad: `Step/ResultsController.php:65-98`; empresa: `Step/ResultsController.php:62-100`) crea `denominatorsForm`. En empresa, solo si `intensityDenominators()->isEmpty()`, se usa `CompanyIntensityDenominatorsDefaults::for()` como valor inicial y `denominatorsPrefilled = true`; no se guarda hasta que el Propietario pulsa el botón. En propiedad, `certificateForm` se precarga con `intensityDenominators()->stays()` (`:65`).

### Ver el panel

`GET /property-carbon-footprints/kpis/{propertyId?}` (`property_carbon_footprint_kpis`) o `/company-carbon-footprints/kpis/{companyId?}` (`company_carbon_footprint_kpis`), con `year` e `indicator` opcionales → `DashboardController` (`:32-110`):

1. `ListKpiSubjectsService` (autoriza `List`) devuelve las opciones de la cuenta efectiva. Sin sujeto: una sola opción → redirige; si no, `select.html.twig`. El sujeto también llega por query (`?propertyId=` / `?companyId=`) porque los selectores GET lo envían así.
2. `GetCarbonFootprintKpiDashboardService::__invoke` (`:58`): resuelve `KpiSubject` (nulo → `\InvalidArgumentException` → 404), **autoriza `View` antes de cargar huellas** (`:66`), `SubjectFootprints::completedOf()` (no eliminadas, completadas, una por año: gana la de `updatedAt ?? registeredAt` más reciente). Sin huellas → `hasData = false`.
3. Año seleccionado: el pedido si está entre los completados, si no el mayor (`:80`).
4. `InventoryResultsBuilder::build()` (`InventoryResultsBuilder.php:33`): `EmissionsBreakdownBuilder` (desglose recalculado), líneas de `OrganizationalCarbonFootprintReportLinesBuilder` + `DerivedSourceEmissionsCalculator` (categoría 3 WTT y propiedades no consolidadas), contribución relativa por alcance·fuente, `GasBreakdownCalculator` y doble reporte (`dualReportingActive` = hay electricidad con resultado por localización y la huella no es «ya calculada»). `totalKg` es `totalEmissions()` (fijado); `recalculatedTotalKg` es el del desglose.
5. Indicadores = `IntensityIndicatorsCalculator::calculate(inventory.totalKg, denominadores de la huella)`.
6. Serie (`:151`): por huella completada, total fijado, total por alcance recalculado, indicadores y `isBaseYear`.
7. Año base: `BaseYearDeclarationRepository::findBySubject()`; `baseYearAlert` = la declaración excede el umbral con el total vigente (de la serie).
8. Variaciones: contra el año anterior completado y, si el año base existe, está completado y no es ni el seleccionado ni el anterior, contra el año base (`:98-103`). Si el año base es posterior al seleccionado la variación se calcula del año base hacia el seleccionado (pregunta Q2 de `review.md`, aceptada).
9. Referencia sectorial: por indicador con dato, `findBySegment($type, $segmentKey, $year, $indicator)` y `isPublishable()`.
10. Solo empresa: `CompanyProperties::all()` → `findByPropertyIdAndYear()`; fila si la huella existe, no está eliminada, está completada y tiene el denominador del indicador de comparación (por defecto indicador principal del año base o primero con dato); si no, `propertiesWithoutData` con motivo `no_completed_footprint` o `no_denominator` (`:222-260`).

`KpiDashboardView::build()` prepara series de gráficas (una gráfica de descomposición por comparación), filas de contribución, gas, evolución y comparación para las plantillas.

### Año base

`POST /carbon-footprint-kpis/{subjectType}/{subjectId}/base-year` (`base_year_declarations_declare`) → `DeclareBaseYearController` (los años permitidos del formulario se toman del valor enviado y se validan en el servicio) → `DeclareBaseYearService` (`:33-72`): autoriza `Edit` con `CarbonFootprintKpiAuthorizer`, exige huella completada del año (`SubjectFootprints::completedOfYear()`), toma como foto `totalEmissions()->co2eq()` y crea (`BaseYearDeclaration::declare`) o actualiza (`redeclare`). `BaseYearNotCompletedException` → flash `danger`.

`GET /carbon-footprint-kpis/base-year/{id}/remove` (`base_year_declarations_remove`) → `RemoveBaseYearDeclarationController`, con `#[CsrfProtected('remove')]` y `_token` en el enlace; el servicio autoriza `Remove` con `BaseYearDeclarationAuthorizer`.

### Refresco de la referencia sectorial

`RefreshSectorBenchmarksService::__invoke` (`:45-91`): autoriza `Register` con un `SectorBenchmark` desechable (patrón de `SyncExchangeRatesService`, ver deuda); recorre `[añoActual − 10, añoActual]` (`YEARS_BACK`); agrupa por tipo + segmento + año + indicador las muestras `(accountId, intensidad)` de `findCompletedByYear` de propiedad y de empresa; `SectorBenchmarkStatistics::from()`; `replaceAll()` sustituye todo en una transacción (`DoctrineSectorBenchmarkRepository::replaceAll`, un único `flush`). Segmento de propiedad = `PropertyType->value` (`other` si nulo) vía `KpiSubjectResolver::segmentKeyOf()`; empresas = `KpiSubject::COMPANY_SEGMENT`.

## Autorización y ámbito

| Pieza | Regla |
|---|---|
| `CarbonFootprintKpiAuthorizer` (`:19-45`) | `canView` y `canEdit`: rol `Owner` (`userHasRole`) y `currentAccountId($user)` igual a la cuenta del sujeto (que no puede ser nula). `canList`: `Owner` con cuenta actual. `canRegister`/`canRemove`: `false`. |
| `BaseYearDeclarationAuthorizer` | `View`/`Edit`/`Remove`: `Owner` con cuenta actual igual a la de la declaración |
| `SectorBenchmarkAuthorizer` | Solo `canRegister` = `canAdmin` (Admin/SuperAdmin); `View`, `List`, `Edit`, `Remove` siempre `false`: no hay pantalla de la referencia |
| Denominadores | Authorizer existente de la huella (`Edit`), igual que el resto de Resultados |

`Admin`/`SuperAdmin` no ven paneles. Con impersonación la cuenta efectiva es la impersonada porque todo pasa por `currentAccountId()`. La referencia sectorial nunca expone datos de otra cuenta: solo agregados que cumplen el anonimato.

## Persistencia y migración

Migración **aplicada**: `migrations/Version20261102101743.php` (`up` `:23-96`, `down` `:99-140`; fuente `migration.sql` del plan).

- `property_carbon_footprints` y `company_carbon_footprints`: siete columnas nulables `denominator_occupied_rooms`, `denominator_stays`, `denominator_built_area_m2` (decimal 12,2), `denominator_covers_served`, `denominator_average_headcount` (10,2), `denominator_revenue_eur` (16,2), `denominator_customers_served`. Filas existentes a `NULL`, sin backfill.
- `carbon_footprint_base_year_declarations`: índice único `UNIQ_byd_subject (subject_type, subject_id)` e `IDX_byd_account`.
- `carbon_footprint_sector_benchmarks`: índice único `UNIQ_sb_segment (subject_type, segment_key, year, indicator)`. Se puebla con el primer refresco programado o a mano tras desplegar.

Sin columnas JSON. Los repositorios se registran por atributos (`#[AsAlias]`/`#[When]`), como el resto del BC.

## Configuración y operación

- **Programado:** `carbon-footprint:refresh-sector-benchmarks`, diario a las 03:00 (`CommandSchedule.php:74-78`). `RefreshSectorBenchmarksRequest` implementa `TimedCommand` (lleva `now`), requisito de `CommandScheduleTest`.
- **Consola:** `make sf c="carbon-footprint:sector-benchmarks:refresh <username>"` (el usuario debe poder ejecutar la acción `Register` de `SectorBenchmark`, es decir `Admin`/`SuperAdmin`). Escribe «N sector benchmarks published.»; falla si el usuario no existe.
- Tras desplegar conviene lanzar el comando una vez para no esperar a las 03:00.
- Sin variables de entorno nuevas, sin colas nuevas (el panel es síncrono), sin correos ni notificaciones.
- **Menú:** nodos «Indicadores» en los grupos «Propiedades» y «Empresas» de `NodeLister::toOwners()`, dentro de `propertiesEnabled`.
- **Traducciones:** ES/EN completas; TR conserva el prefijo `NEW_` como el resto del catálogo turco. Dominio `intensityIndicator` con las siete etiquetas y las siete unidades (`*_unit`).

## Cómo extenderlo

**Añadir un indicador de intensidad:**
1. Nuevo campo nulable en `IntensityDenominators` (constructor, `values()`, `equals()`, `sum()`, `isEmpty()`, getter) y en `DeclareIntensityDenominatorsRequest` de propiedad y de empresa.
2. Nuevo caso en `IntensityIndicator` y en `IntensityDenominators::denominatorFor()`.
3. Columna en `OrganizationalCarbonFootprint.IntensityDenominators.orm.xml`, evento `…DenominatorsDeclared` (payload) y **migración creada a mano**.
4. Campo en `IntensityDenominatorsType`, `_intensity_denominators.html.twig` y claves `<indicador>` y `<indicador>_unit` en `intensityIndicator+intl-icu.*.xlf` (`make translation`).
5. Tests de calculadora, denominadores y agregado. El panel, la serie, la comparación y el refresco sectorial recogen el indicador sin más cambios porque iteran sobre `IntensityIndicator::cases()` / `calculate()`.

**Cambiar el anonimato** de la referencia: solo las constantes de `SectorBenchmarkStatistics`; `SectorBenchmark::isPublishable()` las usa. Actualizar `CONCEPTS.md`.

**Trampas conocidas:**
- Total fijado frente a recalculado: `totalKg` (tiles, indicadores, serie, comparación, referencia) es el fijado; contribución, líneas, gas y alcances salen del recálculo. Si difieren más de 0,01 kg el panel muestra un aviso (`InventoryResults::hasTotalDrift()`).
- Las líneas se emparejan por nombre de elemento en el locale actual: si un maestro cambia de nombre entre años la línea cuenta como nueva (va a eficiencia).
- Los factores sin desglose por gas se registran como CO₂ (`EmissionFactor.php:30-35`), por eso la etiqueta «CO₂ (incl. factores sin desglose por gas)».
- CH₄ y N₂O se guardan en gramos; el desglose convierte con `/1000` y el GWP del grupo de coeficientes.
- `{propertyId?}` es un parámetro opcional: solo el menú llega sin sujeto; acciones de fila y cabecera pasan siempre el id.
- Las «barras horizontales» del plan son barras verticales ordenadas: el controlador Stimulus `charts` no admite orientación horizontal.
- Llamar al panel de empresa con muchas propiedades hace una consulta y un cálculo por propiedad (aceptable hasta decenas).

## Tests

Unitarios (`tests/Unit/CarbonFootprint/`):
- Dominio: `OrganizationalCarbonFootprint/IntensityDenominatorsTest`, `IntensityIndicatorsCalculatorTest`, decomposer, `GasBreakdown`; `BaseYearDeclaration/` (agregado, repositorio); `SectorBenchmark/` (`SectorBenchmarkStatisticsTest`, repositorio con `testReplaceAll`); `PropertyCarbonFootprintTest` / `CompanyCarbonFootprintTest` (declaración de denominadores); repositorios abstractos con `findCompletedByYear`.
- Aplicación: `Application/CarbonFootprintKpi/` (`CarbonFootprintKpiAuthorizerTest`, `ListKpiSubjectsServiceTest`, `GasBreakdownCalculatorTest`, `GetCarbonFootprintKpiDashboardServiceTest`), `BaseYearDeclaration/Declare` y `Remove`, `SectorBenchmark/Refresh/RefreshSectorBenchmarksServiceTest`, `DeclareIntensityDenominators` de propiedad y empresa, `CompanyIntensityDenominatorsDefaultsTest`.
- Infraestructura: `Infrastructure/UI/Web/CarbonFootprintKpi/View/KpiDashboardViewTest` (una gráfica de variación por comparación).

Aceptación (Behat): `tests/Acceptance/CarbonFootprint/property_carbon_footprint_kpis.feature` (declarar datos, certificado precargado, menú con una o varias propiedades, panel, evolución y variación, alta del año base, falta de justificación, baja, otra cuenta denegada, propiedad sin huellas) y `company_carbon_footprint_kpis.feature` (precarga con la suma, comparación con «Sin dato», referencia sectorial con y sin segmento publicado). Pasos nuevos en `CarbonFootprintContext.php`.

Ejecución con los objetivos del `Makefile`: `make test-unit options=tests/Unit/CarbonFootprint/Application/CarbonFootprintKpi`, `make test-acceptance options=tests/Acceptance/CarbonFootprint/property_carbon_footprint_kpis.feature`, `make test-parallel` para todo. `make test-this` (citado en `CLAUDE.md`) **no existe** en el `Makefile`.

Sin hueco de cobertura abierto en `review.md`.

## Desviaciones y deuda

- Plan: [`plan.md`](plan.md) (`## Implementación`). Revisión: [`review.md`](review.md): 9 hallazgos, **todos corregidos**, ninguno abierto; las dudas Q1 (huellas con alcance 3 excluido mezcladas en la muestra) y Q2 (variación hacia atrás si el año base es posterior) se aceptaron tal cual.
- Cambios sobre el plan hechos en la review: umbral de anonimato 10 / 5 / 40 % (A1), total fijado en todo el panel (A2/M1), aviso de deriva (B4), `replaceAll()` atómico (B1), una gráfica de variación por comparación (B2), variante `outlineDanger` y `valueClass` del tile (B3), textos ES/EN del aviso de deriva (B5).
- Desviaciones de implementación del plan: mapping `OrganizationalCarbonFootprint.IntensityDenominators.orm.xml`; los controladores llaman a los servicios directamente; `DashboardController` acepta el sujeto por query.
- Deuda no corregida: `RefreshSectorBenchmarksService` autoriza con un agregado desechable (patrón de `SyncExchangeRatesService.php:41`); `tests/Acceptance/Shared/AcceptanceClient.php:53-59` deja el crawler anterior si la petición revienta (las features nuevas comprueban `the response status should be 200`); `CLAUDE.md` cita `make test-this`, inexistente.
