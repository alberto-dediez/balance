# Bienes y servicios comprados (maestro con factores de emisión y su actividad) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `CarbonFootprint` (más el menú lateral de `Shared` y los tipos DBAL de `config/packages/doctrine.yaml`)

## Qué hace y por qué

La plataforma mantiene maestros de elementos con factores de emisión por año y grupo de coeficientes (materiales, merchandising, catering…). Faltaba uno para los **bienes y servicios comprados** —servicios profesionales, publicidad, tecnología, logística…—, la categoría «Purchased goods and services» del alcance 3 del GHG Protocol, cuyo factor suele expresarse **por euro gastado** (*spend-based*). El plan `2026-10-15-spend-based` ya dejó `Unit::Euro`, las divisas como magnitud `Currency` y `UnitConverter`; este plan las aprovecha.

Se añaden dos cosas:

1. El maestro global `PurchaseService`, con el ciclo de vida completo de un maestro de plataforma: alta, edición (con factores por año y grupo), baja lógica, listado filtrable, edición en bloque de factores por año y grupo con copia de un año a otro, importación y exportación de hoja de cálculo, acción en bloque de eliminar, entrada de menú y trazas en el Registro de auditoría. Unidad elegible por elemento con **`EUR` por defecto**.
2. La actividad `PurchaseServiceActivity` con todas sus piezas enchufables (selector de factor, proveedor de catálogo, manejador de respuesta, servicios Register/Update, mapper HTTP, factory de fila y componentes Twig de tabla y fila). **Ninguna fase de la huella de eventos ni ningún alcance organizacional la usa todavía**: queda preparada para el futuro cálculo del alcance 3 (precedente: `2026-09-15-refrigerant-gas-activity`).

Decisiones clave (tabla `## Decisiones tomadas` del plan): maestro, administración, importación y exportación calcados de `MerchandisingItem`; **un solo factor** por año y grupo (entidad de factor, DTOs, selector y parcial Twig calcados de `OvernightStay`), sin el par nueva fabricación/reciclado ni `ItemCondition`; categorías como enum cerrado de seis valores; autorización idéntica a `MerchandisingItemAuthorizer`.

## Mapa de piezas

Todos los ficheros son **nuevos** salvo donde se indica «modificado».

### Dominio — `src/CarbonFootprint/Domain/Model/PurchaseService/`

| Fichero | Rol |
|---|---|
| `PurchaseService.php` | Agregado raíz (`AggregateRoot`, `Translatable`, `\Stringable`); categoría y unidad por defecto `Others`/`Euro` (líneas 27-37) |
| `PurchaseServiceId.php` · `PurchaseServiceEmissionFactorId.php` | Ids UUID (`AggregateRootIdTrait`) |
| `PurchaseServiceCategory.php` | Enum `professional_services`, `marketing`, `technology`, `logistics`, `facilities`, `others` |
| `PurchaseServiceEmissionFactor.php` | Subentidad de **un** factor (`EmissionFactor` embebido, `createdAt`/`updatedAt`, `update()`) |
| `PurchaseServiceEmissionFactors.php` | Colección: `add` (ignora duplicados año+grupo), `forYearAndGroup`, `hasForYearAndGroup`, `removeForYearAndGroup`, `years()`, `groups()` |
| `PurchaseServiceTranslation.php` | Traducción del nombre por locale |
| `PurchaseServiceFilter.php` | Filtro (`Filter` trait): `name`, `category`, `limit = 10` |
| `PurchaseServiceRepository.php` | Puerto: `add`, `remove`, `find`, `findByFilter`, `totalResultsByFilter` |
| `PurchaseService{Registered,Updated,Translated,Removed}.php` | Eventos del elemento |
| `PurchaseServiceEmissionFactor{Added,Updated,Removed}.php` | Eventos del factor |

### Dominio — `src/CarbonFootprint/Domain/Model/Activity/`

| Fichero | Rol |
|---|---|
| `PurchaseServiceActivity.php` | Actividad (`final`, hereda de `Activity`) con `purchaseServiceId`; sin `condition` |
| `PurchaseServiceActivity{Registered,Updated,Removed}.php` | Eventos de la actividad |
| `Answer/PurchaseServiceAnswer.php` | Respuesta de sección: `purchaseServiceId`, `quantity`, `existingActivityId` |
| `ActivityType.php` (modificado) | Caso `PurchaseService = 'purchase_service'` y brazo en `fromActivity()` |

### Aplicación — `src/CarbonFootprint/Application/PurchaseService/`

| Fichero | Rol |
|---|---|
| `PurchaseServiceAuthorizer.php` | `SuperAdmin`/`Admin` para todas las acciones |
| `Register/RegisterPurchaseService{Service,Request,Response}.php` | Alta (request con `unit = Unit::Euro`, `category = Others`) |
| `Update/UpdatePurchaseService{Service,Request,Response}.php` · `Update/EmissionFactorData.php` | Edición de datos + conciliación de factores (`syncEmissionFactors`) |
| `Remove/RemovePurchaseService{Service,Request,Response}.php` | Baja lógica |
| `List/ListPurchaseServices{Service,Request,Response}.php` | Listado paginado (`Request` extiende `PurchaseServiceFilter`) |
| `EmissionFactor/EmissionFactorRow.php` | Fila de la edición en bloque (`isEmpty()`, `toEmissionFactor()`) |
| `EmissionFactor/List/ListEmissionFactors{Service,Request,Response}.php` | Datos de la pantalla en bloque (año por defecto = último con datos del grupo) |
| `EmissionFactor/Update/UpdateEmissionFactors{Service,Request,Response}.php` | Guardado en bloque de un año y grupo |
| `EmissionFactor/CreateYear/CreateEmissionFactorsYear{Service,Request,Response}.php` · `EmissionFactorYearAlreadyExistsException.php` | Copia de los factores de un año a otro |
| `PurchaseServiceImportationType.php` | Tipo de importación `purchase_services` |
| `Import/ImportPurchaseService{Service,Request,Response}.php` | Una fila importada = un factor |
| `Export/PurchaseServicesExportFilter.php` | Filtro de exportación (añade `emissionFactorGroup`) |

### Aplicación — `src/CarbonFootprint/Application/Activity/`

| Fichero | Rol |
|---|---|
| `PurchaseServiceEmissionFactorSelector.php` | `EmissionFactorSelector`: devuelve el factor del año y grupo; ignora `condition` |
| `Catalog/PurchaseServiceCatalogProvider.php` | `ActivityCatalogProvider`: elementos con factor para el año y grupo |
| `Answer/PurchaseServiceAnswerHandler.php` | `ActivityAnswerHandler`: delega en Register/Update |
| `Register/RegisterPurchaseServiceActivity{Service,Request,Response}.php` | Alta de la actividad con emisiones calculadas |
| `Update/UpdatePurchaseServiceActivity{Service,Request,Response}.php` | Recalcula y actualiza |
| `../CarbonFootprintReport/Data/ActivityItemNameResolver.php` (modificado) | Brazo `PurchaseServiceActivity` en `itemId()` (línea 76) |

### Persistencia — `src/CarbonFootprint/Infrastructure/Persistence/`

| Fichero | Rol |
|---|---|
| `Doctrine/Mapping/PurchaseService.PurchaseService.orm.xml` | Tabla `purchase_services` |
| `Doctrine/Mapping/PurchaseService.PurchaseServiceEmissionFactor.orm.xml` | Tabla `purchase_service_emission_factor`, único `(purchase_service_id, year, emission_factor_group_id)` (líneas 9-11) |
| `Doctrine/Mapping/PurchaseService.PurchaseServiceTranslation.orm.xml` | Tabla `purchase_service_translation` (PK `locale` + `purchase_service_id`) |
| `Doctrine/Mapping/Activity.PurchaseServiceActivity.orm.xml` | Tabla hija JOINED `purchase_service_activities` |
| `Doctrine/Mapping/Activity.Activity.orm.xml` (modificado) | Discriminador `purchase_service` (línea 14) |
| `Doctrine/Type/PurchaseServiceIdType.php` · `PurchaseServiceEmissionFactorIdType.php` | Tipos DBAL, registrados en `config/packages/doctrine.yaml:45-46` (modificado) |
| `Doctrine/Repository/DoctrinePurchaseServiceRepository.php` | Repositorio dev/prod (`#[AsAlias]`, `#[When('dev'|'prod')]`) |
| `InMemory/Repository/InMemoryPurchaseServiceRepository.php` | Repositorio de test; **ordena** por `name`/`registeredAt`/`updatedAt` (línea 60), a diferencia del de merchandising |

### Infraestructura

| Fichero | Rol |
|---|---|
| `src/CarbonFootprint/Infrastructure/UI/Web/Admin/PurchaseService/ListController.php` | `/admin/purchase-services` (`admin_purchase_services`), filtro + acción en bloque |
| `.../RegisterController.php` | `/admin/purchase-services/register`; redirige a edición tras el alta |
| `.../EditController.php` | `/admin/purchase-services/edit/{purchaseServiceId}` |
| `.../RemoveController.php` | `/admin/purchase-services/remove/{purchaseServiceId}`, `#[CsrfProtected('remove')]` |
| `.../EmissionFactor/FactorEmissionController.php` | `/admin/purchase-services/emission-factors/{emissionFactorGroup?}/{year?}` |
| `.../EmissionFactor/CreateYearController.php` | `POST .../emission-factors/{emissionFactorGroup}/{sourceYear}/create-year` |
| `.../GetPurchaseServiceFromIdTrait.php` · `.../Bulk/RemoveAction.php` | 404 si no existe · acción en bloque «Eliminar» |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}PurchaseServiceType.php`, `ListPurchaseServicesType.php`, `BulkPurchaseServiceEmissionFactorsType.php`, `CreatePurchaseServiceEmissionFactorsYearType.php`, `AdminPurchaseServicesExportType.php` | Formularios de pantalla |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/PurchaseService{Category,CategoryCode,Choice,EmissionFactor,EmissionFactorRow}Type.php` | Tipos de campo (la etiqueta de `PurchaseServiceChoiceType` es «nombre - categoría», desambiguada con «(n)») |
| `src/CarbonFootprint/Infrastructure/DataExchange/PurchaseServicesExportationType.php` | Tipo de exportación `purchase_services` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/Answer/PurchaseServiceAnswerHttpMapper.php` | Fila HTTP → `PurchaseServiceAnswer` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/View/PurchaseServiceRowViewFactory.php` | Actividad → `ActivityRowView` |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (modificado) | Nodo «Bienes y servicios comprados» bajo «Materiales» (líneas 258-262) |
| `templates/carbon_footprint/admin/purchase_services/{list,register,edit,emission_factors}.html.twig` | Pantallas de administración; la edición incluye `emission_factors/_form.simple.html.twig` |
| `templates/components/CarbonFootprint/PurchaseServiceActivity{Table,Row}.html.twig` | Componentes de la tabla de actividad (sin uso todavía) |
| `templates/security/admin/events/detail/carbon-footprint.purchaseService*.html.twig` | Siete plantillas del Registro de auditoría |
| `templates/carbon_footprint/translations/purchaseServiceCategoryType.html.twig` · `translations/purchaseServiceCategoryType+intl-icu.{es,en,tr}.xlf` | Dominio de traducción de categorías |
| `translations/fields+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.{es,en,tr}.xlf` (modificados) | Clave `fields.purchaseService` y textos de interfaz |
| `migrations/Version20261015110653.php` | Migración de las cuatro tablas (ver *Persistencia y migración*) |

## Modelo de dominio

### `PurchaseService`

Campos: `id`, `name` (nombre base; `name()` devuelve la traducción del locale actual si existe, `PurchaseService.php:47-53`), `category`, `unit`, `removed`, `registeredAt`, `updatedAt`, `removedAt`, `translations`, `emissionFactors`.

| Método | Evento(s) | Notas |
|---|---|---|
| `register(name, category, unit, ?locale)` | `PurchaseServiceRegistered` (+ `PurchaseServiceTranslated` si hay locale) | `:106-124` |
| `update(name, category, unit, ?locale)` | `PurchaseServiceUpdated` (+ `Translated`) | Con locale no por defecto el nombre base se conserva y el nuevo va a la traducción (`:137`). Si cambia la unidad, **re-estampa** la unidad de todos sus factores (`:147-149`, `restampEmissionFactorUnits` `:240-251`), lo que publica un `EmissionFactorUpdated` por factor |
| `translate(name, locale)` | `PurchaseServiceTranslated` | |
| `remove()` | `PurchaseServiceRemoved` | Baja lógica: `removed = true`, `removedAt` |
| `setEmissionFactor(EmissionFactor)` | `EmissionFactorAdded` / `EmissionFactorUpdated` / nada | `:172-198`. Si ya existe uno para el año y grupo y `equals()`, no publica |
| `removeEmissionFactor(year, groupId)` | `EmissionFactorRemoved` | No publica si no existe (`:202-204`) |
| `syncEmissionFactors(list<EmissionFactor>)` | Combinación de los anteriores | `:216-238`. Pone los recibidos y elimina los existentes que no vengan; un duplicado `año|grupo` lanza `\InvalidArgumentException` (`:223-225`) |

Invariante: **como máximo un factor por año y grupo**, garantizado por `PurchaseServiceEmissionFactors::add()` (ignora duplicados), por `setEmissionFactor` y por el índice único de BD.

Payloads de los eventos de factor: `purchaseServiceId`, `emissionFactorId` (solo `Added`) y el `EmissionFactor` serializado; `EmissionFactorRemoved` lleva `year` y `emissionFactorGroupId`.

### `PurchaseServiceActivity`

`register(id, accountId, purchaseServiceId, emissionFactor, emissions, quantity)` publica `PurchaseServiceActivityRegistered`; `update(...)` publica `Updated` (sin `accountId`); `remove()` registra y publica `Removed` sin aplicar estado (`PurchaseServiceActivity.php:59-64`, igual que el resto de actividades). `ActivityType::fromActivity()` la resuelve a `PurchaseService`.

## Flujo de una operación

### Alta

`GET|POST /admin/purchase-services/register` → `RegisterController` comprueba `Register` con un agregado desechable → `RegisterPurchaseServiceType` (data class `RegisterPurchaseServiceRequest`, unidad precargada `EUR`) → `RegisterPurchaseServiceService` (`RegisterPurchaseServiceService.php:29-52`): autoriza `List` sobre un agregado desechable (convención de los maestros), traduce si el locale no es el por defecto, `add()` → flash «Bien o servicio comprado creado correctamente: {name}» → redirección a `admin_purchase_services_edit`.

### Edición

`EditController` carga el elemento (404 vía `GetPurchaseServiceFromIdTrait`), `denyAccessUnlessGranted(Edit)`, construye `UpdatePurchaseServiceRequest` con un `EmissionFactorData` por factor → `UpdatePurchaseServiceType`, cuyo campo `emissionFactors` es `GroupedEmissionFactorsType` (una colección por grupo de coeficientes con la restricción `UniqueEmissionFactorYears`, mensaje «Ya existe un factor de emisión para el año {{ year }}») → `UpdatePurchaseServiceService` (`:32-70`): `find` o `\InvalidArgumentException`, autoriza `Edit`, `update()`, y `syncEmissionFactors()` con la unidad **ya actualizada** del elemento (`:52-62`); grupo inexistente → `\InvalidArgumentException`. Las excepciones las absorbe `EditTrait` con el flash «Error inesperado. No se ha podido actualizar correctamente.». `saveAndReturn` vuelve al listado; `save` se queda.

### Edición en bloque de factores

`FactorEmissionController`: sin grupo en la URL usa el grupo por defecto (`findDefault()`; si no hay, `NoDefaultEmissionFactorGroupException`) → `ListEmissionFactorsService` (`:23-40`): autoriza `List`, carga todos los elementos (`limit: 0`), año = el de la URL o el último con datos del grupo o el actual; `canGoNext = year < currentYear` → una `EmissionFactorRow` por elemento con etiqueta «nombre - categoría» → `BulkPurchaseServiceEmissionFactorsType` (filas `PurchaseServiceEmissionFactorRowType` con `validateAllOrNothing`: o los cuatro valores numéricos o ninguno) → `UpdateEmissionFactorsService` (`:32-59`): autoriza `Edit` al inicio con agregado desechable (desviación declarada, mejora sobre el original) y por elemento; fila vacía → `removeEmissionFactor`, fila con datos → `setEmissionFactor` con la unidad del elemento.

### Copiar un año

`POST .../create-year` → `CreatePurchaseServiceEmissionFactorsYearType` (`targetYear` entre 1900 y el año actual) → `CreateEmissionFactorsYearService` (`:30-67`): autoriza `Edit`; año destino futuro o igual al origen → `\InvalidArgumentException` (flash «El año destino no es válido.»); si **algún** elemento ya tiene factor en el año destino para ese grupo → `EmissionFactorYearAlreadyExistsException` (flash «El año destino ya tiene factores de emisión.») sin copiar nada; si no, copia con `withYear()` el factor de cada elemento que lo tenga en el año origen.

### Importación

El flujo genérico de `DataExchange` (subir fichero → emparejar campos → traducir valores → asignar valores → finalizar → resultados) con `PurchaseServiceImportationType`: campos `purchaseService`, `name`, `category`, `unit`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source`, `emissionFactorGroup`; `MapExtractor` para `purchaseService`, `category`, `unit` y `emissionFactorGroup` (`PurchaseServiceImportationType.php:120-130`); `isAuthorized()` con `Action::Import` (`:152-159`). Por fila, `ImportPurchaseServiceService` (`:36-51`):

1. Año: entero > 0 o `\InvalidArgumentException('A valid year is required')`.
2. Elemento: si viene `purchaseService` → `find` (no existe → excepción) y autoriza `Edit`; si no, exige `name` y **registra un elemento nuevo** con `category`/`unit` mapeadas o `Others`/`Euro` por defecto (`:76-94`). No busca por nombre: no deduplica.
3. Grupo obligatorio y existente.
4. `setEmissionFactor` con la unidad del elemento; decimales vacíos → `'0'`, coma decimal admitida (`:133-138`).

### Exportación

`PurchaseServicesExportationType`: columnas Identificador (desmarcada por defecto), Nombre, Categoría, Unidad, Año, CO2eq, CO2, CH4, N2O, Fuente, Grupo (`:46-61`); filtro `AdminPurchaseServicesExportType` (Nombre, Categoría, Grupo). `rows()` (`:104-132`) emite una fila por factor; un elemento **sin factores** emite una fila con las columnas de factor vacías aunque haya filtro de grupo; con filtro de grupo, los factores de otros grupos se omiten. Categoría y unidad salen como código (`professional_services`, `EUR`), no traducidas.

### Actividad (sin flujo HTTP todavía)

`PurchaseServiceAnswerHandler::register()` → `RegisterPurchaseServiceActivityService` (`:32-53`): autoriza `Register` con `ActivityAuthorizer` sobre una actividad desechable con la cuenta del request → `PurchaseServiceEmissionFactorSelector::select()` (elemento o factor inexistente → `\InvalidArgumentException`, `PurchaseServiceEmissionFactorSelector.php:25-35`) → `EmissionsCalculator::calculate()` (convierte la cantidad a la unidad del factor con `UnitConverter`; divisas con `ExchangeRateRepository`) → `PurchaseServiceActivity::register()` → `save()`. La actualización (`UpdatePurchaseServiceActivityService.php:32-55`) lanza `ActivityNotFoundException` si la actividad no existe o no es de este tipo.

## Autorización y ámbito

`PurchaseServiceAuthorizer`: `List` y `Edit` solo para `SuperAdmin` y `Admin` (vía `userHasRole`, que respeta los roles actuales); `Register` y `Export` → `canList`; `View` y `Remove` → `canEdit`; `Import` → `canRegister`. Es un **maestro global**: no hay *scoping* por cuenta ni interviene `CurrentAccountIdProvider`. `Owner` y `User` reciben 403 en todas las rutas (escenarios Behat). El menú solo lo pinta `NodeLister::toAdmins()`.

La actividad usa el `ActivityAuthorizer` genérico, que sí trabaja con la cuenta de la actividad.

Deuda heredada (review **B1**, asumida en `review-fix.md`): `ImportPurchaseServiceService` autoriza tras resolver año y elemento, como sus 13 maestros hermanos; la importación ya está filtrada aguas arriba por `isAuthorized()`.

## Persistencia y migración

| Tabla | Contenido |
|---|---|
| `purchase_services` | `id`, `name`, `category`, `unit` (`VARCHAR`, enums), `removed`, `registered_at`, `updated_at`, `removed_at` |
| `purchase_service_translation` | PK (`locale`, `purchase_service_id`), `name`; FK `ON DELETE CASCADE` |
| `purchase_service_emission_factor` | `id`, `purchase_service_id`, `created_at`, `updated_at` + `EmissionFactor` embebido sin prefijo (`co2eq`, `co2`, `ch4`, `n2o` `NUMERIC(20,8)`, `year`, `emission_factor_group_id`, `unit`, `source`); único `uq_purchase_service_emission_factor_year`; FK `ON DELETE CASCADE` |
| `purchase_service_activities` | Hija JOINED de `activities` (`id` FK `ON DELETE CASCADE`), `purchase_service_id` |

La columna discriminadora `activities.type` ya es `VARCHAR`; el valor `purchase_service` no requiere cambio de esquema. Sin backfill.

Migración: `migrations/Version20261015110653.php` (sin commitear en el momento de escribir este manual) crea las cuatro tablas con sus índices y FKs y las elimina en orden inverso en `down()`; coincide con `migration.sql` de la carpeta del plan. Aplicar con `make migrate`.

## Configuración y operación

- Sin variables de entorno, colas, comandos de consola ni tareas programadas nuevas.
- `services.yaml` no cambia: los repositorios se resuelven por `#[AsAlias]`/`#[When]`, el tipo de importación por autoconfiguración (`ImportationTypeFactory`) y las cinco piezas de actividad por las etiquetas `#[AutoconfigureTag]` de sus interfaces (`carbon_footprint.emission_factor_selector`, `.activity_catalog_provider`, `.activity_answer_handler`, `.section_answer_http_mapper`, `.activity_row_view_factory`).
- Icono del menú `solar:cart-large-minimalistic-line-duotone`: se resuelve bajo demanda como el resto de iconos de `NodeLister` (`make icon-lock` no lo descarga porque solo escanea plantillas).
- Sin correos ni notificaciones propias; las trazas del Registro usan las siete plantillas `carbon-footprint.purchaseService*.html.twig`. Los eventos de la actividad no tienen plantilla (se usa `default.html.twig`, como en todos los tipos de actividad).

## Cómo extenderlo

**Exponer la actividad en el alcance 3 (o en una fase de eventos)** — el objetivo previsto:

1. Declarar la `SectionDefinition` con `ActivityType::PurchaseService` en el `Scope` (o `Phase`) correspondiente y su `Source`.
2. Pintar `<twig:CarbonFootprint:PurchaseServiceActivityTable>` en la plantilla del paso con `rows`, `items` (los da `PurchaseServiceCatalogProvider`) y `units`. **Trampa:** el componente solo acepta `eventId` y construye la URL de cálculo con `events_carbon_footprint_emissions_calculate` (`PurchaseServiceActivityTable.html.twig:1,9`). Para usarlo en un asistente organizacional hay que añadir la prop `calculateUrl` como en `RefrigerantGasActivityTable.html.twig:1,12`.
3. Selector, catálogo, handler, mapper HTTP y factory de fila ya se resuelven por `ActivityType` sin configuración. Revisar exclusiones, informe (`ActivityItemNameResolver` ya tiene brazo) y tests de vista que construyen registries con los tipos que tienen sección (`PhaseTest`, `MaterialsStepViewTest`, `GetPhaseServiceTest` o sus equivalentes organizacionales).
4. Capturar `ExchangeRateNotFoundException` (`\DomainException`) donde se calcule con divisas: hoy `CalculateEmissionsController` solo captura `\InvalidArgumentException` (fuera de alcance del plan).

**Añadir una categoría:** caso nuevo en `PurchaseServiceCategory`, `trans` en `templates/carbon_footprint/translations/purchaseServiceCategoryType.html.twig` y `make translation` (ES, EN, TR). No requiere migración (`VARCHAR`).

**Trampas conocidas:**

- Cambiar la unidad de un elemento re-estampa sus factores, pero **no** recalcula actividades ya registradas: cada actividad guarda su `EmissionFactor` y sus `Emissions`.
- El mapper HTTP solo devuelve `null` con ítem vacío, unidad inválida o cantidad negativa; una cantidad ausente se interpreta como `0` (`AbstractSectionAnswerHttpMapper.php:31-40`, review **B2**, asumido).
- La importación sin `purchaseService` siempre crea un elemento nuevo; para actualizar factores hay que emparejar la columna del identificador.

## Tests

| Ámbito | Ficheros |
|---|---|
| Dominio | `tests/Unit/CarbonFootprint/Domain/Model/PurchaseService/` — `PurchaseServiceTest` (unidad `EUR` por defecto, eventos, traducción, factor igual no publica, `sync` y duplicados, re-estampado), `PurchaseServiceRepositoryTest` abstracto + `InMemoryPurchaseServiceRepositoryTest`, `PurchaseServiceMother`, `PurchaseServiceRepositoryMother`; `tests/Unit/CarbonFootprint/Infrastructure/Persistence/Doctrine/Repository/DoctrinePurchaseServiceRepositoryTest.php` |
| Aplicación del maestro | `tests/Unit/CarbonFootprint/Application/PurchaseService/` — authorizer, List, Register, Update, Remove, `EmissionFactor/{CreateYear,List,Update}`, `Import` (alta con `others`/`EUR`, categoría y unidad mapeadas, actualización por id, errores de nombre, grupo, año e id) |
| Actividad | `tests/Unit/CarbonFootprint/Domain/Model/Activity/{PurchaseServiceActivityTest,PurchaseServiceActivityMother,ActivityTypeTest}.php`; `tests/Unit/CarbonFootprint/Application/Activity/` — Register (incluida conversión `USD` a `1.25`), Update, selector, `Catalog/`, `Answer/` |
| Piezas de UI | `tests/Unit/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/{Answer/PurchaseServiceAnswerHttpMapperTest,View/PurchaseServiceRowViewFactoryTest}.php` |
| Aceptación | `tests/Acceptance/CarbonFootprint/purchase-service-{register,list,edit,remove,import,export,emission-factors}.feature`; pasos `purchase service :name with id :id exists` y `... and emission factor for year :year exists` en `CarbonFootprintContext.php:310-330` |

Ejecutar:

```bash
make test-unit options=tests/Unit/CarbonFootprint/Domain/Model/PurchaseService
make test-unit options=tests/Unit/CarbonFootprint/Application/PurchaseService
make test-acceptance options=tests/Acceptance/CarbonFootprint/purchase-service-register.feature
make test-parallel
```

Huecos: no hay `.feature` para la actividad (no existe flujo HTTP que la registre); el caso «cantidad ausente → `Quantity(0)`» del mapper no está cubierto (review B2).

## Desviaciones y deuda

Ver `## Implementación` de [`plan.md`](plan.md), [`review.md`](review.md) y [`review-fix.md`](review-fix.md):

- `InMemoryPurchaseServiceRepository` ordena (el de merchandising no), para que el test abstracto de repositorio pase igual en memoria y en Doctrine.
- `UpdateEmissionFactorsService` autoriza al inicio además de por fila.
- **A1** (migración ausente): la migración existe ya en `migrations/Version20261015110653.php`; `review.md` no se ha re-revisado.
- **B1** (orden de autorización en la importación) y **B2** (cantidad ausente en el mapper): asumidos en `review-fix.md`, pendientes de validación del usuario.
- Deuda detectada en el maestro de referencia, no corregida: `InMemoryMerchandisingItemRepository::findByFilter` ignora la ordenación; `UpdateEmissionFactorsService` y `ImportMerchandisingItemService` de merchandising autorizan tarde; PHPDoc con FQCN en `ListMerchandisingItemsResponse.php:9`.
