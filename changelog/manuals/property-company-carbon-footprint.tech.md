# Huella de carbono de propiedades y de empresas — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-16 · **Revisado:** 2026-09-16 (`review.md`, `review-fix.md`)
- **Bounded Contexts:** `CarbonFootprint` (lee `Property`, `Security` y `EventOperations`)
- **Migración:** pendiente de crear por el usuario a partir de `migration.sql` (seis tablas)

## Qué hace y por qué

Hasta este plan la plataforma solo calculaba la huella de carbono de **eventos** (`EventCarbonFootprint`). Esta funcionalidad añade la huella **organizacional**: la de una **propiedad** concreta y la de una **empresa** (que agrupa todas las propiedades asignadas a ella), para un **año natural**, con **alcance 1** (combustibles en instalaciones fijas, vehículos propios y fugas de gases refrigerantes) y **alcance 2** (electricidad de edificios), más una pantalla de resultados.

Se introducen dos agregados hermanos de `EventCarbonFootprint` —`PropertyCarbonFootprint` y `CompanyCarbonFootprint`— y, sobre todo, una **interfaz común** `App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint` que los tres implementan. Todas las piezas de aplicación que estaban acopladas a eventos (sincronizador de respuestas, constructor de secciones, validación de respuestas y exclusiones, agregación de resultados, resolución del país, mappers HTTP) se refactorizaron una sola vez sobre esa interfaz y se reutilizan en los tres asistentes. Las actividades (`CombustibleActivity`, `RoadTransportActivity`, `RefrigerantGasActivity`, `ElectricityActivity`) se reutilizan sin cambios, con todas sus piezas enchufables por `ActivityType`.

Las dos decisiones estructurales están en la tabla *Decisiones tomadas* de `plan.md`: dos agregados en lugar de uno con tipo de sujeto, y reutilización por interfaz (`CarbonFootprint` + `CalculationStage`) más un valor compartido `ActivityPlacement` que sustituye a los parámetros sueltos `phase/source/providerId/fromEmployees` y añade `propertyId` para el caso de empresa. El estado común y las consultas de colecciones viven en un trait de dominio (`OrganizationalCarbonFootprintTrait`), sin herencia de tablas: cada agregado tiene sus tablas, sus eventos y sus `apply*`.

## Mapa de piezas

### Dominio compartido (nuevo o movido)

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Domain/Model/CarbonFootprint/CarbonFootprint.php` | Interfaz común de las tres huellas | Nuevo |
| `src/CarbonFootprint/Domain/Model/CarbonFootprint/CalculationStage.php` | Interfaz de "paso de cálculo"; la implementan `Phase` (eventos) y `Scope` | Nuevo |
| `src/CarbonFootprint/Domain/Model/CarbonFootprint/ActivityPlacement.php` | Ubicación de una actividad: `stage`, `source`, `providerId`, `fromEmployees`, `propertyId`; `equals()` y `withSource()` | Nuevo |
| `src/CarbonFootprint/Domain/Model/CarbonFootprint/ActivityReference.php` | Par `activityId` + `placement` que devuelve `activityReferences()` | Nuevo |
| `src/CarbonFootprint/Domain/Model/CarbonFootprint/SourceExclusion.php` | Interfaz de exclusión (`stage()`, `source()`, `reason()`, `comment()`) | Nuevo |
| `src/CarbonFootprint/Domain/Model/CarbonFootprint/{Source,SectionDefinition,SectionAnswer,ExclusionAnswer,ExclusionReason}.php` | Tipos compartidos | Movidos desde `EventCarbonFootprint/` |
| `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/Scope.php` | Enum `scope_1` / `scope_2`; implementa `CalculationStage` y define las secciones | Nuevo |
| `.../OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatus.php` | Estados `started`, `scope_1_answered`, `scope_2_answered`, `completed`; `advancedTo()`, `activeStepKey()` | Nuevo |
| `.../OrganizationalCarbonFootprint/OrganizationalCarbonFootprintTrait.php` | Estado, consultas y helpers de colecciones comunes a propiedad y empresa | Nuevo |
| `.../OrganizationalCarbonFootprint/OrganizationalCarbonFootprintAlreadyCompletedException.php` | Excepción de `ensureIsEditable()` | Nuevo |

`Source` incorpora dos casos nuevos: `OwnVehicles = 'own_vehicles'` y `RefrigerantLeaks = 'refrigerant_leaks'` (`Source.php:16-17`).

### Agregados

`src/CarbonFootprint/Domain/Model/PropertyCarbonFootprint/` y `.../CompanyCarbonFootprint/`, 15 ficheros cada uno: agregado, `*Id`, `*Activity`, `*ActivityId`, `*Exclusion`, `*ExclusionId`, `*Filter`, `*Repository` y los eventos `*Registered`, `*ScopeAnswered`, `*ActivityAdded`, `*ActivityRemoved`, `*SourceExcluded`, `*SourceIncluded`, `*Removed`.

### Suscriptores de dominio

| Fichero | Escucha | Efecto |
|---|---|---|
| `Domain/Event/PropertyCarbonFootprint/RemoveActivityOnPropertyCarbonFootprintActivityRemovedSubscriber.php` | `PropertyCarbonFootprintActivityRemoved` | Borra la `Activity` |
| `Domain/Event/PropertyCarbonFootprint/RemovePropertyCarbonFootprintsOnPropertyRemovedSubscriber.php` | `PropertyRemoved` | Elimina (lógico) las huellas de esa propiedad |
| `Domain/Event/CompanyCarbonFootprint/RemoveActivityOnCompanyCarbonFootprintActivityRemovedSubscriber.php` | `CompanyCarbonFootprintActivityRemoved` | Borra la `Activity` |
| `Domain/Event/CompanyCarbonFootprint/RemoveCompanyCarbonFootprintsOnCompanyRemovedSubscriber.php` | `CompanyRemoved` | Elimina (lógico) las huellas de esa empresa |

### Aplicación compartida (`src/CarbonFootprint/Application/CarbonFootprint/`)

| Fichero | Rol |
|---|---|
| `StageAnswersProcessor.php` | Valida secciones y exclusiones contra `stage->sections()` y aplica `excludeSource`/`includeSource` (`applyExclusions`) |
| `ValidatedStageAnswers.php` | DTO: `sectionAnswers` + `exclusionsBySource` |
| `StageAnswersSynchronizer.php` | Registra, actualiza y retiene actividades por `ActivityPlacement` (sustituye a `PhaseAnswersSynchronizer`) |
| `StageSectionsBuilder.php` / `StageSections.php` / `StageSection.php` | Construye las secciones de un paso: definición, actividades del placement, catálogo y catálogo secundario (sustituye a `PhaseSection`) |
| `EmissionsBreakdownBuilder.php` / `EmissionsBreakdown.php` | Agrega emisiones: total, por fuente, por stage, por stage × fuente, por propiedad y electricidad por localización |
| `CarbonFootprintCountryResolver.php` | Resuelve el `CountryId` para el mix eléctrico (sustituye a `EventCarbonFootprintCountryResolver`) |

### Aplicación por agregado

`Application/PropertyCarbonFootprint/` (21 ficheros) y `Application/CompanyCarbonFootprint/` (los equivalentes más `AnswerScope/PropertySectionAnswers.php` y `GetScope/PropertyStageSections.php`): `*Authorizer`, `*AlreadyRegisteredException` y los casos de uso `Register`, `List`, `Remove`, `AnswerScope`, `GetScope`, `GetResults`, `CalculateEmissions`, cada uno con su `Request`/`Response`/`Service`.

### Infraestructura

| Fichero | Rol |
|---|---|
| `Infrastructure/UI/Web/CarbonFootprint/StageAnswerHttpMapper.php` | Lee las filas del POST por sección (sustituye a `PhaseAnswerHttpMapper`); recibe un `array`, no la `Request` |
| `Infrastructure/UI/Web/CarbonFootprint/StageExclusionHttpMapper.php` | Lee `exclusions[source][excluded|reason|comment]` |
| `Infrastructure/UI/Web/OrganizationalCarbonFootprint/View/ScopeStepView.php` | Monta catálogos, filas y unidades de un bloque |
| `.../View/OrganizationalResultsView.php` | Gráficas por fuente, por alcance, por propiedad; exclusiones traducidas |
| `.../View/StepExclusionsView.php` | Exclusiones del paso indexadas por `source->value` |
| `Infrastructure/UI/Web/{Property,Company}CarbonFootprint/` | `ListController`, `RegisterController`, `EditController`, `RemoveController`, `CalculateEmissionsController`, `Get*FromIdTrait`, `Bulk/Remove*`, `Step/{Scope1,Scope2,Results}Controller` |
| `Infrastructure/Symfony/Form/{Register,List}{Property,Company}CarbonFootprint{,s}Type.php` | Formularios de registro y de filtro |
| `Infrastructure/Persistence/Doctrine/Mapping/{Property,Company}CarbonFootprint.*.orm.xml` | Seis mappings |
| `Infrastructure/Persistence/Doctrine/Type/*.php` | Seis tipos de id, registrados en `config/packages/doctrine.yaml:67-72` |
| `Infrastructure/Persistence/{Doctrine,InMemory}/Repository/*{Property,Company}CarbonFootprintRepository.php` | Implementaciones del puerto |

Plantillas nuevas: `templates/carbon_footprint/{property,company}_carbon_footprints/{list,register,wizard-layout}.html.twig` y `step/{scope-1,scope-2,results}.html.twig`, más los parciales compartidos `templates/carbon_footprint/organizational_carbon_footprints/_scope_{1,2}_sections.html.twig`.

Traducciones nuevas: `translations/scope+intl-icu.{es,en,tr}.xlf` (`scope_1` → «Alcance 1») y `translations/organizationalCarbonFootprintStatusType+intl-icu.{es,en,tr}.xlf`; dos claves añadidas a `translations/source+intl-icu.{es,en,tr}.xlf` (`own_vehicles` → «Desplazamientos en vehículos», `refrigerant_leaks` → «Fugas de equipos de refrigeración»).

### Modificados fuera del alcance nuevo

- `Domain/Model/EventCarbonFootprint/{EventCarbonFootprint,EventCarbonFootprintActivity,EventCarbonFootprintActivities,EventCarbonFootprintExclusion,Phase}.php` — implementan las interfaces comunes y aceptan `ActivityPlacement`.
- `Application/EventCarbonFootprint/{AnswerPhase,GetPhase,GetResults}` y `Application/ProviderContribution/*`, `Application/AttendeeSurvey/Close`, `Application/EmployeeParticipation/Close` — delegan en los builders compartidos.
- `templates/components/CarbonFootprint/{RoadTransport,Electricity}ActivityTable.html.twig` — admiten `calculateUrl`.
- `templates/property/{properties,companies}/list.html.twig` — acción `List:Action:CarbonFootprint` por fila.
- `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:394-425` — nodo «Huellas de Carbono» dentro de los grupos «Propiedades» y «Empresas».

## Modelo de dominio

### Agregados

`PropertyCarbonFootprint` (`PropertyCarbonFootprint.php:22-49`) y `CompanyCarbonFootprint` usan `AggregateRoot` + `OrganizationalCarbonFootprintTrait`, con propiedades promocionadas: `id`, `propertyId`/`companyId`, `accountId`, `emissionFactorGroupId`, `year`, `totalEmissions`, `status`, `removed`, `registeredAt`, `updatedAt`, `removedAt`, más las colecciones `iterable $activities` y `iterable $exclusions` (arrays en memoria, `Collection` en Doctrine; el trait resuelve ambos casos en `attachActivity`/`detachActivity`/`attachExclusion`/`detachExclusion`).

`totalEmissions` existe en el estado pero **no se persiste calculado**: los resultados se agregan en vivo en `EmissionsBreakdownBuilder`.

### Eventos

| Evento | Payload relevante |
|---|---|
| `*Registered` | `propertyId`/`companyId`, `accountId`, `emissionFactorGroupId`, `year` |
| `*ScopeAnswered` | `scope` |
| `*ActivityAdded` | `activityId`, `scope`, `source` (y `propertyId` solo en empresa) |
| `*ActivityRemoved` | `activityId` |
| `*SourceExcluded` | `scope`, `source`, `reason`, `comment` |
| `*SourceIncluded` | `scope`, `source` |
| `*Removed` | — |

Nótese que `PropertyCarbonFootprintActivityAdded` **no** lleva `propertyId` (la propiedad es la de la huella), y `CompanyCarbonFootprintActivityAdded` sí lo lleva y puede ser `null` (bloque sin asignar).

### Invariantes

- **Estado monótono.** `applyPropertyCarbonFootprintScopeAnswered` (`PropertyCarbonFootprint.php:192-196`) hace `status->advancedTo($event->scope()->answeredStatus())`, y `advancedTo` (`OrganizationalCarbonFootprintStatus.php:52-59`) solo avanza si el candidato tiene un `stepNumber()` mayor. Responder Alcance 1 estando en `Scope2Answered` no retrocede el estado. Con `Completed` no se mueve nunca.
- **Editabilidad.** `ensureIsEditable()` (trait, `OrganizationalCarbonFootprintTrait.php:206-211`) lanza `OrganizationalCarbonFootprintAlreadyCompletedException` si el estado es `Completed`, y la invocan `answerScope`, `addActivity`, `removeActivity`, `excludeSource` e `includeSource`. Ningún caso de uso de este plan alcanza `Completed`.
- **Sin duplicados.** `addActivity` (`PropertyCarbonFootprint.php:90-115`) ignora un `activityId` ya presente e ignora la alta si la fuente está excluida en ese alcance.
- **Exclusión vacía la fuente.** En propiedad, `excludeSource` llama a `retainActivities(new ActivityPlacement($scope, $source), [])` (`:151`). En empresa **no** sirve esa vía, porque el placement lleva `propertyId` y solo vaciaría el bloque sin asignar: `CompanyCarbonFootprint::excludeSource` recorre `activityReferences()` y elimina todas las actividades de ese alcance y fuente, sea cual sea su bloque (`CompanyCarbonFootprint.php:151-158`, método `removeActivitiesOfSource`). Es una desviación consciente respecto a `plan.md`, anotada en `## Implementación`.
- **Volver a incluir no recupera.** `includeSource` solo quita la exclusión; las actividades borradas no vuelven.
- **Unicidad.** Una sola huella **no eliminada** por `(propertyId, year)` y por `(companyId, year)`, comprobada en el servicio de registro con `findByPropertyIdAndYear` / `findByCompanyIdAndYear` (`RegisterPropertyCarbonFootprintService.php:57-59`). No hay índice único en base de datos porque el borrado es lógico.
- **Borrado lógico.** `remove()` solo publica `*Removed` y marca `removed = true`; **no** elimina las actividades (ver *Desviaciones y deuda*).

### `ActivityPlacement::equals`

`ActivityPlacement.php:19-45` compara `stage->key()`, `source`, `fromEmployees`, y `providerId` y `propertyId` por presencia (`xor` sobre `null`) más igualdad de id. Es la clave de toda la funcionalidad: el sincronizador y el constructor de secciones agrupan por placement completo, de modo que en una huella de empresa cada bloque de propiedad es un conjunto independiente de actividades.

## Flujo de una operación

### Registrar una huella de propiedad

`GET|POST /{_locale}/property-carbon-footprints/register` → `Infrastructure/UI/Web/PropertyCarbonFootprint/RegisterController.php`

1. Exige cuenta activa (`LoggedAccount`); sin ella, 403.
2. `denyAccessUnlessGranted(Register, new PropertyCarbonFootprint(id, accountId: $account->id()))` (`:50-53`).
3. Resuelve el grupo de coeficientes: `$account->emissionFactorGroupId() ?? $emissionFactorGroupRepository->findDefault()?->id()`; si no hay ninguno, `EmissionFactorGroupNotConfiguredException` (`:55-61`).
4. Preselecciona la propiedad si llega `?propertyId=` (`:63-70`) — así funciona el botón del listado de propiedades.
5. Dentro del cierre de `RegisterTrait::register`, invoca `RegisterPropertyCarbonFootprintService`. Como `RegisterTrait` captura toda `\Exception` con un flash genérico, los errores de negocio se capturan **dentro** del cierre y se vuelcan al formulario: `PropertyCarbonFootprintAlreadyRegisteredException` → error en el campo `year` («Ya existe una huella de carbono para esta propiedad y año»); `\InvalidArgumentException` → error en `propertyId` (`:75-89`).
6. El servicio (`RegisterPropertyCarbonFootprintService.php:31-70`) autoriza, valida que la propiedad exista, no esté eliminada y pertenezca a la cuenta, comprueba la unicidad, construye el agregado y lo guarda.
7. Éxito → flash «Cálculo de Huella de Carbono creado correctamente» y redirección a `property_carbon_footprints_scope_1`.

La variante de empresa es idéntica con `CompanyIdType`; `RegisterCompanyCarbonFootprintType` inyecta `LoggedAccount` porque `CompanyIdType` necesita la opción `account_id` (`RegisterCompanyCarbonFootprintType.php:20-33`).

### Rellenar un alcance (propiedad)

`GET|POST /{_locale}/property-carbon-footprints/{id}/step/scope-1` → `Step/Scope1Controller.php`

- **GET:** autoriza `Edit`, llama a `GetPropertyCarbonFootprintScopeService` → `StageSectionsBuilder::build($footprint, Scope::Scope1)`, y renderiza un único bloque: `['blocks' => [$stepView->build($response->sections) + ['property' => null, 'prefixBase' => '']]]` (`:80`), más `exclusions`, `exclusionReasons` y `calculateUrl`.
- **POST:** valida el token CSRF `scope_1_<id>` (`:52-54`), mapea las filas con `StageAnswerHttpMapper::map($request->request->all(), Scope::Scope1->sections())` y las exclusiones con `StageExclusionHttpMapper`, y llama a `AnswerPropertyCarbonFootprintScopeService`. `UnauthorizedActionException` → 403; `\InvalidArgumentException` → flash «No se han podido guardar las actividades: …» y vuelta al mismo paso. Éxito → redirección a `property_carbon_footprints_scope_2`.

El servicio valida con `StageAnswersProcessor::validate` (sección ajena al alcance, tipo de respuesta que no casa con la sección, o respuestas en una fuente excluida → `\InvalidArgumentException`), aplica exclusiones con `applyExclusions`, sincroniza con `StageAnswersSynchronizer::sync` y llama a `answerScope`.

### Rellenar un alcance (empresa)

Igual, con una vuelta de bucle por bloque:

- **GET:** `GetCompanyCarbonFootprintScopeService` (`:25-51`) obtiene las propiedades de la empresa con `PropertyRepository::findByCompanyId` **en cada petición** (no se guardan en la huella), las ordena por nombre (`usort` con `strcasecmp`) y construye un `PropertyStageSections` por bloque: primero el bloque sin asignar (`propertyId = null`), luego uno por propiedad. El controlador compone el `prefixBase`: `'unassigned'` o `sprintf('properties[%s]', $property->id())` (`Scope1Controller.php:84-89`).
- **POST:** `answersByProperty()` (`Scope1Controller.php:105-124`) parte el POST en bloques (`unassigned` + `properties[<id>]`) y produce una `list<PropertySectionAnswers>`. `AnswerCompanyCarbonFootprintScopeService` (`:33-82`) rechaza con `\InvalidArgumentException` cualquier bloque cuya propiedad no pertenezca a la empresa, valida y aplica las exclusiones **una sola vez** (son por alcance y fuente, comunes a todos los bloques), e itera sobre **todas** las propiedades de la empresa más el bloque sin asignar —no solo sobre las que llegan en la petición—, de modo que vaciar un bloque en el formulario elimina sus actividades.

### Cálculo de emisiones en vivo (AJAX)

`POST /{_locale}/property-carbon-footprints/{id}/emissions/calculate` (y su gemelo de empresa) → `CalculateEmissionsController.php`. Autoriza `View`, valida `type`, `itemId`/`itemId2`, `unit` y `quantity` (coma decimal admitida) y devuelve JSON con `emissions` (`co2eq`, `co2`, `ch4`, `n2o`) y `emissionFactor` (`co2eq`, `unit`, `year`). Parámetros inválidos → 400; `\InvalidArgumentException` del cálculo → 422. La URL llega a los componentes Twig de tabla en la variable `calculateUrl`.

### Resultados

`GET /{_locale}/property-carbon-footprints/{id}/step/results` → `Step/ResultsController.php`. Autoriza `View`; si el estado no es al menos `Scope2Answered`, redirige al paso activo (`:36-41`). `GetPropertyCarbonFootprintResultsService` delega en `EmissionsBreakdownBuilder::build($footprint, Scope::cases())`, que recorre `activityReferences()`, carga las actividades con `ActivityRepository::findByIds` y agrega total, por fuente, por alcance, por alcance × fuente, por `propertyId` (clave `''` para el bloque sin asignar) y electricidad por localización (suma de `ElectricityActivity::locationBasedEmissions()` no nulos). En empresa, `GetCompanyCarbonFootprintResultsService` añade `properties` para poder nominar las barras del gráfico por propiedad.

### Resolución del país para el mix eléctrico

`CarbonFootprintCountryResolver::resolve(CarbonFootprint, ?PropertyId)` (`:23-33`):

- `EventCarbonFootprint` → evento → `Event::propertyId()` → `Property::info()->countryId()`.
- `PropertyCarbonFootprint` → `propertyId()` de la huella.
- `CompanyCarbonFootprint` → el `propertyId` del **bloque** que se está sincronizando; en el bloque sin asignar es `null`, de modo que la electricidad allí introducida no obtiene resultado por localización (la dirección de `Company` guarda el país como texto libre, no como `CountryId`).

## Autorización y ámbito

`PropertyCarbonFootprintAuthorizer` y `CompanyCarbonFootprintAuthorizer` extienden `AbstractAuthorizer` y se resuelven por tag mediante `supports()`.

| Acción | Regla |
|---|---|
| `List`, `Register` | `Owner` **de la cuenta actual** (`currentAccountId($user)`) y la huella pertenece a esa cuenta |
| `View` | = `Edit` |
| `Edit` | `Owner` de la cuenta actual y `accountId` de la huella igual al actual |
| `Remove` | `canEdit(...) || userHasRole($user, Role::Admin)` |

El `|| Admin` de `canRemove` es la corrección del hallazgo **A2** de `review.md`: sin él, un `Admin` que borraba una propiedad o una empresa desde `/admin/properties` o `/admin/companies` provocaba `UnauthorizedActionException` en el suscriptor de cascada (que se ejecuta síncrono y antes del `flush`), y el borrado fallaba con 403. `userHasRole` cae a `User::hasRole`, que **no** es jerárquico, así que un `Admin` sin impersonar no tiene `Role::Owner`.

Se usa siempre `currentAccountId($user)` / `userHasRole($user, ...)`, nunca `$user->accountId()`: un `Admin` impersonando una cuenta opera como su `Owner`.

Ninguna ruta lleva restricción de rol a nivel de enrutado; el control es del authorizer. No hay pantallas de administración para estas huellas.

## Persistencia y migración

Seis tablas (DDL completo en `migration.sql`):

| Tabla | Contenido | Índices |
|---|---|---|
| `property_carbon_footprints` | id, property_id, account_id, emission_factor_group_id, year, totales, status, removed, fechas | `IDX_pcf_property_year (property_id, year)`, `IDX_pcf_account (account_id)` |
| `property_carbon_footprint_activities` | id, footprint, activity_id, scope, source, added_at | `IDX_pcfa_footprint`, `IDX_pcfa_activity`, FK a la huella |
| `property_carbon_footprint_exclusions` | id, footprint, scope, source, reason, comment, excluded_at | `UNIQ_pcf_exclusions_scope_source (footprint, scope, source)`, FK |
| `company_carbon_footprints` | ídem con `company_id` | `IDX_ccf_company_year`, `IDX_ccf_account` |
| `company_carbon_footprint_activities` | ídem más `property_id` (nullable: bloque sin asignar) | `IDX_ccfa_footprint`, `IDX_ccfa_activity`, FK |
| `company_carbon_footprint_exclusions` | ídem | `UNIQ_ccf_exclusions_scope_source`, FK |

`activity_id` **no** tiene clave foránea contra `activities`: la única FK de las tablas hijas apunta a su huella.

Los índices por cuenta se declararon también en los mappings (`PropertyCarbonFootprint.PropertyCarbonFootprint.orm.xml`, `CompanyCarbonFootprint.CompanyCarbonFootprint.orm.xml`) al corregir el hallazgo **B2**, porque el listado filtra siempre por `accountId`.

Los mappings de exclusiones y actividades de **eventos** cambiaron el FQCN de `enum-type` para `Source` y `ExclusionReason` (namespace nuevo).

> **La migración no existe todavía.** Es tarea del usuario crearla a partir de `migration.sql` (hallazgo A1, decisión registrada en `review-fix.md`). Hasta aplicarla, registrar una huella en desarrollo falla por tablas inexistentes; los tests no dependen de ella porque el esquema de aceptación se recrea desde los mappings.

## Configuración y operación

- `config/packages/doctrine.yaml:67-72` — seis tipos de id nuevos (`property_carbon_footprint_id`, `..._activity_id`, `..._exclusion_id` y sus tres equivalentes de empresa).
- `config/routes.yaml` — sin cambios: todo `src/CarbonFootprint/Infrastructure/UI/Web/` se carga con prefijo `/{_locale}`.
- `services.yaml` del BC — sin cambios: authorizers, handlers, catálogos y fábricas de fila se autoconfiguran por tag.
- Sin colas, sin comandos de consola, sin tareas programadas, sin correos ni notificaciones: todo es síncrono y web.
- Sin variables de entorno ni feature flags nuevos.

## Cómo extenderlo

**Añadir una fuente a un alcance.** 1) Añadir el caso a `Source` si no existe, con su traducción en `translations/source+intl-icu.{es,en,tr}.xlf`. 2) Añadir una `SectionDefinition(clave, Source, ActivityType)` al `match` de `Scope::sections()`. 3) Añadir el bloque `SourceSection` + tabla de actividad en `_scope_1_sections.html.twig` o `_scope_2_sections.html.twig`, usando la misma convención de prefijos. 4) Exponer la sección en `ScopeStepView::build()` (catálogo, catálogo secundario y filas). El sincronizador, el validador, el breakdown y las exclusiones no requieren cambios: trabajan sobre `stage->sections()`.

**Añadir un tercer alcance.** Añadir el caso a `Scope` con su `stepNumber()` y su `answeredStatus()`, un estado nuevo en `OrganizationalCarbonFootprintStatus` (con su `stepNumber()` y su `activeStepKey()`), un controlador de paso, la plantilla, la entrada en `wizard_steps` de los dos `wizard-layout.html.twig` y las traducciones del dominio `scope`. Cuidado con `advancedTo()`: el orden lo fija `stepNumber()`.

**Cerrar la huella (`Completed`).** El estado ya existe y `ensureIsEditable()` ya bloquea la edición; falta un caso de uso `Complete` (y su `Reopen`) que publique el evento y un `apply*`.

**Trampas conocidas.**

- El trait usa `$this->activities`, `$this->status`, etc. sin declararlas; las propiedades se declaran en la clase que lo usa. No declararlas también en el trait (conflicto en PHP).
- Si el bloque de empresa no propaga el `propertyId` al sincronizador, sus actividades se tratan como "sin asignar" y se eliminan. Cubierto por `AnswerCompanyCarbonFootprintScopeServiceTest`.
- Cada maestro de `RoadTransport` re-sella sus factores a su unidad, así que puede haber transportes en km y otros en litros; si la magnitud no casa, `EmissionsCalculator` lanza `IncompatibleUnitsException` y llega al usuario como el flash de error del paso.
- Si el año elegido no tiene factores en el grupo de coeficientes, los catálogos salen vacíos. No se valida en el registro (mismo comportamiento que en eventos).
- Si una propiedad se **desasigna** de la empresa después de crear la huella, sus actividades dejan de tener bloque en el asistente pero siguen contando en los totales y aparecen en el desglose por propiedad con su id como etiqueta. Comportamiento aceptado en el plan.

## Tests

**Unitarios** (`tests/Unit/CarbonFootprint/`):

- `Domain/Model/{Property,Company}CarbonFootprint/` — `*Mother`, `*RepositoryMother`, `*RepositoryTest` (abstracto) + `InMemory*RepositoryTest`, y `*Test` del agregado (retención de actividades, exclusiones, avance de estado).
- `Application/{Property,Company}CarbonFootprint/` — un test por caso de uso más `*AuthorizerTest` (incluye `testAdminCanRemoveSoThatRemovingThePropertyCascadesToItsFootprints`, añadido al corregir A2).
- `Application/CarbonFootprint/` — `StageAnswersProcessorTest` (7 tests), `StageSectionsBuilderTest` (4), `EmissionsBreakdownBuilderTest` (3), `CarbonFootprintCountryResolverTest` y `StageAnswersSynchronizerTest`. Todos ellos añadidos o ampliados al corregir **B1**.
- `Application/EventCarbonFootprint/GetPhase/GetPhaseServiceTest::testTheOrganizerStepDoesNotListActivitiesFromProvidersNorFromEmployees` — fija el cambio de comportamiento de **M1**.

**Aceptación** (`tests/Acceptance/CarbonFootprint/`): ocho `.feature` — `{property,company}-carbon-footprint-{list,register,remove,scope-steps}.feature` — con los pasos añadidos en `CarbonFootprintContext.php`.

```bash
make test-this test=tests/Unit/CarbonFootprint/Application/CarbonFootprint
make test-this test=tests/Acceptance/CarbonFootprint/company-carbon-footprint-scope-steps.feature
```

En `company-carbon-footprint-scope-steps.feature` el paso de envío construye el POST a mano (con `_token` y `HTTP_REFERER`) en lugar de rellenar el formulario del DOM: las filas de actividad las crea JavaScript desde el prototipo, así que los campos no existen en el HTML inicial.

**Huecos de cobertura conocidos:** no hay escenario Behat para el cálculo AJAX de emisiones ni para el paso de Alcance 2 de empresa con resultado por localización; se cubren en unitarios.

## Desviaciones y deuda

Del apartado `## Implementación` de `plan.md`:

- Los parciales `_scope_{1,2}_sections.html.twig` reciben una lista `blocks` y no un juego único de filas con `prefixBase`: una `SourceSection` por fuente envuelve todos los bloques, de modo que el parcial es el mismo para propiedad (un bloque) y empresa (N+1 bloques) y `showExclusions` no hace falta.
- `CompanyCarbonFootprint::excludeSource` usa `removeActivitiesOfSource` en lugar de `retainActivities` (ver *Invariantes*).
- `RegisterCompanyCarbonFootprintType` y `ListCompanyCarbonFootprintsType` inyectan `LoggedAccount` para pasar `account_id` a `CompanyIdType`.
- `GetPhaseService` filtra ahora por `ActivityPlacement` completo, así que el paso del organizador del asistente de **eventos** ya no lista las actividades de proveedores ni de empleados. Es un cambio de comportamiento visible, confirmado por el usuario como el deseado (corregía además una duplicación latente de emisiones al reenviar una fila ajena). Contradice la restricción `plan.md:120`, que debe corregirse vía `feature-planner`.

De `review.md` / `review-fix.md`:

- **A1 — Migración pendiente.** Tarea del usuario; el DDL está en `migration.sql`.
- **M2 — Asumido.** `remove()` no elimina las actividades de la huella: quedan filas huérfanas en `activities` y en su tabla de subtipo. Sin impacto funcional (las actividades solo son alcanzables desde `activityReferences()` de una huella, y no hay FK que romper), mismo comportamiento que `EventCarbonFootprint`. Si se quisiera corregir, la vía es que `remove()` recorra `activityReferences()` y llame a `removeActivity()`. El criterio de aceptación `plan.md:115` y la decisión *Borrado* del plan deben corregirse vía `feature-planner`.
- **A2, B1, B2, B3, B4 — Corregidos** (ver `review-fix.md` para el detalle y los tests añadidos).
- **D1 — Descartado por decisión global:** borrado por GET sin CSRF en `property_carbon_footprints_remove` y `company_carbon_footprints_remove`, igual que el resto de los CRUD del proyecto.

Deuda técnica de infraestructura de tests detectada durante la implementación: la firma propia de `tests/Acceptance/Shared/AcceptanceClient::request()` obliga a usar `requestWithFiles()` para enviar parámetros POST, y en los listados hay dos botones con el texto «Filtrar», por lo que `selectButton('Filtrar')` elige el que no está dentro del formulario.
