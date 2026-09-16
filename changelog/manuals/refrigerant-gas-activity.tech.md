# Actividad de gas refrigerante (fugas de gases de climatización) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-15 · **Bounded Contexts:** `CarbonFootprint`

## Qué hace y por qué

La huella de carbono de un evento se compone de **actividades** (`Activity`): cada fila que el Propietario o un proveedor rellena en el asistente se guarda como una actividad tipada con su factor de emisión, su cantidad y sus emisiones ya calculadas. Antes de esta funcionalidad había diez tipos y ninguno cubría las **fugas de gases refrigerantes** (R-410A, R-134a, R-32…), partida obligatoria del alcance 1.

Esta funcionalidad añade el undécimo tipo, `RefrigerantGasActivity`, que consume el maestro `RefrigerantGas` (plan `.claude/plans/done/2026-09-15-refrigerant-gases/plan.md`). El agregado está calcado de `CombustibleActivity` (`src/CarbonFootprint/Domain/Model/Activity/CombustibleActivity.php`): un solo maestro, sin condición de ítem, sin ítem secundario y con campo libre `source`. Se entregan todas las piezas enchufables que hoy tiene cualquier tipo de actividad: dominio y eventos, casos de uso de registro y actualización, selector de factor, proveedor de catálogo, respuesta del asistente y su manejador, mapeo HTTP, fábrica de filas de vista y componentes Twig de tabla y fila.

**La actividad no está enchufada al asistente.** Por decisión explícita del plan (tabla `## Decisiones tomadas`) no se añade `SectionDefinition` en `Phase::sections()` ni en `Phase::providerSections()`, ni un caso nuevo al enum `Source`, ni se tocan las vistas y plantillas de paso. Todas las piezas se autorregistran por tags de Symfony, de modo que el plan de alcance 1 solo tendrá que añadir la `SectionDefinition`, el `Source` y la llamada al componente Twig. La única diferencia funcional respecto a `CombustibleActivity` es que **solo se admiten unidades de masa** (`kg`, `g`, `t`), porque el factor del maestro es siempre por kilogramo de gas (`RefrigerantGas.php:195,205`).

## Mapa de piezas

Todos los ficheros son **nuevos** salvo donde se indica «modificado».

### Dominio — `src/CarbonFootprint/Domain/Model/Activity/`

| Fichero | Rol |
|---|---|
| `RefrigerantGasActivity.php` | Subclase `final` de `Activity`; `refrigerantGasId` + `source` |
| `RefrigerantGasActivityRegistered.php` | Evento `refrigerantGasActivityRegistered` (payload plano con factor, emisiones y cantidad) |
| `RefrigerantGasActivityUpdated.php` | Evento `refrigerantGasActivityUpdated` (igual sin `accountId`) |
| `RefrigerantGasActivityRemoved.php` | Evento `refrigerantGasActivityRemoved` (solo `activityId`) |
| `Answer/RefrigerantGasAnswer.php` | Respuesta del asistente; `type()` → `ActivityType::RefrigerantGas` |
| `ActivityType.php` | **Modificado**: caso `RefrigerantGas = 'refrigerant_gas'` (`:19`) y brazo en `fromActivity` (`:34`) |

### Aplicación — `src/CarbonFootprint/Application/Activity/`

| Fichero | Rol |
|---|---|
| `Register/RegisterRefrigerantGasActivity{Request,Response,Service}.php` | Alta de una actividad y cálculo de sus emisiones |
| `Update/UpdateRefrigerantGasActivity{Request,Response,Service}.php` | Reemplazo de gas, cantidad y fuente, con recálculo |
| `RefrigerantGasEmissionFactorSelector.php` | Implementa `EmissionFactorSelector`; resuelve el factor del gas para (año, grupo) |
| `Catalog/RefrigerantGasCatalogProvider.php` | Implementa `ActivityCatalogProvider`; gases con factor para (año, grupo) |
| `Answer/RefrigerantGasAnswerHandler.php` | Implementa `ActivityAnswerHandler`; delega en los dos servicios |
| `EventCarbonFootprintReport/Generate/ActivityItemNameResolver.php` | **Modificado**: brazo `RefrigerantGasActivity` en `itemId()` (`:82`) |

Sin cambios y reutilizados tal cual: `ActivityAuthorizer`, `EmissionsCalculator`, `Remove/RemoveActivityService`, `ActivityEmissionsResolver` y los cinco registries (`EmissionFactorSelectorRegistry`, `ActivityCatalogProviderRegistry`, `ActivityAnswerHandlerRegistry`, `SectionAnswerHttpMapperRegistry`, `ActivityRowViewFactoryRegistry`).

### Persistencia — `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/`

| Fichero | Rol |
|---|---|
| `Activity.RefrigerantGasActivity.orm.xml` | Tabla `refrigerant_gas_activities`: `refrigerant_gas_id` (tipo DBAL `refrigerant_gas_id`, NOT NULL) y `source` (string 255, nullable) |
| `Activity.Activity.orm.xml` | **Modificado**: `<discriminator-mapping value="refrigerant_gas" …>` (`:21`) |
| `migrations/Version20260915154335.php` | Migración creada por el usuario (DDL de la tabla y su FK) |

`DoctrineActivityRepository` e `InMemoryActivityRepository` son genéricos y no cambian. El tipo DBAL `refrigerant_gas_id` ya estaba registrado en `config/packages/doctrine.yaml:49` por el plan del maestro.

### Infraestructura de UI — piezas enchufables

| Fichero | Rol |
|---|---|
| `src/…/UI/Web/EventCarbonFootprint/Step/Answer/RefrigerantGasAnswerHttpMapper.php` | Fila HTTP → `RefrigerantGasAnswer`; descarta filas no másicas |
| `src/…/UI/Web/EventCarbonFootprint/Step/View/RefrigerantGasRowViewFactory.php` | `RefrigerantGasActivity` → `ActivityRowView` |
| `templates/components/CarbonFootprint/RefrigerantGasActivityTable.html.twig` | Tabla del paso; filtra `massUnits` y fija `data-activity-emissions-type-value="refrigerant_gas"` |
| `templates/components/CarbonFootprint/RefrigerantGasActivityRow.html.twig` | Fila en modo lectura y en modo edición |
| `src/CarbonFootprint/Domain/Model/RefrigerantGas/RefrigerantGas.php` | **Modificado** (corrección de review A1): nuevo `unit(): Unit` → `Unit::Kilogram` (`:44-47`) |

Ningún `services.yaml` cambia: el `resource` del BC carga el namespace completo y las interfaces llevan `AutoconfigureTag`.

### Tests

| Fichero | Cubre |
|---|---|
| `tests/Unit/…/Domain/Model/Activity/RefrigerantGasActivityMother.php` | Constructor de actividad registrada para los demás tests |
| `tests/Unit/…/Domain/Model/Activity/RefrigerantGasActivityTest.php` | Eventos registrado/actualizado/eliminado y `rebuildFromPayload` |
| `tests/Unit/…/Domain/Model/Activity/ActivityTypeTest.php` | `fromActivity` resuelve `RefrigerantGas` (y `Combustible` como control) |
| `tests/Unit/…/Application/Activity/Register/RegisterRefrigerantGasActivityServiceTest.php` | Alta, conversión de toneladas, unidad no másica, gas sin factor, rol `User`, `isHandled` |
| `tests/Unit/…/Application/Activity/Update/UpdateRefrigerantGasActivityServiceTest.php` | Actualización, Owner de otra cuenta, actividad de otro tipo, `isHandled` |
| `tests/Unit/…/Application/Activity/RefrigerantGasEmissionFactorSelectorTest.php` | Factor del grupo, gas inexistente, sin factor para el grupo |
| `tests/Unit/…/Application/Activity/Catalog/RefrigerantGasCatalogProviderTest.php` | Sin grupo, filtrado por factor, catálogo secundario vacío |
| `tests/Unit/…/Application/Activity/Answer/RefrigerantGasAnswerHandlerTest.php` | Tipo y delegación de `register`/`update` |
| `tests/Unit/…/Infrastructure/…/Step/Answer/RefrigerantGasAnswerHttpMapperTest.php` | Fila válida, ítem vacío, unidad inválida, cantidad negativa, unidad no másica |
| `tests/Unit/…/Infrastructure/…/Step/View/RefrigerantGasRowViewFactoryTest.php` | Construcción del `ActivityRowView` |
| `tests/Acceptance/CarbonFootprint/refrigerant-gas-activity-calculate.feature` | Los cinco escenarios del endpoint de cálculo |
| `tests/Acceptance/CarbonFootprint/CarbonFootprintContext.php` | **Modificado**: dos pasos nuevos (`:1484-1507`) |

## Modelo de dominio

### Agregado

`RefrigerantGasActivity extends Activity` (`RefrigerantGasActivity.php:13`), `final`, con dos campos propios sobre los heredados (`id`, `emissionFactor`, `emissions`, `quantity`, `registeredAt`, `updatedAt`, `accountId`):

```php
private RefrigerantGasId $refrigerantGasId;
private ?string $source = null;
```

- `__construct(ActivityId $id, ?AccountId $accountId = null)` (`:18`) — constructor «vacío» que usa el servicio de alta para autorizar antes de registrar nada.
- `static register(...)` (`:24`) — `recordApplyAndPublish(new RefrigerantGasActivityRegistered(...))`.
- `update(...)` (`:47`) — `recordApplyAndPublish(new RefrigerantGasActivityUpdated(...))`.
- `remove()` (`:64`) — `record` + `publish` **sin** `apply`, igual que `CombustibleActivity.php:64-69`: el borrado lo materializa el repositorio, no un cambio de estado del agregado.
- `applyRefrigerantGasActivityRegistered` (`:81`) fija además `accountId` y `registeredAt`; `applyRefrigerantGasActivityUpdated` (`:92`) fija `updatedAt` y nunca toca `accountId`.

### Invariantes

- **`emissions = emissionFactor × quantity.convertTo(factor.unit())`** (`EmissionsCalculator.php:15-22`). Como el maestro fuerza `Unit::Kilogram` en cada factor (`RefrigerantGas.php:195,205`), la cantidad debe ser de `Magnitude::Mass`; si no, `Quantity::convertTo` lanza `IncompatibleUnitsException` (`Quantity.php:29-40`), que extiende `\InvalidArgumentException`. Ejemplo: factor 1,5 kg CO2eq/kg y cantidad 2 t → 2000 kg → 3000 kg CO2eq.
- **El factor se copia en la actividad.** `EmissionFactor` se embebe en la actividad, así que editar el maestro después no altera actividades ya calculadas.
- **Una actividad referencia exactamente un `RefrigerantGasId`** y la columna es NOT NULL; no hay FK al maestro, igual que en `combustible_activities`.
- **`source` es texto libre** (255, nullable): la instalación o equipo del que procede la fuga, no un maestro.

### Eventos de dominio

Los tres extienden `App\CarbonFootprint\Domain\DomainEvent` y devuelven `activityId` como `aggregateRootId()`.

| Evento | `$eventName` | Payload |
|---|---|---|
| `RefrigerantGasActivityRegistered` | `refrigerantGasActivityRegistered` | `activityId`, `accountId`, `refrigerantGasId`, `emissionFactor*` (co2eq, co2, ch4, n2o, year, unit, source, groupId), `emissions*`, `quantityValue`, `quantityUnit`, `source` |
| `RefrigerantGasActivityUpdated` | `refrigerantGasActivityUpdated` | Igual sin `accountId` |
| `RefrigerantGasActivityRemoved` | `refrigerantGasActivityRemoved` | `activityId` |

`rebuildFromPayload` reconstruye las unidades con `Unit::tryFrom` + `assert` (`RefrigerantGasActivityRegistered.php:105-109`). Los tres quedan inscritos en `var/events.json` como `carbon-footprint.refrigerantGasActivity{Registered,Updated,Removed}`. No hay plantillas de Registro de auditoría específicas: como el resto de tipos de actividad, usan `default.html.twig`.

## Flujo de una operación

### Alta (`RegisterRefrigerantGasActivityService.php:32-54`)

1. `ActivityId::generate()`.
2. `authorizer->denyUnlessCanDoThat(new RefrigerantGasActivity(id, accountId), SecurityAction::Register, $user)` — se autoriza sobre una instancia vacía con la cuenta de la petición, antes de tocar repositorios.
3. `factorSelector->select((string) $request->refrigerantGasId, $request->year, null, $request->emissionFactorGroupId)` (`:38`) — `$condition` y `$secondaryItemId` no aplican a este tipo.
4. `calculator->calculate($emissionFactor, $request->quantity)` (`:39`) — aquí salta `IncompatibleUnitsException` si la cantidad no es másica.
5. `RefrigerantGasActivity::register(...)` → `activityRepository->save($activity)` → `RegisterRefrigerantGasActivityResponse`.

`isHandled` (`:56`) responde al `Request` por `instanceof`, de modo que el servicio también es despachable por el `CommandBus`.

### Actualización (`UpdateRefrigerantGasActivityService.php:32-56`)

`findById` → si el resultado **no** es `RefrigerantGasActivity` (incluido `null`) se lanza `ActivityNotFoundException` (`:36-38`) → `denyUnlessCanDoThat($activity, SecurityAction::Edit, $user)` → selección de factor → cálculo → `$activity->update(...)` → `save`.

### Borrado

Sin servicio propio: `RemoveActivityService` es genérico y `RefrigerantGasActivity::remove()` publica `RefrigerantGasActivityRemoved`.

### Desde el asistente (circuito preparado, aún no enchufado)

`POST` del paso → `RefrigerantGasAnswerHttpMapper::map(array $row)` → `RefrigerantGasAnswer` → `RefrigerantGasAnswerHandler::register|update` → servicios de arriba. El mapper (`RefrigerantGasAnswerHttpMapper.php:20-39`) devuelve `null` —y la fila se ignora— si:

- falta `itemId` o está en blanco (`parseItemId`);
- `unit` no existe en el enum `Unit`, o `quantity` es negativa (`parseQuantity`, `AbstractSectionAnswerHttpMapper.php:31-41`);
- la unidad no es de masa (`Magnitude::Mass !== $quantity->unit()->magnitude()`, `:29-31`) — **esta comprobación es propia de este tipo**.

`update` exige `existingActivityId` no nulo vía `assert` (`RefrigerantGasAnswerHandler.php:51`).

### Cálculo en vivo (único punto de entrada HTTP activo hoy)

`POST /{_locale}/events/{eventId}/carbon-footprint/emissions/calculate` → `CalculateEmissionsController` (`src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/CalculateEmissionsController.php:26`), ruta `events_carbon_footprint_emissions_calculate`. Es genérico: resuelve `ActivityType::tryFrom($payload->getString('type'))` (`:45`) y por tanto acepta `refrigerant_gas` desde que existe el caso del enum. Autoriza `SecurityAction::View` sobre la huella (`:41`), delega en `CalculateActivityEmissionsService` → `ActivityEmissionsResolver` → el selector registrado para el tipo, y responde:

- **400** parámetros inválidos (tipo desconocido, `itemId` vacío, `unit` desconocida);
- **403** el usuario no puede ver la huella;
- **422** `\InvalidArgumentException`, que incluye tanto `IncompatibleUnitsException` (unidad no másica) como «gas no encontrado» / «sin factor para el año»;
- **200** `{emissions: {co2eq, co2, ch4, n2o}, emissionFactor: {co2eq, unit, year}}`.

El endpoint público de contribuciones de proveedor (`src/CarbonFootprint/Infrastructure/UI/Web/Public/ProviderContribution/CalculateEmissionsController.php:43`) también resuelve el tipo genéricamente y acepta `refrigerant_gas`; solo calcula, nunca persiste (el guardado público se filtra por `Phase::providerSections()`, donde no hay sección). Ver `review.md` [B1].

### Informe

`ActivityItemNameResolver::resolve` (`ActivityItemNameResolver.php:34-46`) obtiene el `itemId` con el brazo nuevo (`:82`) y lo traduce a nombre usando el catálogo del tipo. Si el gas ya no está en el catálogo de ese año y grupo, cae al `trans($activityType->value, [], 'messages')`, es decir a la clave `refrigerant_gas` del dominio `messages`, que hoy **no existe** (como tampoco `waste` ni `combustible`): en ese caso se mostraría la clave cruda. Es el comportamiento preexistente de todos los tipos, no una regresión de este plan.

## Autorización y ámbito

Se reutiliza `ActivityAuthorizer` (`src/CarbonFootprint/Application/Activity/ActivityAuthorizer.php`) sin cambios:

| Acción | Regla | Línea |
|---|---|---|
| `Register` | `canRegister → canList`: `SuperAdmin`, `Admin`, `Owner` o `Provider` | `:15-28` |
| `Edit` (y `Remove`, `View`) | Sobre una `Activity`: `Owner` **o** `Provider` **y** `currentAccountId($user)` igual a `accountId` de la actividad | `:30-44` |

El ámbito de cuenta se resuelve siempre con `currentAccountId($user)` (impersonación respetada), nunca con `$user->accountId()`. Los roles se comprueban con `userHasRole`, que atraviesa `CurrentRolesProvider`.

Consecuencias prácticas: un rol `User` no puede registrar actividades; un `Owner` de otra cuenta no puede actualizarlas. El endpoint de cálculo añade una barrera anterior e independiente, `View` sobre `EventCarbonFootprint`, que exige `Owner` de la cuenta actual (`EventCarbonFootprintAuthorizer.php:20-23,38-53`) — por eso un `ROLE_USER` recibe 403 antes de llegar al selector.

No hay feature flags ni variables de entorno implicadas.

## Persistencia y migración

Tabla `refrigerant_gas_activities`, hija de `activities` en una herencia `JOINED` con columna discriminadora `type`:

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `VARCHAR(36)` `(DC2Type:activity_id)` | PK; FK a `activities(id)` `ON DELETE CASCADE` |
| `refrigerant_gas_id` | `VARCHAR(36)` `(DC2Type:refrigerant_gas_id)` | NOT NULL, sin FK al maestro |
| `source` | `VARCHAR(255)` | NULL |

Discriminador `refrigerant_gas` añadido a `Activity.Activity.orm.xml:21`; la columna `type` no cambia de tipo ni de longitud, así que no hay backfill ni datos que migrar.

**Migración:** `migrations/Version20260915154335.php`, creada a mano por el usuario (el plan declaraba `migration.sql`). `up()` crea la tabla y la constraint `FK_5C3068DABF396750`; `down()` las elimina. Los tests de aceptación usan SQLite con esquema generado desde los mappings, por lo que no dependen de ella.

## Configuración y operación

Nada que configurar. No hay comandos de consola, tareas programadas, colas Messenger, correos ni notificaciones propios de esta funcionalidad. Los servicios se registran por el `resource` del BC y las cinco piezas enchufables se indexan solas por sus tags:

| Interfaz | Tag | Registry |
|---|---|---|
| `EmissionFactorSelector` | `carbon_footprint.emission_factor_selector` | `EmissionFactorSelectorRegistry` |
| `ActivityCatalogProvider` | `carbon_footprint.activity_catalog_provider` | `ActivityCatalogProviderRegistry` |
| `ActivityAnswerHandler` | `carbon_footprint.activity_answer_handler` | `ActivityAnswerHandlerRegistry` |
| `SectionAnswerHttpMapper` | `carbon_footprint.section_answer_http_mapper` | `SectionAnswerHttpMapperRegistry` |
| `ActivityRowViewFactory` | `carbon_footprint.activity_row_view_factory` | `ActivityRowViewFactoryRegistry` |

Todos indexan por `activityType()->value`, de forma que la clave es `refrigerant_gas` en los cinco.

## Cómo extenderlo

### Enchufar la sección en el asistente (plan de alcance 1)

Las piezas de este plan quedan inertes hasta que exista una sección. Para activarla:

1. Añadir el caso correspondiente al enum `Source` (`src/CarbonFootprint/Domain/Model/EventCarbonFootprint/Source.php`).
2. Añadir la `SectionDefinition` en `Phase::sections()` y, si procede, en `Phase::providerSections()` (`Phase.php:17-72`).
3. Pasar el catálogo y las filas a la plantilla del paso desde la step view que corresponda (`AssemblyBreakdownStepView`, `CelebrationStepView` o `ProviderContributionPhaseStepView`) y renderizar `<twig:CarbonFootprint:RefrigerantGasActivityTable …>`. La tabla espera `title, prefix, rows, items, units, eventId, calculateUrl, readOnly` y filtra ella misma las unidades de masa (`RefrigerantGasActivityTable.html.twig:3`).
4. Revisar `GetPhaseServiceTest`, que construye el `ActivityCatalogProviderRegistry` a mano solo con los tipos que tienen sección: habrá que añadir `RefrigerantGasCatalogProvider`.
5. Contemplar la nueva fuente en el informe PDF y en las exclusiones de fuente.

### Añadir otro tipo de actividad con maestro de unidad fija

Copiar las once piezas de este plan cambiando maestro e identificadores, y **exponer `unit(): Unit` en el maestro**: la plantilla de fila lee `item.unit.value` e `item.unit.magnitude.value` (`RefrigerantGasActivityRow.html.twig:25`) para que el controlador Stimulus `activity-emissions` (`assets/controllers/activity_emissions_controller.js:97-113`) restrinja las unidades del selector.

### Trampas conocidas

- **`ActivityType::fromActivity` tiene `default => throw`**: olvidar el brazo de una subclase nueva rompe en tiempo de ejecución al listar actividades. Lo cubre `ActivityTypeTest`.
- **La restricción de masa vive en tres sitios** y los tres son necesarios: el filtro `massUnits` de la tabla Twig (UI), el `return null` del mapper HTTP (entrada), y `EmissionsCalculator` lanzando `IncompatibleUnitsException` (dominio). Un servicio invocado directamente no pasa por los dos primeros.
- **`ActivityItemNameResolver`** necesita su brazo para que el informe muestre el nombre del gas y no la etiqueta genérica del tipo.

## Tests

```bash
make test-this test=tests/Unit/CarbonFootprint/Domain/Model/Activity/RefrigerantGasActivityTest.php
make test-this test=tests/Unit/CarbonFootprint/Application/Activity/Register/RegisterRefrigerantGasActivityServiceTest.php
make test-this test=tests/Acceptance/CarbonFootprint/refrigerant-gas-activity-calculate.feature
```

Los unitarios cubren el agregado y sus eventos (incluido el ciclo `payload()` → `rebuildFromPayload()`), los dos casos de uso con sus caminos de error (rol `User`, Owner de otra cuenta, actividad de otro tipo, unidad no másica, gas sin factor para el año), el selector, el catálogo, el answer handler, el mapper HTTP y la fábrica de filas.

El `.feature` cubre los cinco escenarios del endpoint de cálculo con el usuario logueado en sesión web (200 en kg, 200 en t con conversión, 422 por litros, 422 por gas sin factor, 403 con `ROLE_USER`). El paso `When I calculate emissions for event …` vive en `CarbonFootprintContext` (`:1484`) para compartir el `KernelBrowser` con `I am logged as`; el paso de `ApiContext` no sirve porque está pensado para JWT.

**Huecos de cobertura:** no hay test que renderice los componentes Twig (no existe para ningún tipo de actividad) ni escenario de persistencia end-to-end del alta, porque no hay sección en el asistente desde la que provocarla.

## Desviaciones y deuda

Según `## Implementación` del plan: ninguna desviación, sin deuda técnica declarada; la migración quedó pendiente para el usuario y ya está creada (`migrations/Version20260915154335.php`).

De `review.md` (0 hallazgos abiertos tras la re-revisión 2) y `review-fix.md`:

- **[A1] Corregido.** `RefrigerantGasActivityRow` accedía a `item.unit`, que el maestro no exponía. Se añadió `RefrigerantGas::unit()` devolviendo `Unit::Kilogram`, con el test `testUnitIsAlwaysKilogram`.
- **[B2] Corregido.** Se eliminó la clave `fields.refrigerant_gas` añadida por el plan: quedaba sin consumidor y duplicaba `fields.refrigerantGas` (que sí lo tiene, `RefrigerantGasImportationType.php:50`). Esta corrección **contradice** la decisión del plan sobre traducciones.
- **[B1] Descartado por decisión del usuario.** La frase del plan sobre el «único punto de entrada HTTP» omite el endpoint público de contribuciones de proveedor, que también acepta `type: refrigerant_gas` (solo cálculo, con token y rate limiting). Sin cambios en código; queda como corrección de texto del plan para una iteración futura.
