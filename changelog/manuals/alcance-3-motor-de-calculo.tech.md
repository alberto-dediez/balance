# Alcance 3 en las huellas de propiedad y de empresa — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Revisión:** `review.md` · **Implementado:** 2026-10-21 (correcciones de review el 2026-10-22) · **Bounded Contexts:** `CarbonFootprint` (principal), `Bioscore` (solo una rama inalcanzable en dos controladores)

## Qué hace y por qué

Las huellas organizacionales (`PropertyCarbonFootprint` y `CompanyCarbonFootprint`) solo calculaban los alcances 1 y 2. Esta funcionalidad añade el **alcance 3** (emisiones indirectas de la cadena de valor, por categorías del GHG Protocol) como paso 3 de un asistente que pasa a tener 5 pasos: Alcance 1 → Alcance 2 → **Alcance 3** → Medidas de Reducción y Mitigación → Resultados.

El alcance 3 se modela como un caso más de la etapa `Scope` (`Scope::Scope3`) y cada categoría es un caso nuevo de `Source` con su `SectionDefinition`. Así el motor genérico (sincronizador de respuestas, constructor de secciones, agregación de resultados, exclusiones por fuente, plan de mitigación, informe) funciona sin código especial por categoría. Dos piezas son específicas:

- **Exclusión de etapa**: el alcance 3 es opcional y se puede excluir completo con motivo y comentario (tres campos planos en el agregado y dos eventos). Es distinta de la exclusión por fuente (decisión «Exclusión del alcance completo» del plan).
- **Fuente derivada**: la categoría 3 «Actividades relacionadas con combustibles y energía» (`Source::FuelAndEnergyRelated`) no tiene actividades propias; se calcula sumando las emisiones well-to-tank (WTT) ya persistidas en las actividades de combustible, combustible de maquinaria y electricidad de los alcances 1 y 2 (decisión «Categoría 3» del plan).

Categorías 8, 13 y 14 (activos arrendados y franquicias) quedan fuera: no hay maestro.

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprint/Source.php` | 12 casos nuevos al final del enum (`purchased_goods_and_services` … `investments`) | mod. |
| Dominio | `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/Scope.php` | `Scope3`, sus 12 `SectionDefinition`, `derivedSources()`, `allSources()`, `upstreamSources()`, `downstreamSources()`, `isExcludable()`; `ofSource()` recorre `allSources()` | mod. |
| Dominio | `…/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatus.php` | estado `scope_3_answered`, `isScope3Answered()`, pasos 1-5, `activeStepKey()` con `scope-3` | mod. |
| Dominio | `…/OrganizationalCarbonFootprint/StageNotExcludableException.php` | `\DomainException` al excluir una etapa no excluible | nuevo |
| Dominio | `…/CarbonFootprint/CarbonFootprint.php` | puerto: `excludeStage`, `includeStage`, `isStageExcluded`, `stageExclusionReason`, `stageExclusionComment` | mod. |
| Dominio | `…/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintTrait.php:168-181` | lectura compartida de la exclusión de etapa (solo `scope_3`) | mod. |
| Dominio | `…/PropertyCarbonFootprint/PropertyCarbonFootprint.php`, `…/CompanyCarbonFootprint/CompanyCarbonFootprint.php` | campos `scope3Excluded`, `scope3ExclusionReason`, `scope3ExclusionComment`; `excludeStage`/`includeStage`; plan de mitigación arranca al responder el alcance 3; `complete()` exige `scope_3_answered`; reabrir vuelve a `scope_3_answered` | mod. |
| Dominio | `…/PropertyCarbonFootprint/PropertyCarbonFootprintScope{Excluded,Included}.php`, `…/CompanyCarbonFootprint/CompanyCarbonFootprintScope{Excluded,Included}.php` | eventos de la exclusión de etapa | nuevos |
| Dominio | `…/EventCarbonFootprint/EventCarbonFootprint.php:533-550` | implementación nula del puerto (`excludeStage` lanza `StageNotExcludableException`) | mod. |
| Dominio | `…/CarbonFootprint/DerivedSourceLine.php` | línea de fuente derivada: `source`, `origin` (`ActivityPlacement`), `activityType` (`ActivityType`), `emissions`, `activityId` | nuevo |
| Dominio | `…/MitigationAction/MitigationEmissionSource.php` | `originKey` opcional, `isDerived()`, `reductionKey()` | mod. |
| Dominio | `…/MitigationAction/MitigationProjector.php:22,71` | las fuentes derivadas no son objetivo de efectos y siguen las tasas de su origen | mod. |
| Dominio | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportData.php`, `…ReportExclusionLine.php` | campos `scope3Excluded`, `scope3ExclusionReason`, `scope3ExclusionComment`, `derivedLines`; `source` pasa a `?Source` (null = alcance completo) | mod. |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprint/DerivedSourceEmissionsCalculator.php` | calcula la categoría 3 (`calculate()`, `lines()`) | nuevo |
| Aplicación | `…/CarbonFootprint/StageExclusionAnswer.php` | DTO `(ExclusionReason $reason, ?string $comment)` | nuevo |
| Aplicación | `…/CarbonFootprint/EmissionsBreakdownBuilder.php:89-104` | suma lo derivado a total, fuente, etapa, etapa×fuente y propiedad de origen | mod. |
| Aplicación | `…/CarbonFootprint/StageAnswersProcessor.php`, `ValidatedStageAnswers.php` | valida y aplica la exclusión de etapa; las fuentes derivadas son excluibles | mod. |
| Aplicación | `…/{Property,Company}CarbonFootprint/AnswerScope/*Request.php`, `*Service.php` | campo `stageExclusion` y nueva secuencia | mod. |
| Aplicación | `…/{Property,Company}CarbonFootprint/GetScope/*` | `stageExclusion`, `derivedEmissions`, `derivedLines` en la respuesta | mod. |
| Aplicación | `…/{Property,Company}CarbonFootprint/GetResults/*` | `scope3Exclusion` en la respuesta | mod. |
| Aplicación | `…/MitigationAction/MitigationEmissionSourcesBuilder.php:95` | fuentes derivadas de categoría 3 por origen | mod. |
| Aplicación | `…/MitigationAction/Update/UpdateMitigationActionService.php:57` | ignora fuentes derivadas al validar efectos | mod. |
| Aplicación | `…/CarbonFootprintReport/Data/{Property,Company}CarbonFootprintReportDataCollector.php`, `OrganizationalCarbonFootprintReportLinesBuilder.php:83-91` | datos de alcance 3 y línea de exclusión de etapa | mod. |
| Persistencia | `…/Persistence/Doctrine/Mapping/{Property,Company}CarbonFootprint.*.orm.xml:31-37` | tres columnas `scope_3_*` | mod. |
| Persistencia | `migrations/Version20261021201710.php` | columnas + backfill de huellas completadas | nuevo |
| UI | `…/UI/Web/{Property,Company}CarbonFootprint/Step/Scope3Controller.php` | paso 3 del asistente | nuevos |
| UI | `…/Step/Scope2Controller.php`, `MitigationPlanController.php`, `ResultsController.php`, `EditController.php` | redirecciones y guardas del paso nuevo | mod. |
| UI | `…/{Property,Company}CarbonFootprint/CalculateEmissionsController.php` | cálculo en vivo: modo contenedor de residuos y `ExchangeRateNotFoundException` → 400 | mod. |
| UI | `…/UI/Web/CarbonFootprint/StageExclusionHttpMapper.php:54` | `mapStage()`; `map()` acepta fuentes derivadas | mod. |
| UI | `…/OrganizationalCarbonFootprint/View/ScopeStepView.php:48`, `OrganizationalResultsView.php`, `MitigationPlanView.php:23-70` | salida `sections` genérica; fila «Alcance completo»; reducción esperada con WTT | mod. |
| UI | `…/Symfony/Form/Type/MitigationSourceType.php:34` | `group_by` por alcance | mod. |
| Plantillas | `templates/carbon_footprint/{property,company}_carbon_footprints/step/scope-3.html.twig`, `templates/carbon_footprint/organizational_carbon_footprints/_scope_3_sections.html.twig`, `templates/components/CarbonFootprint/StageExclusion.html.twig` | pantalla del alcance 3 | nuevas |
| Plantillas | `…/wizard-layout.html.twig` ×2, `step/mitigation-plan.html.twig` ×2, `step/results.html.twig` ×2, 6 `*ActivityTable.html.twig` | 5 pasos, tercer color, tablas con `calculateUrl` | mod. |
| Informe | `…/Infrastructure/Tcpdf/CarbonFootprintReportFiguresTrait.php`, `TcpdfCarbonFootprintReportPdfGenerator.php`, `ClassicTcpdfCarbonFootprintReportPdfGenerator.php`, `…/AI/Gemini/GenerateOrganizationalReportTextsPrompt.php` | alcance 3 en PDF (ambos temas) y prompt | mod. |
| Activos | `assets/images/carbon-footprint-report/classic/scope3.jpg` | icono provisional (copia de `lighting.jpg`) | nuevo |
| Traducciones | `translations/{scope,source,organizationalCarbonFootprintStatusType,messages}+intl-icu.*.xlf`; `translations/activityType+intl-icu.*.xlf` y plantillas de extracción `templates/carbon_footprint/translations/{scope,organizationalCarbonFootprintStatusType,activityType}.html.twig` | textos nuevos | mod./nuevos |
| Bioscore | `src/Bioscore/Infrastructure/UI/Web/CarbonFootprint/Load{Property,Company}CarbonFootprintConsumptionsController.php:58` | rama `Scope3 => 404` exigida por el `match` exhaustivo; inalcanzable por `requirements: scope_1\|scope_2` | mod. |

## Modelo de dominio

### Categorías del alcance 3

`Scope::Scope3->sections()` (`Scope.php:35-48`), en este orden:

| Clave de sección | `Source` | `ActivityType` | Dirección |
|---|---|---|---|
| `purchasedGoodsAndServices` | `purchased_goods_and_services` | `PurchaseService` | aguas arriba |
| `capitalGoods` | `capital_goods` | `CapitalGood` | aguas arriba |
| — (derivada) | `fuel_and_energy_related` | — | aguas arriba |
| `upstreamTransport` | `upstream_transport` | `Transport` | aguas arriba |
| `operationsWaste` | `operations_waste` | `Waste` | aguas arriba |
| `businessTravel` | `business_travel` | `Mobility` | aguas arriba |
| `businessTravelStays` | `business_travel` | `OvernightStay` | aguas arriba |
| `employeeCommuting` | `employee_commuting` | `Mobility` | aguas arriba |
| `downstreamTransport` | `downstream_transport` | `Transport` | aguas abajo |
| `soldProductsProcessing` | `sold_products_processing` | `Process` | aguas abajo |
| `soldProductsUse` | `sold_products_use` | `UseProfile` | aguas abajo |
| `soldProductsEndOfLife` | `sold_products_end_of_life` | `Waste` | aguas abajo |
| `investments` | `investments` | `EconomicSector` | aguas abajo |

- `business_travel` tiene dos secciones con la misma fuente: una exclusión por fuente cubre ambas y en resultados suman en una sola fila.
- `derivedSources()` (`Scope.php:53-59`) devuelve `[FuelAndEnergyRelated]` solo para `Scope3`; `allSources()` (`62-78`) = fuentes de secciones sin duplicados + derivadas. `ofSource()` (`117-126`) usa `allSources()`, así que la categoría 3 también resuelve a `Scope3`.
- `upstreamSources()` / `downstreamSources()` (`81-110`) fijan los dos bloques de la pantalla.
- `isExcludable()` (`112-115`): solo `Scope3`.

### Estado y paso activo

`OrganizationalCarbonFootprintStatus.php`: `started → scope_1_answered → scope_2_answered → scope_3_answered → completed`. `stepNumber()` 1-5; `activeStepKey()`: `scope_2_answered → scope-3`, `scope_3_answered → mitigation-plan`. `isScope2Answered()` incluye `scope_3_answered`; `isScope3Answered()` es cierto en `scope_3_answered` y `completed`.

### Exclusión de etapa

En `PropertyCarbonFootprint.php` (`CompanyCarbonFootprint.php` es espejo):

- `excludeStage()` (`193-224`): `ensureIsEditable()`; si la etapa no es excluible lanza `StageNotExcludableException` (`201`); si ya estaba excluida con el mismo motivo y comentario no hace nada (`204`); publica `PropertyCarbonFootprintScopeExcluded` (payload `scope`, `reason`, `comment`); retira las actividades de cada fuente de las secciones (`retainActivities(…, [])`; en empresa `removeActivitiesOfSource()`, de todas las propiedades) e incluye de nuevo las fuentes que estuvieran excluidas.
- `includeStage()` (`226-243`): si estaba excluida publica `PropertyCarbonFootprintScopeIncluded` (payload `scope`).
- `addActivity()` (`118`) y `excludeSource()` (`151`) no hacen nada si la etapa está excluida.
- `answerScope()` (`85-103`): el evento `…MitigationPlanStarted` se publica al responder `Scope3` por primera vez (antes, con `Scope2`).
- `complete()` (`245-258`): exige `Scope3Answered`; si no, `OrganizationalCarbonFootprintNotReadyToCompleteException`.
- `applyPropertyCarbonFootprintReopened()` (`367-369`): vuelve a `Scope3Answered`.
- Nombres de evento según la convención del repo: `propertyCarbonFootprintScopeExcluded`, `propertyCarbonFootprintScopeIncluded`, `companyCarbonFootprintScope{Excluded,Included}`.

Invariantes: `scope3Excluded ⇒` no hay actividades ni exclusiones por fuente en `scope_3` y hay motivo; `scope3Excluded = false ⇒` motivo y comentario nulos (los fija el `apply…ScopeIncluded`, `PropertyCarbonFootprint.php:352-357`).

`EventCarbonFootprint` implementa el puerto en nulo: ninguna `Phase` es excluible.

### Categoría 3 derivada

`DerivedSourceEmissionsCalculator` (`src/CarbonFootprint/Application/CarbonFootprint/DerivedSourceEmissionsCalculator.php`):

- `lines()` (`44-70`): vacío si la etapa no tiene la fuente derivada, si la etapa está excluida o si `fuel_and_energy_related` está excluida (`isCalculated`, `78-82`). Si no, carga las actividades de `scope_1`/`scope_2` y genera una `DerivedSourceLine` por actividad `CombustibleActivity`, `MachineryFuelActivity` o `ElectricityActivity` con `wellToTankEmissions() > 0` (`119-127`). Para electricidad se usa el WTT por mercado; el WTT por localización no entra en ninguna cifra.
- `calculate()` (`28-41`): suma por fuente derivada.

Ejemplo: alcance 1 = 1 430 kg, alcance 2 = 2 500 kg, WTT persistido de gasóleo 210 kg y de electricidad 380 kg, compras 4 000 kg y viajes 900 kg → total 9 420 kg. Excluyendo `fuel_and_energy_related`: 8 830 kg. Excluyendo el alcance 3: 3 930 kg.

## Flujo de una operación

### Guardar el paso «Alcance 3» (propiedad)

1. `POST /property-carbon-footprints/{id}/step/scope-3` → `Scope3Controller` (`…/PropertyCarbonFootprint/Step/Scope3Controller.php`).
2. `denyAccessUnlessGranted(Edit)` (`49`). Si el alcance 2 no está respondido, redirige a `scope_2` o `scope_1` (`51-56`). Con la huella completada, un POST redirige a resultados con aviso (`58-62`). CSRF `scope_3_{id}` (`65`).
3. Mapeo: `StageAnswerHttpMapper::map()` de las 12 secciones, `StageExclusionHttpMapper::map()` (exclusiones por fuente, incluida la derivada) y `mapStage()` (`stageExclusion[excluded|reason|comment]`).
4. `AnswerPropertyCarbonFootprintScopeService` (`…/AnswerScope/AnswerPropertyCarbonFootprintScopeService.php`): autoriza `Edit` → `StageAnswersProcessor::validate(scope, answers, exclusions, stageExclusion)` → `applyStageExclusion()` → si no hay exclusión de etapa: `applyExclusions()` + `StageAnswersSynchronizer::sync()` → `answerScope(Scope3)` → `add()`.
5. `StageAnswersProcessor::validate()` (`StageAnswersProcessor.php:21-76`): con `stageExclusion` exige etapa excluible y ninguna respuesta ni exclusión por fuente (`InvalidArgumentException`); las fuentes válidas para exclusión son `allSources()` (`108-121`). `applyStageExclusion()` (`95-106`) llama a `excludeStage` o, si la etapa es excluible, a `includeStage`.
6. Errores: `InvalidArgumentException` y `ExchangeRateNotFoundException` se convierten en flash `danger` «No se han podido guardar las actividades: {error}» y redirección al mismo paso (`Scope3Controller.php:82-86`). El detalle `{error}` es el mensaje de la excepción, sin traducir.
7. Éxito: redirección a `property_carbon_footprints_mitigation_plan`.

En empresa (`…/CompanyCarbonFootprint/Step/Scope3Controller.php`) las respuestas llegan por bloque (`unassigned` y `properties[<id>]`, `answersByProperty()` `127-143`). El servicio (`AnswerCompanyCarbonFootprintScopeService.php`) valida que las propiedades pertenecen a la empresa; con exclusión de etapa valida también cada bloque (rechaza respuestas) pero no sincroniza; sin ella sincroniza el bloque sin asignar y uno por propiedad.

### Mostrar el paso

`GET` → `Get{Property,Company}CarbonFootprintScopeService` devuelve secciones, exclusiones, `stageExclusion`, `derivedEmissions` y `derivedLines`. El controlador pasa a la plantilla `blocks` (con la salida `sections` de `ScopeStepView`), `stageExclusion`, `currencies` (`Unit::currencies()`, para inversiones), `upstreamSources`/`downstreamSources` y `calculateUrl`. `_scope_3_sections.html.twig` envuelve todo en `StageExclusion`, cuyo `fieldset` deshabilita las secciones (controlador Stimulus `source-exclusion`) cuando se marca el interruptor, de modo que no se envían. La sección de la categoría 3 pinta una tabla de solo lectura con `derivedLines` (alcance, fuente, tipo traducido con el dominio `activityType`, kg CO₂eq) y el total.

### Cálculo en vivo

`{Property,Company}CarbonFootprint/CalculateEmissionsController.php`: con `type=waste` y `mode=container` resuelve la cantidad con `WasteContainerQuantityResolver` (capacidad en unidad de volumen × % de llenado); `ExchangeRateNotFoundException` → 400 con `{error}`. El controlador JS `assets/controllers/activity_emissions_controller.js:237-240` muestra «—» ante cualquier respuesta no OK.

### Resultados, completar y reabrir

`EmissionsBreakdownBuilder::build($fp, Scope::cases())` incluye `scope_3`; las líneas derivadas se suman también en `byPropertyId` según la propiedad de origen (`EmissionsBreakdownBuilder.php:94-103`), así que la gráfica por propiedad de empresa cuadra con el total. `OrganizationalResultsView::build(..., scope3Exclusion:)` añade la fila «Alcance 3 · Alcance completo · motivo · comentario» al principio de la tabla de exclusiones. `MitigationPlanController` y `ResultsController` exigen `isScope3Answered()` y redirigen al paso pendiente.

## Plan de mitigación

- `MitigationSourceType` ofrece las fuentes de las secciones de `Scope::cases()` (sin la derivada) agrupadas por alcance (`group_by`, `MitigationSourceType.php:34`). Las medidas sugeridas y las acciones pueden tener efectos sobre fuentes del alcance 3.
- `MitigationEmissionSourcesBuilder::derivedEmissionSources()` (`MitigationEmissionSourcesBuilder.php:95`) añade, por cada fuente de origen `source|ítem` con WTT, una `MitigationEmissionSource` de `Scope3`/`FuelAndEnergyRelated` con el mismo ítem y `originKey` = clave de origen. Así el año base de la proyección coincide con el total de la huella.
- `MitigationProjector` (`MitigationProjector.php:22`) no cuenta las derivadas como fuentes emisoras, así que un efecto sobre ellas se ignora; al proyectar (`:71`) aplica a cada derivada las tasas de su `reductionKey()` (el origen): el WTT baja al ritmo del combustible o la electricidad que lo genera.
- `MitigationAction/EditController.php:61` y `UpdateMitigationActionService.php:57` descartan las derivadas: una acción no puede tenerlas como objetivo.
- `MitigationPlanView` (`MitigationPlanView.php:23-70`) suma a la «reducción esperada» de cada efecto la parte de las derivadas de ese origen.
- El plan se inicia (e importa acciones previas si está vacío) al guardar el alcance 3 por primera vez. Las huellas que ya estaban en `scope_2_answered` tienen `mitigationPlanStarted = true` y no se reimportan.

## Informe

- `{Property,Company}CarbonFootprintReportDataCollector` rellenan `scope3Excluded`, motivo, comentario y `derivedLines`; `OrganizationalCarbonFootprintReportLinesBuilder::exclusionLines()` antepone una línea con `source = null` si el alcance está excluido (`:83-91`).
- `CarbonFootprintReportFiguresTrait`: cifra clave «Alcance 3» con su total o «Excluido» (`organizationalKeyFigures`, `62`); matriz alcance × fuente y tabla de emisiones incluidas sobre `allSources()` (`scopeSourceRows` `234`, `includedEmissionsRows` `264`, con una sola fila «Alcance completo» si está excluido); páginas por fuente (`scopeSourceDefinitions` `298`): ninguna del alcance 3 si está excluido, y solo las fuentes con exclusión, emisiones, líneas de actividad o líneas derivadas (`hasReportedSource` `323`); tabla de la categoría 3 (`derivedSourceTable` `406`); «Alcance completo» en la tabla de exclusiones (`616`).
- `TcpdfCarbonFootprintReportPdfGenerator.php:237` y `ClassicTcpdfCarbonFootprintReportPdfGenerator.php:165-170` usan `derivedSourceTable` para la categoría 3. El tema clásico pinta las categorías del alcance 3 en un párrafo compacto en «Emisiones incluidas en cada alcance» (o «Alcance completo (excluido)»), con icono `scope3.jpg` (`358`), un texto condicional (`scope3IncludedEmissionsText`, `405`) y la línea «Alcance 3: {total}» / «Alcance 3: excluido» (`554`).
- `GenerateOrganizationalReportTextsPrompt`: «(whole scope)» en las exclusiones (`77`) y límites del inventario con o sin alcance 3 (`boundaries()`, `189`).

## Autorización y ámbito

- Paso, guardado y cálculo en vivo: `Action::Edit` sobre la huella (`denyAccessUnlessGranted` en el controlador y `denyUnlessCanDoThat` en el servicio vía `PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer`). Sin chequeo de rol adicional: quien puede editar los alcances 1 y 2 puede editar el 3 y excluirlo. Resultados: `Action::View`.
- La cuenta y la impersonación las resuelve el authorizer existente; no hay cambios.
- Sin feature flag propio: el paso existe en cuanto están activas las propiedades (nodo de menú `Propiedades`/`Empresas` de `NodeLister`).
- Bioscore: «Cargar consumos de Bioscore» no aparece en el paso 3 (`scope-3.html.twig` no añade acciones) y las rutas solo admiten `scope_1|scope_2`.

## Persistencia y migración

- `property_carbon_footprints` y `company_carbon_footprints`: `scope_3_excluded TINYINT(1) NOT NULL DEFAULT 0`, `scope_3_exclusion_reason VARCHAR(255) NULL` (enum `ExclusionReason`), `scope_3_exclusion_comment LONGTEXT NULL` (mappings `*.orm.xml:31-37`).
- Migración `migrations/Version20261021201710.php`: los dos `ALTER` y el backfill `UPDATE … SET scope_3_excluded = 1, scope_3_exclusion_reason = 'no_data_available' WHERE status = 'completed'` en ambas tablas; `down()` elimina las columnas. Si la migración se ejecutó en algún entorno antes de añadirle el backfill, allí hay que lanzar los dos `UPDATE` a mano (`review.md`, A1).
- Actividades y exclusiones por fuente no cambian: `scope` y `source` ya son `VARCHAR` con `enum-type` y admiten los valores nuevos.
- Las huellas en `scope_2_answered` al desplegar pasan por el paso 3 la próxima vez que se editan. Las actividades de alcance 1 y 2 anteriores a los factores WTT tienen WTT 0, así que su categoría 3 vale 0 hasta que se vuelvan a guardar con factores WTT cargados (aceptado, `review.md` Q2).

## Configuración y operación

- Sin variables de entorno, colas, comandos ni tareas programadas nuevas.
- `EmissionsBreakdownBuilder`, `MitigationEmissionSourcesBuilder` y los colectores reciben `DerivedSourceEmissionsCalculator` por autowiring.
- Traducciones: dominios `scope`, `source`, `organizationalCarbonFootprintStatusType`, `activityType` (nuevo) y `messages`, extraídos con `make translation` desde las plantillas de `templates/carbon_footprint/translations/`. Las claves TR nuevas siguen con prefijo `NEW_`.

## Cómo extenderlo

**Añadir una categoría del alcance 3 con actividades** (p. ej. activos arrendados):

1. Caso nuevo al final de `Source` (el orden de `Source::cases()` ordena tablas, proyección e informe).
2. `SectionDefinition` en `Scope::Scope3->sections()` con un `ActivityType` que ya tenga manejador, catálogo, mapper HTTP, factoría de fila y componente Twig (`#[AutoconfigureTag]`).
3. Añadir la fuente a `upstreamSources()` o `downstreamSources()`.
4. Entrada en el mapa `scope3Tables` de `_scope_3_sections.html.twig` (clave de sección, componente y título) y traducción en `source` (con su plantilla de extracción).
5. Nada más: exclusión por fuente, resultados, mitigación e informe la recogen por `Scope::ofSource()` / `allSources()`.

**Añadir otra fuente derivada**: añadirla a `Scope::derivedSources()` y generar sus líneas en `DerivedSourceEmissionsCalculator::lines()` (hoy solo contempla `FuelAndEnergyRelated`, `:46`); revisar la rama de tabla en los dos generadores PDF y `_scope_3_sections.html.twig`.

**Hacer excluible otra etapa**: `Scope::isExcludable()` y el trait leen solo campos `scope3*` (`OrganizationalCarbonFootprintTrait.php:168-181`): haría falta persistir la exclusión de esa etapa. El componente `StageExclusion.html.twig` tiene fijo el texto «No calcular el alcance 3» (hallazgo B2 descartado).

Trampas:

- Varios `match` sin rama por defecto sobre `Scope` (estado, iconos del PDF clásico, controladores de Bioscore): PHPStan los señala al añadir casos.
- Una fuente no puede pertenecer a dos alcances: `Scope::ofSource()` devuelve el primero.
- Las derivadas no deben llegar como objetivo de efectos: si se añade otro consumidor de `MitigationEmissionSourcesBuilder::build()` que valide efectos, filtrar `isDerived()`.

## Tests

Unitarios principales:

- Dominio: `tests/Unit/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/{ScopeTest,OrganizationalCarbonFootprintStatusTest}.php`; `…/PropertyCarbonFootprint/PropertyCarbonFootprintTest.php` y `…/CompanyCarbonFootprint/CompanyCarbonFootprintTest.php` (exclusión de etapa, idempotencia, `Scope1` no excluible, arranque del plan con el alcance 3, completar y reabrir); `…/EventCarbonFootprint/EventCarbonFootprintTest.php`; `…/MitigationAction/MitigationProjectorTest.php` (derivada que sigue a su origen; efecto sobre derivada ignorado). Mothers: `withScope3Answered()`, `withScope3Excluded()`.
- Aplicación: `tests/Unit/CarbonFootprint/Application/CarbonFootprint/{DerivedSourceEmissionsCalculatorTest,EmissionsBreakdownBuilderTest,StageAnswersProcessorTest}.php`; `AnswerScope`, `GetScope`, `GetResults`, `Complete` y `Reopen` de propiedad y empresa; `MitigationAction/MitigationEmissionSourcesBuilderTest.php`, `Update/UpdateMitigationActionServiceTest.php`; `CarbonFootprintReport/Data/*CollectorTest.php`, `MitigationPlanReportBuilderTest.php`.
- Infraestructura: `tests/Unit/CarbonFootprint/Infrastructure/AI/GenerateOrganizationalReportTextsPromptTest.php`, `Tcpdf/TcpdfCarbonFootprintReportPdfGeneratorTest.php` (ambos temas, páginas de alcance 3 solo con datos), `Chart/ApexChartCarbonFootprintReportChartsGeneratorTest.php`, `UI/Web/CarbonFootprint/StageExclusionHttpMapperTest.php`, `UI/Web/OrganizationalCarbonFootprint/View/{ScopeStepViewTest,OrganizationalResultsViewTest,MitigationPlanViewTest}.php`.

Aceptación: `tests/Acceptance/CarbonFootprint/property-carbon-footprint-scope-3.feature` (redirecciones, compra en EUR, inversión en USD sin tipo de cambio, exclusión completa, viaje de negocio con desplazamiento y alojamiento, exclusión de `business_travel`, resultados con «Alcance completo») y `company-carbon-footprint-scope-3.feature` (bloques por propiedad y sin asignar, exclusión completa). Adaptados: `*-carbon-footprint-{complete,mitigation-plan,scope-steps}.feature`, `mitigation-measure-edit.feature`, `carbon-footprint-report-*.feature`. Pasos nuevos en `CarbonFootprintContext.php`.

Ejecución (el `Makefile` no tiene `test-this`):

```bash
make test-unit options="--filter 'Scope|DerivedSource|EmissionsBreakdown|StageAnswersProcessor|Mitigation'"
make test-acceptance options="tests/Acceptance/CarbonFootprint/property-carbon-footprint-scope-3.feature"
make test-acceptance options="tests/Acceptance/CarbonFootprint/company-carbon-footprint-scope-3.feature"
```

## Desviaciones y deuda

Ver `## Implementación` de `plan.md` y `review.md` (sin hallazgos abiertos; B2 descartado). Estado final respecto al plan:

- `DerivedSourceLine` vive en Dominio (lo usa `OrganizationalCarbonFootprintReportData`), con `ActivityType` como enum y `activityId`.
- La categoría 3 es excluible por fuente (`allSources()` en el procesador y el mapper).
- La categoría 3 **sí** entra en el plan de mitigación como fila que sigue a su origen (review M4, opción 2), aunque sigue sin poder ser objetivo de una acción ni de una medida sugerida.
- `locationBasedElectricity` no incluye WTT (review M1).
- Las páginas por fuente del alcance 3 en el PDF solo aparecen si la fuente tiene datos o está excluida (review M3).
- Pendiente para el usuario: sustituir `assets/images/carbon-footprint-report/classic/scope3.jpg` (provisional) y completar las traducciones TR (`NEW_`).
- El mensaje de error al guardar con una divisa sin tipo de cambio muestra el texto de la excepción en inglés («No exchange rate available for currency "USD".») dentro del aviso traducido; en el cálculo en vivo la fila muestra «—» sin mensaje.
- El tema clásico maqueta «Emisiones incluidas en cada alcance» con coordenadas fijas: traducciones largas de las categorías podrían desbordar la página.
