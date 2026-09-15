# Doble resultado de electricidad según GHG Protocol (mix eléctrico por país) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-15 · **Bounded Contexts:** `CarbonFootprint` (lee `Property` y `EventOperations`)

## Qué hace y por qué

El GHG Protocol exige reportar el alcance 2 (electricidad comprada) por dos métodos: el **basado en el mercado** (factor contractual de la comercializadora o, si hay garantía de origen, el factor GdO) y el **basado en la localización** (factor medio de la red eléctrica del país). Hasta ahora `ElectricityActivity` guardaba un único factor y un único resultado, el contractual.

Esta funcionalidad añade a la actividad de electricidad un **segundo resultado**, el de mix eléctrico, calculado con el factor medio de red del país donde se consume. Para alimentarlo se crea un maestro global de plataforma, `ElectricityMix`, con **un registro por país** y factores de emisión por año y grupo de coeficientes, mantenido por Administradores igual que `ElectricityMarketer` y `GdOFactor`.

Decisiones clave (tabla `## Decisiones tomadas` del plan):

- El resultado contractual sigue siendo el oficial: `Activity::emissions()` alimenta totales, resultados por fase y fuente, dashboard, informe PDF y compensación. El resultado por localización **solo se guarda**, no se muestra ni se suma en ningún sitio todavía.
- El maestro **no tiene nombre propio**: `countryId` es su único dato identificativo, porque hay exactamente un mix por país y el país ya lo identifica y viene traducido. En la interfaz se rotula siempre con el filtro Twig `country_name` (`src/Property/Infrastructure/Symfony/Twig/CountryExtension.php:26`).
- El país se resuelve siempre huella → evento → propiedad → país. Si falta cualquier eslabón, o no hay mix o factor, la actividad se guarda igual con el resultado de mix vacío, **sin aviso al usuario**.
- Sin backfill: las actividades ya guardadas quedan con el resultado de mix a `null` hasta que se reedite su paso.

## Mapa de piezas

### Dominio (`src/CarbonFootprint/Domain/Model/`)

| Fichero | Rol | Estado |
|---|---|---|
| `ElectricityMix/ElectricityMix.php` | Agregado raíz del maestro; país, borrado lógico, colección de factores, unidad forzada a `kWh` | Nuevo |
| `ElectricityMix/ElectricityMixId.php` | Identificador UUID | Nuevo |
| `ElectricityMix/ElectricityMixRepository.php` | Puerto: `add`, `remove`, `find`, `findByCountryId`, `findByFilter`, `totalResultsByFilter` | Nuevo |
| `ElectricityMix/ElectricityMixFilter.php` | Criterio de búsqueda (`?CountryId $countryId`, `limit = 10`) | Nuevo |
| `ElectricityMix/ElectricityMix{Registered,Updated,Removed}.php` | Eventos de ciclo de vida | Nuevos |
| `ElectricityMix/ElectricityMixEmissionFactor{,Id,s}.php` | Entidad hija, su id y la colección con `forYearAndGroup` / `hasForYearAndGroup` | Nuevos |
| `ElectricityMix/ElectricityMixEmissionFactor{Added,Updated,Removed}.php` | Eventos de los factores | Nuevos |
| `Activity/ElectricityActivity.php` | Diez escalares nuevos del resultado por localización, firmas y accesores | Modificado |
| `Activity/ElectricityActivityRegistered.php`, `ElectricityActivityUpdated.php` | Tres parámetros nuevos, `payload()` y `rebuildFromPayload()` | Modificados |

### Aplicación (`src/CarbonFootprint/Application/`)

| Fichero | Rol | Estado |
|---|---|---|
| `ElectricityMix/ElectricityMixAuthorizer.php` | Autorización del maestro: solo `Admin` y `SuperAdmin` | Nuevo |
| `ElectricityMix/ElectricityMixCountryAlreadyRegisteredException.php` | `\DomainException` con factoría `forCountry()` | Nuevo |
| `ElectricityMix/{Register,Update,Remove,List}/**` | Casos de uso CRUD (request/response/service) | Nuevos |
| `ElectricityMix/Import/**` + `ElectricityMixImportationType.php` | Importación por hoja de cálculo, código `electricity_mixes` | Nuevos |
| `ElectricityMix/Export/ElectricityMixesExportFilter.php` | Filtro de exportación (`countryId`, `emissionFactorGroup`) | Nuevo |
| `ElectricityMix/EmissionFactor/**` | Fila `EmissionFactorRow`, listado por año, edición en masa y copia de año | Nuevos |
| `Activity/ElectricityMixEmissions.php` | DTO `{electricityMixId, emissionFactor, emissions}` | Nuevo |
| `Activity/ElectricityMixEmissionsResolver.php` | Resuelve el resultado por localización a partir de país, año, grupo y cantidad | Nuevo |
| `EventCarbonFootprint/EventCarbonFootprintCountryResolver.php` | Huella → evento → propiedad → `CountryId` | Nuevo |
| `Activity/{Register,Update}/…ElectricityActivity{Request,Service}.php` | `?CountryId $countryId` y llamada al resolver | Modificados |
| `Activity/Answer/ActivityAnswerHandler.php` + sus diez implementaciones | Firma con `?CountryId $countryId = null` (solo `ElectricityAnswerHandler` lo usa) | Modificados |
| `Activity/Answer/PhaseAnswersSynchronizer.php` | Resuelve el país una vez por sincronización y lo propaga | Modificado |

### Persistencia (`src/CarbonFootprint/Infrastructure/Persistence/Doctrine/`)

| Fichero | Rol | Estado |
|---|---|---|
| `Mapping/ElectricityMix.ElectricityMix.orm.xml` | Tabla `electricity_mixes`, índice `idx_electricity_mixes_country` | Nuevo |
| `Mapping/ElectricityMix.ElectricityMixEmissionFactor.orm.xml` | Tabla `electricity_mix_emission_factor`, único `(electricity_mix_id, year, emission_factor_group_id)` | Nuevo |
| `Mapping/Activity.ElectricityActivity.orm.xml` | Diez columnas nulables del resultado por localización | Modificado |
| `Type/ElectricityMixIdType.php`, `Type/ElectricityMixEmissionFactorIdType.php` | Tipos DBAL | Nuevos |
| `Repository/DoctrineElectricityMixRepository.php` | Adaptador de producción (`dev`, `prod`) | Nuevo |
| `../InMemory/Repository/InMemoryElectricityMixRepository.php` | Adaptador de tests (`test`) | Nuevo |
| `config/packages/doctrine.yaml:51-52` | Registro de los dos tipos DBAL | Modificado |

### Infraestructura de interfaz

| Fichero | Rol | Estado |
|---|---|---|
| `Infrastructure/UI/Web/Admin/ElectricityMix/{List,Register,Edit,Remove}Controller.php` | CRUD web bajo `/admin/electricity-mixes` | Nuevos |
| `.../ElectricityMix/GetElectricityMixFromIdTrait.php`, `Bulk/RemoveAction.php` | Carga por id y borrado múltiple desde el listado | Nuevos |
| `.../ElectricityMix/EmissionFactor/{FactorEmissionController,CreateYearController}.php` | Edición en masa de factores por año y copia de año | Nuevos |
| `Infrastructure/Symfony/Form/{Register,Update,List}ElectricityMix*Type.php` | Formularios de alta, edición y filtro | Nuevos |
| `Infrastructure/Symfony/Form/AdminElectricityMixesExportType.php` | Filtro de exportación (país y grupo) | Nuevo |
| `Infrastructure/Symfony/Form/{Bulk,Create}ElectricityMixEmissionFactors*Type.php` | Formularios de la pantalla de factores | Nuevos |
| `Infrastructure/Symfony/Form/Type/ElectricityMix{ChoiceType,EmissionFactorRowType,EmissionFactorType}.php` | Tipos auxiliares; el choice rotula con el nombre del país | Nuevos |
| `Infrastructure/DataExchange/ElectricityMixesExportationType.php` | Exportación XLSX/CSV, una fila por factor | Nuevo |
| `templates/carbon_footprint/admin/electricity_mixes/{list,register,edit,emission_factors}.html.twig` | Pantallas | Nuevas |
| `templates/security/admin/events/detail/carbon-footprint.electricityMix*.html.twig` (6) | Textos del registro de auditoría | Nuevas |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:263-267` | Nodo de menú *Instalaciones › Mix eléctrico* | Modificado |
| `translations/fields+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.{es,en,tr}.xlf` | Claves `electricityMix` y textos de la interfaz | Modificados |

## Modelo de dominio

### `ElectricityMix`

`src/CarbonFootprint/Domain/Model/ElectricityMix/ElectricityMix.php`. Usa el trait `AggregateRoot`; constructor promovido con `ElectricityMixId $id`, `?CountryId $countryId = null`, `bool $removed`, fechas.

- `countryId(): CountryId` lanza `\LogicException('Electricity mix has no country yet.')` si aún es `null` (`:40-47`). La propiedad se declara nulable solo para poder construir la instancia vacía que exigen los authorizers y los `isAuthorized()` de import/export.
- **No implementa `\Stringable`** ni tiene `name`: el rótulo es el nombre del país y se resuelve en Infraestructura.
- Unidad fija: `applyElectricityMixEmissionFactorAdded` y `…Updated` aplican `->withUnit(Unit::KWh)` (`:200`, `:210`), de modo que ningún camino de entrada puede grabar otra unidad.
- `syncEmissionFactors(array)` (`:152-174`) lanza `\InvalidArgumentException('Duplicate emission factor for year %d')` si llegan dos factores con el mismo `(año, grupo)`, y elimina los que ya no vengan en la lista.
- `emissionFactorFor(int $year, EmissionFactorGroupId): ?EmissionFactor` (`:74-77`) es el punto de consulta que usa el cálculo.

Eventos: `electricityMixRegistered` / `Updated` (payload `electricityMixId`, `countryId`), `electricityMixRemoved` (`electricityMixId`), y `electricityMixEmissionFactorAdded` / `Updated` / `Removed`.

### `ElectricityActivity`

`src/CarbonFootprint/Domain/Model/Activity/ElectricityActivity.php`. Diez campos escalares nulables (`:21-30`): `electricityMixId` más `locationBasedEmissionFactor{Co2eq,Co2,Ch4,N2o,Source}` y `locationBasedEmissions{Co2eq,Co2,Ch4,N2o}`.

**No se mapean como `<embedded>`** deliberadamente: Doctrine dejaría un objeto con propiedades sin inicializar cuando todo es `null`. Los value objects se reconstruyen en los accesores:

- `locationBasedEmissionFactor()` (`:149-165`) devuelve `null` si `locationBasedEmissionFactorCo2eq` es `null`; si no, construye un `EmissionFactor` tomando **año y grupo del factor contractual** de la propia actividad y `Unit::KWh`.
- `locationBasedEmissions()` (`:167-179`) devuelve `null` o un `Emissions`.

Invariante `assertLocationBasedResultIsComplete()` (`:107-120`), aplicada tanto en `register()` como en `update()`: los tres argumentos (`electricityMixId`, factor, emisiones) son **todos `null` o ninguno**; en otro caso lanza `\InvalidArgumentException('Location-based result requires electricity mix, emission factor and emissions.')`.

Los tres parámetros nuevos van al final de `register()` y `update()` con valor por defecto `null`, para no romper llamadas existentes ni los `Mother` de tests.

### Eventos de la actividad

`ElectricityActivityRegistered` y `ElectricityActivityUpdated` llevan `?ElectricityMixId`, `?EmissionFactor` y `?Emissions` al final del constructor. `payload()` añade las diez claves; `rebuildFromPayload()` reconstruye los objetos **solo si `locationBasedEmissionFactorCo2eq` no es `null`**, y lee todas las claves nuevas con `?? null`, de modo que los `RecordedEvent` antiguos se rehidratan sin resultado de mix.

### Tabla de casos

| Caso | Comercializadora | GdO | Mix del país (año, grupo) | `emissions` (contractual) | `locationBasedEmissions` |
|---|---|---|---|---|---|
| A | 0,25 kg/kWh | — | 0,18 kg/kWh | 1000 × 0,25 = 250 | 1000 × 0,18 = 180 |
| B | 0,25 | 0,00 | 0,18 | 1000 × 0,00 = 0 | 180 |
| C | — | 0,00 | 0,18 | 0 | 180 |
| D | 0,25 | — | sin mix / sin país / sin factor | 250 | `null` |

## Flujo de una operación

### Guardar el paso de electricidad de una huella

```
AnswerPhaseService (o Close/Complete/ProviderContribution)
  └─ PhaseAnswersSynchronizer::sync()
       ├─ $year = $eventCarbonFootprint->calculationYear()
       ├─ $countryId = EventCarbonFootprintCountryResolver::resolve($eventCarbonFootprint)   ← :44
       └─ por cada respuesta:
            ElectricityAnswerHandler::register|update(..., $countryId)                       ← :73, :79
              └─ Register/UpdateElectricityActivityService
                   ├─ authorizer->denyUnlessCanDoThat(...)                                   ← primero siempre
                   ├─ ElectricityEmissionFactorSelector::select(...)  → factor contractual
                   ├─ EmissionsCalculator::calculate(...)             → emissions (oficial)
                   ├─ ElectricityMixEmissionsResolver::resolve($countryId, $year, $group, $qty)
                   └─ ElectricityActivity::register|update(..., mix?->…)
```

Puntos de decisión:

- `EventCarbonFootprintCountryResolver::resolve()` (`src/CarbonFootprint/Application/EventCarbonFootprint/EventCarbonFootprintCountryResolver.php:84-99`) devuelve `null` en cascada: huella sin `eventId`, evento inexistente o sin `propertyId`, propiedad inexistente o sin país.
- `ElectricityMixEmissionsResolver::resolve()` (`src/CarbonFootprint/Application/Activity/ElectricityMixEmissionsResolver.php:19-46`) devuelve `null` si el país es `null`, si `findByCountryId()` no encuentra mix o si `emissionFactorFor($year, $group)` es `null`. Cuando hay factor, calcula con el **mismo** `EmissionsCalculator` que el resultado contractual, lo que incluye la conversión de unidad.
- `RegisterElectricityActivityService:49-62` y su gemelo de `Update` pasan `$mix?->electricityMixId`, `$mix?->emissionFactor` y `$mix?->emissions`, con lo que los tres son `null` a la vez y la invariante del agregado se cumple por construcción.

El país se resuelve **una sola vez por sincronización**, no por actividad. Las otras nueve implementaciones de `ActivityAnswerHandler` reciben el parámetro y lo ignoran.

### Registrar un mix eléctrico

`POST /admin/electricity-mixes/register` → `RegisterController` (`src/CarbonFootprint/Infrastructure/UI/Web/Admin/ElectricityMix/RegisterController.php`):

1. `denyAccessUnlessGranted(Register, new ElectricityMix(...))` (`:30`).
2. `RegisterTrait::register()` con un closure que invoca `RegisterElectricityMixService`.
3. El servicio autoriza (`SecurityAction::List` sobre una instancia vacía, `:33`), exige país (`:37-39`), comprueba `findByCountryId()` y lanza `ElectricityMixCountryAlreadyRegisteredException` si ya existe (`:41-43`), y registra.
4. La excepción se captura **dentro del closure** (`RegisterController:42-46`), añade el flash `danger` «Ya existe un mix eléctrico para ese país» y devuelve `false` para que el formulario se vuelva a pintar. Ver *Desviaciones*.
5. Éxito: flash «Mix eléctrico creado correctamente: {país}» y redirección a `admin_electricity_mixes_edit`.

`UpdateElectricityMixService:49-53` repite la comprobación comparando ids, de modo que conservar el propio país no lanza.

## Autorización y ámbito

`ElectricityMixAuthorizer` extiende `AbstractAuthorizer`; todas las acciones se reducen a `canList` / `canEdit`, que exigen `Role::SuperAdmin` o `Role::Admin` vía `userHasRole()` (rol vigente, compatible con impersonación).

| Acción | Quién | Dónde se comprueba |
|---|---|---|
| `List`, `View`, `Register`, `Edit`, `Remove`, `Import`, `Export` | `Admin`, `SuperAdmin` | Servicio (`denyUnlessCanDoThat`) + controlador (`denyAccessUnlessGranted`) |
| Cualquiera | `Owner`, `User` | 403 (features `electricity-mix-list.feature`, `-edit`, `-remove`, `-import`, `-export`) |

El maestro es **global de plataforma**: no lleva `AccountId` y no se filtra por cuenta. No hay feature flags ni variables de entorno.

El resultado por localización no añade autorización propia: hereda la de `ActivityAuthorizer` en los servicios de la actividad, que se aplica antes de cualquier cálculo (`RegisterElectricityActivityService:38`).

## Persistencia y migración

### Tablas

`electricity_mixes`: `id VARCHAR(36)` (`DC2Type:electricity_mix_id`), `country_id VARCHAR(36)` (`DC2Type:country_id`, `NOT NULL`), `removed TINYINT(1)`, `registered_at`, `updated_at`, `removed_at`; índice `idx_electricity_mixes_country (country_id)`. **Sin columna `name`.**

`electricity_mix_emission_factor`: réplica de la de `ElectricityMarketer` con `electricity_mix_id`, único `uq_electricity_mix_emission_factor_year (electricity_mix_id, year, emission_factor_group_id)` y FK `ON DELETE CASCADE`.

`electricity_activities`: diez columnas nuevas nulables (`electricity_mix_id VARCHAR(36)` y nueve `VARCHAR(255)`), sin backfill.

### Migración

`migrations/Version20260915115213.php` contiene el `up()`/`down()` completo y coincide con los mappings y con el modelo sin nombre. `migration.sql` de la carpeta del plan es su fuente.

No hay índice único de base de datos sobre `country_id`: el borrado lógico conserva el país de los mixes eliminados, así que un único sería incorrecto. La unicidad se garantiza solo en aplicación (deuda asumida, ver `review-fix.md` B4).

### Consultas

`DoctrineElectricityMixRepository::createQueryBuilderFromFilter()` excluye los eliminados (`removed IS NULL OR removed = false`), filtra por igualdad de `countryId`, ordena por `registeredAt` (o `updatedAt`) y desempata siempre por `e.id ASC`. `findByCountryId()` reutiliza ese query builder con `setMaxResults(1)`, de modo que un eventual duplicado por carrera se resuelve de forma determinista.

## Configuración y operación

- `config/packages/doctrine.yaml:51-52` registra `electricity_mix_id` y `electricity_mix_emission_factor_id`.
- `services.yaml` del BC y `config/routes.yaml` **no cambian**: el `resource` del contenedor y las rutas por atributos ya cubren el código nuevo; los adaptadores se seleccionan con `#[When(env: …)]` y `#[AsAlias(id: 'repository.electricity_mix', public: true)]`.
- No hay comandos de consola, colas Messenger, tareas programadas, correos ni notificaciones asociados.
- Operación tras el despliegue: aplicar la migración (`make migrate`) y cargar el maestro por formulario o por importación. Sin datos en el maestro la funcionalidad es inerte: todas las actividades se guardan con el resultado de mix a `null`.

## Cómo extenderlo

**Consumir el resultado por localización** (el caso previsto: huellas de propiedades). `ElectricityActivity::locationBasedEmissionFactor()` y `locationBasedEmissions()` ya devuelven los value objects; basta leerlos donde haga falta. Recuerda que pueden ser `null` para cualquier actividad anterior a esta funcionalidad y para cualquier país sin mix.

**Mostrarlo en el asistente o en los resultados.** Hoy nada de la UI lo expone: `ElectricityRowViewFactory`, `ElectricityAnswerHttpMapper`, `CalculateEmissionsController` y `templates/components/CarbonFootprint/ElectricityActivity{Table,Row}.html.twig` se dejaron intactos a propósito. Exponerlo implica tocar esas cuatro piezas y el JSON del cálculo en vivo.

**Recalcular actividades antiguas.** No hay comando de backfill. Un comando nuevo tendría que releer `EventCarbonFootprint` → país y volver a llamar a `update()` de la actividad con el resultado del `ElectricityMixEmissionsResolver`, respetando la invariante de los tres campos.

**Añadir más de un mix por país** (redes subregionales, mix residual) exige reintroducir un nombre propio en el agregado, en los formularios, en la importación y en la exportación, y sustituir `findByCountryId(): ?ElectricityMix` por una consulta de colección. Es una decisión de producto, no un refactor.

**Un maestro de factores nuevo** se clona de `ElectricityMarketer` o de este: dominio (13 clases) → tipos DBAL + mappings + repositorios → aplicación (authorizer, CRUD, import/export, `EmissionFactor/*`) → formularios, controladores, plantillas, nodo de menú y traducciones.

Trampas conocidas:

- No mapear el factor ni las emisiones por localización como `<embedded>`.
- Toda clave nueva en el payload de los eventos de la actividad debe leerse con `?? null` en `rebuildFromPayload()`.
- `RegisterTrait` y `EditTrait` (`src/Shared/Infrastructure/UI/Web/CRUD/`) capturan `\Exception` dentro del closure y emiten un flash genérico: una excepción de dominio que quieras distinguir hay que capturarla **dentro** del closure.
- `Country::iso()` puede ser `null`; esos países no son importables por fichero, solo desde el formulario.

## Tests

Unitarios (`make test-unit`):

- `tests/Unit/CarbonFootprint/Domain/Model/ElectricityMix/` — `ElectricityMixTest` (registro, cambio de país, alta/actualización/no duplicado de factores, `syncEmissionFactors` con duplicados, unidad forzada a `kWh`), `ElectricityMixRepositoryTest` e `InMemoryElectricityMixRepositoryTest` (`findByCountryId` con mix, sin mix y eliminado; filtro por país), más `ElectricityMixMother` y `ElectricityMixRepositoryMother`.
- `tests/Unit/CarbonFootprint/Application/ElectricityMix/` — authorizer, Register (país duplicado lanza), Update (mover al país de otro lanza; conservar el propio no), Remove, List, Import (ISO desconocido lanza; dos filas del mismo ISO reutilizan el mix) y los tres casos de `EmissionFactor/`.
- `tests/Unit/CarbonFootprint/Application/Activity/ElectricityMixEmissionsResolverTest.php` — los cuatro caminos del resolver.
- `tests/Unit/CarbonFootprint/Application/EventCarbonFootprint/EventCarbonFootprintCountryResolverTest.php` — los seis caminos de resolución del país.
- `tests/Unit/CarbonFootprint/Domain/Model/Activity/ElectricityActivityTest.php` — invariante de los tres campos, ida y vuelta con y sin mix, `rebuildFromPayload()` con payload antiguo y nuevo.
- `RegisterElectricityActivityServiceTest`, `UpdateElectricityActivityServiceTest`, `ElectricityAnswerHandlerTest`, `PhaseAnswersSynchronizerTest` y `AnswerPhaseServiceTest::testAnswersElectricityAndStoresBothResults()` — integración de los dos resultados.

Aceptación (`make test-acceptance`): `tests/Acceptance/CarbonFootprint/electricity-mix-{list,register,edit,remove,emission-factors,import,export}.feature`, con los pasos `Given electricity mix for country :country with id :id exists` y `… and emission factor for year :year exists` en `CarbonFootprintContext.php`.

Ejecución selectiva:

```bash
make test-this test=tests/Unit/CarbonFootprint/Application/ElectricityMix
make test-this test=tests/Acceptance/CarbonFootprint/electricity-mix-register.feature
```

Huecos de cobertura conocidos (ver `plan.md` `## Implementación` y `review-fix.md`):

- No hay feature Behat del paso de electricidad del asistente, porque el repositorio no tiene ninguna; la cobertura extremo a extremo vive en `AnswerPhaseServiceTest`.
- Los escenarios de importación y exportación no ejecutan filas ni inspeccionan el fichero generado: el repositorio no tiene pasos Behat para ello. Cubierto en `ImportElectricityMixServiceTest`.

## Desviaciones y deuda

De `plan.md` (`## Implementación`):

- La captura de `ElectricityMixCountryAlreadyRegisteredException` va **dentro** del closure de `RegisterTrait` / `EditTrait`, no en un `catch` del controlador.
- La columna «País» del listado usa el filtro Twig `country_name` en lugar de un mapa id → nombre cargado en `ListController`.
- No se añadieron los pasos Behat `Then the electricity activity :id has location based emissions …` (quedarían sin uso), ni los escenarios extra de import/export.
- `ElectricityAnswerHandlerTest` se modificó aunque no estaba en el inventario, por el cambio de firma de los servicios.
- Se tradujo también `messages+intl-icu.tr.xlf`; se descartaron los cambios que `make translation` introdujo en `validators.*.xlf`.

De `review-fix.md` (triaje aprobado por el usuario el 2026-09-15):

- **B1** ✅ aplicado: los escenarios de país duplicado comprueban además el efecto en el listado.
- **B2** 🟰 asumido: el formulario de exportación incluye un filtro `countryId` que el inventario del plan no listaba, pero que el filtro y la serialización ya contemplaban.
- **B3** 🟰 asumido: el `catch (UnauthorizedActionException)` de `RegisterController` y `EditController` es inalcanzable; la causa está en `RegisterTrait`/`EditTrait` y afecta a todo el proyecto.
- **B4** 🟰 asumido: unicidad de país solo en aplicación; sin índice único en base de datos por el borrado lógico.
- **A1** ⬜ a cargo del usuario: aplicar la migración con `make migrate`. Sin ella, las pantallas del maestro y el guardado del paso de electricidad fallan contra tablas y columnas inexistentes.

Detalle completo en `review.md` y `review-fix.md` de esta misma carpeta.
