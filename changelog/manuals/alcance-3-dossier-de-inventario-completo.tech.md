# Alcance 3 · Dossier de inventario completo en el informe de huella organizacional — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-30 · **Bounded Contexts:** `CarbonFootprint` (lee `Property` para el régimen de explotación)

## Qué hace y por qué

El informe PDF de las huellas de **propiedad** y de **empresa** pasa a ser un dossier de inventario según el GHG Protocol: metodología, límites y enfoque de consolidación, métodos de cálculo por categoría, resumen de fuentes de los factores e incertidumbre / calidad de los datos. El informe de evento no cambia.

La solución reutiliza el informe genérico ya existente: secciones de texto generadas por IA y revisables (`CarbonFootprintReportSectionKey`), un DTO de datos por sujeto (`OrganizationalCarbonFootprintReportData`) que alimenta al prompt y a los dos generadores PDF, y datos ya persistidos. **No hay migración ni cambio de esquema.**

Decisiones clave (tabla «Decisiones tomadas» del plan):

- Tres textos nuevos obligatorios para propiedad y empresa: `Methodology`, `EmissionFactorSources` (ya existían para evento) y `Uncertainty` (nueva). Los formularios de edición y revisión los muestran solos porque iteran `requiredFor()`.
- Cuatro bloques deterministas (no IA): límites y consolidación, métodos por categoría, fuentes de los factores y calidad de los datos. La IA recibe los mismos datos y los comenta; manda la tabla.
- Enfoque de consolidación fijo: **control operacional** (único implementado). Sin selector.
- Sin incertidumbre numérica tecleada: la calidad es una calificación cualitativa fija por método de cálculo.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/CalculationMethod.php` | Enum de 9 métodos; `label()`, `dataQuality()`, `forNonConsolidatedPropertyLine()` | Nuevo |
| Dominio | `…/CarbonFootprintReport/DataQuality.php` | Enum `high/medium/low`; `rank()`, `worstOf()`, `label()` | Nuevo |
| Dominio | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportBoundaryLine.php` | VO: propiedad, régimen, si consolida, categoría de alcance 3 | Nuevo |
| Dominio | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportEmployeeSurvey.php` | VO de la encuesta de empleados; `fromParticipation()` (línea 19) | Nuevo |
| Dominio | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportData.php` | Campos `gwpCh4`, `gwpN2O`, `operatingRegime`, `boundaryLines`, `employeeSurvey`; agregados `categoryMethodEmissions()` (80), `emissionFactorSourceSummary()` (117), `boundaryTreatment()` (166) | Modificado |
| Dominio | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportActivityLine.php` | Campo obligatorio `method` | Modificado |
| Dominio | `…/CarbonFootprintReport/CarbonFootprintReportSectionKey.php` | Caso `Uncertainty`; `requiredFor(Property|Company)` con 7 claves (línea 22) | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprint/Source.php` | `ghgProtocolCategory(): ?int` (línea 36) | Modificado |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/OrganizationalCarbonFootprintReportLinesBuilder.php` | `methodFor()` (línea 135) asigna el método a cada línea | Modificado |
| Aplicación | `…/Data/PropertyCarbonFootprintReportDataCollector.php` y `…/Data/CompanyCarbonFootprintReportDataCollector.php` | GWP, régimen / líneas de límite, encuesta de empleados | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/AI/Prompt/GenerateOrganizationalReportTextsPrompt.php` | Contexto nuevo y siete secciones (`responseSchema()` línea 251) | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Tcpdf/CarbonFootprintReportFiguresTrait.php` | `sourceLabel()`, `percentage()`, `boundaryRows()` (876), `boundaryPropertyRows()` (917), `categoryMethodRows()` (944), `emissionFactorSourceRows()` (966), `emissionFactorGroupRows()` (984), `dataQualityRows()` (994), columna «% del total» | Modificado |
| Infraestructura | `…/Tcpdf/TcpdfCarbonFootprintReportPdfGenerator.php` | Informe vertical: orden nuevo (`renderOrganizationalReport`, línea 94) y secciones nuevas | Modificado |
| Infraestructura | `…/Tcpdf/ClassicTcpdfCarbonFootprintReportPdfGenerator.php` | Informe apaisado: mismos bloques (línea 130) | Modificado |
| Infraestructura | `templates/carbon_footprint/translations/calculationMethod.html.twig` | Plantilla solo de extracción para que `make translation` vea las etiquetas dinámicas de método y calidad | Nuevo |
| Traducciones | `translations/messages+intl-icu.{es,en,tr}.xlf` | Títulos, cabeceras, etiquetas de método y calidad | Modificado |

Sin cambios deliberados: `AiCarbonFootprintReportTextsGenerator`, `ReportTextsTrait`, `TextsController`, `ReviewController`, los form types y sus plantillas (derivan de `requiredFor()`), el prompt y el informe de evento, `services.yaml` (autowiring de la dependencia nueva).

## Modelo de dominio

### Método de cálculo (`CalculationMethod`)

`OrganizationalCarbonFootprintReportLinesBuilder::methodFor()` (líneas 135-147) aplica esta precedencia a cada línea de actividad:

1. `placement->fromEmployees` → `EmployeeSurveyExtrapolation` (prevalece sobre todo).
2. Tipo `ActivityType::ReportedEmissions` → `SupplierSpecific` si hay `providerId`; si no, `ReportedByOrganization`.
3. Unidad de divisa (`Unit::isCurrency()`) → `SpendBased`.
4. Resto → `ActivityBased` (incluye electricidad con factor de mercado).

Los demás casos no pasan por el builder:

- `DerivedWellToTank`: líneas `derivedLines` (cat. 3).
- `CalculationMethod::forNonConsolidatedPropertyLine()` (línea 21): precedencia `ManualValue` > `OperatorContribution` > `OperatorFootprint`; `null` si la línea no tiene ninguno (queda fuera de las tablas y del prompt).

| Método | Calidad |
|---|---|
| `SupplierSpecific`, `OperatorFootprint` | Alta |
| `ReportedByOrganization`, `ActivityBased`, `EmployeeSurveyExtrapolation`, `DerivedWellToTank`, `OperatorContribution` | Media |
| `SpendBased`, `ManualValue` | Baja |

La calidad de una categoría es la peor de sus métodos (`DataQuality::worstOf()`).

### Agregados del DTO

- `categoryMethodEmissions()` devuelve `list<[Scope, Source, array<método, kgCO2eq>]>` combinando `activityLines`, `derivedLines` y `nonConsolidatedPropertyLines`, en orden `Scope::cases()` × `Source::cases()`. Es la única fuente de la tabla de métodos, la de calidad y el prompt (invariante: la suma por método de una categoría coincide con la celda alcance × fuente).
- `emissionFactorSourceSummary()` devuelve una fila por fuente distinta de factor (años, líneas, emisiones), más una fila WTT si hay `derivedLines` y una fila por método de las no consolidadas (huella / aportación / valor manual). Las emisiones suman el total del inventario salvo líneas no consolidadas sin origen.
- `boundaryTreatment()` devuelve la categoría de alcance 3 por la que entra una propiedad no consolidada, o `null` si consolida, no tiene categoría, el alcance 3 está excluido o su categoría está excluida («Fuera del cálculo»).

### Categorías del GHG Protocol

`Source::ghgProtocolCategory()` numera 1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 14 y 15; `null` para el resto (alcances 1 y 2). La 8 no existe como fuente: se declara en el informe como «no aplicable con el enfoque de control operacional». Las fuentes con número se etiquetan «Cat. N · …» en `sourceLabel()` (trait, línea 209), que usan todas las tablas organizacionales; el informe de evento no se ve afectado (sus fuentes devuelven `null`).

## Flujo de una operación

La generación y revisión del informe no cambia (ver `.claude/plans/done/2026-09-18-carbon-footprint-report/plan.md`). Lo que cambia en cada paso:

1. **Recogida de datos.** `PropertyCarbonFootprintReportDataCollector` / `CompanyCarbonFootprintReportDataCollector` rellenan GWP de CH₄ y N₂O desde `EmissionFactorGroup::gwp()`, la encuesta (`OrganizationalEmployeeParticipationRepository::findByCarbonFootprint()` → `OrganizationalCarbonFootprintReportEmployeeSurvey::fromParticipation()`, `null` si no hay participación), y en empresa `boundaryLines` recorriendo `CompanyProperties::all()` con `Scope::sourceForOperatingRegime()` y `OperatingRegime::isConsolidated()`. En propiedad, `operatingRegime` de la propiedad.
2. **Textos IA.** `AiCarbonFootprintReportTextsGenerator` pide `requiredFor()` (siete claves) y rechaza respuestas incompletas. `GenerateOrganizationalReportTextsPrompt` añade al contexto: consolidación por control operacional, GWP, régimen o tabla de límites (con «outside the calculation (excluded)» cuando `boundaryTreatment()` es `null`), nota de que las categorías 13 y 14 no aplican a una propiedad (si el alcance 3 está incluido), métodos por categoría con calidad, fuentes de factores y encuesta de empleados. `MAX_FACTOR_LINES = 120` sigue acotando los factores aplicados.
3. **Edición / revisión.** `TextsController` y `ReviewController` construyen el formulario con `requiredFor() + optionalFor()`; `Methodology`, `EmissionFactorSources` y `Uncertainty` aparecen sin tocar plantillas.
4. **Emitir / aprobar.** `CarbonFootprintReport::ensureTextsAreComplete` exige todas las `requiredFor()`; sin ellas lanza `CarbonFootprintReportTextsIncompleteException`, capturada en ambos controladores.
5. **PDF.** Cada generador pinta los bloques con las mismas filas del trait.

### Orden del documento

Cifras clave, Datos de la huella, Propósito, Contexto normativo, **Metodología de cálculo**, **Límites del inventario y enfoque de consolidación**, Emisiones incluidas en cada alcance (más Exclusiones de fuentes en Classic), **Métodos de cálculo por categoría**, páginas por fuente, Resultados (con «% del total»), **Fuentes de los factores de emisión**, **Incertidumbre y calidad de los datos**, Medidas de reducción y mitigación (si hay plan), Relación con los ODS, Conclusión, Anexo.

- Vertical: `TcpdfCarbonFootprintReportPdfGenerator::renderOrganizationalReport` (94-120); secciones nuevas `renderBoundariesSection` (221), `renderCategoryMethodsSection` (239), `renderEmissionFactorSourcesSection` (256), `renderUncertaintySection` (279).
- Classic: `ClassicTcpdfCarbonFootprintReportPdfGenerator::renderOrganizationalReport` (130); `renderBoundariesPages`, `renderEmissionFactorSourcesPages`, `renderUncertaintyPages`; los textos van con `renderTextPage` (foto `context.jpg`) y las tablas con `renderDataPage`.

### Contenido de las tablas

- **Límites** (`boundaryRows`): propiedad / empresa, enfoque «Control operacional», régimen (propiedad), año, alcances incluidos. Con alcance 3 excluido: fila «Excluido · motivo» y fin. Con alcance 3 incluido: categorías contempladas (`Scope::Scope3->sourcesFor()`: 12 en propiedad, 14 en empresa), fila de la cat. 8 «No aplicable…», y en propiedad una fila que remite las cat. 13 y 14 a la huella de la empresa.
- **Límite organizacional** (`boundaryPropertyRows`, solo empresa): propiedad · régimen · tratamiento: «Consolidada: alcances 1 y 2», «Alcance 3 · Cat. N · …» o «Fuera del cálculo».
- **Métodos** (`categoryMethodRows`): alcance, categoría, método, kgCO₂eq y % de la categoría; una fila por método y categoría con datos.
- **Fuentes de factores** (`emissionFactorSourceRows` + `emissionFactorGroupRows`): fuente (o etiqueta del método para las filas WTT / no consolidadas), años («-» si no aplican), líneas, emisiones; debajo, grupo de coeficientes y GWP.
- **Calidad** (`dataQualityRows`): alcance, categoría, calificación, observación: «±X % (N %)» de la encuesta si la categoría tiene método de empleados (o «Sin datos de encuesta»), «Valor manual», «Sin factor de mix eléctrico» en electricidad si hay actividades sin factor de localización.

## Autorización y ámbito

Sin acciones, authorizers ni roles nuevos. Quién edita los textos y quién revisa lo siguen gobernando `Account::reportPermissions()` («puede editar textos», «autorrevisión») y los controladores existentes (Propietario en `carbon_footprint_reports_texts`, Admin / SuperAdmin en la revisión). La impersonación no interviene.

## Persistencia y migración

Sin cambios de esquema ni migración. Los métodos, la calidad y las tablas se derivan al generar el PDF; la nueva sección se guarda como una `CarbonFootprintReportSection` más con clave `uncertainty`.

**Informes en curso al desplegar.** Los `generated` no cambian. Un informe de propiedad o empresa en `draft` o `pending_review` no tiene las tres secciones y no se puede emitir ni aprobar. No hay acción nueva: el Admin rechaza el `pending_review` con comentario (la huella se reabre y, al completarla, se genera un informe nuevo); el `draft` se resuelve reabriendo la huella (`RemoveReportOnPropertyCarbonFootprintReopenedSubscriber` y su homólogo de empresa eliminan el informe) y completándola de nuevo. Los `generating_texts` reciben los textos nuevos. Consulta previa útil: `SELECT status, COUNT(*) FROM carbon_footprint_report WHERE subject_type IN ('property','company') GROUP BY status`.

## Configuración y operación

- Sin variables de entorno, colas ni comandos nuevos.
- Traducciones: `make translation` (ES, EN, TR). Las etiquetas de `CalculationMethod` y `DataQuality` son dinámicas y solo las detecta el extractor gracias a `templates/carbon_footprint/translations/calculationMethod.html.twig`: **al añadir un método o una calidad, hay que añadir su etiqueta a esa plantilla**. Otros locales caen al idioma por defecto.
- Previews visuales: `TcpdfCarbonFootprintReportPdfGeneratorTest::previews()` copia un PDF por tema, tipo y locale a `var/carbon-footprint-report-preview/`.

## Cómo extenderlo

**Añadir un método de cálculo**

1. Caso nuevo en `CalculationMethod` con su `label()` y su `dataQuality()`.
2. Decidir dónde se asigna: línea de actividad (`methodFor()` en el builder), derivada o no consolidada (`categoryMethodEmissions()` / `forNonConsolidatedPropertyLine()`).
3. Añadir la etiqueta a `calculationMethod.html.twig` y ejecutar `make translation`.
4. Si debe verse como fila propia en la tabla de fuentes de factores, tocar `emissionFactorSourceSummary()`.
5. Tests: `CalculationMethodTest`, `OrganizationalCarbonFootprintReportLinesBuilderTest`, `OrganizationalCarbonFootprintReportDataTest`.

**Añadir un bloque o columna al dossier**

Calcular la fila en el trait (un único sitio para los dos temas), llamarla desde `TcpdfCarbonFootprintReportPdfGenerator` y `ClassicTcpdfCarbonFootprintReportPdfGenerator`, y pasar los mismos datos al prompt desde el DTO, no recalculándolos.

**Añadir una sección de texto**

Caso en `CarbonFootprintReportSectionKey` (con `label()`), inclusión en `requiredFor()` / `optionalFor()` y en `responseSchema()` y las instrucciones del prompt. Los informes ya `draft` sin la sección dejarán de poder emitirse (ver arriba).

**Trampas**

- Una categoría con método `ManualValue` o sin origen cambia las cifras de las tablas: las líneas no consolidadas sin huella, aportación ni valor manual no tienen método y quedan fuera.
- El enfoque de consolidación está fijo en texto (traducción «Control operacional» y línea del prompt). Si algún día hay otro enfoque habrá que parametrizarlo en el DTO.
- `sourceLabel()` sustituye a `translator->trans($source->value, [], 'source')` en las tablas organizacionales; el informe de evento sigue usando la traducción directa.

## Tests

- **Unitarios:** `tests/Unit/CarbonFootprint/Domain/Model/CarbonFootprint/SourceTest.php`; `…/CarbonFootprintReport/{CalculationMethodTest,DataQualityTest,OrganizationalCarbonFootprintReportDataTest,CarbonFootprintReportSectionKeyTest,CarbonFootprintReportTest}.php`; `…/Application/CarbonFootprintReport/Data/{OrganizationalCarbonFootprintReportLinesBuilderTest,PropertyCarbonFootprintReportDataCollectorTest,CompanyCarbonFootprintReportDataCollectorTest}.php`; `…/Infrastructure/AI/GenerateOrganizationalReportTextsPromptTest.php`; `…/Infrastructure/Tcpdf/TcpdfCarbonFootprintReportPdfGeneratorTest.php` (filas por reflexión de límites, métodos, calidad y fuentes, para ambos temas). Mothers: `OrganizationalCarbonFootprintReportDataMother` (`withMixedMethods()`, `forCompanyWithBoundaryLines()`), `CarbonFootprintReportTextsMother`.
- **Behat:** `tests/Acceptance/CarbonFootprint/carbon-footprint-report-texts.feature` (el Propietario edita `methodology` y `uncertainty`) y `carbon-footprint-report-review.feature` (el formulario de revisión muestra `methodology`, `emissionFactorSources`, `uncertainty`).
- **Ejecución** (objetivos del `Makefile`): `make test-unit`, `make test-acceptance`, `make test-parallel`. `CLAUDE.md` cita `make test-this`, que no existe en el `Makefile`.
- **Huecos:** no hay test del orden de los bloques del PDF (frágil con TCPDF; solo previews visuales).

## Desviaciones y deuda

Ver `## Implementación` en [plan.md](plan.md) y [review.md](review.md) (0 hallazgos abiertos tras la revisión 2).

- Cambios respecto al plan: la derivación de métodos de líneas derivadas / no consolidadas y los agregados viven en el DTO de dominio (no en el generador); la tabla de fuentes de factores incluye filas por origen derivado (WTT) y por método de las no consolidadas; el informe de propiedad declara las cat. 13 y 14 remitidas a la empresa; en el código las categorías solo de empresa son la 13 y la 14 (el plan mencionaba también la 15).
- Pendiente de actualizar el plan por `feature-planner` en esos puntos (anotado en `review.md`).
- Ajena a este plan: 83 claves `NEW_` previas sin traducir en `translations/messages+intl-icu.tr.xlf`.
