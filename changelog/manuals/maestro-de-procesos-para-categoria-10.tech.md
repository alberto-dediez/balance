# Procesos (maestro de la categoría 10 del alcance 3 y su actividad): manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `CarbonFootprint` (más el menú lateral de `Shared` y los tipos DBAL de `config/packages/doctrine.yaml`)

## Qué hace y por qué

La categoría 10 del alcance 3 del GHG Protocol, «Procesamiento de los productos vendidos» (`StandardScope3Category::ProcessingOfSoldProducts`), necesita un catálogo de **procesos**. Un proceso es la transformación que un tercero aplica a un producto intermedio que vende la organización. Su factor de emisión expresa las emisiones de esa transformación por unidad del proceso, que por defecto es la unidad de producto vendido. Este plan añade ese maestro y la actividad que lo consumirá.

Se añaden tres piezas:

1. El maestro global `Process`, copiado uno a uno de `UseProfile` (plan `2026-10-15-maestro-de-perfiles-de-uso-para-categoria-11`). Tiene solo nombre traducible y unidad, sin categoría. Incluye alta, edición con factores por año y grupo, baja lógica, listado filtrable por nombre, edición en bloque de factores con copia de año, importación, exportación, acción en bloque de eliminar y trazas en el Registro. La unidad se puede elegir y por defecto es **`Unit::Piece` (`ud`)**.
2. Una entrada de menú «Procesos» dentro del nodo existente «Productos vendidos», **antes** de «Perfiles de uso», para seguir el orden de las categorías 10 y 11.
3. La actividad `ProcessActivity` con todas sus piezas enchufables, sin atributos de línea propios. **Todavía no la usa ninguna fase de la huella de eventos ni ningún alcance organizacional**: queda preparada para el futuro plan del alcance 3.

Las decisiones clave están en `## Decisiones tomadas` del plan:
- un solo factor por año y grupo, sin reparto ni amortización;
- sin categoría ni atributos propios;
- autorización idéntica a `UseProfileAuthorizer`;
- nombres técnicos `processes` / `admin_processes*` / `process`.

## Mapa de piezas

Todos los ficheros son **nuevos** salvo los marcados como «modificado».

### Dominio: `src/CarbonFootprint/Domain/Model/Process/`

| Fichero | Rol |
|---|---|
| `Process.php` | Agregado raíz (`AggregateRoot`, `Translatable`, `\Stringable`); unidad por defecto `Unit::Piece` (línea 30) |
| `ProcessId.php` · `ProcessEmissionFactorId.php` | Ids UUID (`AggregateRootIdTrait`) |
| `ProcessEmissionFactor.php` · `ProcessEmissionFactors.php` | Subentidad de **un** factor y su colección (`forYearAndGroup`, `hasForYearAndGroup`, `removeForYearAndGroup`) |
| `ProcessTranslation.php` | Traducción del nombre por locale |
| `ProcessFilter.php` · `ProcessRepository.php` | Filtro (`name`, `limit = 10`) y puerto |
| `Process{Registered,Updated,Translated,Removed}.php` | Eventos del proceso (`processId`, `name`, `unit` / `locale`) |
| `ProcessEmissionFactor{Added,Updated,Removed}.php` | Eventos del factor |

### Dominio: `src/CarbonFootprint/Domain/Model/Activity/`

| Fichero | Rol |
|---|---|
| `ProcessActivity.php` | Actividad `final` con `processId` |
| `ProcessActivity{Registered,Updated,Removed}.php` | Eventos con payload plano de factor, emisiones y cantidad |
| `Answer/ProcessAnswer.php` | Respuesta de sección: `processId`, `quantity`, `existingActivityId` (sin `source`) |
| `ActivityType.php` (modificado) | Caso `Process = 'process'` (línea 15) y brazo en `fromActivity()` (línea 34) |

### Aplicación: `src/CarbonFootprint/Application/Process/`

| Fichero | Rol |
|---|---|
| `ProcessAuthorizer.php` | `SuperAdmin`/`Admin` para todas las acciones |
| `Register/RegisterProcess{Service,Request,Response}.php` | Alta (request con `unit = Unit::Piece`) |
| `Update/UpdateProcess{Service,Request,Response}.php` · `Update/EmissionFactorData.php` | Edición y conciliación de factores |
| `Remove/RemoveProcess{Service,Request,Response}.php` | Baja lógica |
| `List/ListProcesses{Service,Request,Response}.php` | Listado paginado |
| `EmissionFactor/EmissionFactorRow.php`, `EmissionFactor/{List,Update,CreateYear}/*` | Pantalla de edición en bloque y copia de año |
| `ProcessImportationType.php` · `Import/ImportProcess{Service,Request,Response}.php` | Importación `processes` |
| `Export/ProcessesExportFilter.php` | Filtro de exportación (añade `emissionFactorGroup`) |

### Aplicación: `src/CarbonFootprint/Application/Activity/`

| Fichero | Rol |
|---|---|
| `ProcessEmissionFactorSelector.php` | Factor del año y grupo; ignora `condition` |
| `Catalog/ProcessCatalogProvider.php` | Procesos con factor para el año y grupo |
| `Answer/ProcessAnswerHandler.php` | Delega en Register/Update |
| `Register/RegisterProcessActivity{Service,Request,Response}.php` · `Update/UpdateProcessActivity{Service,Request,Response}.php` | Alta y actualización con emisiones calculadas |
| `../CarbonFootprintReport/Data/ActivityItemNameResolver.php` (modificado) | Brazo `ProcessActivity` en `itemId()` (línea 82) |

### Persistencia: `src/CarbonFootprint/Infrastructure/Persistence/`

| Fichero | Rol |
|---|---|
| `Doctrine/Mapping/Process.Process.orm.xml` | Tabla `processes` |
| `Doctrine/Mapping/Process.ProcessEmissionFactor.orm.xml` | Tabla `process_emission_factor`, único `uq_process_emission_factor_year` |
| `Doctrine/Mapping/Process.ProcessTranslation.orm.xml` | Tabla `process_translation` |
| `Doctrine/Mapping/Activity.ProcessActivity.orm.xml` | Tabla hija JOINED `process_activities` |
| `Doctrine/Mapping/Activity.Activity.orm.xml` (modificado) | Discriminador `process` (línea 17) |
| `Doctrine/Type/ProcessIdType.php` · `ProcessEmissionFactorIdType.php` | Tipos DBAL registrados en `config/packages/doctrine.yaml:51-52` (modificado) |
| `Doctrine/Repository/DoctrineProcessRepository.php` | `#[AsAlias('repository.process')]` y `#[When('dev'\|'prod')]` (líneas 19-21). Alias DQL `pr`. Excluye los borrados (`:64-65`), filtra `name` (o su traducción) con LIKE (`:76`) y ordena por `name`, `registeredAt` o `updatedAt` (`:88-94`) |
| `InMemory/Repository/InMemoryProcessRepository.php` | Repositorio de test |

### Infraestructura

| Fichero | Rol |
|---|---|
| `src/CarbonFootprint/Infrastructure/UI/Web/Admin/Process/ListController.php` | `/admin/processes` (`admin_processes`) |
| `.../RegisterController.php` | `/admin/processes/register`; tras el alta redirige a la edición (`:48-49`) |
| `.../EditController.php` | `/admin/processes/edit/{processId}` |
| `.../RemoveController.php` | `/admin/processes/remove/{processId}`, `#[CsrfProtected('remove')]` (`:19`) |
| `.../EmissionFactor/FactorEmissionController.php` | `/admin/processes/emission-factors/{emissionFactorGroup?}/{year?}` |
| `.../EmissionFactor/CreateYearController.php` | `POST .../emission-factors/{emissionFactorGroup}/{sourceYear}/create-year` |
| `.../GetProcessFromIdTrait.php` · `.../Bulk/RemoveAction.php` | 404 traducible «Proceso no encontrado» (`:19`) · acción en bloque «Eliminar» |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}ProcessType.php`, `ListProcessesType.php`, `BulkProcessEmissionFactorsType.php`, `CreateProcessEmissionFactorsYearType.php`, `AdminProcessesExportType.php` | Formularios |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/Process{Choice,EmissionFactor,EmissionFactorRow}Type.php` | Tipos de campo (`ProcessChoiceType` etiqueta solo con el nombre) |
| `src/CarbonFootprint/Infrastructure/DataExchange/ProcessesExportationType.php` | Exportación `processes` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/Answer/ProcessAnswerHttpMapper.php` | Fila HTTP → `ProcessAnswer` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/View/ProcessRowViewFactory.php` | Actividad → `ActivityRowView` (`ActivityRowView` no cambia) |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (modificado) | Hijo «Procesos» (`solar:settings-line-duotone`, ruta `admin_processes`) del nodo «Productos vendidos», antes de «Perfiles de uso» (líneas 275-279) |
| `assets/icons/solar/settings-line-duotone.svg` | Icono cacheado de la entrada |
| `templates/carbon_footprint/admin/processes/{list,register,edit,emission_factors}.html.twig` | Pantallas de administración |
| `templates/components/CarbonFootprint/ProcessActivity{Table,Row}.html.twig` | Componentes de la tabla de actividad (sin uso todavía) |
| `templates/security/admin/events/detail/carbon-footprint.process*.html.twig` | Siete plantillas del Registro de auditoría |
| `translations/fields+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.{es,en,tr}.xlf` (modificados) | Clave `fields.process` («Proceso», `fields+intl-icu.es.xlf:153-154`) y los textos de interfaz, con ES, EN y TR |
| `migrations/Version20261015164918.php` | Migración de las cuatro tablas |

## Modelo de dominio

### `Process`

| Método | Evento(s) | Notas |
|---|---|---|
| `register(name, unit, ?locale)` | `ProcessRegistered` (+ `Translated`) | `Process.php:100-116` |
| `update(name, unit, ?locale)` | `ProcessUpdated` (+ `Translated`) | Con un locale distinto del por defecto se conserva el nombre base (`:128`). Si cambia la unidad, re-estampa la unidad de todos los factores sin convertir los valores (`:137-139`, `restampEmissionFactorUnits` en `:230-241`) y publica un `EmissionFactorUpdated` por cada factor |
| `translate(name, locale)` | `ProcessTranslated` | `:142-151` |
| `remove()` | `ProcessRemoved` | Baja lógica (`removed`, `removedAt`) |
| `setEmissionFactor(EmissionFactor)` | `Added` / `Updated` / nada | `:162-188`. Si el factor es igual (`equals()`), no publica nada |
| `removeEmissionFactor(year, groupId)` | `EmissionFactorRemoved` | `:190-203`. No publica nada si el factor no existe |
| `syncEmissionFactors(list<EmissionFactor>)` | Combinación de los anteriores | `:206-228`. Un duplicado `año\|grupo` lanza `\InvalidArgumentException` (`:214`) |

Invariante: **como máximo un factor por año y grupo**. La protegen la colección, `setEmissionFactor` y el índice único de BD. El factor expresa las emisiones del procesamiento por unidad del proceso. No hay reparto ni amortización.

### `ProcessActivity`

`register(id, accountId, processId, emissionFactor, emissions, quantity)` y `update(processId, emissionFactor, emissions, quantity)` (`ProcessActivity.php:23-57`) registran y aplican sus eventos. `remove()` (`:59-64`) registra y publica `Removed` sin aplicar estado, como el resto de actividades. No tiene validaciones propias.

## Flujo de una operación

### Alta, edición, factores en bloque, copia de año y baja

- **Alta:** `RegisterController` deniega el acceso si no hay `Register` (`:28`). El formulario es `RegisterProcessType`: `name` obligatorio, de 255 caracteres como máximo, y `unit` con `UnitType`. `RegisterProcessService` autoriza `List` con un agregado desechable (`:33`) y traduce si el locale no es el por defecto (`:35-42`). Después muestra el flash «Proceso creado correctamente: {name}» (`RegisterController.php:42`) y redirige a la edición.
- **Edición:**
  - `EditController` autoriza `Edit` mediante el voter (`:37`).
  - `UpdateProcessType` incluye el campo `emissionFactors` (`GroupedEmissionFactorsType`). El año debe estar entre 1900 y 2200 y los cuatro gases son obligatorios (`Type/ProcessEmissionFactorType.php`).
  - `UpdateProcessService` hace `find` o lanza `\InvalidArgumentException('Process not found')` (`:37`) y autoriza `Edit` (`:40`). Llama a `update()` y a `syncEmissionFactors()`, que ya usa la unidad actualizada. Un grupo inexistente lanza una excepción (`:78`).
  - Muestra el flash «Proceso actualizado con éxito» (`EditController.php:59`). «Guardar y salir» vuelve al listado y «Guardar» se queda en la ficha (`Shared/Infrastructure/UI/Web/CRUD/EditTrait.php`).
- **Factores en bloque:**
  - `FactorEmissionController` usa el grupo por defecto si la ruta no lo trae. Cada fila se etiqueta solo con el nombre del proceso.
  - `ListEmissionFactorsService` muestra el año pedido; si no hay, el último con factores en el grupo, y si tampoco hay, el año actual. `canGoNext` solo es verdadero si el año es anterior al actual (`:37`).
  - `UpdateEmissionFactorsService` autoriza `Edit` al inicio (`:34`) y por cada proceso (`:43`). Una fila vacía elimina el factor (`:45-46`).
  - `ProcessEmissionFactorRowType::validateAllOrNothing` exige los cuatro gases si la fila no está vacía.
- **Copia de año:**
  - `CreateYearController` delega en `CreateEmissionFactorsYearService` (`:30-66`).
  - Un destino futuro o igual al origen lanza `\InvalidArgumentException`, que se muestra como «El año destino no es válido.».
  - Si algún proceso ya tiene factores en el año destino y el grupo, lanza `EmissionFactorYearAlreadyExistsException` («El año destino ya tiene factores de emisión.») y no copia nada.
  - El formulario limita el destino a `[1900, año actual]` (`CreateProcessEmissionFactorsYearType.php`).
- **Baja:** `RemoveController` (con CSRF) o `Bulk/RemoveAction` delegan en `RemoveProcessService`. El flash individual usa el nombre del proceso; el de la acción en bloque usa el id (`Bulk/RemoveAction.php:44,51`), como en los demás maestros.

### Importación

`ProcessImportationType` tiene `code() = 'processes'`. Sus campos son `process`, `name`, `unit`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source` y `emissionFactorGroup` (`:48-62`). Traduce los valores de `process`, `unit` y `emissionFactorGroup` (`:64-71`). `isAuthorized()` comprueba `Action::Import` (`:146-153`).

`ImportProcessService` procesa cada fila así:

1. Exige un año entero mayor que 0; si no, lanza `'A valid year is required'` (`:120-129`).
2. Con `process`, hace `find`: si no existe, lanza `'Process not found'` (`:58`); si existe, autoriza `Edit` (`:61`). Sin `process`, exige `name` (`:69`) y **registra un proceso nuevo** con la unidad mapeada o `Unit::Piece` (`:75-92`). No deduplica por nombre.
3. Exige un grupo y comprueba que exista (`:105-118`).
4. Llama a `setEmissionFactor` con la unidad del proceso. Los decimales vacíos pasan a `'0'` y se acepta la coma decimal (`:131-136`).

### Exportación

`ProcessesExportationType` tiene las columnas Identificador (desmarcada), Nombre, Unidad, Año, CO2eq, CO2, CH4, N2O, Fuente y Grupo (`:45-59`). Su filtro es `AdminProcessesExportType` (Nombre y Grupo).

`rows()` (`:98-126`) emite una fila por factor:
- Un proceso sin factores emite una fila con las columnas de factor vacías.
- Con filtro de grupo se omiten los factores de otros grupos, así que un proceso cuyos factores son todos de otro grupo no emite ninguna fila.
- La unidad sale como código (`ud`, `kWh`…, `:143`).

### Actividad (todavía sin flujo HTTP)

- `ProcessAnswerHttpMapper::map()` devuelve `null` si falta `itemId`, si la unidad no es válida o si la cantidad es negativa.
- `ProcessAnswerHandler` delega en `RegisterProcessActivityService` (`:32-53`):
  1. Autoriza `Register` con `ActivityAuthorizer` (`:36`).
  2. `ProcessEmissionFactorSelector::select()` obtiene el factor. Si no existe el proceso o el factor, lanza `\InvalidArgumentException` (`ProcessEmissionFactorSelector.php:28,32`).
  3. `EmissionsCalculator::calculate()` convierte la cantidad a la unidad del factor; `ud` frente a `kg` lanza `IncompatibleUnitsException`.
  4. `ProcessActivity::register()` crea la actividad y `save()` la guarda.
- `UpdateProcessActivityService` lanza `ActivityNotFoundException` si la actividad no existe o no es de este tipo (`:36-38`), y después autoriza `Edit` (`:40`).
- `ProcessCatalogProvider::catalog()` devuelve una lista vacía si no hay grupo. Con grupo, devuelve los procesos que tienen factor para ese año (`:21-31`).

## Autorización y ámbito

`ProcessAuthorizer` permite `List` y `Edit` solo a `SuperAdmin` y `Admin`, mediante `userHasRole`. El resto de acciones se derivan de esas dos:
- `Register` y `Export` usan `canList`;
- `View` y `Remove` usan `canEdit`;
- `Import` usa `canRegister`.

Es un **maestro global**: no se acota por cuenta. `Owner` y `User` reciben 403 en todas las rutas, como comprueban los escenarios Behat. Solo `NodeLister::toAdmins()` pinta la entrada de menú. La actividad usa el `ActivityAuthorizer` genérico.

Deuda asumida (review **B2**, descartado por una decisión global): `ImportProcessService` autoriza después de resolver el año y el proceso (`:37-39`). Lo hacen así todos los servicios de importación de maestros de `CarbonFootprint`, y la importación ya está filtrada por `isAuthorized()`.

## Persistencia y migración

| Tabla | Contenido |
|---|---|
| `processes` | `id`, `name`, `unit` (`VARCHAR`), `removed`, `registered_at`, `updated_at`, `removed_at` |
| `process_translation` | PK (`locale`, `process_id`), `name`; FK `ON DELETE CASCADE` |
| `process_emission_factor` | `id`, `process_id`, `created_at`, `updated_at` y el `EmissionFactor` embebido sin prefijo (`co2eq`, `co2`, `ch4`, `n2o` en `NUMERIC(20,8)`, `year`, `emission_factor_group_id`, `unit`, `source`). Único `(process_id, year, emission_factor_group_id)`; FK `ON DELETE CASCADE` |
| `process_activities` | Hija JOINED de `activities` (`id` FK `ON DELETE CASCADE`), `process_id` |

No cambia ninguna tabla existente: `activities.type` es `VARCHAR` y admite `process`. No hay backfill.

Migración: `migrations/Version20261015164918.php`, sin commitear cuando se escribió este manual. Crea las cuatro tablas con los nombres `IDX_`/`FK_` que genera Doctrine (`IDX_CAD0F517EC2F574`, `IDX_BA1C867E7EC2F574`, `FK_1BBA8CEDBF396750`…) y en `down()` las elimina en orden inverso. Es la opción recomendada en `review-fix.md` (A1). Se aplica con `make migrate`.

## Configuración y operación

- No hay variables de entorno, colas, comandos de consola ni tareas programadas nuevas.
- `services.yaml` no cambia:
  - los repositorios se registran con `#[AsAlias]` y `#[When]`;
  - la importación y la exportación, por autoconfiguración;
  - las cinco piezas de actividad (selector, proveedor de catálogo, manejador, mapper HTTP y factory de fila), por las etiquetas `#[AutoconfigureTag]` de sus interfaces.
- No hay correos ni notificaciones propias. El Registro usa las siete plantillas `carbon-footprint.process*.html.twig`, con textos como «Se ha registrado el proceso "{process}" por el usuario {user}». Los eventos de actividad usan `default.html.twig`, como todos los tipos de actividad.

## Cómo extenderlo

**Exponer la actividad en el alcance 3 (categoría 10):**

1. Declarar la `SectionDefinition` con `ActivityType::Process` en el `Scope` (o la `Phase`) y su `Source`.
2. Pintar `<twig:CarbonFootprint:ProcessActivityTable>` con `rows`, `items` (de `ProcessCatalogProvider`) y `units`. **Trampa:** el componente solo acepta `eventId` y construye la URL con `events_carbon_footprint_emissions_calculate` (`ProcessActivityTable.html.twig:1,10`). Para un asistente organizacional hay que añadir la prop `calculateUrl`, como en `RefrigerantGasActivityTable.html.twig`.
3. El selector, el catálogo, el manejador, el mapper y la factory ya se resuelven por `ActivityType`. Quedan por revisar las exclusiones, el informe (`ActivityItemNameResolver` ya tiene el brazo) y los tests de vista que construyen registries solo con los tipos que tienen sección (`PhaseTest`, `MaterialsStepViewTest`, `GetPhaseServiceTest` o sus equivalentes organizacionales).

**Relacionar procesos con perfiles de uso o con productos:** hoy cada maestro es independiente; queda fuera de alcance del plan.

**Añadir atributos al proceso** (energía por unidad, rendimiento, merma…): hoy no existen, por decisión del plan. Habría que extender el agregado, sus eventos (con payloads que toleren eventos antiguos), los formularios, la importación y exportación y la migración.

**Trampas conocidas:**

- Cambiar la unidad de un proceso re-estampa sus factores sin convertir los valores y **no** recalcula las actividades ya registradas.
- `AbstractSectionAnswerHttpMapper::parseQuantity` trata una cantidad ausente como `0` (`AbstractSectionAnswerHttpMapper.php:34`, deuda compartida declarada en el plan).
- Una importación sin `process` siempre crea un proceso nuevo.
- Los mensajes de excepción del selector (`'Process "%s" not found.'`…) no están en los catálogos de traducción, igual que en los maestros de referencia.
- El nombre de clase `Process` no colisiona con nada del proyecto. Si un test necesitara `Symfony\Component\Process`, habría que importarlo con alias.

## Tests

| Ámbito | Ficheros |
|---|---|
| Dominio del maestro | `tests/Unit/CarbonFootprint/Domain/Model/Process/`: `ProcessTest` (incluye `testDefaultUnitIsPiece` y `testUpdatingUnitRestampsEmissionFactors`), `ProcessRepositoryTest` (abstracto) + `InMemoryProcessRepositoryTest`, `ProcessMother` y `ProcessRepositoryMother`. Además, `tests/Unit/CarbonFootprint/Infrastructure/Persistence/Doctrine/Repository/DoctrineProcessRepositoryTest.php` |
| Aplicación del maestro | `tests/Unit/CarbonFootprint/Application/Process/`: authorizer, List, Register, Update, Remove, `EmissionFactor/{CreateYear,List,Update}` e `Import` |
| Actividad | `tests/Unit/CarbonFootprint/Domain/Model/Activity/{ProcessActivityTest,ProcessActivityMother}.php` y `ActivityTypeTest::testFromActivityResolvesProcess`. En `tests/Unit/CarbonFootprint/Application/Activity/`: `Register/RegisterProcessActivityServiceTest` (`testThrowsWhenQuantityMagnitudeDiffersFromFactor`, `testThrowsWhenNoFactorForYear`, `testUnauthorizedAccess`), `Update/`, `ProcessEmissionFactorSelectorTest`, `Catalog/` y `Answer/` |
| Piezas de UI | `tests/Unit/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/{Answer/ProcessAnswerHttpMapperTest,View/ProcessRowViewFactoryTest}.php` |
| Aceptación | `tests/Acceptance/CarbonFootprint/process-{register,list,edit,remove,import,export,emission-factors}.feature`, con 27 escenarios; el de edición cambia la unidad a `m2`. Los pasos `process :name with id :id exists` y `process :name with id :id and emission factor for year :year exists` están en `CarbonFootprintContext.php:424-452` |

Para ejecutarlos (el `Makefile` no define `test-this`):

```bash
make test-unit options=tests/Unit/CarbonFootprint/Domain/Model/Process
make test-unit options=tests/Unit/CarbonFootprint/Application/Process
make test-acceptance options=tests/Acceptance/CarbonFootprint/process-edit.feature
make test-parallel
```

Huecos: no hay `.feature` para la actividad, porque no existe ningún flujo HTTP que la registre. Tampoco está cubierto el caso «cantidad ausente → `Quantity(0)`».

## Desviaciones y deuda

Ver `## Implementación` de [`plan.md`](plan.md), [`review.md`](review.md) y [`review-fix.md`](review-fix.md):

- Sin desviaciones funcionales. Los datos de prueba copiados se sustituyeron por otros del dominio del proceso («Mecanizado de pieza», «Envasado», «Proceso 1»).
- **A1** (migración ausente): ya existe en `migrations/Version20261015164918.php`, con los nombres de Doctrine. `review.md` no se ha vuelto a revisar después.
- **B1** (alias DQL `up` heredado de la copia): corregido a `pr` en `DoctrineProcessRepository.php` según `review-fix.md`.
- **B2**: descartado por una decisión global (orden de autorización en los servicios de importación).
- Deuda compartida declarada: `AbstractSectionAnswerHttpMapper.php:34` trata una cantidad ausente como 0.
