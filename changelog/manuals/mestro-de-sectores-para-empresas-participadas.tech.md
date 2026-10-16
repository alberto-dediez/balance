# Sectores económicos (maestro de la categoría 15 del alcance 3 y su actividad) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `CarbonFootprint` (más el menú lateral de `Shared` y los tipos DBAL de `config/packages/doctrine.yaml`)

## Qué hace y por qué

La categoría 15 del alcance 3 del GHG Protocol, «Inversiones» (`StandardScope3Category::Investments`), necesita un catálogo de **sectores económicos**: la actividad de una empresa participada y la intensidad de emisiones de esa actividad por unidad monetaria. Será el catálogo con el que el cliente declare «participo en una empresa del sector X con un importe de N». Este plan añade el maestro y la actividad que lo consumirá.

Se añaden tres cosas:

1. El maestro global `EconomicSector`, calco uno a uno de `UseProfile` (plan `2026-10-15-maestro-de-perfiles-de-uso-para-categoria-11`) con **dos diferencias de modelo**:
   - La unidad es **siempre una divisa** (`Magnitude::Currency`): `Unit::Euro` por defecto; el agregado rechaza cualquier otra magnitud con `NonCurrencyUnitException`, los formularios solo ofrecen `Unit::currencies()` (`CurrencyUnitType`) y la importación mapea con `CurrencyUnitCodeType`.
   - Un **código de clasificación opcional** (`classificationCode`, CNAE 2009 o NACE Rev. 2, máx. 20 caracteres), normalizado a mayúsculas sin espacios en los extremos y **único entre los sectores no eliminados**. Se lista, se filtra por prefijo, se importa, se exporta y la importación lo usa para localizar un sector existente.
2. Un nodo de menú de primer nivel nuevo, «Inversiones», entre «Productos vendidos» e «Instalaciones», con un único hijo «Sectores económicos».
3. La actividad `EconomicSectorActivity` con todas sus piezas enchufables, sin atributos de línea propios (la cantidad es un importe en divisa). **Ninguna fase de la huella de eventos ni ningún alcance organizacional la usa todavía**: queda preparada para el plan del alcance 3.

Decisiones clave en `## Decisiones tomadas` del plan: un único campo de código sin esquema (`6201` y `62.01` son códigos distintos); unicidad comprobada en servicios y no en base de datos (para poder reutilizar el código de un sector eliminado); la importación localiza por id, luego por código y luego crea por nombre; etiquetas con «económico(s)» para no chocar con el maestro «Sectores» de Materialidad (`MaterialitySector`).

## Mapa de piezas

Todos los ficheros son **nuevos** salvo donde se indica «modificado».

### Dominio — `src/CarbonFootprint/Domain/Model/EconomicSector/`

| Fichero | Rol |
|---|---|
| `EconomicSector.php` | Agregado raíz (`AggregateRoot`, `Translatable`, `\Stringable`); unidad por defecto `Unit::Euro` y `classificationCode` en el constructor (líneas 27-40) |
| `EconomicSectorId.php` · `EconomicSectorEmissionFactorId.php` | Ids UUID (`AggregateRootIdTrait`) |
| `EconomicSectorEmissionFactor.php` · `EconomicSectorEmissionFactors.php` | Subentidad de **un** factor y su colección (`forYearAndGroup`, `hasForYearAndGroup`, `removeForYearAndGroup`) |
| `EconomicSectorTranslation.php` | Traducción del nombre por locale |
| `EconomicSectorFilter.php` | Filtro (`name`, `classificationCode`, `limit = 10`) |
| `EconomicSectorRepository.php` | Puerto: `add`, `remove`, `find`, `findByClassificationCode`, `findByFilter`, `totalResultsByFilter` |
| `EconomicSector{Registered,Updated,Translated,Removed}.php` | Eventos del sector (`Registered`/`Updated` llevan `classificationCode`) |
| `EconomicSectorEmissionFactor{Added,Updated,Removed}.php` | Eventos del factor |
| `NonCurrencyUnitException.php` | `\InvalidArgumentException`: `Unit "%s" is not a currency.` |
| `EconomicSectorCodeAlreadyExistsException.php` | `\DomainException` con `code(): string`: `An economic sector with code "%s" already exists.` |

### Dominio — `src/CarbonFootprint/Domain/Model/Activity/`

| Fichero | Rol |
|---|---|
| `EconomicSectorActivity.php` | Actividad `final` con `economicSectorId` (`register` :23, `update` :44, `remove` :59) |
| `EconomicSectorActivity{Registered,Updated,Removed}.php` | Eventos con payload plano de factor, emisiones y cantidad |
| `Answer/EconomicSectorAnswer.php` | Respuesta de sección: `economicSectorId`, `quantity`, `existingActivityId` (sin `source`, ver review B1) |
| `ActivityType.php` (modificado) | Caso `EconomicSector = 'economic_sector'` (línea 16) y brazo en `fromActivity()` (línea 36) |

### Aplicación — `src/CarbonFootprint/Application/EconomicSector/`

| Fichero | Rol |
|---|---|
| `EconomicSectorAuthorizer.php` | `SuperAdmin`/`Admin` para todas las acciones |
| `ClassificationCodeNormalizer.php` | `normalize(?string): ?string` — `trim` + `mb_strtoupper`; vacío → `null` |
| `Register/RegisterEconomicSector{Service,Request,Response}.php` | Alta (request con `unit = Unit::Euro`) y comprobación de código duplicado |
| `Update/UpdateEconomicSector{Service,Request,Response}.php` · `Update/EmissionFactorData.php` | Edición, comprobación de código duplicado (excluyendo el propio sector) y conciliación de factores |
| `Remove/RemoveEconomicSector{Service,Request,Response}.php` | Baja lógica |
| `List/ListEconomicSectors{Service,Request,Response}.php` | Listado paginado |
| `EmissionFactor/EmissionFactorRow.php`, `EmissionFactor/{List,Update,CreateYear}/*` | Pantalla de edición en bloque y copia de año; la fila lleva también `classificationCode` |
| `EconomicSectorImportationType.php` · `Import/ImportEconomicSector{Service,Request,Response}.php` | Importación `economic_sectors` |
| `Export/EconomicSectorsExportFilter.php` | Filtro de exportación (añade `emissionFactorGroup`) |

### Aplicación — `src/CarbonFootprint/Application/Activity/`

| Fichero | Rol |
|---|---|
| `EconomicSectorEmissionFactorSelector.php` | Factor del año y grupo; ignora `condition` |
| `Catalog/EconomicSectorCatalogProvider.php` | Sectores con factor para el año y grupo |
| `Answer/EconomicSectorAnswerHandler.php` | Delega en Register/Update |
| `Register/RegisterEconomicSectorActivity{Service,Request,Response}.php` · `Update/UpdateEconomicSectorActivity{Service,Request,Response}.php` | Alta y actualización con emisiones calculadas |
| `../CarbonFootprintReport/Data/ActivityItemNameResolver.php` (modificado) | Brazo `EconomicSectorActivity` en `itemId()` (línea 84) |

### Persistencia — `src/CarbonFootprint/Infrastructure/Persistence/`

| Fichero | Rol |
|---|---|
| `Doctrine/Mapping/EconomicSector.EconomicSector.orm.xml` | Tabla `economic_sectors`, columna `classification_code` `VARCHAR(20)` nullable con índice no único `idx_economic_sector_classification_code` |
| `Doctrine/Mapping/EconomicSector.EconomicSectorEmissionFactor.orm.xml` | Tabla `economic_sector_emission_factor`, único `uq_economic_sector_emission_factor_year` |
| `Doctrine/Mapping/EconomicSector.EconomicSectorTranslation.orm.xml` | Tabla `economic_sector_translation` |
| `Doctrine/Mapping/Activity.EconomicSectorActivity.orm.xml` | Tabla hija JOINED `economic_sector_activities` |
| `Doctrine/Mapping/Activity.Activity.orm.xml` (modificado) | Discriminador `economic_sector` (línea 18) |
| `Doctrine/Type/EconomicSectorIdType.php` · `EconomicSectorEmissionFactorIdType.php` | Tipos DBAL registrados en `config/packages/doctrine.yaml:53-54` (modificado) |
| `Doctrine/Repository/DoctrineEconomicSectorRepository.php` | `#[AsAlias('repository.economic_sector')]`, `#[When('dev'|'prod')]` (líneas 19-21); excluye borrados (`:77-82`), `findByClassificationCode` por igualdad exacta (`:55-68`), filtro de código por prefijo (`:101-108`), orden por `name`, `registeredAt`, `updatedAt` o `classificationCode` (`:112-117`) |
| `InMemory/Repository/InMemoryEconomicSectorRepository.php` | Repositorio de test con la misma semántica (`:50`, `:76`, `:90-91`) |

### Infraestructura

| Fichero | Rol |
|---|---|
| `src/CarbonFootprint/Infrastructure/UI/Web/Admin/EconomicSector/ListController.php` | `/admin/economic-sectors` (`admin_economic_sectors`) |
| `.../RegisterController.php` | `/admin/economic-sectors/register`; código duplicado → `FormError` (`:42-46`); redirige a edición tras el alta (`:55-58`) |
| `.../EditController.php` | `/admin/economic-sectors/edit/{economicSectorId}`; código duplicado → `FormError` (`:60-66`); redirige solo si `$needsRedirect && $form->isValid()` (`:71`) |
| `.../RemoveController.php` | `/admin/economic-sectors/remove/{economicSectorId}`, `#[CsrfProtected('remove')]` (`:19`) |
| `.../EmissionFactor/FactorEmissionController.php` | `/admin/economic-sectors/emission-factors/{emissionFactorGroup?}/{year?}` |
| `.../EmissionFactor/CreateYearController.php` | `POST .../emission-factors/{emissionFactorGroup}/{sourceYear}/create-year` |
| `.../GetEconomicSectorFromIdTrait.php` · `.../Bulk/RemoveAction.php` | 404 traducible «Sector económico no encontrado» (`:19`) · acción en bloque «Eliminar» |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}EconomicSectorType.php`, `ListEconomicSectorsType.php`, `BulkEconomicSectorEmissionFactorsType.php`, `CreateEconomicSectorEmissionFactorsYearType.php`, `AdminEconomicSectorsExportType.php` | Formularios (campo `classificationCode` con `Length(max: 20)` y ayuda «Código CNAE 2009 o NACE Rev. 2, opcional. Debe ser único.»; `unit` con `CurrencyUnitType` y etiqueta «Divisa») |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/CurrencyUnitType.php` · `CurrencyUnitCodeType.php` | Tipos reutilizables: hijos de `UnitType`/`UnitCodeType` con `choices` restringidas a `Unit::currencies()` (`:14-30` de cada uno) |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/EconomicSector{Choice,EmissionFactor,EmissionFactorRow}Type.php` | Tipos de campo; `EconomicSectorChoiceType` etiqueta `"{código} · {nombre}"` cuando hay código y desambigua duplicados con `(n)` (`:37-50`) |
| `src/CarbonFootprint/Infrastructure/DataExchange/EconomicSectorsExportationType.php` | Exportación `economic_sectors` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/Answer/EconomicSectorAnswerHttpMapper.php` | Fila HTTP → `EconomicSectorAnswer` |
| `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/View/EconomicSectorRowViewFactory.php` | Actividad → `ActivityRowView` |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (modificado) | Nodo «Inversiones» (`solar:pie-chart-2-line-duotone`, `#inversiones`) con hijo «Sectores económicos» (`solar:graph-up-line-duotone`), líneas 289-300, dentro de `toAdmins()` |
| `assets/icons/solar/{pie-chart-2,graph-up}-line-duotone.svg` | Iconos cacheados del menú |
| `templates/carbon_footprint/admin/economic_sectors/{list,register,edit,emission_factors}.html.twig` | Pantallas de administración |
| `templates/components/CarbonFootprint/EconomicSectorActivity{Table,Row}.html.twig` | Componentes de la tabla de actividad (sin uso todavía); la fila etiqueta con `código · nombre` |
| `templates/security/admin/events/detail/carbon-footprint.economicSector*.html.twig` | Siete plantillas del Registro de auditoría |
| `translations/fields+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.{es,en,tr}.xlf` (modificados) | Claves `fields.economicSector` («Sector económico») y `fields.classificationCode` («Código CNAE/NACE») y los textos de interfaz, con ES, EN y TR |
| `migrations/Version20261016085141.php` | Migración de las cuatro tablas |

## Modelo de dominio

### `EconomicSector`

| Método | Evento(s) | Notas |
|---|---|---|
| `register(name, unit, ?classificationCode, ?locale)` | `EconomicSectorRegistered` (+ `Translated`) | `EconomicSector.php:106-126`. `assertCurrency()` antes de publicar (`:112`) |
| `update(name, unit, ?classificationCode, ?locale)` | `EconomicSectorUpdated` (+ `Translated`) | `:128-154`. `assertCurrency()` (`:134`); con locale no por defecto se conserva el nombre base (`:141`); si cambia la divisa, re-estampa la unidad de todos los factores (`:151-153`, `restampEmissionFactorUnits` `:251-262`) **sin convertir valores**, publicando un `EmissionFactorUpdated` por factor |
| `translate(name, locale)` | `EconomicSectorTranslated` | `:156-165` |
| `remove()` | `EconomicSectorRemoved` | Baja lógica (`removed`, `removedAt`) |
| `setEmissionFactor(EmissionFactor)` | `Added` / `Updated` / nada | `:176-202`. Factor igual (`equals()`) no publica |
| `removeEmissionFactor(year, groupId)` | `EmissionFactorRemoved` | `:204-217`. No publica si no existe |
| `syncEmissionFactors(list<EmissionFactor>)` | Combinación | `:220-242`. Duplicado `año|grupo` → `\InvalidArgumentException` (`:228`) |

El agregado guarda el código tal como lo recibe: la normalización es responsabilidad de los servicios (`ClassificationCodeNormalizer`).

Invariantes:

- `unit()->magnitude() === Magnitude::Currency` siempre (`assertCurrency`, `:244-249`). El constructor admite cualquier `Unit`, pero el estado solo se fija a través de `register()`/`update()`.
- `classificationCode` es `null` o una cadena no vacía en mayúsculas; entre sectores con `removed = false` es único. **Lo garantizan los servicios de alta, edición e importación**, no el esquema (índice no único).
- Como máximo un factor por `(sector, year, emissionFactorGroupId)` (colección, `setEmissionFactor` e índice único de BD).

### `EconomicSectorActivity`

`register(id, accountId, economicSectorId, emissionFactor, emissions, quantity)` y `update(economicSectorId, emissionFactor, emissions, quantity)` (`EconomicSectorActivity.php:23-57`) registran y aplican sus eventos; `remove()` (`:59-64`) publica `Removed` sin aplicar estado, como el resto de actividades. Sin validaciones propias: la compatibilidad de unidades la impone `EmissionsCalculator`.

## Flujo de una operación

### Alta

`RegisterController` (deniega si no hay `Register`, `:30`) → `RegisterEconomicSectorType` (`name` obligatorio, máx. 255; `classificationCode` opcional, máx. 20; `unit` con `CurrencyUnitType`, `NotNull`) → `RegisterEconomicSectorService`:

1. Autoriza `List` con un agregado desechable (`RegisterEconomicSectorService.php:35`).
2. Normaliza el código y, si no es `null` y `findByClassificationCode()` devuelve un sector, lanza `EconomicSectorCodeAlreadyExistsException` (`:37-41`) antes de crear nada.
3. `register()` traduciendo si el locale no es el por defecto (`:43-52`) y `add()`.

El controlador captura la excepción y añade «Ya existe un sector económico con ese código» como `FormError` del campo (`RegisterController.php:42-46`), re-renderizando el formulario. Con éxito: flash «Sector económico creado correctamente: {name}» (`:50`) y redirección a la edición (`:55-58`).

Una unidad que no sea divisa no llega al dominio desde la web: `CurrencyUnitType` no la ofrece y el formulario la rechaza como opción inválida (escenario «unit that is not a currency» de `economic-sector-register.feature`).

### Edición

`EditController` (autoriza `Edit` vía voter, `:39`) → `UpdateEconomicSectorType` (los mismos campos + `emissionFactors` `GroupedEmissionFactorsType`; año entre 1900 y 2200 y los cuatro gases obligatorios en `Type/EconomicSectorEmissionFactorType.php`) → `UpdateEconomicSectorService`:

1. `find` o `\InvalidArgumentException('EconomicSector not found')` (`UpdateEconomicSectorService.php:38-40`); autoriza `Edit` (`:42`).
2. Normaliza el código; si otro sector **distinto** lo tiene, lanza `EconomicSectorCodeAlreadyExistsException` (`:46-52`). Mantener el propio código no lanza.
3. `update()` (`:57`) y `syncEmissionFactors()` con la divisa ya actualizada (`:66-74`); grupo inexistente → `\InvalidArgumentException('Emission factor group not found')` (`:91`).

`EditController.php:60-66` convierte la excepción de código en `FormError` y, como el formulario deja de ser válido, la condición `$needsRedirect && $form->isValid()` (`:71`) evita la redirección de «Guardar y salir» que haría `EditTrait`. Con éxito: flash «Sector económico actualizado con éxito» (`:68`).

### Factores en bloque y copia de año

- `FactorEmissionController` (grupo por defecto si no viene en la ruta, `:50-52`; filas construidas con nombre y código, `:70-76`) → `ListEmissionFactorsService` (año pedido, o el último con factores en el grupo, o el actual, `:31`; `canGoNext` solo si el año es anterior al actual, `:37`) → `UpdateEmissionFactorsService` (autoriza `Edit` al inicio `:34` y por sector `:43`; fila vacía elimina el factor `:45-46`). `EconomicSectorEmissionFactorRowType::validateAllOrNothing` (`:67`) exige los cuatro gases si la fila no está vacía. La celda muestra `código · nombre` (`emission_factors.html.twig:78`).
- `CreateYearController` → `CreateEmissionFactorsYearService`: destino futuro (`:38`) o igual al origen (`:42`) → `\InvalidArgumentException` («El año destino no es válido.»); algún sector con factores en el destino y grupo → `EmissionFactorYearAlreadyExistsException` («El año destino ya tiene factores de emisión.», `:48-52`), sin copiar nada. El formulario limita el destino a `[1900, año actual]`.

### Baja

`RemoveController` (CSRF) o `Bulk/RemoveAction` → `RemoveEconomicSectorService` (autoriza `Remove`). El flash individual usa el nombre; el de la acción en bloque, el id (`Bulk/RemoveAction.php:44,51`), como en los demás maestros. Tras la baja, el código queda libre: `findByClassificationCode()` ignora los eliminados.

### Importación

`EconomicSectorImportationType` (`code() = 'economic_sectors'`): campos `economicSector`, `name`, `classificationCode`, `unit`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source`, `emissionFactorGroup` (`:48-63`); traducción de valores para `economicSector` (`EconomicSectorChoiceType`), `unit` (`CurrencyUnitCodeType`) y `emissionFactorGroup` (`:65-72`); `MapExtractor` para esos tres y `DirectExtractor` para el resto, incluido `classificationCode` (`:108-147`); `isAuthorized()` con `Action::Import` (`:149-156`). Por fila, `ImportEconomicSectorService`:

1. Año entero > 0 o `'A valid year is required'` (`:140-149`).
2. `resolveEconomicSector()` (`:53-86`), en este orden:
   1. `economicSector` informado → `find` (no existe → `'Economic sector not found'`) y autoriza `Edit` (`:55-65`).
   2. Código normalizado informado y `findByClassificationCode()` lo encuentra → autoriza `Edit` y usa ese sector (`:67-77`).
   3. Si no, exige `name` (`'An economic sector, a classification code or a name is required'`, `:79-83`) y **registra un sector nuevo** con ese nombre y código (`:88-112`): autoriza `List`, unidad mapeada o `Unit::Euro` (`:94`) y, si no es divisa, `'Unit must be a currency'` antes de llamar al agregado (`:96-98`).

   En los casos 1 y 2, `name`, `classificationCode` y `unit` de la fila se ignoran: el sector existente no se renombra.
3. Grupo obligatorio y existente (`:125-138`).
4. `setEmissionFactor` con la divisa del sector; decimales vacíos → `'0'`, coma decimal aceptada (`:151-156`).

Cualquier excepción marca la fila como errónea en el resultado de la importación (comportamiento genérico de DataExchange).

### Exportación

`EconomicSectorsExportationType`: columnas Identificador (desmarcada), Código CNAE/NACE, Nombre, Unidad, Año, CO2eq, CO2, CH4, N2O, Fuente, Grupo (`:45-60`); filtro `AdminEconomicSectorsExportType` (Nombre, Código CNAE/NACE, Grupo). `rows()` (`:101-129`) emite una fila por factor; un sector sin factores emite una fila con columnas de factor vacías; con filtro de grupo se omiten los factores de otros grupos (un sector cuyos factores son todos de otro grupo no emite filas). La divisa sale como código (`EUR`, `USD`…) y el código vacío como cadena vacía (`:141-149`).

### Actividad (sin flujo HTTP todavía)

`EconomicSectorAnswerHttpMapper::map()` (`:19-33`) → `null` sin `itemId` o con cantidad inválida. `EconomicSectorAnswerHandler` → `RegisterEconomicSectorActivityService`: autoriza `Register` con `ActivityAuthorizer` (`:36`) → `EconomicSectorEmissionFactorSelector::select()` (sector o factor inexistente → `\InvalidArgumentException`, `EconomicSectorEmissionFactorSelector.php:28,32`) → `EmissionsCalculator::calculate()` (`:39`), que convierte la cantidad a la divisa del factor con `UnitConverter` y el tipo de cambio vigente (USD → EUR en `testConvertsForeignCurrencyQuantity`); una cantidad en `kg` lanza `IncompatibleUnitsException` → `EconomicSectorActivity::register()` → `save()`. `UpdateEconomicSectorActivityService` lanza `ActivityNotFoundException` si la actividad no existe o no es de este tipo (`:36-38`) y después autoriza `Edit` (`:40`). `EconomicSectorCatalogProvider::catalog()` devuelve vacío sin grupo y, con grupo, los sectores con factor para el año (`:21-31`).

## Autorización y ámbito

`EconomicSectorAuthorizer`: `List` y `Edit` solo para `SuperAdmin` y `Admin` (vía `userHasRole`, `:24-32`); `Register` y `Export` → `canList`; `View` y `Remove` → `canEdit`; `Import` → `canRegister`. **Maestro global**: sin *scoping* por cuenta. `Owner` y `User` reciben 403 en todas las rutas (escenarios Behat). El menú solo lo pinta `NodeLister::toAdmins()`. La actividad usa el `ActivityAuthorizer` genérico.

## Persistencia y migración

| Tabla | Contenido |
|---|---|
| `economic_sectors` | `id`, `name`, `unit` (`VARCHAR`), `classification_code` (`VARCHAR(20)` nullable, índice no único `idx_economic_sector_classification_code`), `removed`, `registered_at`, `updated_at`, `removed_at` |
| `economic_sector_translation` | PK (`locale`, `economic_sector_id`), `name`; FK `ON DELETE CASCADE` |
| `economic_sector_emission_factor` | `id`, `economic_sector_id`, `created_at`, `updated_at` + `EmissionFactor` embebido sin prefijo (`co2eq`, `co2`, `ch4`, `n2o` `NUMERIC(20,8)`, `year`, `emission_factor_group_id`, `unit`, `source`); único `(economic_sector_id, year, emission_factor_group_id)`; FK `ON DELETE CASCADE` |
| `economic_sector_activities` | Hija JOINED de `activities` (`id` FK `ON DELETE CASCADE`), `economic_sector_id` |

Sin cambios en tablas existentes: `activities.type` es `VARCHAR` y admite `economic_sector`. Sin backfill.

Migración: `migrations/Version20261016085141.php` (sin commitear al escribir este manual) crea las cuatro tablas con los nombres de Doctrine (`IDX_34EA9D74C36BFB08`, `IDX_CD66329BC36BFB08`, `FK_663EFFECBF396750`, `FK_34EA9D74C36BFB08`, `FK_CD66329BC36BFB08`), coincidentes con `migration.sql` del plan, y las elimina en orden inverso en `down()`. Aplicar con `make migrate`.

Sin índice único sobre `classification_code` a propósito: la baja es lógica y el índice impediría reutilizar el código de un sector eliminado. Consecuencia aceptada en el plan: dos altas concurrentes con el mismo código podrían colarse.

## Configuración y operación

- Sin variables de entorno, colas, comandos de consola ni tareas programadas nuevas.
- `services.yaml` no cambia: repositorios por `#[AsAlias]`/`#[When]`, importación y exportación por autoconfiguración y las cinco piezas de actividad por las etiquetas `#[AutoconfigureTag]` de sus interfaces (selector, proveedor de catálogo, manejador, mapper HTTP, factory de fila).
- Sin correos ni notificaciones propias (la importación envía el correo genérico de DataExchange al terminar); el Registro usa las siete plantillas `carbon-footprint.economicSector*.html.twig`. Los eventos de actividad usan `default.html.twig`, como todos los tipos de actividad.
- La conversión entre divisas depende de que existan tipos de cambio (`ExchangeRate`, plan `spend-based`); sin el de una divisa, el cálculo de una actividad en esa divisa falla.

## Cómo extenderlo

**Exponer la actividad en el alcance 3 (categoría 15):**

1. Declarar la `SectionDefinition` con `ActivityType::EconomicSector` en el `Scope` (o `Phase`) y su `Source`.
2. Pintar `<twig:CarbonFootprint:EconomicSectorActivityTable>` con `rows`, `items` (de `EconomicSectorCatalogProvider`) y **`units: Unit::currencies()`**: el componente no filtra unidades por sí mismo. **Trampa:** solo acepta `eventId` y construye la URL con `events_carbon_footprint_emissions_calculate` (`EconomicSectorActivityTable.html.twig:1,10`); para un asistente organizacional hay que añadir una prop `calculateUrl` como en `RefrigerantGasActivityTable.html.twig`.
3. Selector, catálogo, handler, mapper y factory ya se resuelven por `ActivityType`. Revisar exclusiones, informe (`ActivityItemNameResolver` ya tiene brazo) y los tests de vista que construyen registries solo con los tipos que tienen sección.
4. Si el alcance 3 necesita porcentaje de participación o tipo de inversión, añadirlos como atributo de línea de la actividad (como `acquisitionYear` en `CapitalGoodActivity`) o en un agregado propio, según decida ese plan.

**Reutilizar la restricción a divisas en otro maestro:** usar `CurrencyUnitType` en formularios y `CurrencyUnitCodeType` en `translationDefinitions()` de la importación; validar en el agregado como `assertCurrency()`.

**Reutilizar el código externo único:** `findByClassificationCode()` + normalizador en los servicios de alta, edición e importación, sin índice único si el agregado tiene baja lógica.

**Trampas conocidas:**

- Cambiar la divisa de un sector re-estampa sus factores sin convertir valores y **no** recalcula actividades ya registradas.
- El normalizador no unifica formatos: `6201` y `62.01` son códigos distintos y no colisionan.
- El filtro por código usa `LIKE 'X%'` sin escapar `%` ni `_` del texto introducido.
- `AbstractSectionAnswerHttpMapper::parseQuantity` trata una cantidad ausente como `0` (deuda compartida declarada en el plan).
- Los mensajes de excepción del selector y del servicio de importación no están en los catálogos de traducción, como en los maestros de referencia.

## Tests

| Ámbito | Ficheros |
|---|---|
| Dominio del maestro | `tests/Unit/CarbonFootprint/Domain/Model/EconomicSector/` — `EconomicSectorTest` (20 tests: divisa obligatoria sin publicar evento, re-estampado, round trip de payload con y sin código, factores), `EconomicSectorRepositoryTest` (abstracto: prefijo de código, `findByClassificationCode` exacto e ignorando eliminados, orden por código) + `InMemoryEconomicSectorRepositoryTest`, `EconomicSectorMother`, `EconomicSectorRepositoryMother`; `tests/Unit/CarbonFootprint/Infrastructure/Persistence/Doctrine/Repository/DoctrineEconomicSectorRepositoryTest.php` |
| Aplicación del maestro | `tests/Unit/CarbonFootprint/Application/EconomicSector/` — authorizer, `ClassificationCodeNormalizerTest`, List, Register (`EUR` por defecto, código normalizado, duplicado que no persiste, reutilización del código de un sector eliminado), Update (re-estampado, código ajeno, propio código), Remove, `EmissionFactor/{CreateYear,List,Update}`, `Import` (`testLocatesEconomicSectorByClassificationCode`, `testThrowsWhenCreatingWithNonCurrencyUnit`…) |
| Actividad | `tests/Unit/CarbonFootprint/Domain/Model/Activity/{EconomicSectorActivityTest,EconomicSectorActivityMother,ActivityTypeTest}.php`; `tests/Unit/CarbonFootprint/Application/Activity/` — Register (`testConvertsForeignCurrencyQuantity`, `testThrowsWhenQuantityMagnitudeDiffersFromFactor`, `testThrowsWhenNoFactorForYear`), Update, selector, `Catalog/`, `Answer/` |
| Piezas de UI | `tests/Unit/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/Step/{Answer/EconomicSectorAnswerHttpMapperTest,View/EconomicSectorRowViewFactoryTest}.php` |
| Aceptación | `tests/Acceptance/CarbonFootprint/economic-sector-{register,list,edit,remove,import,export,emission-factors}.feature` (33 escenarios: alta con `kg` rechazada, código duplicado en alta y edición, cambio a `USD`, filtro por código, campo «Código CNAE/NACE» en emparejado de importación y en exportación); pasos `economic sector :name with id :id exists`, `... and classification code :code exists` y `... and emission factor for year :year exists` en `CarbonFootprintContext.php:431-465` |

Ejecutar:

```bash
make test-unit options=tests/Unit/CarbonFootprint/Domain/Model/EconomicSector
make test-unit options=tests/Unit/CarbonFootprint/Application/EconomicSector
make test-acceptance options=tests/Acceptance/CarbonFootprint/economic-sector-edit.feature
make test-parallel
```

Huecos: no hay `.feature` para la actividad (no existe flujo HTTP que la registre); los escenarios de importación y exportación no ejecutan filas ni leen el fichero descargado (DataExchange no tiene pasos para ello), así que la localización por código y el rechazo de `kg` al importar solo están cubiertos por tests unitarios.

## Desviaciones y deuda

Ver `## Implementación` de [`plan.md`](plan.md), [`review.md`](review.md) y [`review-fix.md`](review-fix.md):

- Escenarios de importación/exportación sustituidos por comprobaciones de las pantallas de emparejado y exportación (ver «Huecos»).
- El escenario de alta con `kg` inyecta la opción con `I pick "kg" in the autocomplete "unit"` porque el desplegable solo ofrece divisas.
- `EditController` redirige solo con el formulario válido para no perder el error de código duplicado con «Guardar y salir».
- Posiciones de `case EconomicSector`, brazos de `fromActivity()`/`ActivityItemNameResolver`, discriminador y tipos de `doctrine.yaml` tras los de `Process`.
- **A1** (migración ausente): la migración existe ya en `migrations/Version20261016085141.php`; `review.md` no se ha re-revisado.
- **M1** (asumible): `EconomicSectorImportationType` (Application) importa form types de Infrastructure y compara `fields()` con claves literales, patrón de todos los `*ImportationType`.
- **M2** (asumible): `EditTrait::edit()` traga cualquier `\Exception` y redirige con «Guardar y salir»; mitigado aquí solo para el código duplicado.
- **B1** (asumible): `EconomicSectorAnswer` y su mapper no llevan `source`, igual que `UseProfile` y `Process`.
