# Anexo de materialidad reestructurado con narrativa de IA — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-03 (correcciones de review hasta 2026-11-04) · **Bounded Context:** `CarbonFootprint`
- **Migración:** ninguna (la sección nueva es una fila más de `CarbonFootprintReportSection`).

## Qué hace y por qué

El anexo «Anexo: análisis de materialidad del alcance 3» del informe PDF de huella de propiedad y de empresa se rehízo en dos frentes:

1. **Anexo determinista reestructurado.** La metodología se imprime una vez como párrafo, con una tabla de trazabilidad de 7 filas; la tabla de categorías pasa a ser una matriz con celdas coloreadas; cada categoría excluida lleva una tabla de evidencias («Valor × peso») y una línea de cierre sin frase metodológica ni enumeración de respuestas.
2. **Sección narrativa opcional generada por IA**, `materialityAnalysis` («Análisis de materialidad del alcance 3»). Se genera en la misma llamada que el resto de textos, se edita en la pantalla de textos, se traduce al emitir y se imprime en el anexo de los dos diseños.

El plan previo (`2026-11-03-materialidad-justificacion-de-exclusiones-para-auditorias`) fijó «la IA solo cita»; aquí se revisa porque la sección es **opcional** (mismo mecanismo que `MitigationPlan`) y no bloquea `submitForReview`, `approve` ni `issue`. Decisiones en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/CarbonFootprintReportSectionKey.php` | Caso `MaterialityAnalysis = 'materialityAnalysis'`; `optionalFor()` (propiedad y empresa) lo añade tras `MitigationPlan`; `label()` «Análisis de materialidad del alcance 3»; `isOptional()` | modificado |
| Dominio | `.../OrganizationalCarbonFootprintReportMaterialityAnnex.php` | `requestsNarrative(bool $scope3Excluded)`: `!isAnalysisMissing() && !$scope3Excluded && [] !== justifications` | modificado |
| IA | `src/CarbonFootprint/Infrastructure/AI/Prompt/GenerateOrganizationalReportTextsPrompt.php` | Pide la sección, añade respuestas por categoría excluida, esquema y JSON de ejemplo | modificado |
| IA | `src/CarbonFootprint/Infrastructure/AI/AiCarbonFootprintReportTextsGenerator.php` | `translate()` no envía a traducir las secciones vacías | modificado |
| UI | `src/CarbonFootprint/Infrastructure/Symfony/Form/UpdateCarbonFootprintReportTextsType.php` | `help` traducible solo en `MaterialityAnalysis` | modificado |
| PDF | `src/CarbonFootprint/Infrastructure/Tcpdf/MaterialityAnnexFiguresTrait.php` | Párrafo de metodología, tabla de trazabilidad, matriz con color, evidencias, línea de cierre | modificado |
| PDF | `.../TcpdfCarbonFootprintReportPdfGenerator.php`, `.../ClassicTcpdfCarbonFootprintReportPdfGenerator.php` | Orden del anexo y narrativa en cada diseño | modificado |
| PDF | `.../TcpdfJustifiedExclusionsDocumentPdfGenerator.php` | Documento de exclusiones: mismos bloques deterministas, nunca la narrativa | modificado |
| i18n | `translations/messages+intl-icu.{es,en,tr}.xlf` | Claves nuevas y retiradas (TR con `NEW_`) | modificado |

## Modelo de dominio

- `CarbonFootprintReportSectionKey` (`CarbonFootprintReportSectionKey.php:20,36,54,61`): no hay evento ni agregado nuevo. `CarbonFootprintReportTexts::sections` sigue siendo `array<string,string>` y `CarbonFootprintReport::replaceSections` persiste una `CarbonFootprintReportSection` por clave presente.
- Es **opcional en todos los estados**: `isCompleteFor()` solo exige las obligatorias, por lo que un borrador anterior al despliegue muestra el campo vacío y puede enviarse a revisión o emitirse.
- Condición de «aplica»: `OrganizationalCarbonFootprintReportMaterialityAnnex::requestsNarrative()` (`...MaterialityAnnex.php:56-59`). Es la única fuente: la usa el prompt (`requestsMaterialityAnalysis()`, `GenerateOrganizationalReportTextsPrompt.php:~330`).
- Informe de **evento**: la clave no se ofrece (`optionalFor` solo la añade a Property y Company).

## Flujo de una operación

**Generación de textos** (al pedir el informe o reemitirlo, `ReissueCarbonFootprintReportService.php:50`):
`GenerateCarbonFootprintReportTextsService` → `AiCarbonFootprintReportTextsGenerator::generate()` → `GenerateOrganizationalReportTextsPrompt`.

En el prompt:
- `materialityLines()` ya emitía veredictos; ahora `materialityVerdictLines()` añade, por cada categoría excluida con respuestas, líneas `Answer: criterio | "pregunta" | respuesta | valor × peso = puntos` y `Total: N points in range min–max` (`GenerateOrganizationalReportTextsPrompt.php:~291-312`), vía `justificationOf()`.
- La cabecera de veredictos cambia según se pida o no la sección (solo con sección se permite «explicar»); sin ella se mantiene «cite them, do not produce your own justification», para que «materialityAnalysis» no aparezca en el prompt cuando no se pide.
- Numeración: «Write seven/eight/nine sections», la sección es la 8 o la 9 según haya plan de mitigación (`:166-173,197-206`).
- Evaluación **reabierta** (`isReopened()`): la instrucción sustituye «connect the answers…» por explicar solo con relevancia, umbral, veredicto y anulación, y añade «The answers are not available: do not describe, assume or infer any answer.»
- `responseSchema()` añade `materialityAnalysis` a `properties` y nunca a `required` (`:~419-421`).

**Edición**: `TextsController` construye los campos con `requiredFor + optionalFor` (`TextsController.php:62-66`); el campo aparece siempre en propiedad/empresa aunque no aplique, por lo que lleva la ayuda «Solo se imprime si la huella tiene evaluación de materialidad con categorías excluidas y el alcance 3 incluido.» (`UpdateCarbonFootprintReportTextsType.php:35-37`). Los campos vacíos llegan como `''` y se persisten como secciones vacías.

**Traducción al emitir** (`GenerateCarbonFootprintReportPdfService.php:81`): `AiCarbonFootprintReportTextsGenerator::translate()` (`:38-62`) separa las secciones con `'' === trim($content)`, traduce solo las rellenas y devuelve `[...traducidas, ...vacías]`; si todas están vacías no llama a la IA. Esto evita que una opción vacía viaje como obligatoria en `TranslateReportTextsPrompt` (`'required' => array_keys(sections)`), defecto que ya afectaba a `MitigationPlan`.

**Impresión** (orden del anexo en `TcpdfCarbonFootprintReportPdfGenerator::renderMaterialityAnnexSection`, `:487-560`):
1. título; 2. declaración si no hay análisis o decisiones; 3. párrafo de metodología + tabla de trazabilidad; 4. tabla de preguntas (si `showsAnswers()`); 5. matriz con color; 6. narrativa (`$texts->has(MaterialityAnalysis)`, título «Análisis» + Markdown; el método sale antes si `scope3Excluded`); 7. «Evidencias por categoría»: `<h5>` por categoría, tabla de evidencias y línea de cierre.

Classic (`ClassicTcpdfCarbonFootprintReportPdfGenerator::renderMaterialityAnnexPages`, `:~205-260`): cada bloque es una página (`renderTextPage` para metodología y narrativa con `context.jpg`; `renderDataPage` para trazabilidad, matriz y evidencias; el pie de las páginas de evidencias se prefija con `materialityEvidenceTitle()`).

**Documento de exclusiones justificadas** (`TcpdfJustifiedExclusionsDocumentPdfGenerator.php:80-160`, enlazado desde el botón «Exclusiones justificadas» del asistente): usa el mismo trait pero no recibe textos; es determinista.

### Piezas del trait (`MaterialityAnnexFiguresTrait.php`)

| Método | Función |
|---|---|
| `materialityMethodologyParagraph()` | Norma, criterios, escala, umbral y regla de magnitud alta en un párrafo |
| `materialityTraceabilityRows()` | Sector, iniciada el, completada el, completada por, identificador, versión de las preguntas, estado |
| `buildMaterialityMatrixHtml()` | Matriz propia (los `buildTableHtml()` no admiten estilo por celda); cabecera específica por generador (`materialityMatrixHeaderCell()`) |
| `materialityVerdictColor()` / `materialityStateColor()` | `Calculate` verde `#DFF2E4`, `Omit` ámbar `#FBE9C6`, sin veredicto gris `#ECEFF1`; estado ámbar si `ActiveOverridden`, `ExcludedOverridden` o `PendingReview` |
| `materialityAnswerRows()` | Columna «Valor × peso» (`"%d × %d"`) y fila Total con rango |
| `materialityJustificationText()` | Línea de cierre: total/relevancia/umbral, veredicto, motivo y comentario, anulación (sin `$annex`) |

Los tres colores son constantes del trait porque `PdfBranding` no tiene colores semánticos.

## Autorización y ámbito

Sin cambios: la pantalla de textos exige `Edit` sobre el informe, `isEditableByOwner()` y `permissions->canEditTexts()` (`TextsController.php:50-58`). Con `autoReview` el Propietario puede emitir directamente, de modo que el texto de IA no pasa por revisión de un Admin. El Admin ve y corrige el mismo campo en la revisión del informe.

## Persistencia y migración

Sin migración: la clave es un valor de cadena en la fila de sección. Los informes ya generados no se regeneran; la narrativa llega a los generados o reemitidos tras el despliegue.

## Configuración y operación

Sin variables, colas ni comandos nuevos. Usa el nivel `AiModelTier::MEDIUM` ya existente del prompt; con 15 categorías excluidas la sección es larga: vigilar el límite de tokens de salida.

Traducciones: `make translation` extrae las cadenas; retiradas las claves de las filas de metodología antiguas (review B4 y B6).

## Cómo extenderlo

- **Otra sección opcional:** 1) caso en `CarbonFootprintReportSectionKey` con `label()` e `isOptional()`; 2) añadirla a `optionalFor()` del tipo de sujeto; 3) en el prompt, condición de petición, numeración, JSON de ejemplo y `responseSchema()` (solo en `properties`); 4) imprimirla en ambos generadores con `$texts->has(...)`; 5) `help` en el form type si no siempre aplica.
- **Un texto por categoría:** requiere un esquema de textos nuevo (descartado en el plan).
- **Colores de marca:** derivar las constantes de `PdfBranding` (plan posterior).
- **Trampa:** cualquier sección opcional vacía debe seguir saltándose en `translate()`.

## Tests

- Unitarios: `GenerateOrganizationalReportTextsPromptTest` (petición, esquema, respuestas, evaluación reabierta), `AiCarbonFootprintReportTextsGeneratorTest` (`testTranslateSkipsEmptySectionsAndKeepsThemEmpty`, `testTranslateDoesNotCallTheAiWhenEverySectionIsEmpty`), `CarbonFootprintReportSectionKeyTest`, `OrganizationalCarbonFootprintReportMaterialityAnnexTest`, `TcpdfCarbonFootprintReportPdfGeneratorTest`, `TcpdfJustifiedExclusionsDocumentPdfGeneratorTest`. Los de PDF comprueban textos con un traductor espía; para la narrativa en Classic, con el número de páginas (el contenido va comprimido).
- Aceptación: `tests/Acceptance/CarbonFootprint/carbon-footprint-report-texts.feature` (campo y ayuda visibles; guardar a mano; enviar a revisión con el campo vacío).
- Ejecutar: `make test-unit options=tests/Unit/CarbonFootprint/Infrastructure/AI`, `make test-acceptance options=...`, o `make test-parallel` completo. `make test-this` no existe en el `Makefile`.
- Hueco: la maquetación visual no está automatizada (se validó generando un informe local).

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: cabecera de veredictos condicional, prefijo «Evidencias por categoría» en Classic, dos métodos nuevos en `OrganizationalCarbonFootprintReportDataMother`.
- La review (`review.md`, revisión 3) no deja hallazgos abiertos: A1, M1, M2 y B1–B6 corregidos. El plan contradecía el comportamiento final en dos puntos (campo siempre ofrecido con ayuda; `translate()` salta secciones vacías).
- Deuda residual: `GenerateEventReportTextsPromptTest.php` contiene la clase `GenerateReportTextsPromptTest` (aviso de PHPUnit, anterior a este plan).
