# Huella ya calculada — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-29 (correcciones de review el 2026-10-30) · **Bounded Contexts:** `CarbonFootprint`, `Bioscore`

## Qué hace y por qué

Una huella organizacional (`PropertyCarbonFootprint` y `CompanyCarbonFootprint`) puede marcarse como **«Huella ya calculada»** (`precalculated`, booleano persistido). Con la marca, los pasos Alcance 1 y Alcance 2 del asistente no piden actividades de maestros (combustible, vehículo, gas refrigerante, electricidad) sino filas libres `concepto + kg|t de CO₂eq`, es decir `ReportedEmissionsActivity` (factor identidad, ya existente desde el plan `2026-10-27-alcance-3-recogida-datos-proveedores`). En la huella de empresa se piden por propiedad consolidada y en el bloque «Sin asignar a propiedad». La categoría 3 del alcance 3 (`fuel_and_energy_related`) también admite filas comunicadas, porque sin actividades no hay *well-to-tank* del que derivarla.

La solución es mínima porque el motor ya suma cualquier actividad contada: se amplía qué secciones admite cada alcance según la marca (`Scope::sectionsFor()`), se persiste la marca, se bloquea Bioscore y se adapta el informe. Decisiones del plan (tabla «Decisiones tomadas» de `plan.md`): marca persistida por huella, filas libres por fuente, cambio de modo destructivo con confirmación, sin cifra de localización para el alcance 2, mitigación sin cambios.

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/Scope.php:189-228` | `precalculatedSources()`, `precalculatedSections()`, `sectionsFor(bool)`; `reportedSectionFor()` admite también las fuentes precalculadas | Mod. |
| Dominio | `.../OrganizationalCarbonFootprintTrait.php:62,134` | `isPrecalculated()`, `removeActivitiesOfStage()` | Mod. |
| Dominio | `.../PropertyCarbonFootprint/PropertyCarbonFootprint.php:49,287-326,404-414` y `.../CompanyCarbonFootprint/CompanyCarbonFootprint.php:56,447-486,564-574` | Campo, `markAsPrecalculated()`, `unmarkAsPrecalculated()`, `removePrecalculatedSourcesActivities()`, `apply…` | Mod. |
| Dominio | `…/PropertyCarbonFootprint/PropertyCarbonFootprint{Marked,Unmarked}AsPrecalculated.php` y los dos de `CompanyCarbonFootprint/` | Eventos sin payload (solo id) | Nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/OrganizationalCarbonFootprintReportData.php:47` | `public readonly bool $precalculated = false` | Mod. |
| Aplicación | `Application/{Property,Company}CarbonFootprint/ChangePrecalculatedMode/` (Request + Service) | Caso de uso de cambio de modo | Nuevo |
| Aplicación | `Application/{Property,Company}CarbonFootprint/Register/Register…Request.php:19` y `…Service.php:67` | Marca al registrar | Mod. |
| Aplicación | `Application/CarbonFootprint/StageAnswersProcessor.php:22-44` | `validate(..., ?array $sections = null)` | Mod. |
| Aplicación | `AnswerScope/Answer…ScopeService.php` (propiedad `:39-48`, empresa `:63`), `GetScope/Get…ScopeService.php` (empresa `:51`) | Pasan `sectionsFor(isPrecalculated())` a validar, sincronizar y construir secciones | Mod. |
| Aplicación | `Application/CarbonFootprintReport/Data/{Property,Company}CarbonFootprintReportDataCollector.php:89,108` | Rellenan `precalculated` | Mod. |
| Bioscore | `src/Bioscore/Application/BioscoreConsumption/Load{Property,Company}CarbonFootprintConsumptions/…Service.php:43,48` | Lanza `InvalidArgumentException` si la huella está marcada | Mod. |
| Persistencia | `Infrastructure/Persistence/Doctrine/Mapping/{Property,Company}CarbonFootprint.*.orm.xml:38` | Columna `precalculated` (boolean, default 0) | Mod. |
| Persistencia | `migrations/Version20261030061543.php` | `ADD precalculated TINYINT(1) DEFAULT 0 NOT NULL` en ambas tablas | Nuevo |
| UI | `Infrastructure/Symfony/Form/Register{Property,Company}CarbonFootprintType.php:42,48` | Casilla `precalculated` | Mod. |
| UI | `Infrastructure/UI/Web/{Property,Company}CarbonFootprint/ChangePrecalculatedModeController.php` | Acción GET de cambio de modo | Nuevo |
| UI | `Infrastructure/UI/Web/{Property,Company}CarbonFootprint/Step/Scope{1,2,3}Controller.php` y `Step/ResultsController.php` | Secciones por modo en el POST y variable `precalculated` en la vista | Mod. |
| UI | `templates/components/TopAction/PrecalculatedMode.html.twig` | Acción de cabecera con confirmación | Nuevo |
| UI | `templates/carbon_footprint/{property,company}_carbon_footprints/step/scope-{1,2}.html.twig`, `results.html.twig` | Acciones de cabecera, aviso, `---` en localización | Mod. |
| UI | `templates/carbon_footprint/organizational_carbon_footprints/_scope_{1,2,3}_sections.html.twig` | Tabla comunicada o de actividades según `precalculated` | Mod. |
| Informe | `Infrastructure/Tcpdf/CarbonFootprintReportFiguresTrait.php:77,376-377,559`, `TcpdfCarbonFootprintReportPdfGenerator.php:232`, `ClassicTcpdfCarbonFootprintReportPdfGenerator.php:164-165,406`, `Infrastructure/AI/Prompt/GenerateOrganizationalReportTextsPrompt.php:195` | Frase de declaración, tablas por fuente sin columnas de actividad, localización `---`, contexto de IA | Mod. |
| i18n | `translations/messages+intl-icu.{es,en,tr}.xlf`, `templates/bioscore/translations/carbonFootprintConsumptions.html.twig` | Claves nuevas; la plantilla existe solo para que `make translation` extraiga el mensaje de Bioscore | Mod./Nuevo |

## Modelo de dominio

Secciones admitidas por alcance (`Scope::sectionsFor`, `Scope.php:209-219`):

| Alcance | `precalculated = false` | `precalculated = true` |
|---|---|---|
| 1 | `sections()` (combustibles, vehículos, gases refrigerantes) | `fixed_facilities_reported`, `own_vehicles_reported`, `refrigerant_leaks_reported` |
| 2 | `electricity` | `building_electricity_reported` |
| 3 | `sections()` | `sections()` + `fuel_and_energy_related_reported` |

- Las secciones comunicadas se crean con `reportedSectionFor(Source)` (`Scope.php:221-228`): clave `<source>_reported`, tipo `ActivityType::ReportedEmissions`. Sigue lanzando `InvalidArgumentException` para fuentes que no son de proveedor ni precalculadas (p. ej. `Investments`).
- `markAsPrecalculated()` / `unmarkAsPrecalculated()` (`PropertyCarbonFootprint.php:287-319`): `ensureIsEditable()` (lanza `OrganizationalCarbonFootprintAlreadyCompletedException` con la huella completada), no-op si ya está en ese estado, publica el evento y **elimina siempre** las actividades de los alcances 1 y 2 y de `FuelAndEnergyRelated` en el alcance 3 (`removePrecalculatedSourcesActivities()`, `:321`). Estado y exclusiones se conservan.
- Invariante de coexistencia: `ActivityPlacement::equals()` no compara el tipo de actividad, por lo que `retainActivities` al guardar un paso retira cualquier actividad de la misma colocación que no venga en el formulario; combinado con el borrado al cambiar de modo evita mezclar tipos.
- `excludeStage()` retira actividades de `allSources()` (no solo de `sections()`), para que excluir el alcance 3 también retire las comunicadas de la categoría 3.
- Emisiones de una fila comunicada = cantidad en kg × 1 (2,5 t → 2.500 kg). Una fila comunicada no tiene resultado por localización: el total por localización usa su valor de mercado.

## Flujo de una operación

**Registrar con la marca.** `RegisterController` → `Register…Type` (campo `precalculated`, `data_class` = `Register…Request`) → `RegisterPropertyCarbonFootprintService.php:67` (`if ($request->precalculated) markAsPrecalculated()` tras `register()`) → redirección a `…_scope_1`.

**Guardar un paso (p. ej. Alcance 1).** `Scope1Controller` (POST con CSRF `scope_1_<id>`) → `StageAnswerHttpMapper::map($data, $scope->sectionsFor($footprint->isPrecalculated()))` (solo lee las claves de las secciones del modo activo; `StageAnswerHttpMapper.php:21-25`) → `Answer…ScopeService`: calcula `$sections = scope->sectionsFor(isPrecalculated())` → `StageAnswersProcessor::validate(..., $sections)` (una sección fuera de la lista lanza `InvalidArgumentException`) → `StageAnswersSynchronizer::sync(..., $sections)` (registra/actualiza/retiene vía `ReportedEmissionsAnswerHandler`) → `answerScope()` → `add()`. En empresa se repite por bloque de propiedad y el bloque sin asignar (`AnswerCompanyCarbonFootprintScopeService`).

**Mostrar un paso.** `Get…ScopeService` construye las secciones con `sectionsFor(...)`; el controlador añade `precalculated` a la vista; los parciales `_scope_N_sections` eligen entre `CarbonFootprint:ReportedEmissionsActivityTable` y la tabla de actividades. Prefijos de campo: `<source>_reported` (propiedad) y `<prefixBase>[<source>_reported]` (empresa, `properties[<id>]` / `unassigned`).

**Cambiar el modo.** Enlace `TopAction:PrecalculatedMode` (confirmación `data-confirm-text-param`) → `GET /property-carbon-footprints/{id}/precalculated-mode/{0|1}?_token=…` (`ChangePrecalculatedModeController.php:19-25`; empresa `/company-carbon-footprints/{id}/precalculated-mode/{0|1}`). El controlador: carga la huella, `denyAccessUnlessGranted(Edit)`, si `isCompleted()` flash `warning` y redirige a resultados; si no invoca el servicio (`find` → `InvalidArgumentException('PropertyCarbonFootprint not found')` → `authorizer->denyUnlessCanDoThat(Edit)` → mark/unmark → `add`), flash `success` y redirige a `…_scope_1`. `UnauthorizedActionException` → 403; `InvalidArgumentException` → flash `danger`.

**Cargar consumos de Bioscore.** Los servicios de carga lanzan `InvalidArgumentException('No se pueden cargar consumos de Bioscore en una huella ya calculada')` tras autorizar (`LoadPropertyCarbonFootprintConsumptionsService.php:43`); el controlador existente lo muestra como flash `danger` «No se han podido cargar los consumos: {error}». Las plantillas además ocultan `TopAction:BioscoreConsumptions` con la marca.

**Informe.** Los colectores copian la marca al DTO. Con `precalculated`: ambos generadores PDF añaden la frase de declaración en «Emisiones incluidas en cada alcance» (`TcpdfCarbonFootprintReportPdfGenerator.php:232`, `Classic…:406`); `organizationalSourceTable()` (`CarbonFootprintReportFiguresTrait.php:374-381`) usa la tabla genérica `sourceTable()` para `BuildingElectricity` y `FuelAndEnergyRelated` en vez de las tablas de electricidad y derivadas (que leen kWh/comercializadora y `derivedLines`); la cifra «Alcance 2 · localización» y la fila de doble reporte muestran `---` (`:77`, `:559`); el prompt de IA añade «scope 1 and scope 2 figures were provided by the organization as already calculated results, without activity breakdown» (`GenerateOrganizationalReportTextsPrompt.php:189-199`).

## Autorización y ámbito

Sin autorización nueva: cambio de modo y guardado de pasos usan `PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer` con `SecurityAction::Edit`. `canEdit()` exige rol Owner en la cuenta actual de la huella (resuelta con `currentAccountId()`, por tanto respeta la impersonación); Admin y SuperAdmin no editan (solo pueden quitar, `canRemove`). El controlador además bloquea el cambio de modo con la huella completada.

CSRF: el controlador usa `#[CsrfProtected('remove')]` (listener `Shared/Infrastructure/Symfony/Event/CsrfProtectedListener.php`) y las plantillas generan `_token: csrf_token('remove')`.

## Persistencia y migración

- `property_carbon_footprints.precalculated` y `company_carbon_footprints.precalculated`: `TINYINT(1) NOT NULL DEFAULT 0`. Migración aplicada/presente: `migrations/Version20261030061543.php` (la que creó el usuario; `migration.sql` del plan es la propuesta original, con `AFTER scope_3_exclusion_comment` que la migración definitiva no incluye).
- Sin backfill: las huellas existentes quedan sin marca. Sin índices. Repositorios Doctrine/InMemory sin cambios.

## Configuración y operación

Sin variables de entorno, colas ni comandos. Los servicios nuevos se registran por el `resource` del BC (`src/CarbonFootprint/Infrastructure/Symfony/DependencyInjection/services.yaml`). Tras tocar textos: `make translation`. Las 9 claves nuevas de `messages+intl-icu.tr.xlf` quedan con prefijo `NEW_` pendientes de traducir (ES y EN completas).

## Cómo extenderlo

- **Admitir otra fuente en modo precalculado:** añadirla a `Scope::precalculatedSources()` (`Scope.php:190`); `precalculatedSections()`/`sectionsFor()` la propagan. Después: bloque `{% if precalculated %}` en el parcial `_scope_N_sections.html.twig` con prefijo `<source>_reported`; si la fuente es derivada o tiene tabla propia en el PDF, ampliar `organizationalSourceTable()`; si debe borrarse al cambiar de modo, `removePrecalculatedSourcesActivities()` en las dos entidades. Comprobar `StageExclusionHttpMapper` (admite fuentes = secciones + derivadas, no depende del modo).
- **Importación desde hoja de cálculo:** fuera de alcance; reutilizar la marca y las secciones `…_reported`.
- **Trampa:** `StageAnswerHttpMapper` solo lee las secciones del modo activo; un formulario abierto antes de cambiar el modo pierde sus filas sin aviso y una sección de otro modo se ignora en la capa web (el rechazo con excepción solo se produce si llega al servicio).

## Tests

- Unitarios: `tests/Unit/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/ScopeTest.php`; `.../PropertyCarbonFootprint/PropertyCarbonFootprintTest.php` y `.../CompanyCarbonFootprint/CompanyCarbonFootprintTest.php` (marcar/desmarcar, idempotencia, borrado, completada); `Application/{Property,Company}CarbonFootprint/{AnswerScope,GetScope,Register,ChangePrecalculatedMode}/`; `tests/Unit/Bioscore/Application/BioscoreConsumption/Load*CarbonFootprintConsumptions/`; colectores de informe; `GenerateOrganizationalReportTextsPromptTest`; `TcpdfCarbonFootprintReportPdfGeneratorTest`. Mothers: `PropertyCarbonFootprintMother::precalculated()`, `CompanyCarbonFootprintMother::precalculated()`, `OrganizationalCarbonFootprintReportDataMother::precalculated()`.
- Aceptación (Behat): `tests/Acceptance/CarbonFootprint/property-carbon-footprint-precalculated.feature` (11 escenarios) y `company-carbon-footprint-precalculated.feature` (5), con pasos en `CarbonFootprintContext.php`.
- Ejecución: `make test-unit`, `make test-acceptance`, `make test-parallel`, o `make test-this test=tests/Acceptance/CarbonFootprint/property-carbon-footprint-precalculated.feature`.
- Huecos: ver «Desviaciones y deuda».

## Desviaciones y deuda

Detalle en [`plan.md`](plan.md) (`## Implementación`) y [`review.md`](review.md):

- Una sección de actividades enviada a una huella marcada se ignora en la capa web en lugar de mostrar flash de error (decisión del usuario).
- Correcciones de review aplicadas sobre lo planificado (A1, M1, M2, B1-B4): tablas de PDF por modo, localización `---`, `#[CsrfProtected('remove')]`, mensaje de Bioscore traducible y aviso oculto con la huella completada. El plan no las recoge.
- Deuda abierta: los controladores de carga de Bioscore no comprueban `isCompleted()`; traducción TR pendiente; duda Q1 abierta de `review.md`: el plan de mitigación apenas reduce los alcances 1 y 2 (filas comunicadas sin elemento concreto) y la categoría 3 comunicada no admite medidas (`MitigationSourceType`).
