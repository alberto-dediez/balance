# Materialidad · Justificación de exclusiones para auditorías — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-03 · **Bounded Contexts:** `CarbonFootprint`, `Materiality`, `Security` (solo plantillas del Registro)

## Qué hace y por qué

Las exclusiones del alcance 3 de una huella de propiedad o de empresa necesitan un razonamiento defendible ante un verificador (Registro MITECO, verificación GHG Protocol). Los planes previos de materialidad ya guardaban ese razonamiento (instantánea de preguntas y respuestas en `MaterialityAssessment`, decisiones con umbral, veredicto y anulación en la huella), pero no llegaba al informe. Este plan lo lleva a cuatro sitios, todos derivados de la misma fuente de datos:

1. Un **anexo «Anexo: análisis de materialidad del alcance 3»** en el PDF del informe (temas vertical y Classic).
2. Un **documento independiente «Exclusiones justificadas del alcance 3»**, PDF generado en el momento de la descarga desde el desplegable «Acciones» de la huella completada.
3. **Cinco columnas opcionales de materialidad** en la exportación en detalle.
4. Un **bloque de veredictos en el prompt** de la IA, que debe citarlos sin inventar justificaciones.

Además, ocho plantillas nuevas del Registro de auditoría para las exclusiones/inclusiones de fuente y de alcance.

Decisiones clave (tabla «Decisiones tomadas» de `plan.md`):

- **Dependencia `Materiality → CarbonFootprint`, nunca al revés.** `CarbonFootprint` define un puerto (`MaterialityAssessmentSnapshotProvider`) con DTOs propios; el adaptador vive en `Materiality`. `CarbonFootprint` no importa ninguna clase de `Materiality`.
- **Nada se persiste.** El texto de justificación es una función determinista de hechos ya inmutables y se compone al vuelo, en el idioma de quien lo pide. No hay migración.
- **Manda la huella.** Relevancia, umbral, veredicto y anulación salen de las decisiones aplicadas a la huella (`MaterialityDecision`); preguntas, respuestas, sector y autoría salen de la evaluación vía el puerto.
- **La IA solo cita.** No se añadió ninguna sección de texto nueva (`CarbonFootprintReportSectionKey` no cambia).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio CF | `src/CarbonFootprint/Domain/Model/CarbonFootprint/MaterialityCategoryState.php` | Enum de estado de la categoría; movido desde Aplicación para que el DTO de dominio lo referencie | Movido |
| Dominio CF | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/MaterialityAssessmentSnapshotProvider.php` | Puerto: `find(subjectType, carbonFootprintId): ?MaterialityAssessmentSnapshot` | Nuevo |
| Dominio CF | `…/CarbonFootprintReport/MaterialityAssessmentSnapshot.php`, `…SnapshotTopic.php`, `…SnapshotAnswer.php` | DTOs de la evaluación (`final readonly`) | Nuevo |
| Dominio CF | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportMaterialityAnnex.php`, `MaterialityAnnexTopicLine.php`, `MaterialityAnnexJustification.php` | DTOs del anexo | Nuevo |
| Dominio CF | `…/CarbonFootprintReport/JustifiedExclusionsDocumentPdfGenerator.php` | Puerto del PDF independiente | Nuevo |
| Dominio CF | `…/CarbonFootprintReport/OrganizationalCarbonFootprintReportData.php` | Campo `materialityAnnex` y `hasMaterialityDecisions()` | Modificado |
| Aplicación CF | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/MaterialityAnnexBuilder.php` | Construye el anexo a partir de la huella y el puerto | Nuevo |
| Aplicación CF | `…/Data/{Property,Company,Event}CarbonFootprintReportDataCollector.php`, `CarbonFootprintReportDataCollector.php`, `CarbonFootprintReportDataCollectorRegistry.php` | `collectByCarbonFootprintId()` / `collectFor()`; propiedad y empresa inyectan el anexo | Modificado |
| Aplicación CF | `src/CarbonFootprint/Application/CarbonFootprint/JustifiedExclusions/*` | Caso de uso de descarga (Request, Response, Service, excepción de huella abierta) | Nuevo |
| Aplicación CF | `src/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRow.php`, `CarbonFootprintDetailRowsBuilder.php` | Campos de materialidad en las filas de exclusión | Modificado |
| Aplicación CF | `…/Materiality/MaterialitySummaryBuilder.php`, `MaterialitySummaryRow.php` | Solo cambio de `use` del enum | Modificado |
| Aplicación Mat. | `src/Materiality/Application/MaterialityAssessment/Snapshot/MaterialityAssessmentSnapshotAdapter.php` | Adaptador del puerto | Nuevo |
| Infra. CF | `src/CarbonFootprint/Infrastructure/Tcpdf/MaterialityAnnexFiguresTrait.php` | Filas, textos y declaraciones comunes a los tres PDF | Nuevo |
| Infra. CF | `…/Tcpdf/TcpdfJustifiedExclusionsDocumentPdfGenerator.php` | PDF independiente | Nuevo |
| Infra. CF | `…/Tcpdf/TcpdfCarbonFootprintReportPdfGenerator.php`, `ClassicTcpdfCarbonFootprintReportPdfGenerator.php` | Anexo y nota al pie | Modificado |
| Infra. CF | `src/CarbonFootprint/Infrastructure/AI/Prompt/GenerateOrganizationalReportTextsPrompt.php` | Bloque de veredictos e instrucciones condicionales | Modificado |
| Infra. CF | `src/CarbonFootprint/Infrastructure/DataExchange/CarbonFootprintDetailCellsFormatter.php`, `EventCarbonFootprintDetailExportationType.php` | Cinco columnas; el tipo de evento las excluye | Modificado |
| Infra. CF | `src/CarbonFootprint/Infrastructure/UI/Web/CarbonFootprint/JustifiedExclusionsDocumentController.php` | Ruta de descarga | Nuevo |
| Infra. CF | `src/CarbonFootprint/Infrastructure/Symfony/DependencyInjection/services.yaml` | Alias del puerto del PDF | Modificado |
| Infra. Mat. | `src/Materiality/Infrastructure/Symfony/DependencyInjection/services.yaml` | Alias del puerto de la evaluación → adaptador | Modificado |
| Shared | `src/Shared/Infrastructure/Tcpdf/GenericGenerator.php` | `renderSectionTitle` (orden de palabras) y `markdownToStyledHtml` (HTML crudo escapado; `<br>` simple permitido) | Modificado por la review |
| Plantillas | `templates/components/TopAction/JustifiedExclusions.html.twig`; `templates/carbon_footprint/{property,company}_carbon_footprints/wizard-layout.html.twig` | Ítem «Exclusiones justificadas» | Nuevo / Modificado |
| Plantillas | `templates/security/admin/events/detail/carbon-footprint.{property,company}CarbonFootprint{SourceExcluded,SourceIncluded,ScopeExcluded,ScopeIncluded}.html.twig` | Texto del Registro (8) | Nuevo |

## Modelo de dominio

No hay agregados ni eventos nuevos ni cambios de esquema. Todo son DTOs de lectura.

- `MaterialityAssessmentSnapshot` (`src/CarbonFootprint/Domain/Model/CarbonFootprintReport/MaterialityAssessmentSnapshot.php`): `assessmentId`, `completed`, `startedAt`, `completedAt`, `completedByName`, `sectorName`, `thresholdPercent`, `highMagnitudeRule` (`?bool`), `questionSetVersion`, `questionSetChangedSince`, `topics`, `answers`; `answersOf(topicId)`.
- `MaterialityAssessmentSnapshotAnswer`: texto, clave de criterio, clave de etiqueta de respuesta (`answerLabelKey`, `''` si sin responder), clave de escala (`answerScaleKey`), peso, valor, puntos, rango, `questionUpdatedAt`, `questionChangedSince`, `questionRemoved`.
- `OrganizationalCarbonFootprintReportMaterialityAnnex`:
  - `showsAnswers()`: evaluación completada.
  - `isReopened()`: hay evaluación pero está en borrador.
  - `isAnalysisMissing()`: no hay decisiones y la evaluación no está completada o no tiene ningún veredicto (cubre huellas anteriores al plan de activación, sin backfill).
  - `isEmpty()`: sin evaluación y sin decisiones.
  - `topicFor(Source)`.
- `MaterialityAnnexTopicLine`: una por categoría de la tabla (número, nombre instantaneado o `null`, fuente, relevancia, umbral, veredicto, estado, motivo y comentario de la exclusión, anulación). `isExcluded()` es cierto para `ExcludedRecommended` y `ExcludedOverridden`.
- `MaterialityAnnexJustification`: línea excluida + respuestas + puntos y rango.

Invariantes (`MaterialityAnnexBuilder.php:30-87`):

- Una justificación por fila del resumen excluida (`isExcluded()`) y con `source` no nula. `pending_review` y `active_overridden` salen en la tabla, no como justificación.
- Las respuestas solo se adjuntan si la evaluación está completada (`:62`).
- Categoría 8 y categorías propias sin número tienen estado `without_source`, nunca «exclusión justificada» (lo decide `MaterialitySummaryBuilder`).

## Flujo de una operación

### A. Descarga del documento independiente

1. `GET /carbon-footprints/{property|company}/{carbonFootprintId}/justified-exclusions` → `JustifiedExclusionsDocumentController.php:19` (ruta `carbon_footprint_justified_exclusions_download`).
2. El controlador construye `DownloadJustifiedExclusionsDocumentRequest` y llama al servicio con `LoggedUser`.
3. `DownloadJustifiedExclusionsDocumentService::__invoke` (`DownloadJustifiedExclusionsDocumentService.php:37`):
   - Tipo `Event` o huella inexistente → `\InvalidArgumentException` (404).
   - `denyUnlessCanDoThat(…, Action::View, $user)` con el authorizer del tipo (`:50`) → `UnauthorizedActionException` (403).
   - Huella no completada → `JustifiedExclusionsRequireCompletedFootprintException` (`:52`; 409). Se comprueba **después** de autorizar para no filtrar el estado a otras cuentas.
   - `collectFor(subjectType, id, $locale)` (`:57`) reutiliza el colector del informe; se exige `OrganizationalCarbonFootprintReportData`.
   - `PdfBranding::fromTokens(BrandingResolver::resolve(accountId), subjectName)` aplica la marca blanca de la cuenta.
   - `JustifiedExclusionsDocumentPdfGenerator::generate()` devuelve una ruta temporal.
4. El controlador responde `BinaryFileResponse` con `Content-Type: application/pdf`, `attachment` y `deleteFileAfterSend(true)`. El nombre es `exclusiones-justificadas-{id}-{año}-{locale}.pdf`.

### B. Construcción del anexo (compartida por informe, documento y prompt)

`Property|CompanyCarbonFootprintReportDataCollector::collectByCarbonFootprintId()` → `MaterialityAnnexBuilder::build()`:

1. `MaterialityAssessmentSnapshotProvider::find()` → adaptador en `Materiality` (`MaterialityAssessmentSnapshotAdapter.php:28`): carga la evaluación vigente de la huella (`findCurrentByCarbonFootprint`), mapea categorías y respuestas, y por cada pregunta distinta consulta una vez el maestro (cache local por `questionId`) para marcar `questionChangedSince` (`updatedAt ?? registeredAt` posterior a `questionUpdatedAt`) y `questionRemoved`.
2. Las entradas del `MaterialitySummaryBuilder` salen de las categorías de la evaluación o, sin evaluación pero con decisiones, de las decisiones de la huella.
3. Por cada fila se crea la línea y, si está excluida, su justificación. El nombre de quien anula se resuelve con `UserRepository`; usuario borrado → `'---'` (`MaterialityAnnexBuilder.php:113`).

`collect(CarbonFootprintReport)` delega ahora en `collectByCarbonFootprintId($id, $report->baseLocale())`; el `?Locale` opcional conserva la traducción del plan de mitigación y de «Sin asignar a propiedad».

### C. Anexo en el informe

- Vertical: `TcpdfCarbonFootprintReportPdfGenerator::renderMaterialityAnnexSection` (`:490`), invocado en `:128` tras el anexo de factores. Declaración (si procede), metodología, preguntas, categorías y una justificación por categoría excluida.
- Classic: `renderMaterialityAnnexPages` tras la página de anexo; una página por justificación.
- Nota al pie «Las exclusiones del alcance 3 se justifican en el anexo…»: `TcpdfCarbonFootprintReportPdfGenerator.php:317` y, como parte del pie de la página de exclusiones, `ClassicTcpdfCarbonFootprintReportPdfGenerator.php:145`. Solo si hay decisiones (`hasMaterialityDecisions()`).
- La declaración (`materialityAnnexStatement`, `MaterialityAnnexFiguresTrait.php:194`) elige, por este orden: alcance 3 excluido completo; sin análisis (`isAnalysisMissing()`); evaluación reabierta; si no, `null` y se imprime el anexo completo. Con declaración y sin decisiones solo se imprime el párrafo; con decisiones se añade la tabla de categorías. Con alcance 3 excluido completo no hay justificaciones por categoría.

### D. Documento independiente

`TcpdfJustifiedExclusionsDocumentPdfGenerator::generate` (`:27`) fija el locale actual, pinta portada y secciones: «Datos de la organización y de la evaluación» (con la metodología si hay análisis), «Alcance 3 en el inventario» (tabla filtrada al alcance 3 o declaración de exclusión total), «Metodología del análisis de materialidad» (preguntas, solo con evaluación completada), «Resultado por categoría», «Justificación de cada exclusión» y «Trazabilidad». Sin análisis solo queda la declaración. No lleva índice.

### E. Prompt y exportación

- Prompt: `materialityLines()` (`GenerateOrganizationalReportTextsPrompt.php:210`) emite el bloque «Scope 3 materiality assessment (system verdicts…)» con una línea por categoría, o la frase de «sin análisis», o la de alcance 3 excluido. Las instrucciones de `regulatoryContext`, `methodology` y `uncertainty` son condicionales (`:164-182`): solo mencionan la materialidad cuando hay análisis y el alcance 3 no está excluido en su totalidad. El esquema de respuesta no cambia.
- Exportación: `CarbonFootprintDetailRowsBuilder` rellena las cinco columnas solo en filas de exclusión por fuente del alcance 3 con decisión (`materialityDecisionFor`); la fila de alcance 3 completo no las lleva. `CarbonFootprintDetailCellsFormatter` las define desmarcadas por defecto. `EventCarbonFootprintDetailExportationType` las añade a `EXCLUDED_COLUMNS`.

## Autorización y ámbito

- **Documento:** `Action::View` sobre la huella con `PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer`: Propietario de la cuenta actual (impersonación incluida, vía el authorizer). Otra cuenta, 403. No hay acceso de Administrador al documento (decisión del plan).
- **Visibilidad del enlace:** solo con la huella completada y `is_authorized('view')` (`wizard-layout.html.twig`, después de «Exportar» y antes de «Ver indicadores»). El servicio lo refuerza con el 409.
- **Anexo del PDF:** hereda las autorizaciones del informe. **Columnas de exportación:** las de la exportación en detalle (Propietario, huella completada).
- Registro: las plantillas solo se ven en el listado de eventos de administración.

## Persistencia y migración

Sin cambios de esquema ni migración. `MaterialityCategoryState` solo cambió de namespace (`App\CarbonFootprint\Domain\Model\CarbonFootprint`); no se persiste con Doctrine. Los informes ya `generated` no se regeneran: el anexo aparece en los generados tras el despliegue.

## Configuración y operación

- `src/Materiality/Infrastructure/Symfony/DependencyInjection/services.yaml:15`: alias `MaterialityAssessmentSnapshotProvider` → `MaterialityAssessmentSnapshotAdapter`. Es obligatorio: Symfony no autowirea una interfaz cuya única implementación se carga desde el `services.yaml` de otro BC (declarado en `## Implementación` del plan).
- `src/CarbonFootprint/Infrastructure/Symfony/DependencyInjection/services.yaml`: alias de `JustifiedExclusionsDocumentPdfGenerator` → `TcpdfJustifiedExclusionsDocumentPdfGenerator`.
- Sin variables de entorno, colas ni comandos nuevos. El PDF independiente es síncrono; si en producción tarda demasiado, el paso siguiente sería el patrón diferido del certificado.
- Traducciones ES/EN en `translations/messages+intl-icu.{es,en}.xlf` (`make translation`); dominios reutilizados: `materialityVerdictType`, `materialityCategoryStateType`, `materialityCriterionType`, `materialityAnswerValueType`, `materialityAnswerScaleType`, `exclusionReason`, `source`, `scope`. El catálogo TR queda con claves `NEW_` pendientes.
- Seguridad de los textos libres: `markdownToStyledHtml` convierte con `html_input: escape` y `allow_unsafe_links: false` (`GenericGenerator.php:428-435`), de modo que HTML crudo no llega a `writeHTML` (TCPDF descarga `<img src="http…">`). Solo se readmite el token `<br>` sin atributos. Los textos de usuario interpolados en markdown pasan por `materialityPlain()` (`MaterialityAnnexFiguresTrait.php:226`: colapsa espacios y saltos, escapa HTML y caracteres markdown).

## Cómo extenderlo

- **Nueva frase o fila del anexo:** añadirla en `MaterialityAnnexFiguresTrait`, que alimenta a la vez el informe vertical, el Classic y el documento; añadir las claves ES/EN y un caso en `TcpdfCarbonFootprintReportPdfGeneratorTest`.
- **Nuevo dato de la evaluación:** 1) campo en `MaterialityAssessmentSnapshot*`; 2) rellenarlo en `MaterialityAssessmentSnapshotAdapter`; 3) usarlo en el trait o en el prompt; 4) actualizar `MaterialityAssessmentSnapshotMother` y `InMemoryMaterialityAssessmentSnapshotProvider`.
- **Nueva columna de exportación:** seguir la receta del manual de la exportación en detalle (`CarbonFootprintDetailRow`, builder, formatter) y añadirla a `EXCLUDED_COLUMNS` de los tipos a los que no aplique.
- **Informe de evento:** no tiene anexo ni documento (fuera de alcance); el servicio rechaza el tipo `Event`.
- Trampas: no importar clases de `Materiality` en `CarbonFootprint` (`grep -r "App\\\\Materiality" src/CarbonFootprint` debe seguir vacío); la regla «sin análisis» vive solo en `isAnalysisMissing()`, no duplicarla; no interpolar texto de usuario en markdown sin `materialityPlain()`; la fuente de TCPDF no tiene el glifo «→».

## Tests

- **Unitarios:** `MaterialityAnnexBuilderTest`, `OrganizationalCarbonFootprintReportMaterialityAnnexTest`, `MaterialityAnnexTopicLineTest`, `MaterialityAssessmentSnapshotAdapterTest` (`tests/Unit/Materiality/Application/MaterialityAssessment/Snapshot/`), `DownloadJustifiedExclusionsDocumentServiceTest`, `TcpdfJustifiedExclusionsDocumentPdfGeneratorTest`, ampliaciones de `TcpdfCarbonFootprintReportPdfGeneratorTest`, `GenerateOrganizationalReportTextsPromptTest`, `CarbonFootprintDetailRowsBuilderTest`, `EventCarbonFootprintDetailExportationTypeTest` y `tests/Unit/Shared/Infrastructure/Tcpdf/GenericGeneratorTest.php`. Mothers: `MaterialityAssessmentSnapshotMother`, `OrganizationalCarbonFootprintReportMaterialityAnnexMother`; doble `tests/Unit/CarbonFootprint/Infrastructure/Materiality/InMemoryMaterialityAssessmentSnapshotProvider.php`.
- **Behat:** `tests/Acceptance/CarbonFootprint/carbon-footprint-justified-exclusions.feature` (descarga de propiedad y empresa con decisiones, sin evaluación, 403 de otra cuenta, 409 con huella abierta de propiedad y de empresa, 404, enlace visible/oculto en ambos asistentes).
- **Ejecutar:** `make test-this test=tests/Unit/CarbonFootprint/Application/CarbonFootprintReport/Data/MaterialityAnnexBuilderTest.php` y `make test-this test=tests/Acceptance/CarbonFootprint/carbon-footprint-justified-exclusions.feature`; completo con `make test-parallel`; además `make phpstan` y `make cs`.
- Los tests de PDF generan previews en `var/carbon-footprint-report-preview/` para revisión visual (la corrección del orden de palabras de los títulos no se revisó visualmente, según `review.md`).

## Desviaciones y deuda

Desviaciones declaradas en `## Implementación` de `plan.md` (alias explícito del puerto, parámetro `?Locale` en el colector, nota de reapertura sin fecha, «×» en lugar de «→» en las respuestas, documento sin fila «Estado de la huella») y correcciones posteriores de `review.md` (5 rondas, sin hallazgos abiertos) que **cambian el comportamiento respecto al plan**:

- La descarga con la huella abierta devuelve 409 (M2).
- El nombre de una categoría sin tema en la evaluación se traduce al idioma del PDF, no al por defecto (B2).
- «Regla de magnitud alta» deja de leerse de la configuración actual: se deduce de la evaluación (`true` si alguna categoría se forzó, `false` si alguna con magnitud máxima quedó en Omitir, `null` si no se puede saber) (B3).
- Las instrucciones del prompt son condicionales y la cabecera del bloque añade criterios y escala (M4, M6).
- `markdownToStyledHtml` escapa HTML crudo (A1, B7) y `renderSectionTitle` conserva el orden de las palabras (M3).
- Las claves TR de este plan siguen pendientes de traducir.

Ver `plan.md` y `review.md` (misma carpeta).
