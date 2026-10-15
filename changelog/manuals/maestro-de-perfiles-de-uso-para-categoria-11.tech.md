# Perfiles de uso (maestro de la categoría 11 del alcance 3 y su actividad) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `CarbonFootprint` (más el menú lateral de `Shared` y los tipos DBAL de `config/packages/doctrine.yaml`)

## Qué hace y por qué

La categoría 11 del alcance 3 del GHG Protocol, «Uso de productos vendidos» (`StandardScope3Category::UseOfSoldProducts`), necesita un catálogo de **perfiles de uso**: cómo se usa un producto o servicio que vende la organización y cuántas emisiones genera ese uso durante toda su vida útil, expresado como factor por unidad del perfil (por defecto, por unidad vendida). Este plan añade ese maestro y la actividad que lo consumirá.

Se añaden tres cosas:

1. El maestro global `UseProfile`, calco uno a uno de `PurchaseService` (plan `2026-10-15-bienes-y-servicios-comprados`) **sin categoría**: solo nombre traducible y unidad, como `Combustible`. Alta, edición con factores por año y grupo, baja lógica, listado filtrable por nombre, edición en bloque de factores con copia de año, importación, exportación, acción en bloque de eliminar y trazas en el Registro. Unidad elegible con **`Unit::Piece` (`ud`) por defecto**.
2. Un nodo de menú de primer nivel nuevo, «Productos vendidos», entre «Materiales» e «Instalaciones», pensado para alojar los maestros de las categorías 10 y 11; hoy solo contiene «Perfiles de uso».
3. La actividad `UseProfileActivity` con todas sus piezas enchufables, sin atributos de línea propios. **Ninguna fase de la huella de eventos ni ningún alcance organizacional la usa todavía**: queda preparada para el futuro plan del alcance 3.

Decisiones clave en `## Decisiones tomadas` del plan: un solo factor por año y grupo que cubre toda la vida útil (sin amortización); sin categoría ni atributos propios (vida útil, consumo, usos); autorización idéntica a `CapitalGoodAuthorizer`.

## Mapa de piezas

Todos los ficheros son **nuevos** salvo donde se indica «modificado».

### Dominio — `src/CarbonFootprint/Domain/Model/UseProfile/`

| Fichero | Rol |
|---|---|
| `UseProfile.php` | Agregado raíz (`AggregateRoot`, `Translatable`, `\Stringable`); unidad por defecto `Unit::Piece` (línea 30) |
| `UseProfileId.php` · `UseProfileEmissionFactorId.php` | Ids UUID (`AggregateRootIdTrait`) |
| `UseProfileEmissionFactor.php` · `UseProfileEmissionFactors.php` | Subentidad de **un** factor y su colección (`forYearAndGroup`, `hasForYearAndGroup`, `removeForYearAndGroup`) |
| `UseProfileTranslation.php` | Traducción del nombre por locale |
| `UseProfileFilter.php` · `UseProfileRepository.php` | Filtro (`name`, `limit = 10`) y puerto |
| `UseProfile{Registered,Updated,Translated,Removed}.php` | Eventos del perfil (`useProfileId`, `name`, `unit` / `locale`) |
| `UseProfileEmissionFactor{Added,Updated,Removed}.php` | Eventos del factor |

### Dominio — `src/CarbonFootprint/Domain/Model/Activity/`

| Fichero | Rol |
|---|---|
| `UseProfileActivity.php` | Actividad `final` con `useProfileId` |
| `UseProfileActivity{Registered,Updated,Removed}.php` | Eventos con payload plano de factor, emisiones y cantidad |
| `Answer/UseProfileAnswer.php` | Respuesta de sección: `useProfileId`, `quantity`, `existingActivityId` (sin `source`, como `PurchaseServiceAnswer`) |
| `ActivityType.php` (modificado) | Caso `UseProfile = 'use_profile'` (línea 14) y brazo en `fromActivity()` (línea 32) |

### Aplicación — `src/CarbonFootprint/Application/UseProfile/`

| Fichero | Rol |
|---|---|
| `UseProfileAuthorizer.php` | `SuperAdmin`/`Admin` para todas las acciones |
| `Register/RegisterUseProfile{Service,Request,Response}.php` | Alta (request con `unit = Unit::Piece`) |
| `Update/UpdateUseProfile{Service,Request,Response}.php` · `Update/EmissionFactorData.php` | Edición + conciliación de factores |
| `Remove/RemoveUseProfile{Service,Request,Response}.php` | Baja lógica |
| `List/ListUseProfiles{Service,Request,Response}.php` | Listado paginado |
| `EmissionFactor/EmissionFactorRow.php`, `EmissionFactor/{List,Update,CreateYear}/*` | Pantalla de edición en bloque y copia de año |
| `UseProfileImportationType.php` · `Import/ImportUseProfile{Service,Request,Response}.php` | Importación `use_profiles` |
| `Export/UseProfilesExportFilter.php` | Filtro de exportación (añade `emissionFactorGroup`) |

### Aplicación — `src/CarbonFootprint/Application/Activity/`

| Fichero | Rol |
|---|---|
| `UseProfileEmissionFactorSelector.php` | Factor del año y grupo; ignora `condition` |
| `Catalog/UseProfileCatalogProvider.php` | Perfiles con factor para el año y grupo |
| `Answer/UseProfileAnswerHandler.php` | Delega en Register/Update |
| `Register/RegisterUseProfileActivity{Service,Request,Response}.php` · `Update/UpdateUseProfileActivity{Service,Request,Response}.php` | Alta y actualización con emisiones calculadas |
| `../CarbonFootprintReport/Data/ActivityItemNameResolver.php` (modificado) | Brazo `UseProfileActivity` en `itemId()` (línea 80) |

### Persistencia — `src/CarbonFootprint/Infrastructure/Persistence/`

| Fichero | Rol |
|---|---|
| `Doctrine/Mapping/UseProfile.UseProfile.orm.xml` | Tabla `use_profiles` |
| `Doctrine/Mapping/UseProfile.UseProfileEmissionFactor.orm.xml` | Tabla `use_profile_emission_factor`, único `uq_use_profile_emission_factor_year` |
| `Doctrine/Mapping/UseProfile.UseProfileTranslation.orm.xml` | Tabla `use_profile_translation` |
| `Doctrine/Mapping/Activity.UseProfileActivity.orm.xml` | Tabla hija JOINED `use_profile_activities` |
| `Doctrine/Mapping/Activity.Activity.orm.xml` (modificado) | Discriminador `use_profile` (línea 16) |
| `Doctrine/Type/UseProfileIdType.php` · `UseProfileEmissionFactorIdType.php` | Tipos DBAL registrados en `config/packages/doctrine.yaml:49-50` (modificado) |
| `Doctrine/Repository/DoctrineUseProfileRepository.php` | `#[AsAlias('repository.use_profile')]`, `#[When('dev'|'prod')]` (líneas 19-21); excluye borrados, filtra `name` (o su traducción) con LIKE, ordena por `name`, `registeredAt` o `updatedAt` (`:86-94`) |
| `InMemory/Repository/InMemoryUseProfileRepository.php` | Repositorio de test |

### Infraestructura

| Fichero | Rol |
|---|---|
| `src/CarbonFootprint/Infrastructure/UI/Web/Admin/UseProfile/ListController.php` | `/admin/use-profiles` (`admin_use_profiles`) |
| `.../RegisterController.php` | `/admin/use-profiles/register`; redirige a edición tras el alta (`:48-50`) |
| `.../EditController.php` | `/admin/use-profiles/edit/{useProfileId}` |
| `.../RemoveController.php` | `/admin/use-profiles/remove/{useProfileId}`, `#[CsrfProtected('remove')]` |
| `.../EmissionFactor/FactorEmissionController.php` | `/admin/use-profiles/emission-factors/{emissionFactorGroup?}/{year?}` |
| `.../EmissionFactor/CreateYearController.php` | `POST .../emission-factors/{emissionFactorGroup}/{sourceYear}/create-year` |
| `.../GetUseProfileFromIdTrait.php` · `.../Bulk/RemoveAction.php` | 404 traducible «Perfil de uso no encontrado» (`:19`) · acción en bloque «Eliminar» |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}UseProfileType.php`, `ListUseProfilesType.php`, `BulkUseProfileEmissionFactorsType.php`, `CreateUseProfileEmissionFactorsYearType.php`, `AdminUseProfilesExportType.php` | Formularios |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/UseProfile{Choice,EmissionFactor,EmissionFactorRow}Type.php` | Tipos de campo (`UseProfileChoiceType` etiqueta solo con el nombre) |
| `src/CarbonFootprint/Infrastructure/DataExchange/UseProfilesExportationType.php` | Exportación `use_profiles` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/Answer/UseProfileAnswerHttpMapper.php` | Fila HTTP → `UseProfileAnswer` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/View/UseProfileRowViewFactory.php` | Actividad → `ActivityRowView` (`ActivityRowView` no cambia) |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (modificado) | Nodo «Productos vendidos» (`solar:box-line-duotone`, `#productos-vendidos`) con hijo «Perfiles de uso» (`solar:tuning-3-line-duotone`), líneas 271-282 |
| `assets/icons/solar/box-line-duotone.svg` | Icono cacheado del nodo |
| `templates/carbon_footprint/admin/use_profiles/{list,register,edit,emission_factors}.html.twig` | Pantallas de administración |
| `templates/components/CarbonFootprint/UseProfileActivity{Table,Row}.html.twig` | Componentes de la tabla de actividad (sin uso todavía) |
| `templates/security/admin/events/detail/carbon-footprint.useProfile*.html.twig` | Siete plantillas del Registro de auditoría |
| `translations/fields+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.{es,en,tr}.xlf` (modificados) | Clave `fields.useProfile` («Perfil de uso») y 17 textos de interfaz, con ES, EN y TR |
| `migrations/Version20261015145115.php` | Migración de las cuatro tablas |

## Modelo de dominio

### `UseProfile`

Mismos métodos y semántica que `PurchaseService`, sin categoría:

| Método | Evento(s) | Notas |
|---|---|---|
| `register(name, unit, ?locale)` | `UseProfileRegistered` (+ `Translated`) | `UseProfile.php:100-116` |
| `update(name, unit, ?locale)` | `UseProfileUpdated` (+ `Translated`) | Con locale no por defecto se conserva el nombre base (`:128`). Si cambia la unidad, re-estampa la unidad de todos los factores (`:137-139`, `restampEmissionFactorUnits` `:230-241`) sin convertir valores; publica un `EmissionFactorUpdated` por factor |
| `translate(name, locale)` | `UseProfileTranslated` | |
| `remove()` | `UseProfileRemoved` | Baja lógica (`removed`, `removedAt`) |
| `setEmissionFactor(EmissionFactor)` | `Added` / `Updated` / nada | `:162-188`. Factor igual (`equals()`) no publica |
| `removeEmissionFactor(year, groupId)` | `EmissionFactorRemoved` | `:190-203`. No publica si no existe |
| `syncEmissionFactors(list<EmissionFactor>)` | Combinación | `:206-228`. Duplicado `año|grupo` → `\InvalidArgumentException` (`:214`) |

Invariante: **como máximo un factor por año y grupo** (colección, `setEmissionFactor` e índice único de BD). El factor expresa las emisiones de uso de toda la vida útil por unidad del perfil; no hay amortización ni reparto por años.

### `UseProfileActivity`

`register(id, accountId, useProfileId, emissionFactor, emissions, quantity)` y `update(useProfileId, emissionFactor, emissions, quantity)` (`UseProfileActivity.php:23-57`) registran y aplican sus eventos; `remove()` (`:59-64`) registra y publica `Removed` sin aplicar estado, como el resto de actividades. Sin validaciones propias.

## Flujo de una operación

### Alta, edición, factores en bloque, copia de año, baja

- **Alta:** `RegisterController` (deniega si no hay `Register`, `:28`) → `RegisterUseProfileType` (`name` obligatorio, máx. 255; `unit` con `UnitType`) → `RegisterUseProfileService` (autoriza `List` con agregado desechable, `:33`; traduce si el locale no es el por defecto, `:35-43`) → flash «Perfil de uso creado correctamente: {name}» (`RegisterController.php:42`) → redirección a edición.
- **Edición:** `EditController` (autoriza `Edit` vía voter, `:37`) → `UpdateUseProfileType` (campo `emissionFactors` `GroupedEmissionFactorsType`; año entre 1900 y 2200 y los cuatro gases obligatorios en `Type/UseProfileEmissionFactorType.php:24-66`) → `UpdateUseProfileService` (`find` o `\InvalidArgumentException('UseProfile not found')` `:37`; autoriza `Edit` `:40`; `update()` y `syncEmissionFactors()` con la unidad ya actualizada `:45-61`; grupo inexistente → excepción `:78`) → flash «Perfil de uso actualizado con éxito» (`EditController.php:59`).
- **Factores en bloque:** `FactorEmissionController` (grupo por defecto si no viene en la ruta, `:50-52`; fila etiquetada solo con el nombre) → `ListEmissionFactorsService` (año pedido, o el último con factores en el grupo, o el actual, `:31`; `canGoNext` solo si el año es anterior al actual, `:37`) → `UpdateEmissionFactorsService` (autoriza `Edit` al inicio `:34` y por perfil `:43`; fila vacía elimina el factor `:45-46`). `UseProfileEmissionFactorRowType::validateAllOrNothing` exige los cuatro gases si la fila no está vacía.
- **Copia de año:** `CreateYearController` → `CreateEmissionFactorsYearService` (`:32-66`): destino futuro o igual al origen → `\InvalidArgumentException` («El año destino no es válido.»); algún perfil con factores en el destino y grupo → `EmissionFactorYearAlreadyExistsException` («El año destino ya tiene factores de emisión.»), sin copiar nada. El formulario limita el destino a `[1900, año actual]` (`CreateUseProfileEmissionFactorsYearType.php:31`).
- **Baja:** `RemoveController` (CSRF) o `Bulk/RemoveAction` → `RemoveUseProfileService`. El flash individual usa el nombre; el de la acción en bloque, el id (`Bulk/RemoveAction.php:44,51`), como en los demás maestros.

### Importación

`UseProfileImportationType` (`code() = 'use_profiles'`): campos `useProfile`, `name`, `unit`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source`, `emissionFactorGroup` (`:48-62`); traducción de valores para `useProfile`, `unit` y `emissionFactorGroup` (`:64-71`); `isAuthorized()` con `Action::Import` (`:146-153`). Por fila, `ImportUseProfileService`:

1. Año entero > 0 o `'A valid year is required'` (`:120-129`).
2. Con `useProfile` → `find` (no existe → `'Use profile not found'`, `:58`) y autoriza `Edit` (`:61`); sin él exige `name` (`:69`) y **registra un perfil nuevo** con la unidad mapeada o `Unit::Piece` (`:75-92`). No deduplica por nombre.
3. Grupo obligatorio y existente (`:105-118`).
4. `setEmissionFactor` con la unidad del perfil; decimales vacíos → `'0'`, coma decimal aceptada (`:131-136`).

### Exportación

`UseProfilesExportationType`: columnas Identificador (desmarcada), Nombre, Unidad, Año, CO2eq, CO2, CH4, N2O, Fuente, Grupo (`:45-59`); filtro `AdminUseProfilesExportType` (Nombre, Grupo). `rows()` (`:98-126`) emite una fila por factor; un perfil sin factores emite una fila con columnas de factor vacías; con filtro de grupo se omiten los factores de otros grupos (un perfil cuyos factores son todos de otro grupo no emite filas). La unidad sale como código (`ud`, `kWh`…, `:143`).

### Actividad (sin flujo HTTP todavía)

`UseProfileAnswerHttpMapper::map()` (`:19-33`) → `null` sin `itemId`, con unidad inválida o cantidad negativa. `UseProfileAnswerHandler` → `RegisterUseProfileActivityService` (`:32-53`): autoriza `Register` con `ActivityAuthorizer` (`:36`) → `UseProfileEmissionFactorSelector::select()` (perfil o factor inexistente → `\InvalidArgumentException`, `UseProfileEmissionFactorSelector.php:28,32`) → `EmissionsCalculator::calculate()` (convierte la cantidad a la unidad del factor; `ud` frente a `kg` lanza `IncompatibleUnitsException`) → `UseProfileActivity::register()` → `save()`. `UpdateUseProfileActivityService` lanza `ActivityNotFoundException` si la actividad no existe o no es de este tipo (`:36-38`) y después autoriza `Edit` (`:40`). `UseProfileCatalogProvider::catalog()` devuelve vacío sin grupo y, con grupo, los perfiles con factor para el año (`:21-31`).

## Autorización y ámbito

`UseProfileAuthorizer`: `List` y `Edit` solo para `SuperAdmin` y `Admin` (vía `userHasRole`, `:24-32`); `Register` y `Export` → `canList`; `View` y `Remove` → `canEdit`; `Import` → `canRegister`. **Maestro global**: sin *scoping* por cuenta. `Owner` y `User` reciben 403 en todas las rutas (escenarios Behat). El menú solo lo pinta `NodeLister::toAdmins()`. La actividad usa el `ActivityAuthorizer` genérico.

Deuda asumida (review **B1**, descartado por decisión global): `ImportUseProfileService` autoriza tras resolver año y perfil, como los demás servicios de importación de maestros de `CarbonFootprint`; la importación ya está filtrada por `isAuthorized()`.

## Persistencia y migración

| Tabla | Contenido |
|---|---|
| `use_profiles` | `id`, `name`, `unit` (`VARCHAR`), `removed`, `registered_at`, `updated_at`, `removed_at` |
| `use_profile_translation` | PK (`locale`, `use_profile_id`), `name`; FK `ON DELETE CASCADE` |
| `use_profile_emission_factor` | `id`, `use_profile_id`, `created_at`, `updated_at` + `EmissionFactor` embebido sin prefijo (`co2eq`, `co2`, `ch4`, `n2o` `NUMERIC(20,8)`, `year`, `emission_factor_group_id`, `unit`, `source`); único `(use_profile_id, year, emission_factor_group_id)`; FK `ON DELETE CASCADE` |
| `use_profile_activities` | Hija JOINED de `activities` (`id` FK `ON DELETE CASCADE`), `use_profile_id` |

Sin cambios en tablas existentes: `activities.type` es `VARCHAR` y admite `use_profile`. Sin backfill.

Migración: `migrations/Version20261015145115.php` (sin commitear al escribir este manual) crea las cuatro tablas con los nombres `IDX_`/`FK_` que genera Doctrine (`IDX_74E3A9B6CC8EFC91`, `IDX_15BF482CC8EFC91`, `FK_578F7358BF396750`…) y las elimina en orden inverso en `down()`; es la opción 1 recomendada en `review-fix.md` (A1). Aplicar con `make migrate`.

## Configuración y operación

- Sin variables de entorno, colas, comandos de consola ni tareas programadas nuevas.
- `services.yaml` no cambia: repositorios por `#[AsAlias]`/`#[When]`, importación y exportación por autoconfiguración y las cinco piezas de actividad por las etiquetas `#[AutoconfigureTag]` de sus interfaces (selector, proveedor de catálogo, manejador, mapper HTTP, factory de fila).
- Sin correos ni notificaciones propias; el Registro usa las siete plantillas `carbon-footprint.useProfile*.html.twig`. Los eventos de actividad usan `default.html.twig`, como todos los tipos de actividad.

## Cómo extenderlo

**Exponer la actividad en el alcance 3 (categoría 11):**

1. Declarar la `SectionDefinition` con `ActivityType::UseProfile` en el `Scope` (o `Phase`) y su `Source`.
2. Pintar `<twig:CarbonFootprint:UseProfileActivityTable>` con `rows`, `items` (de `UseProfileCatalogProvider`) y `units`. **Trampa:** el componente solo acepta `eventId` y construye la URL con `events_carbon_footprint_emissions_calculate` (`UseProfileActivityTable.html.twig:1,10`); para un asistente organizacional hay que añadir la prop `calculateUrl` como en `RefrigerantGasActivityTable.html.twig`.
3. Selector, catálogo, handler, mapper y factory ya se resuelven por `ActivityType`. Revisar exclusiones, informe (`ActivityItemNameResolver` ya tiene brazo) y los tests de vista que construyen registries solo con los tipos que tienen sección (`PhaseTest`, `MaterialsStepViewTest`, `GetPhaseServiceTest` o sus equivalentes organizacionales).

**Añadir el maestro de la categoría 10 («Procesado de productos vendidos»):** colgarlo como segundo hijo del nodo «Productos vendidos» de `NodeLister.php:271-282`, calcando este maestro.

**Añadir atributos al perfil (vida útil, consumo anual…):** hoy no existen por decisión del plan; habría que extender el agregado, sus eventos (con payloads tolerantes a eventos antiguos), formularios, import/export y migración.

**Trampas conocidas:**

- Cambiar la unidad de un perfil re-estampa sus factores sin convertir valores y **no** recalcula actividades ya registradas.
- `AbstractSectionAnswerHttpMapper::parseQuantity` trata una cantidad ausente como `0` (`AbstractSectionAnswerHttpMapper.php:31-41`, deuda compartida declarada en el plan).
- La importación sin `useProfile` siempre crea un perfil nuevo.
- Los mensajes de excepción del selector (`'UseProfile "%s" not found.'`…) no están en los catálogos de traducción, como en los maestros de referencia.

## Tests

| Ámbito | Ficheros |
|---|---|
| Dominio del maestro | `tests/Unit/CarbonFootprint/Domain/Model/UseProfile/` — `UseProfileTest`, `UseProfileRepositoryTest` (abstracto) + `InMemoryUseProfileRepositoryTest`, `UseProfileMother`, `UseProfileRepositoryMother`; `tests/Unit/CarbonFootprint/Infrastructure/Persistence/Doctrine/Repository/DoctrineUseProfileRepositoryTest.php` |
| Aplicación del maestro | `tests/Unit/CarbonFootprint/Application/UseProfile/` — authorizer, List, Register (`ud` por defecto, traducción), Update (re-estampado de unidad), Remove, `EmissionFactor/{CreateYear,List,Update}`, `Import` |
| Actividad | `tests/Unit/CarbonFootprint/Domain/Model/Activity/{UseProfileActivityTest,UseProfileActivityMother,ActivityTypeTest}.php`; `tests/Unit/CarbonFootprint/Application/Activity/` — Register (`testThrowsWhenQuantityMagnitudeDiffersFromFactor`: `ud` frente a `kg`; sin factor para el año), Update, selector, `Catalog/`, `Answer/` |
| Piezas de UI | `tests/Unit/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/{Answer/UseProfileAnswerHttpMapperTest,View/UseProfileRowViewFactoryTest}.php` |
| Aceptación | `tests/Acceptance/CarbonFootprint/use-profile-{register,list,edit,remove,import,export,emission-factors}.feature` (27 escenarios; el de edición cambia la unidad a `m2`); pasos `use profile :name with id :id exists` y `... and emission factor for year :year exists` en `CarbonFootprintContext.php:386-414` |

Ejecutar:

```bash
make test-unit options=tests/Unit/CarbonFootprint/Domain/Model/UseProfile
make test-unit options=tests/Unit/CarbonFootprint/Application/UseProfile
make test-acceptance options=tests/Acceptance/CarbonFootprint/use-profile-edit.feature
make test-parallel
```

Huecos: no hay `.feature` para la actividad (no existe flujo HTTP que la registre); «cantidad ausente → `Quantity(0)`» no está cubierto.

## Desviaciones y deuda

Ver `## Implementación` de [`plan.md`](plan.md), [`review.md`](review.md) y [`review-fix.md`](review-fix.md):

- `make icon-lock` no importa iconos usados solo en `NodeLister.php`; `box-line-duotone` se importó con `make icon-import p=solar:box-line-duotone` y se retiraron los iconos ajenos que trajo `icon-lock`.
- El Makefile no tiene `test-this`; los tests por fichero se lanzan con `make test-unit options=…` / `make test-acceptance options=…`.
- **A1** (migración ausente): la migración existe ya en `migrations/Version20261015145115.php` con los nombres de Doctrine; `review.md` no se ha re-revisado.
- **B2** (el plan describe un `source` en `UseProfileAnswer` que el código no tiene): asumible según `review-fix.md`; el código sigue a `PurchaseServiceAnswer`.
- **B1**: descartado por decisión global (orden de autorización en los servicios de importación).
- Deuda declarada: los traits `Get{PurchaseService,CapitalGood}FromIdTrait` siguen con el 404 en texto plano; este maestro ya lo traduce.
