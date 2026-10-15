# Bienes de capital (maestro con factores de emisión y su actividad) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `CarbonFootprint` (más el menú lateral de `Shared` y los tipos DBAL de `config/packages/doctrine.yaml`)

## Qué hace y por qué

La plataforma mantiene maestros de elementos con factores de emisión por año y grupo de coeficientes (materiales, merchandising, catering, bienes y servicios comprados). Faltaba uno para los **bienes de capital**, la categoría «Capital goods» del alcance 3 del GHG Protocol: reformas y obra nueva, mobiliario y equipamiento, vehículos y maquinaria. A diferencia de los bienes y servicios comprados, la unidad del factor varía por elemento (€, m², unidad, kg), y aparece una magnitud nueva: la superficie.

Se añaden tres cosas:

1. La unidad `Unit::SquareMeter` (`'m2'`, «Metro cuadrado») con su magnitud `Magnitude::Area`, de la que es la única unidad y la base. Aparece en todos los selectores de unidad de la plataforma.
2. El maestro global `CapitalGood`, calco uno a uno de `PurchaseService` (plan `2026-10-15-bienes-y-servicios-comprados`): alta, edición con factores por año y grupo, baja lógica, listado filtrable, edición en bloque de factores con copia de año, importación, exportación, acción en bloque de eliminar, entrada de menú y trazas en el Registro. Unidad elegible por elemento con **`EUR` por defecto**.
3. La actividad `CapitalGoodActivity` con todas sus piezas enchufables y un atributo de línea propio, el **año de adquisición** (`?int acquisitionYear`, entre 1900 y 2200, sin efecto en el cálculo). **Ninguna fase de la huella de eventos ni ningún alcance organizacional la usa todavía**: queda preparada para el futuro plan del alcance 3.

Decisiones clave en la tabla `## Decisiones tomadas` del plan: un solo factor por año y grupo; categorías como enum cerrado de cuatro valores; sin «sujeto al que se imputa»; autorización idéntica a `PurchaseServiceAuthorizer`. La cota del año de adquisición se valida en el dominio (corrección B3 de `review-fix.md`).

## Mapa de piezas

Todos los ficheros son **nuevos** salvo donde se indica «modificado».

### Dominio — unidades

| Fichero | Rol |
|---|---|
| `src/CarbonFootprint/Domain/Model/Unit.php` (modificado) | Caso `SquareMeter = 'm2'` (línea 19) y brazo `Magnitude::Area` en `magnitude()` (línea 64). `fixedFactorToBase()` cae en el `default => 1.0` |
| `src/CarbonFootprint/Domain/Model/Magnitude.php` (modificado) | Caso `Area = 'area'` (línea 15) con base `Unit::SquareMeter` (línea 27) |

### Dominio — `src/CarbonFootprint/Domain/Model/CapitalGood/`

| Fichero | Rol |
|---|---|
| `CapitalGood.php` | Agregado raíz (`AggregateRoot`, `Translatable`, `\Stringable`); categoría y unidad por defecto `Others`/`Euro` (líneas 30-31) |
| `CapitalGoodId.php` · `CapitalGoodEmissionFactorId.php` | Ids UUID (`AggregateRootIdTrait`) |
| `CapitalGoodCategory.php` | Enum `renovations`, `furniture_and_equipment`, `vehicles_and_machinery`, `others` |
| `CapitalGoodEmissionFactor.php` · `CapitalGoodEmissionFactors.php` | Subentidad de **un** factor y su colección (`forYearAndGroup`, `hasForYearAndGroup`, `removeForYearAndGroup`, `years()`, `groups()`) |
| `CapitalGoodTranslation.php` | Traducción del nombre por locale |
| `CapitalGoodFilter.php` · `CapitalGoodRepository.php` | Filtro (`name`, `category`, `limit = 10`) y puerto |
| `CapitalGood{Registered,Updated,Translated,Removed}.php` | Eventos del elemento |
| `CapitalGoodEmissionFactor{Added,Updated,Removed}.php` | Eventos del factor |

### Dominio — `src/CarbonFootprint/Domain/Model/Activity/`

| Fichero | Rol |
|---|---|
| `CapitalGoodActivity.php` | Actividad `final` con `capitalGoodId` y `acquisitionYear`; guarda de rango 1900–2200 (`:15-16`, `:88-97`) |
| `InvalidAcquisitionYearException.php` | Extiende `\InvalidArgumentException` (añadido en `review-fix.md`, B3) |
| `CapitalGoodActivity{Registered,Updated,Removed}.php` | Eventos; `acquisitionYear` en payload, tolerante a payloads antiguos sin la clave |
| `Answer/CapitalGoodAnswer.php` | Respuesta de sección: `capitalGoodId`, `quantity`, `existingActivityId`, `source`, `acquisitionYear` |
| `ActivityType.php` (modificado) | Caso `CapitalGood = 'capital_good'` (línea 13) y brazo en `fromActivity()` (línea 30) |

### Aplicación — `src/CarbonFootprint/Application/CapitalGood/`

| Fichero | Rol |
|---|---|
| `CapitalGoodAuthorizer.php` | `SuperAdmin`/`Admin` para todas las acciones |
| `Register/RegisterCapitalGood{Service,Request,Response}.php` | Alta (request con `unit = Unit::Euro`, `category = Others`) |
| `Update/UpdateCapitalGood{Service,Request,Response}.php` · `Update/EmissionFactorData.php` | Edición + conciliación de factores |
| `Remove/RemoveCapitalGood{Service,Request,Response}.php` | Baja lógica |
| `List/ListCapitalGoods{Service,Request,Response}.php` | Listado paginado |
| `EmissionFactor/EmissionFactorRow.php`, `EmissionFactor/{List,Update,CreateYear}/*` | Pantalla de edición en bloque y copia de año |
| `CapitalGoodImportationType.php` · `Import/ImportCapitalGood{Service,Request,Response}.php` | Importación `capital_goods` |
| `Export/CapitalGoodsExportFilter.php` | Filtro de exportación (añade `emissionFactorGroup`) |

### Aplicación — `src/CarbonFootprint/Application/Activity/`

| Fichero | Rol |
|---|---|
| `CapitalGoodEmissionFactorSelector.php` | Factor del año y grupo; ignora `condition` |
| `Catalog/CapitalGoodCatalogProvider.php` | Elementos con factor para el año y grupo |
| `Answer/CapitalGoodAnswerHandler.php` | Delega en Register/Update pasando `acquisitionYear` |
| `Register/RegisterCapitalGoodActivity{Service,Request,Response}.php` · `Update/UpdateCapitalGoodActivity{Service,Request,Response}.php` | Alta y actualización con emisiones calculadas |
| `../CarbonFootprintReport/Data/ActivityItemNameResolver.php` (modificado) | Brazo `CapitalGoodActivity` en `itemId()` (línea 78) |

### Persistencia — `src/CarbonFootprint/Infrastructure/Persistence/`

| Fichero | Rol |
|---|---|
| `Doctrine/Mapping/CapitalGood.CapitalGood.orm.xml` | Tabla `capital_goods` |
| `Doctrine/Mapping/CapitalGood.CapitalGoodEmissionFactor.orm.xml` | Tabla `capital_good_emission_factor`, único `uq_capital_good_emission_factor_year` (líneas 9-11) |
| `Doctrine/Mapping/CapitalGood.CapitalGoodTranslation.orm.xml` | Tabla `capital_good_translation` |
| `Doctrine/Mapping/Activity.CapitalGoodActivity.orm.xml` | Tabla hija JOINED `capital_good_activities` con `acquisition_year` nullable |
| `Doctrine/Mapping/Activity.Activity.orm.xml` (modificado) | Discriminador `capital_good` (línea 15) |
| `Doctrine/Type/CapitalGoodIdType.php` · `CapitalGoodEmissionFactorIdType.php` | Tipos DBAL registrados en `config/packages/doctrine.yaml:47-48` (modificado) |
| `Doctrine/Repository/DoctrineCapitalGoodRepository.php` | `#[AsAlias('repository.capital_good')]`, `#[When('dev'|'prod')]` (líneas 19-21) |
| `InMemory/Repository/InMemoryCapitalGoodRepository.php` | Repositorio de test |

### Infraestructura

| Fichero | Rol |
|---|---|
| `src/CarbonFootprint/Infrastructure/UI/Web/Admin/CapitalGood/ListController.php` | `/admin/capital-goods` (`admin_capital_goods`) |
| `.../RegisterController.php` | `/admin/capital-goods/register`; redirige a edición tras el alta |
| `.../EditController.php` | `/admin/capital-goods/edit/{capitalGoodId}` |
| `.../RemoveController.php` | `/admin/capital-goods/remove/{capitalGoodId}`, `#[CsrfProtected('remove')]` |
| `.../EmissionFactor/FactorEmissionController.php` | `/admin/capital-goods/emission-factors/{emissionFactorGroup?}/{year?}` |
| `.../EmissionFactor/CreateYearController.php` | `POST .../emission-factors/{emissionFactorGroup}/{sourceYear}/create-year` |
| `.../GetCapitalGoodFromIdTrait.php` · `.../Bulk/RemoveAction.php` | 404 si no existe · acción en bloque «Eliminar» |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}CapitalGoodType.php`, `ListCapitalGoodsType.php`, `BulkCapitalGoodEmissionFactorsType.php`, `CreateCapitalGoodEmissionFactorsYearType.php`, `AdminCapitalGoodsExportType.php` | Formularios |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/CapitalGood{Category,CategoryCode,Choice,EmissionFactor,EmissionFactorRow}Type.php` | Tipos de campo |
| `src/CarbonFootprint/Infrastructure/DataExchange/CapitalGoodsExportationType.php` | Exportación `capital_goods` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/Answer/CapitalGoodAnswerHttpMapper.php` | Fila HTTP → `CapitalGoodAnswer`; `parseAcquisitionYear()` privado (`:38-47`) |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/View/CapitalGoodRowViewFactory.php` | Actividad → `ActivityRowView` (sin `source`: la actividad no la persiste) |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/View/ActivityRowView.php` (modificado) | Propiedad opcional `acquisitionYear` al final (línea 23) |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (modificado) | Nodo «Bienes de capital» bajo «Materiales», icono `solar:buildings-2-line-duotone` (líneas 263-267) |
| `assets/icons/solar/buildings-2-line-duotone.svg` | Icono cacheado del menú |
| `templates/carbon_footprint/admin/capital_goods/{list,register,edit,emission_factors}.html.twig` | Pantallas de administración |
| `templates/components/CarbonFootprint/CapitalGoodActivity{Table,Row}.html.twig` | Componentes de la tabla de actividad (sin uso todavía) |
| `templates/security/admin/events/detail/carbon-footprint.capitalGood*.html.twig` | Siete plantillas del Registro de auditoría |
| `templates/carbon_footprint/translations/capitalGoodCategoryType.html.twig` · `translations/capitalGoodCategoryType+intl-icu.{es,en,tr}.xlf` | Dominio de categorías |
| `templates/carbon_footprint/translations/unitType.html.twig`, `translations/unitType+intl-icu.{es,en,tr}.xlf` (modificados) | Clave `m2` («Metro cuadrado») |
| `translations/fields+intl-icu.*.xlf`, `translations/messages+intl-icu.*.xlf` (modificados) | Clave `fields.capitalGood` («Bien de capital») y textos de interfaz |
| `migrations/Version20261015124557.php` | Migración de las cuatro tablas |

## Modelo de dominio

### `Unit::SquareMeter` / `Magnitude::Area`

Única unidad de su magnitud: no es divisa, factor fijo a base `1.0`, y `Quantity(10, m²)->convertTo(kg)` lanza `IncompatibleUnitsException`. `UnitType` recorre `Unit::cases()`, así que «Metro cuadrado» aparece en todos los selectores de unidad sin cambios adicionales; `activity_emissions_controller.js` filtra por `data-magnitude` de forma genérica.

### `CapitalGood`

Mismos métodos y semántica que `PurchaseService`:

| Método | Evento(s) | Notas |
|---|---|---|
| `register(name, category, unit, ?locale)` | `CapitalGoodRegistered` (+ `Translated`) | `CapitalGood.php:106` |
| `update(name, category, unit, ?locale)` | `CapitalGoodUpdated` (+ `Translated`) | Con locale no por defecto se conserva el nombre base (`:137`). Si cambia la unidad, re-estampa la unidad de todos los factores (`:147-149`, `restampEmissionFactorUnits` `:240`), sin convertir valores; publica un `EmissionFactorUpdated` por factor |
| `translate(name, locale)` | `CapitalGoodTranslated` | |
| `remove()` | `CapitalGoodRemoved` | Baja lógica |
| `setEmissionFactor(EmissionFactor)` | `Added` / `Updated` / nada | `:172`. Factor igual (`equals()`) no publica |
| `removeEmissionFactor(year, groupId)` | `EmissionFactorRemoved` | `:200`. No publica si no existe |
| `syncEmissionFactors(list<EmissionFactor>)` | Combinación | `:216`. Duplicado `año|grupo` → `\InvalidArgumentException` (`:224`) |

Invariante: **como máximo un factor por año y grupo** (colección, `setEmissionFactor` e índice único de BD).

### `CapitalGoodActivity`

`register(id, accountId, capitalGoodId, emissionFactor, emissions, quantity, ?acquisitionYear)` y `update(...)` validan primero el año de adquisición con `guardAcquisitionYear()` (`CapitalGoodActivity.php:36,59,88-97`): `null` se acepta; fuera de `[1900, 2200]` lanza `InvalidAcquisitionYearException`. Los `apply*` no validan, de modo que la rehidratación de eventos antiguos no se ve afectada. `remove()` registra y publica `Removed` sin aplicar estado, como el resto de actividades. El año de adquisición **no interviene en el cálculo**: las emisiones se imputan íntegras a la huella del año en que se registra la actividad.

## Flujo de una operación

### Alta, edición, factores en bloque, copia de año, baja

Idénticos a los de `PurchaseService` (ver `tech-manual.md` del plan `2026-10-15-bienes-y-servicios-comprados`), con estas piezas:

- **Alta:** `RegisterController` → `RegisterCapitalGoodType` → `RegisterCapitalGoodService` (autoriza `List` con agregado desechable, `RegisterCapitalGoodService.php:33`) → flash «Bien de capital creado correctamente: {name}» (`RegisterController.php:42`) → redirección a edición.
- **Edición:** `EditController` → `UpdateCapitalGoodType` (campo `emissionFactors` `GroupedEmissionFactorsType`, año entre 1900 y 2200 en `Type/CapitalGoodEmissionFactorType.php:29`) → `UpdateCapitalGoodService` (`find` o `\InvalidArgumentException('CapitalGood not found')` `:37`; autoriza `Edit` `:40`; `update()` y `syncEmissionFactors()` con la unidad ya actualizada; grupo inexistente → excepción `:79`) → flash «Bien de capital actualizado con éxito» (`EditController.php:60`).
- **Factores en bloque:** `FactorEmissionController` (etiqueta de fila «nombre - categoría», `:74`) → `ListEmissionFactorsService` (`:25`) → `UpdateEmissionFactorsService` (autoriza `Edit` al inicio `:34` y por elemento `:43`; fila vacía elimina el factor).
- **Copia de año:** `CreateYearController` → `CreateEmissionFactorsYearService` (`:32-50`): destino futuro o igual al origen → `\InvalidArgumentException` («El año destino no es válido.»); destino con factores en el grupo → `EmissionFactorYearAlreadyExistsException` («El año destino ya tiene factores de emisión.»). El formulario limita el destino a `[1900, año actual]` (`CreateCapitalGoodEmissionFactorsYearType.php:31`).
- **Baja:** `RemoveController` (CSRF) o `Bulk/RemoveAction` → `RemoveCapitalGoodService`.

### Importación

`CapitalGoodImportationType` (`code() = 'capital_goods'`): campos `capitalGood`, `name`, `category`, `unit`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source`, `emissionFactorGroup` (`:49-63`); traducción de valores para `capitalGood`, `category`, `unit` y `emissionFactorGroup` (`:66-74`); `isAuthorized()` con `Action::Import` (`:152-158`). Por fila, `ImportCapitalGoodService`:

1. Año entero > 0 o `'A valid year is required'` (`:122-127`).
2. Con `capitalGood` → `find` (no existe → `'Capital good not found'`, `:59`) y autoriza `Edit` (`:62`); sin él exige `name` (`:70`) y **registra un elemento nuevo** con categoría/unidad mapeadas o `Others`/`Euro` (`:76-89`). No deduplica por nombre.
3. Grupo obligatorio y existente (`:107-116`).
4. `setEmissionFactor` con la unidad del elemento; decimales vacíos → `'0'` (`:133`).

### Exportación

`CapitalGoodsExportationType`: columnas Identificador (desmarcada), Nombre, Categoría, Unidad, Año, CO2eq, CO2, CH4, N2O, Fuente, Grupo (`:49-59`); filtro `AdminCapitalGoodsExportType` (Nombre, Categoría, Grupo). `rows()` (`:104-132`) emite una fila por factor; un elemento sin factores emite una fila con columnas de factor vacías; con filtro de grupo se omiten los factores de otros grupos.

### Actividad (sin flujo HTTP todavía)

`CapitalGoodAnswerHttpMapper::map()` → `null` sin `itemId` o con unidad inválida o cantidad negativa; `acquisitionYear` vacío o no numérico → `null`, si no `(int)` (trunca decimales). `CapitalGoodAnswerHandler` → `RegisterCapitalGoodActivityService` (`:32-53`): autoriza `Register` con `ActivityAuthorizer` (`:36`) → `CapitalGoodEmissionFactorSelector::select()` (elemento o factor inexistente → `\InvalidArgumentException`, `:28,32`) → `EmissionsCalculator::calculate()` (convierte la cantidad a la unidad del factor; m² frente a kg lanza `IncompatibleUnitsException`; divisas vía tipos de cambio) → `CapitalGoodActivity::register()` (valida el año de adquisición) → `save()`. `UpdateCapitalGoodActivityService` lanza `ActivityNotFoundException` si la actividad no existe o no es de este tipo (`:37`). Los controladores de paso capturan `\InvalidArgumentException` y la muestran como flash, por eso `InvalidAcquisitionYearException` la extiende.

## Autorización y ámbito

`CapitalGoodAuthorizer`: `List` y `Edit` solo para `SuperAdmin` y `Admin` (vía `userHasRole`); `Register` y `Export` → `canList`; `View` y `Remove` → `canEdit`; `Import` → `canRegister`. **Maestro global**: sin *scoping* por cuenta. `Owner` y `User` reciben 403 en todas las rutas (escenarios Behat). El menú solo lo pinta `NodeLister::toAdmins()`. La actividad usa el `ActivityAuthorizer` genérico.

Deuda asumida (review **B1**, `review-fix.md`): `ImportCapitalGoodService` autoriza tras resolver año y elemento, como los demás servicios de importación de maestros de `CarbonFootprint`; la importación ya está filtrada por `isAuthorized()`.

## Persistencia y migración

| Tabla | Contenido |
|---|---|
| `capital_goods` | `id`, `name`, `category`, `unit` (`VARCHAR`), `removed`, `registered_at`, `updated_at`, `removed_at` |
| `capital_good_translation` | PK (`locale`, `capital_good_id`), `name`; FK `ON DELETE CASCADE` |
| `capital_good_emission_factor` | `id`, `capital_good_id`, `created_at`, `updated_at` + `EmissionFactor` embebido sin prefijo (`co2eq`, `co2`, `ch4`, `n2o` `NUMERIC(20,8)`, `year`, `emission_factor_group_id`, `unit`, `source`); único `(capital_good_id, year, emission_factor_group_id)`; FK `ON DELETE CASCADE` |
| `capital_good_activities` | Hija JOINED de `activities` (`id` FK `ON DELETE CASCADE`), `capital_good_id`, `acquisition_year INT NULL` |

Sin cambios en tablas existentes: `activities.type` y las columnas `unit` son `VARCHAR` y admiten `capital_good` y `m2`. Sin backfill.

Migración: `migrations/Version20261015124557.php` (sin commitear al escribir este manual) crea las cuatro tablas con índices y FKs y las elimina en orden inverso en `down()`; es coherente con `migration.sql` de la carpeta del plan, con los nombres `IDX_`/`FK_` generados por Doctrine. Aplicar con `make migrate`.

## Configuración y operación

- Sin variables de entorno, colas, comandos de consola ni tareas programadas nuevas.
- `services.yaml` no cambia: repositorios por `#[AsAlias]`/`#[When]`, importación por autoconfiguración y las cinco piezas de actividad por las etiquetas `#[AutoconfigureTag]` de sus interfaces (selector, proveedor de catálogo, manejador, mapper HTTP, factory de fila).
- Sin correos ni notificaciones; el Registro usa las siete plantillas `carbon-footprint.capitalGood*.html.twig`. Los eventos de actividad usan `default.html.twig`, como todos los tipos de actividad.

## Cómo extenderlo

**Exponer la actividad en el alcance 3 (o en una fase de eventos):**

1. Declarar la `SectionDefinition` con `ActivityType::CapitalGood` en el `Scope` (o `Phase`) y su `Source`.
2. Pintar `<twig:CarbonFootprint:CapitalGoodActivityTable>` con `rows`, `items` (de `CapitalGoodCatalogProvider`) y `units`. **Trampa:** el componente solo acepta `eventId` y construye la URL con `events_carbon_footprint_emissions_calculate` (`CapitalGoodActivityTable.html.twig:1,10`); para un asistente organizacional hay que añadir la prop `calculateUrl` como en `RefrigerantGasActivityTable.html.twig`.
3. Selector, catálogo, handler, mapper y factory ya se resuelven por `ActivityType`. Revisar exclusiones, informe (`ActivityItemNameResolver` ya tiene brazo) y los tests de vista que construyen registries solo con los tipos que tienen sección (`PhaseTest`, `MaterialsStepViewTest`, `GetPhaseServiceTest` o sus equivalentes organizacionales).
4. Si se quiere amortizar las emisiones según el año de adquisición, hoy no existe: el atributo solo se guarda y se muestra.

**Añadir una categoría:** caso en `CapitalGoodCategory`, `trans` en `templates/carbon_footprint/translations/capitalGoodCategoryType.html.twig` y `make translation` (ES, EN, TR). Sin migración.

**Añadir otra unidad de superficie (ha, ft²):** caso en `Unit` con brazo `Magnitude::Area` y factor en `fixedFactorToBase()` (hoy `m2` cae en el `default => 1.0`), clave en `unitType` y test en `UnitTest`.

**Trampas conocidas:**

- Cambiar la unidad de un elemento re-estampa sus factores sin convertir valores y **no** recalcula actividades ya registradas.
- El mapper trata una cantidad ausente como `0` (`AbstractSectionAnswerHttpMapper.php:31-40`, review **B2**, asumido).
- La importación sin `capitalGood` siempre crea un elemento nuevo.
- `parseAcquisitionYear` no valida rango: un año fuera de `[1900, 2200]` llega al dominio y se rechaza con excepción, que el paso mostrará como flash de error.

## Tests

| Ámbito | Ficheros |
|---|---|
| Unidades | `tests/Unit/CarbonFootprint/Domain/Model/UnitTest.php` (m² en `Area`, no convertible a kg) |
| Dominio del maestro | `tests/Unit/CarbonFootprint/Domain/Model/CapitalGood/` — `CapitalGoodTest` (incluye `testUpdatingUnitToSquareMeterRestampsEmissionFactors`), repositorio abstracto + InMemory, Mothers; `tests/Unit/CarbonFootprint/Infrastructure/Persistence/Doctrine/Repository/DoctrineCapitalGoodRepositoryTest.php` |
| Aplicación del maestro | `tests/Unit/CarbonFootprint/Application/CapitalGood/` — authorizer, List, Register, Update, Remove, `EmissionFactor/{CreateYear,List,Update}`, `Import` |
| Actividad | `tests/Unit/CarbonFootprint/Domain/Model/Activity/{CapitalGoodActivityTest,CapitalGoodActivityMother,ActivityTypeTest}.php` (límites 1900/2200, rechazo de 1899/2201/-5, rehidratación sin `acquisitionYear`); `tests/Unit/CarbonFootprint/Application/Activity/` — Register (conversión `USD` a `1.25`, m² frente a kg, año fuera de rango), Update, selector, `Catalog/`, `Answer/` |
| Piezas de UI | `tests/Unit/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/{Answer/CapitalGoodAnswerHttpMapperTest,View/CapitalGoodRowViewFactoryTest}.php` |
| Aceptación | `tests/Acceptance/CarbonFootprint/capital-good-{register,list,edit,remove,import,export,emission-factors}.feature` (el de edición cambia la unidad a `m2`); pasos `capital good :name with id :id exists` y `... and emission factor for year :year exists` en `CarbonFootprintContext.php:348-376` |

Ejecutar:

```bash
make test-unit options=tests/Unit/CarbonFootprint/Domain/Model/CapitalGood
make test-unit options=tests/Unit/CarbonFootprint/Application/CapitalGood
make test-acceptance options=tests/Acceptance/CarbonFootprint/capital-good-edit.feature
make test-parallel
```

Huecos: no hay `.feature` para la actividad (no existe flujo HTTP que la registre); «cantidad ausente → `Quantity(0)`» no está cubierto (review B2).

## Desviaciones y deuda

Ver `## Implementación` de [`plan.md`](plan.md), [`review.md`](review.md) y [`review-fix.md`](review-fix.md):

- `make icon-lock` trajo 42 iconos ajenos que se retiraron; solo queda `assets/icons/solar/buildings-2-line-duotone.svg`.
- `CapitalGoodRowViewFactory` no expone `source` porque la actividad no la persiste.
- **A1** (migración ausente): la migración existe ya en `migrations/Version20261015124557.php`; `review.md` no se ha re-revisado.
- **B3** (año de adquisición sin cota en servidor): corregido con la guarda de dominio e `InvalidAcquisitionYearException`. El plan solo describe la cota en la plantilla.
- **B1** y **B2**: asumidos en `review-fix.md`.
- Deuda declarada: el 404 de `GetCapitalGoodFromIdTrait` no usa `TranslatableMessage` (convención compartida con los traits homónimos).
