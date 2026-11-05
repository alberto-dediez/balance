# Auditoría de huella · cobertura de alcances 1, 2, 3 y materialidad — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-04 · **Bounded Contexts:** `CarbonFootprint`, `Materiality`, `Security` (solo plantillas del Registro)
- **Migración:** ninguna. Sin cambio de esquema ni del cálculo de emisiones.

## Qué hace y por qué

La «auditoría de huella» es el conjunto de documentos que recibe un verificador, una consultora o el Registro MITECO de una huella organizacional (propiedad o empresa) completada: el informe PDF (dossier de inventario GHG Protocol), el documento de exclusiones justificadas, la exportación en detalle y, en propiedades, el certificado. Cuatro planes previos construyeron esas piezas por separado; este plan las revisó de forma transversal frente al GHG Protocol (Corporate y Scope 3) y corrigió errores de cifras y huecos de cobertura.

Correcciones de datos: CH₄ y N₂O se imprimían en gramos con etiqueta «kg»; el anexo de materialidad incluía categorías de alcance 1 y 2; el certificado afirmaba «alcances 1, 2 y 3» con el 3 excluido; con huella «ya calculada» el total por localización mezclaba el método de mercado. Ampliaciones: año base, estado de cada fuente (15 categorías del alcance 3 siempre visibles), aportaciones de proveedores, encuesta de empleados, límite mínimo de las categorías 13 y 14, unidad del factor, fila WTT con cantidad/factor/año/fuente, trazabilidad de la evaluación también con evaluación reabierta, e invariante de dominio para la justificación de anulaciones.

Las decisiones (qué se corrige, qué queda fuera, textos literales) están en la tabla «Decisiones tomadas» del `plan.md`; aquí se describe el estado final del código. El informe de evento no cambia, salvo la unidad CH₄/N₂O y el formato del factor con unidad en las tablas de electricidad/anexo de factores, que son métodos compartidos (`review.md` M2).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprint/DerivedSourceLine.php` | Gana cantidad, unidad y factor WTT (valor, unidad, año, fuente), todos con valor por defecto | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/PropertyCarbonFootprint/PropertyCarbonFootprint.php:362`, `.../CompanyCarbonFootprint/CompanyCarbonFootprint.php:551` | `overrideMaterialityDecision()` lanza `MaterialityOverrideJustificationRequiredException` si la justificación está vacía tras `trim` | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/OrganizationalCarbonFootprintReportData.php` | DTO compartido por prompt y PDF: `baseYear`, `providerLines`, `hasCo2eqOnlyActivities()` (:178), `isBaseYear()`, `baseYearVariationPercent()` (:194), `baseYearExceedsThreshold()` (:211), `supplierDataSharePercent()` (:232), `hasSourceLines()` (:255), `emissionFactorSourceSummary()` (:123, WTT agrupado por fuente) | Modificado |
| Dominio | `.../CarbonFootprintReport/OrganizationalCarbonFootprintReportBaseYear.php` | VO con año, foto del total, umbral y justificación | Nuevo |
| Dominio | `.../CarbonFootprintReport/OrganizationalCarbonFootprintReportProviderLine.php` | VO de una aportación de proveedor en el informe | Nuevo |
| Dominio | `.../CarbonFootprintReport/OrganizationalCarbonFootprintReportMaterialityAnnex.php` | `isAnalysisMissing()`, `isCompletedWithoutVerdicts()`, `hasNoScope3Topics()` (estados de la evaluación) | Modificado |
| Dominio | `.../CarbonFootprintReport/OrganizationalCarbonFootprintReportEmployeeSurvey.php` | `hasSurvey()`, `participationPercent()` | Modificado |
| Aplicación | `src/Materiality/Application/MaterialityAssessment/Snapshot/MaterialityAssessmentSnapshotAdapter.php` | Filtra temas y preguntas a `MaterialityScope::Scope3` antes de construir la instantánea | Modificado |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/ProviderNameResolver.php` | Resuelve y cachea el nombre del proveedor (`reset()` para el exportador) | Nuevo |
| Aplicación | `.../Data/ProviderContributionsReportBuilder.php` | Construye las líneas de aportaciones (orden: contestadas, pendientes, descartadas) | Nuevo |
| Aplicación | `.../Data/PropertyCarbonFootprintReportDataCollector.php`, `CompanyCarbonFootprintReportDataCollector.php` | Inyectan `BaseYearDeclarationRepository` y el builder de proveedores; pasan el `Locale` al constructor de líneas | Modificado |
| Aplicación | `.../Data/OrganizationalCarbonFootprintReportLinesBuilder.php`, `ActivityItemNameResolver.php` | Reciben `?Locale` opcional (nombres de ítem y prefijo «Empleados» en el idioma del PDF) | Modificado |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprint/DerivedSourceEmissionsCalculator.php` | Rellena los campos nuevos de `DerivedSourceLine`; `isDerivedSourceCalculated()` (:105) es público | Modificado |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRowsBuilder.php` | `materialityFieldsFor()` en toda fila de alcance 3 con fuente; `exportsWellToTank()`; usa `ProviderNameResolver` | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Tcpdf/CarbonFootprintReportFiguresTrait.php` | Filas de todas las tablas (ver «Flujo») | Modificado |
| Infraestructura | `.../Tcpdf/TcpdfCarbonFootprintReportPdfGenerator.php`, `ClassicTcpdfCarbonFootprintReportPdfGenerator.php` | Temas vertical y Classic: secciones nuevas, nota de gases, trazabilidad | Modificado |
| Infraestructura | `.../Tcpdf/MaterialityAnnexFiguresTrait.php` | Metodología (regla de magnitud alta), declaraciones del anexo (:233) | Modificado |
| Infraestructura | `.../Tcpdf/TcpdfJustifiedExclusionsDocumentPdfGenerator.php` | Tabla de 15 categorías con «Estado»; declaración con `hasNoScope3Topics()` | Modificado |
| Infraestructura | `.../Tcpdf/TcpdfPropertyCarbonFootprintCertificatePdfGenerator.php` | `footerNotice()`: dos pies según `scope3Co2eqKg` | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/AI/Prompt/GenerateOrganizationalReportTextsPrompt.php` | El prompt recibe kg, año base, proveedores, encuesta y los estados de evaluación nuevos | Modificado |
| Infraestructura | `templates/carbon_footprint/translations/includedEmissionsStatus.html.twig` | Referencia las claves «Calculada», «Sin actividad declarada», «Excluida», «No aplicable» para el extractor | Nuevo |
| Infraestructura | `templates/security/admin/events/detail/carbon-footprint.{property,company}CarbonFootprint{Completed,Reopened}.html.twig` | Texto del Registro | Nuevo (4) |
| Traducciones | `translations/messages+intl-icu.{es,en,tr}.xlf`, `translations/unitType+intl-icu.es.xlf` | Claves nuevas (TR con `NEW_`); «Kilómetro» y «Pernoctación» con tilde | Modificado |

## Modelo de dominio

- **Invariante de anulación.** `PropertyCarbonFootprint::overrideMaterialityDecision()` y la de empresa lanzan `MaterialityOverrideJustificationRequiredException($source)` si `'' === trim($justification)`, antes de registrar el evento. `MaterialityOverrideReconciler` conserva su comprobación para dar un mensaje traducido en el formulario. Los eventos y los `apply` no cambian.
- **Año base.** `OrganizationalCarbonFootprintReportData::baseYearVariationPercent()` = (total − foto) / foto × 100 con signo; nulo sin año base, con foto 0 o si el año de cálculo es el año base. `baseYearExceedsThreshold()`: con foto 0 y fuera del año base devuelve verdadero si el total no es 0 (alineado con `BaseYearDeclaration::exceedsThreshold()`, review B1); si no, `abs(variación) > umbral`.
- **Proveedores.** `supplierDataSharePercent()` = Σ emisiones de aportaciones `Answered` / total del alcance 3 × 100; nulo con alcance 3 excluido o a 0. Las `Ignored` se listan con 0 kg (`ProviderContributionsReportBuilder.php`, `isIgnored()`).
- **Estados de la evaluación de materialidad** (`OrganizationalCarbonFootprintReportMaterialityAnnex.php`): `isAnalysisMissing()` es verdadero sin decisiones cuando la evaluación no está completada o no existe, o cuando está completada con categorías pero ninguna con veredicto; es falso con decisiones y en el caso `hasNoScope3Topics()`. `isCompletedWithoutVerdicts()` (completada, con categorías, ninguna con veredicto) es por tanto un subcaso de `isAnalysisMissing()` que recibe declaración propia. `hasNoScope3Topics()` (completada, sin decisiones y `[] === assessment->topics`) se añadió por review A1 porque el filtro del adaptador puede dejar la lista vacía; no coincide con ninguno de los otros dos.
- **Adaptador.** Solo se transportan temas de alcance 3 y las preguntas de esos temas; `highMagnitudeRuleOf()` se calcula sobre la lista filtrada (`MaterialityAssessmentSnapshotAdapter.php`). `CarbonFootprint` sigue sin importar `Materiality`.

## Flujo de una operación

Generación del informe organizacional (propiedad o empresa), sin cambios en el orden general:

1. El colector (`Property|CompanyCarbonFootprintReportDataCollector`) arma el DTO: busca la declaración con `BaseYearDeclarationRepository::findBySubject()`, las líneas de actividad con `OrganizationalCarbonFootprintReportLinesBuilder` (con `Locale`), las aportaciones con `ProviderContributionsReportBuilder::build()` y la instantánea de materialidad por el puerto `MaterialityAssessmentSnapshotProvider`.
2. El prompt (`GenerateOrganizationalReportTextsPrompt`) y los dos generadores PDF consumen el mismo DTO. Orden de secciones del tema vertical (`TcpdfCarbonFootprintReportPdfGenerator.php:109-130`): cifras clave, datos, propósito, contexto, metodología, límites (con año base), emisiones incluidas, métodos por categoría, aportaciones de proveedores, páginas por fuente, resultados, fuentes de factores, incertidumbre (abre con la encuesta de empleados), plan de mejora, ODS, conclusión, anexo de factores y anexo de materialidad.
3. Las filas las construye `CarbonFootprintReportFiguresTrait`:
   - `emissionsRows()` (:179): CH₄/N₂O con `ch4InKilograms()`/`n2oInKilograms()`, etiquetas por `trans()`. `co2eqOnlyNote()` (:189) añade la nota si hay actividades `RefrigerantGas` o `ReportedEmissions` (`EmissionFactor` no se toca: `co2 = co2eq` cuando los tres gases son 0).
   - `organizationalDualReportingRows()` (:690): con `precalculated`, las dos filas de localización imprimen `---`.
   - `includedEmissionsEntries()` (:356) y `includedSourceEntry()` (:408): estado por fuente con prioridad No aplicable (categoría 8 sin `Source`; 13/14 fuera de `sourcesFor(subjectType)`) > Excluida > Calculada (`hasReportedSource()`, :451) > Sin actividad declarada. Con alcance 3 excluido, una sola fila «Alcance completo».
   - `scopeSourceDefinitions()` (:426) aplica `hasReportedSource()` a los tres alcances: una fuente «Sin actividad declarada» no genera página.
   - `sourceTable()` (:487) y `factorCell()` (:532): factor como «{factor} kg CO₂eq / {unidad}» con `factorNumber()` (2 a 6 decimales). `derivedSourceTable()` (:538): cantidad, factor WTT, año y fuente. `nonConsolidatedPropertyTable()` (:583) más `minimumBoundaryNote()` (:635).
   - `baseYearRows()` (:1013), `providerContributionRows()` (:1039), `supplierDataShareRow()` (:1085), `employeeSurveyRows()` (:1093).
4. Anexo de materialidad: `renderMaterialityAnnexSection()` (`TcpdfCarbonFootprintReportPdfGenerator.php:531`) imprime la tabla de trazabilidad siempre que `hasAssessment()`; `materialityAnnexStatement()` (`MaterialityAnnexFiguresTrait.php:233`) decide la declaración en este orden: alcance 3 excluido, sin categorías de alcance 3, completada antes de los veredictos, sin análisis, reabierta. `materialityMethodologyParagraph()` imprime siempre la frase de la regla de magnitud alta (activada / desactivada / «no registrada en la evaluación»).
5. Documento de exclusiones: `renderScope3Section()` filtra `includedEmissionsRows()` al alcance 3 con cabecera «Estado»; `renderMaterialitySections()` imprime solo la declaración cuando `null === $annex`, `isAnalysisMissing()` o `hasNoScope3Topics()` y el alcance 3 no está excluido (review B4).
6. Certificado: `footerNotice()` elige el pie según `scope3Co2eqKg` nulo o no.

Exportación en detalle (`CarbonFootprintDetailRowsBuilder`): `materialityFieldsFor()` rellena las cinco columnas de materialidad en filas de actividad, derivadas, no consolidadas y exclusiones del alcance 3 con fuente y decisión; `exportsWellToTank()` deja vacía «Emisiones WTT» en actividades de alcance 1 y 2 cuando la categoría 3 no se calcula (siempre se exporta en huellas de evento).

Registro: `templates/shared/history/show.html.twig` y `templates/security/admin/events/list.html.twig` incluyen la plantilla `@security/admin/events/detail/{contexto}.{eventName}.html.twig` y caen a `default`; las cuatro plantillas nuevas cubren `propertyCarbonFootprintCompleted/Reopened` y `companyCarbonFootprintCompleted/Reopened` (total con `number_format(2, ',', '.')`).

## Autorización y ámbito

No se añaden acciones, roles ni authorizers. Informe, documento de exclusiones y exportación conservan los permisos vigentes del Propietario; el Admin sigue sin acceso a la exportación ni al documento (fuera de alcance en el plan). El Registro es solo de administración.

## Persistencia y migración

Sin migración. Todo se deriva de datos existentes: `BaseYearDeclaration` (índice único por sujeto), `OrganizationalProviderContribution`, `OrganizationalEmployeeParticipation` y las decisiones de materialidad de la huella. Los informes ya `generated` no se regeneran y los certificados emitidos conservan su PDF.

## Configuración y operación

- Sin variables de entorno, colas ni comandos nuevos.
- `ProviderNameResolver` mantiene una caché por instancia; el exportador llama a `reset()` en cada `build` para no arrastrar nombres entre ejecuciones.
- Las etiquetas pasadas por constante («Calculada», «Sin actividad declarada», «Excluida», «No aplicable») no las detecta el extractor: se referencian en `templates/carbon_footprint/translations/includedEmissionsStatus.html.twig`. Tras tocar textos, `make translation` (ES y EN; TR se queda con `NEW_`).

## Cómo extenderlo

- **Nuevo bloque determinista del informe:** 1) añade el dato al DTO `OrganizationalCarbonFootprintReportData` (campo con valor por defecto); 2) cárgalo en los dos colectores; 3) crea la función de filas en `CarbonFootprintReportFiguresTrait`; 4) llámala desde los dos generadores (vertical y Classic) y, si aplica, desde `GenerateOrganizationalReportTextsPrompt`; 5) añade el caso al Mother y un test por reflexión en `TcpdfCarbonFootprintReportPdfGeneratorTest`.
- **Nuevo estado de fuente:** añade la constante `INCLUDED_*` y su referencia en `includedEmissionsStatus.html.twig`, y la rama en `includedSourceEntry()`.
- **Nueva columna de exportación de materialidad:** pásala por `materialityFieldsFor()` y `CarbonFootprintDetailCellsFormatter`.
- Trampas: `DerivedSourceLine` y `ActivityItemNameResolver` tienen parámetros opcionales para no romper a los demás consumidores (`InventoryResultsBuilder`, plan de mejora, informe de evento); no los hagas obligatorios sin revisar todos. Los métodos del trait son compartidos con el informe de evento.

## Tests

- Unitarios: `OrganizationalCarbonFootprintReportDataTest`, `...MaterialityAnnexTest`, `...EmployeeSurveyTest`, `ProviderContributionsReportBuilderTest`, `ProviderNameResolverTest`, `MaterialityAssessmentSnapshotAdapterTest`, `DerivedSourceEmissionsCalculatorTest`, `CarbonFootprintDetailRowsBuilderTest`, `CarbonFootprintDetailCellsFormatterTest`, `PropertyCarbonFootprintTest`, `CompanyCarbonFootprintTest`, `GenerateOrganizationalReportTextsPromptTest`, `TcpdfCarbonFootprintReportPdfGeneratorTest` (filas por reflexión con traductor espía `RecordingTranslator` / `LocaleSpyTranslator`), `TcpdfJustifiedExclusionsDocumentPdfGeneratorTest` y `TcpdfPropertyCarbonFootprintCertificatePdfGeneratorTest`.
- Aceptación: `tests/Acceptance/CarbonFootprint/property-carbon-footprint-materiality.feature` y `company-carbon-footprint-materiality.feature` (Registro de completar y reabrir; la huella se completa por HTTP porque el paso `Given ... is completed` no pasa por `RecordEventSubscriber`).
- Ejecución: `make test-this test=tests/Unit/CarbonFootprint/Infrastructure/Tcpdf`, `make test-unit`, `make test-parallel`; calidad con `make phpstan` y `make cs`.
- Validación registrada en el plan: `make cs-fix`, `make phpstan` y `make test-parallel` correctos (8188 unitarios, 2031 escenarios).
- No se extrae texto del PDF: las tablas se prueban por sus filas.

## Desviaciones y deuda

Ver `plan.md` (sección «Implementación») y `review.md` (cuatro revisiones; 0 abiertos: A1, M1, M2, B1-B4 corregidos). Resumen:

- Desviaciones aceptadas: parámetros `?Locale` opcionales; `factorNumber()` en lugar de 2 decimales fijos; filas WTT etiquetadas «Derivado: emisiones del pozo al tanque (WTT) · {fuente}»; pie alternativo del certificado mantiene «método de mercado»; en Classic el porcentaje de proveedores y la nota de descartadas van como nota bajo la tabla.
- Duda abierta Q2 del review: revisión visual de las previews, pendiente del usuario.
- Deuda declarada en el plan, ya resuelta después por la review: caché duplicada de proveedores (B2), tildes en unidades (B3), instrucción del prompt con evaluación sin veredictos (M1), factor sin unidad en electricidad y anexos (M2).
