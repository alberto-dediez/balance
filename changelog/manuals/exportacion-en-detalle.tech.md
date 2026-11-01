# Exportación en detalle de una huella de carbono — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-30 (con correcciones de la review, ver `review.md`) · **Bounded Contexts:** `CarbonFootprint`, `DataExchange` (sin cambios en este último)

## Qué hace y por qué

Las huellas de evento, propiedad y empresa solo se podían sacar como informe PDF. Esta funcionalidad añade el **detalle crudo** (una fila por actividad contabilizada, con cantidad, unidad, factor y emisiones) como exportación XLSX/CSV/PDF del framework genérico de `DataExchange`. No se crea ningún flujo de descarga propio: se registran tres `ExportationType` que el framework descubre por `#[AutoconfigureTag('exchange.exportation.type')]` (sin tocar `services.yaml`), se ejecutan en diferido (`Priority::LOW`) y se entregan por correo (`src/DataExchange/Application/Exportation/Register/RegisterExportationService.php`, `.../Execute/ExecuteExportationService.php`).

Decisiones clave (tabla de `plan.md`): un tipo de exportación por sujeto (columnas, filtro y autorización difieren) pero un único constructor de filas y un único formateador de celdas compartidos; solo se exportan huellas completadas; el fichero incluye además de las actividades las filas derivadas WTT, las propiedades no consolidadas (solo empresa) y las exclusiones, de modo que la suma de emisiones cuadre con el total de la huella; las aportaciones de proveedor ignoradas no aparecen.

El punto de entrada es el ítem «Exportar» del desplegable «Acciones» (`templates/shared/layout/action.html.twig:8`) del asistente de cada huella, que lleva a la pantalla genérica `/{code}/export` con la huella preseleccionada por query string.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRowType.php` | Enum de tipo de fila: `activity`, `derived_fuel_and_energy`, `non_consolidated_property`, `exclusion` | Nuevo |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRow.php` | DTO de una fila (valores ya resueltos) | Nuevo |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRowsBuilder.php` | Construye y ordena las filas de cualquier `CarbonFootprint` | Nuevo |
| Aplicación | `src/CarbonFootprint/Application/{Property,Company,Event}CarbonFootprint/Export/*DetailExportFilter.php` | DTO de filtro con el id de la huella (nulable) | Nuevo |
| Aplicación | `src/CarbonFootprint/Application/{Property,Company,Event}CarbonFootprint/*Authorizer.php` | `canExport()` delega en `canView()` | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/DataExchange/CarbonFootprintDetailCellsFormatter.php` | Definición de columnas y mapeo fila → celdas traducidas (compartido) | Nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/DataExchange/{Property,Company,Event}CarbonFootprintDetailExportationType.php` | `FilterAuthorizedExportationType` por sujeto | Nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/{Property,Company,Event}CarbonFootprintDetailExportType.php` | Form type del filtro (un campo `Huella de carbono`, obligatorio) | Nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/{Property,Company,Event}CarbonFootprintChoiceType.php` | Selector (padre `AutocompleteType`) con las huellas completadas de la cuenta actual | Nuevo |
| Interfaz | `templates/carbon_footprint/{property,company,event}_carbon_footprints/wizard-layout.html.twig` | Ítem `TopAction:Export` en `top_actions` | Modificado |
| i18n | `templates/carbon_footprint/translations/carbonFootprintDetailRowType.html.twig`, `translations/carbonFootprintDetailRowType+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.*.xlf` | Dominio nuevo de tipos de fila y claves de cabecera | Nuevo / modificado |
| Tests | ver sección *Tests* | | Nuevo |

## Modelo de dominio

Sin cambios en `Domain/`. Todo se lee de lo que ya exponen `CarbonFootprint` (`activityReferences()`, `allExclusions()`, `isStageExcluded()`, `stageExclusionReason()/Comment()`, `isCompleted()`), `ActivityPlacement` (etapa, categoría, proveedor, empleados, propiedad), las subclases de `Activity`, `DerivedSourceLine` y `NonConsolidatedPropertyLine`.

Tipos de fila y su origen (`CarbonFootprintDetailRowsBuilder.php`):

| Tipo | Origen | Líneas |
|---|---|---|
| `activity` | `CountedActivityReferences::of()` + `ActivityRepository::findById()` | 70-78 |
| `derived_fuel_and_energy` | `DerivedSourceEmissionsCalculator::lines($fp, Scope::Scope3)`; no se genera en eventos | 80-100 |
| `non_consolidated_property` | `DerivedSourceEmissionsCalculator::nonConsolidatedPropertyLines()`; solo `CompanyCarbonFootprint` | 102-119 |
| `exclusion` | exclusión completa del alcance 3 (sin categoría) y `allExclusions()` | 121-138 |

**Invariante:** para propiedad y empresa, `Σ emisiones(activity) + Σ emisiones(derived) + Σ emisiones(non_consolidated)` es el total de `EmissionsBreakdownBuilder::build($fp, Scope::cases())` porque ambos usan las mismas tres colecciones. La protege `testTheEmissionsOfTheRowsAddUpToTheTotalOfTheFootprint` (`tests/Unit/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRowsBuilderTest.php:314`). Las actividades referenciadas que ya no existen se omiten (`:73-75`).

## Flujo de una operación

1. **Entrada.** El ítem se pinta en el bloque `top_actions` de los tres `wizard-layout.html.twig` solo si `<huella>.status.isCompleted` e `is_authorized('export')`, antes de `TopAction:Return`. Enlaza a `path('export', {code: '<tipo>_carbon_footprint_detail', <sujeto>CarbonFootprintId: …})`.
2. **Pantalla.** `src/DataExchange/Infrastructure/UI/Web/Export/ExportController.php` comprueba `isAuthorized(User)` del tipo (rol Owner, sin instanciar agregados: `*ExportationType::isAuthorized()`, p. ej. `CompanyCarbonFootprintDetailExportationType.php:107-112`), llama a `createFilter()` (que lee la query string, `:59-72`) y pinta `templates/data_exchange/export/export.html.twig` con el filtro, las columnas y el formato.
3. **Registro.** `RegisterExportationService` llama a `isAuthorizedForFilter()` (`:114-123`): carga la huella, exige `isCompleted()` y `Authorizer::canDoThat($fp, Action::Export, $user)`. Si pasa, persiste la `Exportation` con `dehydrateFilter()` (un único escalar) y despacha el comando diferido.
4. **Worker.** `ExecuteExportationService` reevalúa `isAuthorizedForFilter()`, hidrata el filtro (`hydrateFilter()`), escribe la cabecera con las etiquetas traducidas y una fila por elemento de `rows()`. `rows()` (`:94-105`) carga la huella (si no existe, no emite nada), invoca `CarbonFootprintDetailRowsBuilder::build()` y por cada fila `CarbonFootprintDetailCellsFormatter::cells()`.
5. **Entrega.** Correo con el enlace de descarga (flujo genérico; el fichero se llama `<code>-<id>.<ext>`, `DownloadExportationService.php:50`).

Orden de filas (`CarbonFootprintDetailRowsBuilder::sortKey()`, `:253-264`): exclusiones al final → etapa (`Scope::stepNumber()` o índice de `Phase`) → tipo de fila → categoría (`Source::cases()`; sin categoría = -1) → propiedad → elemento. Las derivadas y no consolidadas quedan por tanto al final de su alcance y las exclusiones al final del fichero.

Datos específicos por actividad (`activityRow()`, `:145-186`): peso convertido a toneladas y capacidad de contenedor a litros con `Quantity::convertTo()`; `dataSource` = `description()` en `ReportedEmissionsActivity` o `source()` en Combustible, MachineryFuel, Electricity, OvernightStay, Transport, Waste y RefrigerantGas (`:188-201`); WTT solo en Combustible, MachineryFuel y Electricity (`:213-221`); resultado por localización solo en Electricity (`:181`). El nombre del elemento lo resuelve `ActivityItemNameResolver::resolve()` (para electricidad solo resuelve la comercializadora: un GdO sin comercializadora cae al nombre del tipo). Proveedor y propiedad se resuelven con cachés locales que `build()` vacía al empezar (`:62-63`), para no arrastrar nombres entre mensajes del worker.

Las filas derivadas llevan además propiedad, proveedor, marca de empleados, id de la actividad origen y CO₂/CH₄/N₂O de la línea; las no consolidadas, el nombre de la propiedad como elemento y el valor manual.

## Autorización y ámbito

- `canExport()` → `canView()` en los tres `*CarbonFootprintAuthorizer` (p. ej. `EventCarbonFootprintAuthorizer.php`, método `canExport`): rol Owner y misma cuenta actual (`currentAccountId()`, impersonación incluida). Sin supervisión de consultora adicional.
- `isAuthorized(User)` del tipo: si `CurrentRolesProvider::currentRoles()` devuelve `null` (sin impersonación) se mira `User::hasRole(Role::Owner)`; en impersonación, si `Owner` está en los roles actuales. Un Administrador sin impersonar queda fuera; impersonando una cuenta, dentro.
- `isAuthorizedForFilter()` devuelve `false` para filtro de otra clase, sin id, huella inexistente, no completada o no autorizada. Se aplica en registro y en el worker.
- El selector solo ofrece huellas completadas de la cuenta actual; sin cuenta (`LoggedAccount` nulo) no ofrece ninguna (p. ej. `EventCarbonFootprintChoiceType.php:34-47`). Etiqueta `«<nombre> — <año>»`, con sufijo `(n)` si se repite y el id si el sujeto no se resuelve. Eventos filtran `isCompleted()` en PHP porque `EventCarbonFootprintFilter` no admite estado.
- El formato PDF del formulario genérico sigue disponible (`ExportationFormat`); con tantas columnas es poco legible, comportamiento genérico no restringido.

## Persistencia y migración

Sin cambios de esquema ni de repositorios. La tabla `exportation` existente guarda el filtro serializado (`{"<sujeto>CarbonFootprintId": "…"}`). No hay migración.

## Configuración y operación

- Sin variables de entorno ni `services.yaml`: autowiring + `AutoconfigureTag`.
- Cola `LOW` (`async_low`): `make queues-low`. La purga de ficheros caducados es la genérica (`PurgeExpiredExportationsCommand`).
- Códigos de exportación: `property_carbon_footprint_detail`, `company_carbon_footprint_detail`, `event_carbon_footprint_detail`. `returnRoute()`: `property_carbon_footprints`, `company_carbon_footprints`, `events`.
- Traducciones: cabeceras y valores se traducen con el `TranslatorInterface` del worker. En tests el worker no corre (transporte `in-memory://`), así que el idioma real de cabeceras y valores en el worker no está verificado por tests (ver *Desviaciones y deuda*). Las claves de TR están pendientes (`NEW_`).
- Regenerar traducciones tras cambios: `make translation`.

## Cómo extenderlo

- **Nueva columna común:** añadirla en `CarbonFootprintDetailCellsFormatter::commonColumns()` y `cells()` (misma clave, mismo orden), y el campo en `CarbonFootprintDetailRow` y en `CarbonFootprintDetailRowsBuilder`. Los tests `CarbonFootprintDetailCellsFormatterTest` y los de cada tipo comprueban que claves de fila = `columnDefinitions()`.
- **Columna que no aplica a un sujeto:** añadir su clave a `EXCLUDED_COLUMNS` del tipo (hoy propiedad `['property','manualCo2eq']`, evento `['property','acquisitionYear','manualCo2eq']`) y pasarla a `commonColumns()` y `cells()`.
- **Nuevo tipo de actividad con atributos propios:** añadirlo a `activityRow()` y a los `match` de `dataSourceOf()`, `conditionOf()` o `wellToTankEmissionsOf()` según corresponda.
- **Nuevo sujeto de huella:** copiar un `ExportationType`, un filtro, un form type y un choice type; `canExport()` en su Authorizer; ítem en su `wizard-layout`.
- Trampa: `cells()` devuelve todas las claves (menos las excluidas) aunque el usuario desmarque columnas; el filtrado por columnas seleccionadas lo hace el framework al escribir.

## Tests

- Unitarios: `tests/Unit/CarbonFootprint/Application/CarbonFootprint/Export/CarbonFootprintDetailRowsBuilderTest.php` (13 tests: filas por tipo, proveedor/empleados, aportación ignorada, derivadas, no consolidadas, exclusiones, evento, actividad inexistente, orden, invariante de totales, nombres vigentes en cada `build()`, atributos específicos); `tests/Unit/CarbonFootprint/Infrastructure/DataExchange/CarbonFootprintDetailCellsFormatterTest.php`; `{Property,Company,Event}CarbonFootprintDetailExportationTypeTest.php` (filtro, hidratación, filas, `isAuthorized`, `isAuthorizedForFilter`); `tests/Unit/CarbonFootprint/Infrastructure/Symfony/Form/Type/{Property,Company,Event}CarbonFootprintChoiceTypeTest.php`; y los `*CarbonFootprintAuthorizerTest.php` ampliados con `Action::Export`.
- Aceptación (Behat): `tests/Acceptance/CarbonFootprint/{property,company,event}-carbon-footprint-detail-export.feature` (preselección y registro, huella no completada/ajena sin opción, 403 para `ROLE_USER`, ítem visible solo con huella completada).
- Ejecución con el `Makefile`: `make test-unit options="tests/Unit/CarbonFootprint/Infrastructure/DataExchange"`, `make test-acceptance options="tests/Acceptance/CarbonFootprint/property-carbon-footprint-detail-export.feature"`, `make test-parallel`. El `Makefile` no define `test-this`.

## Desviaciones y deuda

- Los escenarios Behat de huella no completada/ajena comprueban el rechazo del `ChoiceType`; el rechazo de `isAuthorizedForFilter()` queda en unitarios (`plan.md`, *Implementación*).
- El orden final sigue la tabla de decisiones (exclusiones al final, derivadas al final de su alcance), no el paso 5 del plan, que quedó contradicho (`review.md`, B1).
- Abierta **Q1** (`review.md`): comprobar con una exportación real que el worker traduce cabeceras y valores al idioma del usuario.
- Deuda no corregida: `PropertyTripCalculationsExportationType.php:196-204` y `EventAttendeeSurveysExportationType.php:192-200` siguen instanciando agregados de sonda en `isAuthorized()`; `OrganizationalCollectorContextTrait::itemNameResolver()` solo registra cuatro catálogos.
- Rendimiento: un `findById()` por actividad (como el PDF); `ActivityRepository::findByIds()` permitiría carga en lote.
- Sin hallazgos abiertos en `review.md` salvo Q1.
