# Alcance 3 · Deducir el método de cálculo aplicado — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-01 · **Bounded Contexts:** `CarbonFootprint`

## Qué hace y por qué

El dossier de inventario de las huellas de propiedad y de empresa muestra, por categoría, el método de cálculo (`CalculationMethod`) y la calidad de los datos (`DataQuality`). Antes, toda línea de actividad con unidad física se etiquetaba `ActivityBased`. Ahora cada línea del alcance 3 recibe el nombre que le corresponde en el GHG Protocol Scope 3 Standard: basado en distancia, en combustible, específico por tipo de residuo o basado en datos medios. Los alcances 1 y 2 siguen como `ActivityBased`.

La deducción no pide ni persiste nada: se calcula en cada generación del informe a partir del tipo de actividad, la magnitud de la unidad de la cantidad y la procedencia (`ActivityPlacement`). No hay migración, ni pantallas, ni rutas. Las decisiones (taxonomía, calidad `medium` para los nuevos, masa en transporte = combustible, no usar `EmissionFactor::source()`) están en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/CalculationMethod.php` | Cuatro casos nuevos, fábrica `forActivity()`, etiquetas y calidad | Modificado |
| `src/CarbonFootprint/Application/CarbonFootprintReport/Data/OrganizationalCarbonFootprintReportLinesBuilder.php:74` | Delega en `CalculationMethod::forActivity()`; se elimina `methodFor()` | Modificado |
| `src/CarbonFootprint/Infrastructure/AI/Prompt/GenerateOrganizationalReportTextsPrompt.php:159,161` | Instrucciones de `methodology` y `uncertainty` nombran los métodos nuevos | Modificado |
| `templates/carbon_footprint/translations/calculationMethod.html.twig` | Plantilla de extracción con las cuatro etiquetas nuevas | Modificado |
| `translations/messages+intl-icu.{es,en,tr}.xlf` | Claves nuevas (ES, EN «Distance-based», TR «Mesafe bazlı»…) | Modificado |
| `.claude/CONCEPTS.md:104` | «Método de cálculo» con los trece valores | Modificado |

Sin cambios de código, y por eso se aprovechan automáticamente: `OrganizationalCarbonFootprintReportData::categoryMethodEmissions()` (agrega por `->value`), `CarbonFootprintReportFiguresTrait::categoryMethodRows()` (`:944`) y `dataQualityRows()`, y los dos generadores PDF (`TcpdfCarbonFootprintReportPdfGenerator.php:239`, tabla «Métodos de cálculo por categoría»; `ClassicTcpdfCarbonFootprintReportPdfGenerator.php:147`).

## Modelo de dominio

`CalculationMethod` es un enum con 13 casos: `SupplierSpecific`, `ReportedByOrganization`, `ActivityBased`, `SpendBased`, `DistanceBased`, `FuelBased`, `WasteTypeSpecific`, `AverageData`, `EmployeeSurveyExtrapolation`, `DerivedWellToTank`, `OperatorFootprint`, `OperatorContribution`, `ManualValue`.

`CalculationMethod::forActivity(ActivityType, Unit, ActivityPlacement)` (`CalculationMethod.php`, `match (true)`): la primera regla que encaja decide.

| Orden | Regla | Método |
|---|---|---|
| 1 | `ReportedEmissions` con `placement->providerId` | `SupplierSpecific` |
| 2 | `ReportedEmissions` sin proveedor | `ReportedByOrganization` |
| 3 | `unit->isCurrency()` | `SpendBased` |
| 4 | `placement->fromEmployees` | `EmployeeSurveyExtrapolation` |
| 5 | `placement->stage !== Scope::Scope3` | `ActivityBased` |
| 6 | Transporte o movilidad con magnitud `Distance` o `Freight` | `DistanceBased` |
| 7 | Transporte o movilidad con magnitud `Volume`, `Energy` o `Mass` | `FuelBased` |
| 8 | `Waste` | `WasteTypeSpecific` |
| 9 | Resto | `AverageData` |

Notas:
- La categoría (`placement->source`) no interviene.
- La magnitud de `quantity()->unit()` equivale a la del factor, porque `UnitConverter` exige la misma magnitud.
- Transporte o movilidad con magnitud `Pieces`, `Stay` o `Area` cae en `AverageData`.
- `forNonConsolidatedPropertyLine()` y `DerivedWellToTank` no cambian.

`dataQuality()`: alta para `SupplierSpecific` y `OperatorFootprint`; baja para `SpendBased` y `ManualValue`; media para el resto, incluidos los cuatro nuevos.

## Flujo de una operación

Generación del informe (sin cambios de flujo): el colector de datos de la huella organizacional construye las líneas en `OrganizationalCarbonFootprintReportLinesBuilder` → por cada actividad crea `OrganizationalCarbonFootprintReportActivityLine` con `method: CalculationMethod::forActivity($activityType, $activity->quantity()->unit(), $reference->placement)` (`:74`) → `OrganizationalCarbonFootprintReportData::categoryMethodEmissions()` agrega alcance × fuente × método → el prompt de IA recibe una línea por combinación con el `value` del enum y la calidad, y los PDF pintan `label()` traducido.

## Autorización y ámbito

Sin cambios. Solo afecta a informes de propiedad y de empresa; el informe de evento no muestra métodos.

## Persistencia y migración

Nada se persiste ni hay migración. Los informes `generated` no se regeneran; los que estén en `draft` o `pending_review` conservan sus textos de IA, pero el PDF usará las etiquetas nuevas.

## Configuración y operación

Textos nuevos mediante `make translation`. Ninguna variable, cola ni comando nuevos.

## Cómo extenderlo

Para añadir un método: 1) caso en `CalculationMethod` con `label()` y `dataQuality()` (el `match` es exhaustivo); 2) regla en `forActivity()` en la posición de precedencia adecuada; 3) línea en `calculationMethod.html.twig` y traducciones en los xlf; 4) actualizar la instrucción de `methodology` del prompt y la definición en `.claude/CONCEPTS.md`. Las agregaciones y los PDF no requieren cambios.

Trampas: una unidad de divisa gana siempre (gasto) aunque sea transporte; un transporte en kg se interpreta como masa de combustible (decisión asumida en el plan); la unidad la fija el Admin en el maestro, así que el mismo tipo de actividad puede salir como gasto o como físico.

## Tests

- `tests/Unit/CarbonFootprint/Domain/Model/CarbonFootprintReport/CalculationMethodTest.php`: tests de `forActivity()` por regla, calidad y etiquetas.
- `tests/Unit/CarbonFootprint/Application/CarbonFootprintReport/Data/OrganizationalCarbonFootprintReportLinesBuilderTest.php`: `testTheScopeThreeActivitiesReceiveTheGhgProtocolMethod` y los de método existentes.
- `OrganizationalCarbonFootprintReportDataMother` (`withMixedMethods()`), `OrganizationalCarbonFootprintReportDataTest`, `TcpdfCarbonFootprintReportPdfGeneratorTest`, `GenerateOrganizationalReportTextsPromptTest`.
- `OrganizationalCollectorContextTrait` registra `WasteCatalogProvider` en el resolvedor de nombres de test.
- Sin escenarios Behat nuevos (la deducción no tiene UI).

Ejecución: `make test-unit options="--filter CalculationMethodTest"` (`make test-this` no existe en el Makefile).

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: se elimina también el `use` de `Activity` en el builder; se toca `OrganizationalCollectorContextTrait`; el mother usa fuentes de factor `GLEC` y `Ecoinvent` para las líneas nuevas.
- `review.md`: sin hallazgos abiertos (B1 y B2 corregidos en la revisión 2).
- Pendiente ajeno al plan: `messages+intl-icu.tr.xlf` tiene 104 `target` con prefijo `NEW_` preexistentes.
