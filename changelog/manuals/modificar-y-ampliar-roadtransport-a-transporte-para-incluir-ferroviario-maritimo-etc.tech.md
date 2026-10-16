# Medios de transporte con modo y tonelada-kilómetro — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-16 · **Bounded Contexts:** `CarbonFootprint` (principal), `Bioscore` (consumidor), `Shared` (menú y `List/FilterBar`)
- **Revisión:** `review.md` (revisión 1) · **Triaje y correcciones:** `review-fix.md`

## Qué hace y por qué

El maestro antes llamado `RoadTransport` («Transporte por carretera») solo describía vehículos de carretera y una actividad «cantidad × factor». Para las categorías 4 y 9 del alcance 3 los factores de referencia se publican por **tonelada-kilómetro** y el reporte agrupa por **modo de transporte**. Esta funcionalidad:

1. Renombra el agregado y todo su vocabulario a `Transport` («Medios de transporte»): clases, rutas, tablas, discriminador, nombres de evento, código de importación/exportación y dominios de traducción (decisión «Renombrado completo» del plan).
2. Añade la unidad `Unit::TonneKilometre` (`tkm`) con magnitud propia `Magnitude::Freight`.
3. Amplía `Vehicle` con tren de mercancías, buque portacontenedores, barcaza y carga aérea, y deriva de cada vehículo un `TransportMode` (carretera, ferrocarril, marítimo y fluvial, aéreo), visible y filtrable en el listado de administración y exportable.
4. Permite que una fila de actividad de transporte cuyo elemento esté en tkm capture **peso** (kg o t) y **distancia** (km); la cantidad en tkm se calcula en dominio y la actividad guarda peso y distancia para trazabilidad.

No se añade ninguna sección nueva a los asistentes: el maestro se sigue usando en `transports` (montaje, celebración y desmontaje de eventos, `Source::FreightTransport`) y en `vehicles` (alcance 1 de empresa y propiedad, `Source::OwnVehicles`). Las líneas del informe y el PDF no cambian.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/Unit.php:18,64` | Caso `TonneKilometre = 'tkm'`, magnitud `Freight` | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/Magnitude.php:17,30` | Caso `Freight`, unidad base `tkm` | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/Transport/Transport.php:65` | Agregado renombrado; `mode()` delega en el vehículo | Renombrado |
| Dominio | `src/CarbonFootprint/Domain/Model/Transport/Vehicle.php` | Diez vehículos; `mode()` (match exhaustivo) y `ofMode()` | Renombrado + ampliado |
| Dominio | `src/CarbonFootprint/Domain/Model/Transport/TransportMode.php` | `road`, `rail`, `sea`, `air` | Nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/Transport/TransportFilter.php:17` | Criterio `?TransportMode $mode` | Renombrado + campo |
| Dominio | `src/CarbonFootprint/Domain/Model/Transport/Transport{Registered,Updated,Translated,Removed,EmissionFactorAdded,EmissionFactorUpdated,EmissionFactorRemoved}.php` | Eventos del maestro, `rebuildFromPayload()` tolerante a `roadTransportId` | Renombrados |
| Dominio | `src/CarbonFootprint/Domain/Model/Activity/TransportActivity.php` | Actividad con `weightValue`, `weightUnit`, `distanceKm` | Renombrado + campos |
| Dominio | `src/CarbonFootprint/Domain/Model/Activity/TransportActivity{Registered,Updated,Removed}.php` | Eventos; payload con peso y distancia | Renombrados + payload |
| Dominio | `src/CarbonFootprint/Domain/Model/Activity/Answer/TransportAnswer.php:26` | `fromWeightAndDistance()` calcula las tkm | Renombrado + factoría |
| Dominio | `src/CarbonFootprint/Domain/Model/Activity/ActivityType.php:22,42` | `Transport = 'transport'` | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/EventCarbonFootprint/Phase.php:35,45,57`, `OrganizationalCarbonFootprint/Scope.php:28` | Secciones `transports` / `vehicles` con `ActivityType::Transport` | Modificados |
| Aplicación | `src/CarbonFootprint/Application/Transport/**` | Authorizer, List/Register/Update/Remove, EmissionFactor, Import, Export, `TransportImportationType` | Renombrados |
| Aplicación | `src/CarbonFootprint/Application/Activity/{Register,Update}/*TransportActivity*.php` | Requests con `weight`/`distanceKm`; servicios los propagan | Renombrados + campos |
| Aplicación | `src/CarbonFootprint/Application/Activity/Answer/TransportAnswerHandler.php` | Traduce `TransportAnswer` a los requests | Renombrado + campos |
| Aplicación | `src/CarbonFootprint/Application/Activity/TransportEmissionFactorSelector.php`, `Catalog/TransportCatalogProvider.php` | Selección de factor y catálogo por `ActivityType::Transport` | Renombrados |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/ActivityItemNameResolver.php` | Nombre del elemento en el informe | Modificado (renombrado) |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/Transport.*.orm.xml` | Tablas `transports`, `transport_translation`, `transport_emission_factor` | Renombrados |
| Persistencia | `.../Mapping/Activity.TransportActivity.orm.xml:8-13` | Tabla `transport_activities` + `weight_value`, `weight_unit`, `distance_km` | Renombrado + columnas |
| Persistencia | `.../Mapping/Activity.Activity.orm.xml:24` | Discriminador `transport` | Modificado |
| Persistencia | `.../Doctrine/Type/Transport{,EmissionFactor}IdType.php`, `config/packages/doctrine.yaml:58-59` | Tipos `transport_id`, `transport_emission_factor_id` | Renombrados |
| Persistencia | `.../Repository/DoctrineTransportRepository.php:22,96-100`, `.../InMemory/Repository/InMemoryTransportRepository.php:70` | Filtro por modo | Renombrados + filtro |
| Persistencia | `migrations/Version20261016194147.php` (y `migration.sql` en esta carpeta) | Renombrado de esquema y datos, columnas nuevas | Nuevo |
| Persistencia | `migrations/Version20261014081026.php` | Usa el schema manager en vez de `$schema` (D1 de `review-fix.md`) | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}TransportType.php` | Etiqueta «Tipo de vehículo o medio» | Renombrados |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/ListTransportsType.php`, `Type/TransportModeType.php` | Filtro «Modo» | Renombrado / Nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/Admin/Transport/**` | Controladores admin (`/admin/transports…`) | Renombrados |
| Infraestructura | `src/CarbonFootprint/Infrastructure/DataExchange/TransportsExportationType.php:51,146` | Exportación `transports` con columna `mode` | Renombrado + columna |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/Answer/TransportAnswerHttpMapper.php` | Parsea peso/distancia o cantidad directa | Renombrado + lógica |
| Infraestructura | `.../Step/View/TransportRowViewFactory.php`, `.../Step/View/ActivityRowView.php:24-26` | Props `weight`, `weightUnit`, `distanceKm` en la fila | Renombrado / Modificado |
| Infraestructura | `.../Step/View/{AssemblyBreakdownStepView,CelebrationStepView}.php`, `.../OrganizationalCarbonFootprint/View/ScopeStepView.php` | Variables `transports` / `transportRows` | Modificados |
| UI | `templates/carbon_footprint/admin/transports/*.html.twig` | Listado (columna «Modo»), alta, edición, factores | Renombrados |
| UI | `templates/components/CarbonFootprint/Transport{ActivityTable,ActivityRow}.html.twig` | Tabla y fila con panel `default` / `freight` | Renombrados + panel |
| UI | `assets/controllers/activity_emissions_controller.js` | `applyMagnitudePanel()`, cálculo de tkm en vivo | Modificado |
| UI | `templates/components/List/FilterBar.html.twig:58` | Rama `mode` → dominio `transportModeType` | Modificado |
| UI | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:371-373` | Menú «Transporte → Medios de transporte» | Modificado |
| Bioscore | `src/Bioscore/Application/BioscoreCatalogItem/{CatalogTarget,CatalogTargetFinder}.php`, `BioscoreConsumption/BioscoreConsumptionAnswersBuilder.php:131`, `templates/bioscore/admin/catalogs/list.html.twig` | Consumen `Transport`/`Vehicle`/`TransportAnswer` | Modificados (renombrado) |
| i18n | `translations/transportVehicleType+intl-icu.*.xlf`, `translations/transportModeType+intl-icu.*.xlf`, `translations/unitType+intl-icu.*.xlf` (`tkm`) y sus plantillas de extracción en `templates/carbon_footprint/translations/` | Vehículos, modos y unidad | Renombrados / Nuevos |

## Modelo de dominio

### Unidad y magnitud

- `Unit::TonneKilometre = 'tkm'` → `Magnitude::Freight` (`Unit.php:64`); `Magnitude::Freight->baseUnit()` es `tkm` (`Magnitude.php:30`). Como la magnitud solo tiene una unidad, `Quantity::convertTo()` / `UnitConverter` lanzan `IncompatibleUnitsException` al convertir tkm a o desde kg o km, y `EmissionsCalculator` multiplica sin conversión cuando factor y cantidad están en tkm.
- La unidad por defecto del maestro sigue siendo `Unit::Kilogram`; kg, km y cualquier otra unidad siguen siendo válidas para un medio de transporte.

### `Vehicle` y `TransportMode`

| Vehículo (`Vehicle`) | Valor | Modo (`TransportMode`) |
|---|---|---|
| `PassengerCars`, `Vans`, `Trucks`, `Mopeds`, `Motorcycles`, `Buses` | `passenger_cars` … `buses` | `Road` (`road`) |
| `FreightTrain` | `freight_train` | `Rail` (`rail`) |
| `ContainerShip`, `Barge` | `container_ship`, `barge` | `Sea` (`sea`) |
| `AirFreight` | `air_freight` | `Air` (`air`) |

- `Vehicle::mode()` es un `match` exhaustivo (`Vehicle.php:20-34`): añadir un caso sin modo rompe PHPStan.
- `Vehicle::ofMode()` (`Vehicle.php:37-40`) devuelve los vehículos de un modo; lo usa el repositorio Doctrine para filtrar.
- El modo **no se persiste**: `Transport::mode()` (`Transport.php:65-68`) lo deriva del vehículo.

### `TransportActivity`

- Campos nuevos `weightValue`, `weightUnit` (columnas planas, como la capacidad de contenedor de `WasteActivity`) y `distanceKm` (`TransportActivity.php:18-20`). `weight()` devuelve `null` si falta valor o unidad (`:93-100`).
- `register()` y `update()` aceptan `?Quantity $weight` y `?float $distanceKm` opcionales (`:28-74`); los `apply*` los asignan desde el evento (`:107-132`).
- Eventos `TransportActivityRegistered` / `TransportActivityUpdated`: el payload añade `weightValue`, `weightUnit`, `distanceKm` (`TransportActivityRegistered.php:109-111`, `TransportActivityUpdated.php:101-103`); `rebuildFromPayload()` reconstruye el peso solo si hay unidad válida y valor, y devuelve nulos para payloads antiguos sin esas claves (`TransportActivityRegistered.php:126-154`).
- Los nueve `rebuildFromPayload()` que leen el identificador del maestro aceptan la clave antigua: `new TransportId($payload['transportId'] ?? $payload['roadTransportId'])` (p. ej. `TransportRegistered.php:71`, `TransportActivityRegistered.php:134`). Es la corrección C1 de `review-fix.md` (opción 2): los `recorded_events` anteriores al renombrado conservan `roadTransportId` en el payload serializado y la migración no lo reescribe.

### Cálculo en tkm

`TransportAnswer::fromWeightAndDistance()` (`TransportAnswer.php:26-41`):

```php
quantity: new Quantity($weight->convertTo(Unit::Tonne)->value() * $distanceKm, Unit::TonneKilometre)
```

- `$weight` debe ser de magnitud masa; otra magnitud → `IncompatibleUnitsException` (desde `convertTo()`).
- 2 500 kg × 350 km = 875 tkm; 2,5 t × 350 km = 875 tkm; 800 kg × 0 km = 0 tkm.
- La respuesta conserva `weight` y `distanceKm` para que lleguen a la actividad.

## Flujo de una operación

### Guardar una fila de transporte en un asistente

1. El formulario del paso envía `{prefix}[{i}][itemId|activityId|source]` y, según el panel visible, `quantity`+`unit` **o** `weight`+`weightUnit`+`distanceKm`. El panel oculto tiene sus campos deshabilitados (`activity_emissions_controller.js`, `applyMagnitudePanel()`), así que no se envía.
2. `TransportAnswerHttpMapper::map()` (`TransportAnswerHttpMapper.php`):
   - sin `itemId` → fila descartada;
   - con `weight` ≥ 0, `weightUnit` de magnitud masa y `distanceKm` ≥ 0 (comas admitidas como decimal, `parseNonNegativeFloat()`) → `TransportAnswer::fromWeightAndDistance()`;
   - si no, `parseQuantity()` (cantidad directa); si tampoco hay cantidad → descartada.
3. `TransportAnswerHandler` (`Application/Activity/Answer/TransportAnswerHandler.php:32-65`) construye `RegisterTransportActivityRequest` o `UpdateTransportActivityRequest` con `weight` y `distanceKm`.
4. `RegisterTransportActivityService` (`Register/RegisterTransportActivityService.php:32-56`): autoriza `Register` con `ActivityAuthorizer` → `TransportEmissionFactorSelector::select()` (lanza `InvalidArgumentException` si el maestro no existe o no tiene factor para el año y grupo) → `EmissionsCalculator::calculate()` (convierte la cantidad a la unidad del factor; tkm contra un factor en kg → `IncompatibleUnitsException`) → `TransportActivity::register()` → `save()`. `UpdateTransportActivityService` es equivalente con `Edit`.
5. Al reabrir el paso, `TransportRowViewFactory` rellena `weight`, `weightUnit`, `distanceKm` en `ActivityRowView`; `TransportActivityRow.html.twig` abre el panel `freight` si `row.weight is not null` y, en solo lectura, añade «(peso unidad × distancia km)» tras la cantidad.

### Cálculo en vivo

`activity_emissions_controller.js`:

- `connect()` e `itemChanged()` llaman a `applyMagnitudePanel(row)`: si la opción seleccionada tiene `data-magnitude="freight"` se muestra el panel `freight` y se oculta/deshabilita el `default`, y viceversa.
- `resolveQuantity()`: con el panel `freight` visible calcula `toneladas × km` (kg / 1000), pinta el total en `[data-field="quantityTotal"]` y lo devuelve; `calculate()` envía `unit = 'tkm'`. Los `CalculateEmissionsController` (evento, empresa, propiedad, proveedor) no cambian.

### Listado de administración filtrado por modo

`ListController` (`/admin/transports`) → `ListTransportsType` (campo `mode` con `TransportModeType`, placeholder «Todos») → `ListTransportsRequest extends TransportFilter` → `ListTransportsService` → `DoctrineTransportRepository::findByFilter()` añade `r.vehicle IN (:vehicles)` con `Vehicle::ofMode($filter->mode)` (`DoctrineTransportRepository.php:96-100`). La barra de filtros traduce el valor con `transportModeType` (`FilterBar.html.twig:58`).

## Autorización y ámbito

- **Maestro:** `TransportAuthorizer` (`Application/Transport/TransportAuthorizer.php`), sin cambios funcionales: listar, registrar, editar, ver, eliminar, importar y exportar solo `Admin` y `SuperAdmin`.
- **Actividad:** `ActivityAuthorizer`. Registrar/listar: `SuperAdmin`, `Admin`, `Owner`, `Provider`; editar/eliminar una actividad concreta: `Owner` o `Provider` de la cuenta actual (`currentAccountId()`, respeta la impersonación).
- Sin feature flags.

## Persistencia y migración

- Tablas renombradas: `road_transports` → `transports`, `road_transport_translation` → `transport_translation`, `road_transport_emission_factor` → `transport_emission_factor` (único `uq_transport_emission_factor_year`), `road_transport_activities` → `transport_activities`; columna `road_transport_id` → `transport_id` y comentarios `DC2Type` actualizados; índices y FK recreados con los nombres de Doctrine (`IDX_1670911E9909C13F`, `FK_1670911E9909C13F`, `IDX_2207A6689909C13F`, `FK_2207A6689909C13F`, `FK_D2BA26AFBF396750`).
- Columnas nuevas en `transport_activities`: `weight_value` (double, nulo), `weight_unit` (varchar, nulo, enum `Unit`), `distance_km` (double, nulo).
- Datos: `activities.type` `road_transport` → `transport`; `recorded_events.event_name` `roadTransport*` → `transport*`; `importation.type`, `importation_profile.type` y `exportation.type` `road_transports` → `transports`. El `payload` de `recorded_events` **no** se reescribe (lo tolera el dominio, ver *Modelo de dominio*).
- Fichero: `migrations/Version20261016194147.php` (creado desde `migration.sql`, solo `addSql()`, con `down()`). Debe desplegarse junto con el código: el discriminador y los nombres de evento nuevos no existen en el código anterior.
- `migrations/Version20261014081026.php` (de otro desarrollo) se cambió para introspeccionar solo sus tablas con el schema manager: con el esquema antiguo y los tipos ya renombrados en `doctrine.yaml`, una introspección completa fallaba con «Unknown column type "road_transport_id"» (D1 de `review-fix.md`).

## Configuración y operación

- Tras desplegar: `make migrate` y `make cc` (regenera `var/events.json` con los nombres de evento nuevos; ver `src/Shared/Infrastructure/Symfony/Kernel.php`).
- Comprobaciones recomendadas antes y después de migrar (de `plan.md`, *Migración*): recuentos de `activities` con tipo de transporte, de `recorded_events` con nombre `roadTransport%`/`transport%` y de `importation` por tipo.
- Rutas admin: `admin_transports`, `admin_transports_register`, `admin_transports_edit`, `admin_transports_remove`, `admin_transports_emission_factors`, `admin_transports_emission_factors_create_year`. Importación y exportación por el código `transports` (`/{locale}/transports/import…`).
- Repositorio Doctrine publicado como `repository.transport` (`DoctrineTransportRepository.php:22`). Los registros por `ActivityType` (selector, catálogo, manejador, mapper, factory de fila) se autoconfiguran por interfaz; `services.yaml` no referencia el maestro.
- Sin colas, comandos de consola ni correos nuevos.

## Cómo extenderlo

- **Nuevo vehículo o medio:** 1) caso en `Vehicle` y rama en `mode()` (PHPStan obliga); 2) clave en `templates/carbon_footprint/translations/transportVehicleType.html.twig` y en `translations/transportVehicleType+intl-icu.{es,en,tr}.xlf`; 3) ampliar `VehicleTest`. Las importaciones lo aceptan por su valor (`TransportVehicleCodeType`); un valor desconocido cae en `PassengerCars` (`ImportTransportService.php:89`).
- **Nuevo modo:** caso en `TransportMode`, reasignar vehículos en `Vehicle::mode()`, traducción en `transportModeType`. El filtro y la columna funcionan sin más cambios.
- **Otra unidad derivada de dos entradas:** seguir el patrón de `TransportAnswer::fromWeightAndDistance()` (cálculo en dominio, mapper solo parsea) y el panel por magnitud de `applyMagnitudePanel()`, que se activa por `data-magnitude` de la opción seleccionada.
- **Agrupar por modo en informes (alcance 3, categorías 4 y 9):** fuera de alcance; partir de `Transport::mode()` desde `ActivityItemNameResolver` o las líneas de informe.
- **Trampas:**
  - Un elemento del maestro tiene una única unidad: el mismo vehículo en kg y en tkm son dos elementos.
  - Si se cambia a tkm la unidad de un maestro con actividades en kg/km, las actividades guardadas mantienen sus emisiones, pero al reabrir el paso la fila muestra el panel de peso y distancia vacío y hay que reintroducirlos.
  - Bioscore crea respuestas con cantidad directa (sin peso ni distancia): si la unidad de Bioscore se reconoce y su magnitud no es `Freight`, un consumo emparejado con un medio en tkm se omite como `IncompatibleUnit` (`BioscoreConsumptionAnswersBuilder.php:73-74`); si la unidad no se reconoce, se asume la del maestro (`:79`).
  - Cualquier nueva clave de payload de los eventos de transporte debe tolerar los payloads históricos (`?? null`), igual que `weightValue`/`distanceKm`.

## Tests

- **Unitarios nuevos:** `tests/Unit/CarbonFootprint/Domain/Model/Transport/VehicleTest.php` (modo de los diez vehículos, `ofMode()`), `tests/Unit/CarbonFootprint/Domain/Model/Activity/{TransportAnswerTest,TransportActivityTest,TransportActivityRegisteredTest,TransportActivityUpdatedTest}.php` (cálculo de tkm, magnitud incorrecta, accesores, *roundtrip* de payload, payload sin claves nuevas y con `roadTransportId`).
- **Unitarios ampliados:** `UnitTest`, `QuantityTest` (tkm/Freight, conversiones prohibidas), `TransportTest` (`mode()`, reconstrucción de los siete eventos con la clave antigua), `TransportRepositoryTest` (filtro por modo; corre en InMemory y Doctrine), `ListTransportsServiceTest`, `Register/UpdateTransportActivityServiceTest` (875 tkm, emisiones `factor × 875`, kg contra factor tkm → excepción), `StageAnswerHttpMapperTest` (peso+distancia, cantidad directa, *fallback*), `ScopeStepViewTest`, `AssemblyBreakdownStepViewTest`.
- **Behat:** `tests/Acceptance/CarbonFootprint/transport-{list,register,edit,remove,import,export,emission-factors}.feature` (filtro por modo con «Ferrocarril», alta con `tkm` y `freight_train`, columna `mode`) y el escenario «A transport in tonne-kilometre is calculated from weight and distance» de `company-carbon-footprint-scope-steps.feature` (2 500 kg × 350 km → 875 tkm). Pasos en `tests/Acceptance/CarbonFootprint/CarbonFootprintContext.php` (`transport :name with id :id …`).
- **Ejecutar:** `make test-unit options="--filter Transport"`, `make test-acceptance options="tests/Acceptance/CarbonFootprint/transport-list.feature"` o la batería completa con `make test-parallel`.
- **Huecos:** la migración no tiene test automático (los tests corren en SQLite sin datos históricos); el JS de los paneles solo se cubre indirectamente.

## Desviaciones y deuda

- Desviaciones declaradas en `plan.md` (*Implementación*): el JS deshabilita los campos del panel oculto; claves obsoletas de traducción retiradas a mano; textos adicionales renombrados («Medio de transporte actualizado con éxito», «Medio de transporte no encontrado»); `migration.sql` con nombres de índice reales.
- Correcciones posteriores (`review-fix.md`): C1 resuelto con tolerancia en dominio a `roadTransportId` (contradice la decisión del plan de un único vocabulario; la sección *Migración* del plan no trata el payload); B1–B4 aplicados; D1 (migración biohydra con schema manager) aplicado. A1 y B5 propuestos como falsos positivos, pendientes de validación del usuario.
- Deuda abierta:
  - La exportación hereda `mode` en `TransportsExportFilter`, pero ni `AdminTransportsExportType` ni `dehydrateFilter()`/`hydrateFilter()` de `TransportsExportationType` lo exponen: no se puede exportar filtrando por modo.
  - Los valores de `mode` y `vehicle` se exportan como códigos (`rail`, `freight_train`), no traducidos.
  - `UpdateTransportActivityService` carga la actividad antes de autorizar (patrón común de los `Update*ActivityService`; B5).
- Pendiente de despliegue: ejecutar `migrations/Version20261016194147.php` (`make migrate`) y `make cc`.
