# Doble reporte de alcance 2 (totales por localización y electricidad en la huella de evento) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-23 · **Bounded Contexts:** `CarbonFootprint`

## Qué hace y por qué

El GHG Protocol pide reportar la electricidad comprada por dos métodos: mercado (factor de la comercializadora o GdO) y localización (mix medio de la red del país). Desde `electricity-mix-location-based` cada `ElectricityActivity` guarda ambos resultados, pero la presentación estaba incompleta: la huella de evento no mostraba la localización en ningún sitio, no existía un total de huella por localización y, si una actividad carecía de resultado de localización, el total quedaba bajo sin aviso.

La solución no toca el dominio ni el cálculo oficial. Se amplía el DTO `EmissionsBreakdown` con cuatro magnitudes derivadas (electricidad por mercado, total por localización y dos contadores), y se propagan a las respuestas de resultados web, a los DTO de datos de informe, a los dos generadores PDF (tema por defecto y clásico) y a los prompts de Gemini. El método de mercado sigue alimentando totales, dashboard, compensación y gráficos (decisiones en la tabla «Decisiones tomadas» de `plan.md`).

Decisión clave: `Total · método de localización` = total oficial − Σ mercado de las electricidades que **sí** tienen localización + Σ localización de esas mismas. Las electricidades sin localización conservan su valor de mercado en ese total y se avisa con un contador.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Application | `src/CarbonFootprint/Application/CarbonFootprint/EmissionsBreakdown.php` | DTO: `marketBasedElectricity`, `locationBasedTotal`, `electricityActivities`, `electricityActivitiesWithoutLocationBased` (todos obligatorios) | modificado |
| Application | `src/CarbonFootprint/Application/CarbonFootprint/EmissionsBreakdownBuilder.php` | Acumula las cuatro magnitudes en el bucle de actividades y en las líneas derivadas | modificado |
| Application | `src/CarbonFootprint/Application/EventCarbonFootprint/GetResults/GetResultsResponse.php`, `GetResultsService.php` | Cinco campos nuevos en resultados de evento | modificado |
| Application | `.../PropertyCarbonFootprint/GetResults/GetPropertyCarbonFootprintResultsResponse.php`, `...Service.php` | `locationBasedTotalEmissions`, `electricityActivitiesWithoutLocationBased` | modificado |
| Application | `.../CompanyCarbonFootprint/GetResults/GetCompanyCarbonFootprintResultsResponse.php`, `...Service.php` | Ídem para empresa | modificado |
| Application | `.../CarbonFootprintReport/Data/{Event,Property,Company}CarbonFootprintReportDataCollector.php` | Copian las cifras del breakdown al DTO de informe; el de evento rellena además la localización por línea | modificado |
| Domain | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/EventCarbonFootprintReportData.php` | Cinco campos nuevos | modificado |
| Domain | `.../OrganizationalCarbonFootprintReportData.php` | `locationBasedTotalEmissions`, `electricityActivitiesWithoutLocationBased` | modificado |
| Domain | `.../EventCarbonFootprintReportActivityLine.php` | `?string $locationBasedEmissionsCo2eq = null` (último parámetro) | modificado |
| Infrastructure | `src/CarbonFootprint/Infrastructure/Tcpdf/CarbonFootprintReportFiguresTrait.php` | Cifras clave (`:54`, `:77`), `organizationalDualReportingRows` (`:542`), `eventDualReportingRows` (`:558`), `eventElectricityTable` (`:578`) | modificado |
| Infrastructure | `.../Tcpdf/TcpdfCarbonFootprintReportPdfGenerator.php` | Tabla de totales (`:158`, `:190`) y página de electricidad de evento (`:173`) | modificado |
| Infrastructure | `.../Tcpdf/ClassicTcpdfCarbonFootprintReportPdfGenerator.php` | Ídem en tema clásico (`:575`, `:578`, `:599`) | modificado |
| Infrastructure | `.../AI/Gemini/GenerateEventReportTextsPrompt.php`, `GenerateOrganizationalReportTextsPrompt.php` | Líneas de datos nuevas para el resumen ejecutivo | modificado |
| Infrastructure | `templates/carbon_footprint/{property,company,event}_carbon_footprints/step/results.html.twig` | Filas nuevas en la tarjeta «Totales» y aviso `Alert` | modificado |
| Traducciones | `translations/messages+intl-icu.{es,en,tr}.xlf` | Claves nuevas (dominio `messages`) | modificado |

## Modelo de dominio

No hay cambios en agregados, eventos, repositorios ni mappings. Todo se deriva de `ElectricityActivity::emissions()` (mercado) y `ElectricityActivity::locationBasedEmissions(): ?Emissions` (localización, nula si no hay mix para país/año).

Sea `E` el conjunto de electricidades de la huella y `E_loc ⊆ E` las que tienen localización:

| Campo de `EmissionsBreakdown` | Fórmula |
|---|---|
| `marketBasedElectricity` | Σ_{E} mercado |
| `locationBasedElectricity` (previo) | Σ_{E_loc} localización |
| `locationBasedTotal` | total − Σ_{E_loc} mercado + Σ_{E_loc} localización |
| `electricityActivities` | \|E\| |
| `electricityActivitiesWithoutLocationBased` | \|E \ E_loc\| |

Invariantes (cubiertos por `EmissionsBreakdownBuilderTest`): en huellas organizativas `marketBasedElectricity == byStage['scope_2']` (el alcance 2 solo contiene electricidad); sin electricidades `locationBasedTotal == total` y todo a 0; sin actividades sin mix, `locationBasedTotal == total − marketBasedElectricity + locationBasedElectricity`.

`Emissions` no tiene resta (solo `add`), por eso `locationBasedTotal` se acumula actividad a actividad: `EmissionsBreakdownBuilder.php:75-76` suma `locationBasedEmissions ?? emissions()`; y las líneas derivadas y las propiedades no consolidadas de alcance 3 se suman tal cual (`:108`, `:120`).

## Flujo de una operación

Resultados web: `events_carbon_footprint_results` (`/events/{eventId}/carbon-footprint/step/results`, `Infrastructure/UI/Web/EventCarbonFootprint/Step/ResultsController.php:38`) → `GetResultsService` → `EmissionsBreakdownBuilder::build()` → `GetResultsResponse` → `event_carbon_footprints/step/results.html.twig`. Los controladores y las vistas (`ResultsView`, `OrganizationalResultsView`) no cambian: las plantillas leen `results.*`. Propiedad y empresa siguen el mismo camino con sus servicios `GetPropertyCarbonFootprintResultsService` y `GetCompanyCarbonFootprintResultsService`.

Informe PDF: collector → `EmissionsBreakdownBuilder` → DTO de informe → generador (por defecto o clásico) → métodos del trait.

Origen del país que decide si hay resultado de localización: `CarbonFootprintCountryResolver::resolve()` (evento → su propiedad → país; propiedad → su país; empresa → el de la propiedad del bloque), pasado como `countryId` por `ElectricityAnswerHandler` a `RegisterElectricityActivityService`, que lo resuelve con `ElectricityMixEmissionsResolver` (devuelve `null` si no hay país, mix, o factor del año/grupo).

### Qué muestra cada superficie

| Superficie | Filas / cifras | Condición |
|---|---|---|
| Resultados web propiedad y empresa | `Alcance 2 · método de mercado` (`emissionsByScope['scope_2']`), `Alcance 2 · método de localización`, `Total · método de localización` | siempre |
| Resultados web evento | `Electricidad · método de mercado`, `Electricidad · método de localización`, `Total · método de localización` | `electricityActivities > 0` |
| Aviso `<twig:Alert:Alert type="warning">` | Texto con `{count}` | `electricityActivitiesWithoutLocationBased > 0` |
| PDF organizativo | Tres filas en la tabla de totales + fila `Actividades de electricidad sin factor de mix eléctrico` si hay sin mix; cifra clave `Alcance 2 · localización` (t CO₂eq) | siempre / contador > 0 |
| PDF evento | Mismas tres filas con prefijo «Electricidad», cifra clave `Electricidad · localización` y página `Electricidad: método de mercado y de localización` | `electricityActivities > 0` |

La página de evento (`eventElectricityTable`) lista una fila por actividad con tipo `Electricity` (columnas Actividad, Fase, kWh, Factor, Emisiones mercado, Emisiones localización; `---` si no hay localización) y una fila `Total`. Si no hay filas no se pinta página. En el tema clásico usa `renderDataPage` con cabecera agrupada y `electricity.jpg`.

## Autorización y ámbito

Sin cambios: solo lectura de pantallas y informes ya existentes, con la autorización de sus controladores actuales. Sin feature flags nuevos.

## Persistencia y migración

Sin cambio de esquema ni migración. Las actividades de electricidad guardadas antes de septiembre de 2026 y no reeditadas no tienen resultado de localización: entran en el contador del aviso y no hay backfill (decisión heredada de `electricity-mix-location-based`). Los informes ya emitidos no se regeneran; las cifras nuevas salen al generar o reemitir.

## Configuración y operación

Sin variables de entorno, colas ni comandos nuevos. Traducciones: `make translation` extrae las claves; las claves antiguas `Electricidad por localización` y `Electricidad por localización (kg CO₂eq)` han quedado huérfanas en los `.xlf`.

Prompts de Gemini: se añaden las líneas `Market-based electricity emissions`, `Location-based electricity emissions`, `Location-based total emissions` y `Electricity activities without location-based factor` (evento) y las dos últimas (organizativo); la estructura del prompt no cambia.

## Cómo extenderlo

- **Nueva magnitud derivada de electricidad:** añadir el campo obligatorio a `EmissionsBreakdown`, acumularlo en `EmissionsBreakdownBuilder`, y dejar que PHPStan señale todos los puntos de construcción (respuestas, DTO de informe, mothers de tests).
- **Nueva superficie (por ejemplo, otra pantalla):** leer del breakdown, no recalcular. Reutilizar `organizationalDualReportingRows` / `eventDualReportingRows` para PDF.
- **Trampa:** `locationBasedTotal` no es `total − electricidad + localización`; si hay electricidades sin mix, difiere. No simplificarlo a una resta.
- Los gráficos y barras por alcance/fase siguen en método de mercado; no mezclarlos con cifras de localización.

## Tests

Unitarios: `tests/Unit/CarbonFootprint/Application/CarbonFootprint/EmissionsBreakdownBuilderTest.php` (suma de mercado, total por localización, sin electricidad, líneas derivadas, electricidad de maquinaria de evento, igualdad con alcance 2); servicios de resultados de evento, propiedad y empresa; los tres `*ReportDataCollectorTest`; `TcpdfCarbonFootprintReportPdfGeneratorTest` (filas de doble reporte y tabla de electricidad de evento por reflexión); tests de los dos prompts; `ResultsViewTest`; mothers `EventCarbonFootprintReportDataMother` y `OrganizationalCarbonFootprintReportDataMother`.

Aceptación (Behat): `tests/Acceptance/CarbonFootprint/event-carbon-footprint-results-dual-reporting.feature` (nuevo, tres escenarios) y dos escenarios nuevos en `property-carbon-footprint-scope-steps.feature`; pasos nuevos en `CarbonFootprintContext.php`.

Ejecución (objetivos del `Makefile`):

```
make test-unit options="tests/Unit/CarbonFootprint/Application/CarbonFootprint/EmissionsBreakdownBuilderTest.php"
make test-acceptance options="tests/Acceptance/CarbonFootprint/event-carbon-footprint-results-dual-reporting.feature"
make test-parallel
```

Los escenarios Behat de resultados cubren propiedad y evento; la plantilla de empresa se comprueba solo a través de su servicio de resultados.

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`) y `review.md`. Los cuatro hallazgos de la review (M1, B1, B2, B3) están corregidos y no queda ninguno abierto. Pendiente: limpieza manual de las claves huérfanas `Electricidad por localización*` en `translations/messages+intl-icu.{es,en,tr}.xlf`; además, en `translations/messages+intl-icu.*.xlf` existe una clave de aviso (`Hay {count} actividades…`) que ya no usa ninguna plantilla (las plantillas usan `Actividades de electricidad sin factor de mix eléctrico para su país y año: {count}. …`).
