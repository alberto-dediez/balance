# Carga de consumos de Bioscore en la huella de propiedades y de empresas — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-17 · **Bounded Contexts:** `Bioscore` (gateway de consumos, traducción a respuestas, casos de uso de carga, controladores), `CarbonFootprint` (casos de uso de sustitución de un alcance, plantillas de los pasos) · **Migración:** no

## Qué hace y por qué

Las propiedades hoteleras registran en Bioscore sus consumos anuales de electricidad (por comercializadora), de combustibles (de instalaciones y de vehículos) y sus recargas de gas refrigerante. Hasta ahora el Propietario tenía que volver a teclearlos como actividades de Alcance 1 y Alcance 2 al calcular la huella de una propiedad o de una empresa en esta plataforma. Bioscore expone `GET /api/platform/consumptions`, que devuelve los consumos anuales prorrateados de las propiedades de una cuenta de Events agrupados por elemento de catálogo, y el plan `2026-09-17-bioscore-catalog-mapping` ya construyó la tabla de equivalencias entre esos elementos y los maestros de `CarbonFootprint`.

Esta funcionalidad añade la acción «Cargar consumos de Bioscore» al desplegable «Acciones» de los pasos Alcance 1 y Alcance 2 de las huellas de propiedad y de empresa. Al confirmarla, la plataforma descarga de forma síncrona los consumos del año de la huella para la propiedad (o para todas las propiedades de la empresa, en bloques de 100), los traduce a respuestas de sección usando `BioscoreCatalogItem` y `CatalogTargetFinder`, y sustituye **todas** las actividades del alcance de cada propiedad que Bioscore devuelve. Lo que no se puede traducir se omite y se enumera en un aviso; el estado de la huella no cambia; la huella de eventos no participa.

Las decisiones (sustituir el alcance completo de cada propiedad devuelta, retirar la exclusión de las fuentes con datos, omitir en vez de abortar, ejecución síncrona, GET con token CSRF y confirmación, doble uso de un combustible → Instalaciones, unidad de Bioscore o del maestro, reparto de piezas entre los dos BCs, extracción del login JWT a `BioscoreApiAuthenticator`) están justificadas en la tabla *Decisiones tomadas* de `plan.md`.

## Mapa de piezas

### `Bioscore` — dominio (`src/Bioscore/Domain/Model/`)

| Fichero | Rol | Estado |
|---|---|---|
| `BioscoreConsumption/BioscoreConsumptionGateway.php` | Puerto `fetch(BioscoreConsumptionsQuery): list<BioscorePropertyConsumptions>`, lanza `BioscoreUnavailableException` | nuevo |
| `BioscoreConsumption/BioscoreConsumptionsQuery.php` | VO de petición: `accountId`, `propertyIds` (1..100, `MAX_PROPERTIES`), `year` | nuevo |
| `BioscoreConsumption/BioscorePropertyConsumptions.php` | VO de una propiedad devuelta: `propertyId` (de Events), `bioscoreId`, `name`, `year`, listas `electricity`, `combustibles`, `refrigerantGases` (por defecto `[]`) | nuevo |
| `BioscoreConsumption/BioscoreConsumption.php` | VO de un grupo de consumo: `itemId`, `itemName`, `itemUnit`, `usableForFacilities`, `usableForTransportation`, `consumption`, `amountEur` | nuevo |
| `BioscoreCatalogItem/BioscoreUnavailableException.php` | Constructor `forConsumptions(?\Throwable)` (`:14-17`) | modificado |

### `Bioscore` — aplicación (`src/Bioscore/Application/BioscoreConsumption/`)

| Fichero | Rol | Estado |
|---|---|---|
| `BioscoreConsumptionSkipReason.php` | Enum `unmapped`, `target_missing`, `incompatible_unit` (dominio de traducción `bioscoreConsumptionSkipReason`) | nuevo |
| `SkippedBioscoreConsumption.php` | VO de un consumo omitido: `bioscoreId`, `name`, `consumption`, `unit`, `reason`, `propertyId` | nuevo |
| `BioscoreConsumptionAnswers.php` | Resultado del builder: `sectionAnswers` y `skipped`, con `answerCount()` | nuevo |
| `BioscoreConsumptionAnswersBuilder.php` | Traduce `BioscorePropertyConsumptions` a `list<SectionAnswer>` de un `Scope`; servicio puro sin autorización | nuevo |
| `LoadPropertyCarbonFootprintConsumptions/{Request,Response,Service}.php` | Caso de uso de carga sobre una huella de propiedad | nuevos |
| `LoadCompanyCarbonFootprintConsumptions/{Request,Response,Service}.php` | Caso de uso de carga sobre una huella de empresa | nuevos |

### `Bioscore` — infraestructura (`src/Bioscore/Infrastructure/`)

| Fichero | Rol | Estado |
|---|---|---|
| `Gateway/BioscoreApiAuthenticator.php` | Login JWT compartido (`POST {apiBaseUrl}/login`), memoriza el token en la instancia, `invalidate()` | nuevo |
| `Gateway/HttpBioscoreConsumptionGateway.php` | Cliente HTTP de `GET {apiBaseUrl}/platform/consumptions` (dev/prod) | nuevo |
| `Gateway/InMemoryBioscoreConsumptionGateway.php` | Doble configurable (test): `set`, `add`, `fail(int $fromQuery = 1)`, `reset`, `lastQuery`, `queries` | nuevo |
| `Gateway/HttpBioscoreCatalogGateway.php` | Usa `BioscoreApiAuthenticator` en lugar de su propio `token()` | modificado |
| `Symfony/DependencyInjection/services.yaml` | Argumentos escalares del autenticador y de los dos gateways HTTP en `when@dev` y `when@prod` | modificado |
| `UI/Web/CarbonFootprint/LoadPropertyCarbonFootprintConsumptionsController.php` | Ruta `property_carbon_footprints_bioscore_load` | nuevo |
| `UI/Web/CarbonFootprint/LoadCompanyCarbonFootprintConsumptionsController.php` | Ruta `company_carbon_footprints_bioscore_load` | nuevo |
| `UI/Web/CarbonFootprint/BioscoreLoadFlashesTrait.php` | Composición de los flashes de resultado, común a ambos controladores | nuevo |

### `CarbonFootprint` — aplicación

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Application/PropertyCarbonFootprint/ReplaceScopeAnswers/ReplacePropertyCarbonFootprintScopeAnswers{Request,Response,Service}.php` | Sustituye todas las fuentes de un alcance de una huella de propiedad sin cambiar el estado | nuevos |
| `src/CarbonFootprint/Application/CompanyCarbonFootprint/ReplaceScopeAnswers/ReplaceCompanyCarbonFootprintScopeAnswers{Request,Response,Service}.php` | Ídem para los bloques (propiedades) recibidos de una huella de empresa | nuevos |

### Plantillas, traducciones y documentación

| Fichero | Rol | Estado |
|---|---|---|
| `templates/components/TopAction/BioscoreConsumptions.html.twig` | Ítem del desplegable «Acciones» con `confirm` | nuevo |
| `templates/carbon_footprint/property_carbon_footprints/step/{scope-1,scope-2}.html.twig` | Bloque `top_actions` con `parent()` + la acción | modificados |
| `templates/carbon_footprint/company_carbon_footprints/step/{scope-1,scope-2}.html.twig` | Ídem | modificados |
| `templates/bioscore/translations/bioscoreConsumptionSkipReason.html.twig` | Plantilla de extracción de las claves del enum (no estaba en el inventario del plan; `review.md` B1) | nuevo |
| `translations/bioscoreConsumptionSkipReason+intl-icu.{es,en,tr}.xlf` | Etiquetas de los motivos de omisión | nuevos |
| `translations/messages+intl-icu.{es,en,tr}.xlf` | Nueve literales nuevos (acción, confirmación, flashes) | modificados |
| `.claude/ARCHITECTURE.md`, `.claude/CONCEPTS.md` | Párrafo «Carga de consumos» de la sección Bioscore; términos «Consumo omitido» y «Cargar consumos de Bioscore» | modificados |

## Modelo de dominio

No hay agregado nuevo. `src/Bioscore/Domain/Model/BioscoreConsumption/` contiene solo el puerto y tres VOs `final readonly` que reproducen la respuesta de Bioscore. La única invariante de dominio nueva vive en `BioscoreConsumptionsQuery.php:20-26`: la lista de propiedades no puede estar vacía ni superar `MAX_PROPERTIES = 100` (límite del endpoint de Bioscore); ambos casos lanzan `\InvalidArgumentException`.

`AccountId` (`App\Security\Domain\Model\Account\AccountId`) y `PropertyId` (`App\Property\Domain\Model\Property\PropertyId`) se importan directamente en los VOs, dependencia dominio → dominio entre BCs del mismo tipo que `BioscoreUnitParser` → `Unit`.

### Traducción de un consumo a una respuesta de sección

`BioscoreConsumptionAnswersBuilder::build(Scope, BioscorePropertyConsumptions)` (`src/Bioscore/Application/BioscoreConsumption/BioscoreConsumptionAnswersBuilder.php`):

1. `classify()` (`:98-125`) decide lista, catálogo y sección por alcance. Alcance 1 recorre `combustibles` (`usableForFacilities === true` → sección `combustibles`, catálogo `FacilityFuel`; si no y `usableForTransportation === true` → `vehicles`, `TransportFuel`; sin ninguno de los dos → sección `combustibles` con catálogo `null`) y `refrigerantGases` (`refrigerantGases`, `RefrigerantGas`). Alcance 2 recorre solo `electricity` (`electricity`, `ElectricityMarketer`). Las demás listas se ignoran en cada alcance.
2. Por cada grupo (`:43-82`): `consumption <= 0` → se descarta sin contar; catálogo `null` → omitido `Unmapped`; `findByCatalogAndBioscoreId(catalog, itemId)` nulo, no `isMapped()` o sin `targetId()` → `Unmapped`; `CatalogTargetFinder::find()` nulo (id no UUID, maestro inexistente o eliminado) → `TargetMissing`; `BioscoreUnitParser::parse(itemUnit)` reconocida pero de magnitud distinta a `target->unit` → `IncompatibleUnit`. Si pasa, `Quantity(consumption, parsedUnit ?? target->unit)`.
3. `answer()` (`:127-135`) construye `CombustibleAnswer`, `RoadTransportAnswer`, `RefrigerantGasAnswer` o `ElectricityAnswer(marketerId, gdOFactorId: null, …)`, siempre con `source: 'Bioscore'` (`SOURCE`, `:30`), que es lo que muestra la columna «Fuente» de la fila.
4. Solo las secciones con al menos una respuesta aparecen en `sectionAnswers`, en el orden de `$scope->sections()` (`:84-92`). Los omitidos llevan `propertyId` para poder atribuirlos en la huella de empresa.

Las unidades que reconoce `BioscoreUnitParser` (`src/Bioscore/Domain/Model/BioscoreCatalogItem/BioscoreUnitParser.php:16-23`): kg/g/t, l/lt, m3/m³, kWh, kWhPCS. Una unidad vacía o desconocida se sustituye por la del maestro (`Combustible::unit()`, `RoadTransport::unit()`, `Kilogram` para gases y `KWh` para electricidad, fijadas por `CatalogTargetFinder`). Una unidad reconocida de la misma magnitud pero distinta a la del maestro (p. ej. `l` con maestro en `m3`) se respeta y la convierte el cálculo de emisiones.

### Sustitución de un alcance (invariantes)

Para la propiedad `P` y el alcance `S` cargados (`ReplacePropertyCarbonFootprintScopeAnswersService` / `ReplaceCompanyCarbonFootprintScopeAnswersService`):

- Toda fuente `F` de `S` queda con exactamente las respuestas construidas para `F` (cero si Bioscore no trajo nada). Lo garantiza `StageAnswersSynchronizer::sync()` (`src/CarbonFootprint/Application/CarbonFootprint/StageAnswersSynchronizer.php:49-90`): crea una entrada por cada sección del stage aunque no tenga respuestas, registra las nuevas con el `ActivityAnswerHandler` del tipo y hace `retainActivities($placement, $keptIds)` por fuente.
- Toda fuente con respuestas deja de estar excluida: el servicio llama a `includeSource()` **antes** de `sync()` (`ReplacePropertyCarbonFootprintScopeAnswersService.php:48-61`, `ReplaceCompanyCarbonFootprintScopeAnswersService.php:72-89`), porque `CompanyCarbonFootprint::addActivity()` ignora en silencio las fuentes excluidas y dejaría actividades huérfanas en `ActivityRepository`.
- Una fuente excluida sin respuestas conserva su exclusión.
- En la huella de empresa solo se tocan los bloques recibidos; el bloque «Sin asignar a propiedad» y las propiedades que Bioscore no devuelve quedan intactos.
- El estado (`OrganizationalCarbonFootprintStatus`) no cambia: ninguno de los dos servicios llama a `answerScope()`.

## Flujo de una operación

### Carga en una huella de propiedad

1. **Plantilla.** `templates/carbon_footprint/property_carbon_footprints/step/scope-1.html.twig:5-8` (y `scope-2`) redefine `top_actions` con `parent()` («Volver» primero) y `<twig:TopAction:BioscoreConsumptions>` apuntando a `property_carbon_footprints_bioscore_load` con `scope: 'scope_1'` y `_token: csrf_token('bioscore_load_' ~ propertyCarbonFootprint.id)`. El componente (`templates/components/TopAction/BioscoreConsumptions.html.twig`) añade `data-controller="confirm"`, `data-confirm-text-param` con el texto de confirmación y `data-action="confirm#link:prevent"`.
2. **Controlador.** `LoadPropertyCarbonFootprintConsumptionsController` (`GET /{_locale}/property-carbon-footprints/{id}/step/{scope}/bioscore`, `scope` ∈ `scope_1|scope_2`, enrutado por el bloque `bioscore-web` de `config/routes.yaml`): `find` o 404 (`:42-46`); `denyAccessUnlessGranted(Edit)` (`:48`); token CSRF `bioscore_load_<id>` en la query o 403 (`:50-52`); resuelve la ruta del paso de vuelta (`:54-59`); invoca el servicio capturando `UnauthorizedActionException` → 403, `BioscoreUnavailableException` → flash `danger` «No se ha podido conectar con Bioscore», `\InvalidArgumentException|\DomainException` → flash `danger` «No se han podido cargar los consumos: {error}» (`:61-76`); `flashResult()` y redirección al mismo paso (`:78-80`).
3. **Servicio de carga.** `LoadPropertyCarbonFootprintConsumptionsService::__invoke` (`src/Bioscore/Application/BioscoreConsumption/LoadPropertyCarbonFootprintConsumptions/LoadPropertyCarbonFootprintConsumptionsService.php`): `find` o `\InvalidArgumentException` (`:35-39`); `denyUnlessCanDoThat(Edit)` antes de llamar a Bioscore (`:41`); `accountId()` y `propertyId()` de la huella (`:43-46`); `gateway->fetch(new BioscoreConsumptionsQuery($accountId, [$propertyId], $footprint->year()))` (`:48`); busca la entrada cuyo `propertyId` coincide y si no hay devuelve `resolved: false` sin tocar nada (`:50-61`); `answersBuilder->build()` (`:63`); delega en `ReplacePropertyCarbonFootprintScopeAnswersService` (`:65-68`); responde `resolved, activities, skipped, includedSources`.
4. **Gateway HTTP.** `HttpBioscoreConsumptionGateway::request()` (`:53-84`): `GET {apiBaseUrl}/platform/consumptions` con `auth_bearer` y cuerpo `json` `{platform: 'events', account_id, property_ids[], year}`; `401` → `authenticator->invalidate()` y un único reintento; estado `!== 200` o `toArray()` fallido → `BioscoreUnavailableException::forConsumptions()`. `toPropertyConsumptions()` (`:87-109`) exige `property_id` UUID válido, `bioscore_id` y `year` numéricos (si no, `warning` «Skipping malformed Bioscore consumptions row» y se omite la fila); `toConsumption()` (`:138-165`) exige `item.id` numérico, `item.name` string no vacío y `consumption` numérico (si no, `warning` y se omite el grupo); `unit` no string → `''`; flags no bool → `null`; `amount_eur` ausente o no numérico → `0.0`.
5. **Sustitución.** `ReplacePropertyCarbonFootprintScopeAnswersService::__invoke`: vuelve a autorizar `Edit` (`:38`); `StageAnswersProcessor::validate($scope, $sectionAnswers, [])` (`:40`, comprueba que cada sección pertenece al alcance y que el tipo de cada respuesta coincide, sin rechazar por exclusión); incluye las fuentes excluidas con respuestas (`:42-59`); `sync()` (`:61`); `add()` (`:63`); responde `activities` (suma de respuestas) e `includedSources`.
6. **Flashes.** `BioscoreLoadFlashesTrait::flashResult()`: no resuelto → `warning` «Ninguna propiedad de esta huella está vinculada a Bioscore» (`:20-24`); resuelto → `success` «Consumos de Bioscore cargados: {activities} actividades en {properties} propiedad(es)» (`:26-29`, `properties` = 1 en propiedad); `includedSources` no vacío → `warning` «Se han vuelto a incluir las fuentes: {sources}» con los nombres del dominio `source` (`:31-38`); `skipped` no vacío → `warning` «Consumos de Bioscore omitidos ({count}): {items}» con cada ítem como `{name} (#{bioscoreId}, {consumption} {unit}): {motivo}` separados por `; `, donde cantidad y unidad se componen con `trim()` para no dejar espacio cuando la unidad viene vacía (`:40-54`, corrección B2 de `review-fix.md`).

### Carga en una huella de empresa

Misma secuencia con `LoadCompanyCarbonFootprintConsumptionsController` (ruta `company_carbon_footprints_bioscore_load`, redirección a `company_carbon_footprints_scope_1|2`) y `LoadCompanyCarbonFootprintConsumptionsService`:

- `findByCompanyId(companyId)` indexado por id (`:53-57`); si no hay propiedades responde `totalProperties: 0` sin llamar al gateway (`:59-63`) y el controlador muestra `warning` «La empresa no tiene propiedades» (`LoadCompanyCarbonFootprintConsumptionsController.php:78-82`).
- `array_chunk(…, MAX_PROPERTIES)` y un `fetch()` por bloque de 100 (`:65-69`). **Todas** las llamadas preceden a la sustitución: un fallo en cualquiera de ellas se propaga sin haber modificado nada.
- Se descartan las entradas cuyo `propertyId` no pertenece a la empresa (`:73-76`); por cada entrada restante se construye un `PropertySectionAnswers($entry->propertyId, $answers->sectionAnswers)` y se acumulan los omitidos (`:78-81`).
- Sin bloques → `resolvedProperties: 0` (`:84-86`), que el trait traduce al aviso «Ninguna propiedad de esta huella está vinculada a Bioscore».
- `ReplaceCompanyCarbonFootprintScopeAnswersService` (`:43-64`) rechaza con `\InvalidArgumentException` un bloque de una propiedad ajena («Property does not belong to company») o duplicado («Duplicated property block»); por cada bloque valida, incluye las fuentes excluidas con datos una sola vez por fuente (`:80-85`) y sincroniza con `propertyId: $block->propertyId` (`:88`). Devuelve además `blocks`.
- El flash de éxito usa `resolvedProperties` como `{properties}` (`LoadCompanyCarbonFootprintConsumptionsController.php:84`).

### Huella completada

Los pasos de alcance siguen siendo accesibles con una huella en estado `Completed` (el `Authorizer` solo comprueba rol y cuenta), así que la acción se muestra. Cualquier modificación de la huella pasa por `ensureIsEditable()` (`src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintTrait.php:207-212`), que lanza `OrganizationalCarbonFootprintAlreadyCompletedException` (`\DomainException`); el controlador la convierte en el flash «No se han podido cargar los consumos: {error}» y no queda nada a medias.

## Autorización y ámbito

- Los dos servicios de carga autorizan `Action::Edit` con `PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer` **antes** de llamar a Bioscore (`LoadPropertyCarbonFootprintConsumptionsService.php:41`, `LoadCompanyCarbonFootprintConsumptionsService.php:46`); los servicios de sustitución vuelven a autorizar (`ReplacePropertyCarbonFootprintScopeAnswersService.php:38`, `ReplaceCompanyCarbonFootprintScopeAnswersService.php:41`).
- `canEdit` (`src/CarbonFootprint/Application/PropertyCarbonFootprint/PropertyCarbonFootprintAuthorizer.php:38-53`): rol `Owner` y cuenta actual (`currentAccountId($user)`, impersonación incluida) igual a la de la huella. Un `Admin` impersonando al Propietario puede cargar; un `User` o un `Owner` de otra cuenta reciben 403. `Admin` sin impersonar no puede.
- Los controladores repiten `denyAccessUnlessGranted(Edit)` y exigen el token CSRF `bioscore_load_<id>` antes de invocar nada; sin token la respuesta es 403 (escenario *Without a valid token* de `bioscore-consumptions-property.feature`).
- Ámbito de datos: los ids enviados a Bioscore salen de la propia huella (`accountId()`, `propertyId()` o `findByCompanyId()`), y las entradas devueltas se filtran por propiedad de la huella o de la empresa, así que no hay forma de cargar datos de otra cuenta aunque Bioscore los devolviera. No se filtra por `Property::externalId()->bioscore()`: Bioscore resuelve por el id de Events y omite lo que no tiene vinculado.

## Persistencia y migración

Sin cambios de esquema. Las actividades se persisten por los servicios `Register*Activity` existentes a través de `StageAnswersSynchronizer`, y las exclusiones y el vínculo huella–actividad por los repositorios de `PropertyCarbonFootprint` / `CompanyCarbonFootprint`. No hay `migration.sql` en la carpeta del plan.

## Configuración y operación

- `src/Bioscore/Infrastructure/Symfony/DependencyInjection/services.yaml`: en `when@dev` y `when@prod`, `BioscoreApiAuthenticator` recibe `$apiBaseUrl: '%sso_api_url%'`, `$username: '%env(BIOSCORE_API_USERNAME)%'`, `$password: '%env(BIOSCORE_API_PASSWORD)%'`; `HttpBioscoreCatalogGateway` y `HttpBioscoreConsumptionGateway` reciben solo `$apiBaseUrl`. Las tres clases llevan `#[When(env: 'dev')]` y `#[When(env: 'prod')]`; en `test` el contenedor solo compila `InMemoryBioscoreConsumptionGateway` (`#[When(env: 'test')]`).
- Credenciales: las mismas que la sincronización de maestros (`ApiUser` de Bioscore con `ROLE_API_PLATFORM`). Sin ellas, toda carga termina en «No se ha podido conectar con Bioscore».
- El token JWT se memoriza por instancia del autenticador (por proceso); no hay caché compartida. Un `401` provoca `invalidate()` y un único reintento con login nuevo.
- Ejecución síncrona dentro de la petición: no hay comando de consola, cola Messenger, tarea programada, correo ni notificación.
- Filas y grupos malformados de Bioscore se registran con `LoggerInterface::warning` y se omiten en silencio para el usuario.

## Cómo extenderlo

- **Cargar un nuevo tipo de consumo** (p. ej. una nueva lista en la respuesta de Bioscore): añadir la propiedad a `BioscorePropertyConsumptions` y su mapeo en `HttpBioscoreConsumptionGateway::toPropertyConsumptions()` y en `InMemoryBioscoreConsumptionGateway::add()`; añadir el caso en `BioscoreConsumptionAnswersBuilder::classify()` (lista → clave de sección de `Scope::sections()` y `BioscoreCatalog`) y en `answer()` (respuesta del tipo correspondiente). Si el catálogo es nuevo hay que darlo de alta antes en `BioscoreCatalog` y en `CatalogTargetFinder` (plan `bioscore-catalog-mapping`).
- **Nuevo motivo de omisión**: caso en `BioscoreConsumptionSkipReason`, etiqueta en los tres `bioscoreConsumptionSkipReason+intl-icu.*.xlf` y línea en `templates/bioscore/translations/bioscoreConsumptionSkipReason.html.twig` para que `make translation` la extraiga.
- **Cargar en la huella de eventos**: no existe `ReplaceScopeAnswers` para `EventCarbonFootprint`; habría que crearlo en `CarbonFootprint` con la misma semántica (incluir fuente → `sync` → sin cambiar estado) y un servicio de carga y un controlador en `Bioscore`.
- **Trampas conocidas**: (1) llamar a `sync()` sin haber incluido antes la fuente excluida crea actividades huérfanas (`CompanyCarbonFootprint::addActivity()` las ignora); (2) `BioscoreConsumptionsQuery` lanza con más de 100 ids: trocear siempre con `array_chunk(…, MAX_PROPERTIES)`; (3) `CatalogTargetFinder::find()` devuelve `null` si el `targetId` no es un UUID válido, así que las features deben usar UUIDs con variante válida; (4) `BioscoreApiAuthenticator` es un servicio compartido: no volver a instanciarlo en los gateways.

## Tests

### Unitarios (`make test-unit`, o uno concreto con `make test-this test=<ruta>`)

- `tests/Unit/Bioscore/Domain/Model/BioscoreConsumption/BioscoreConsumptionsQueryTest.php`: lista vacía y 101 ids lanzan; 100 se aceptan. Mothers `BioscorePropertyConsumptionsMother` y `BioscoreConsumptionMother` (`facilityFuel`, `transportFuel`, `dualUseFuel`, `fuelWithoutUse`, `refrigerantGas`, `electricity`).
- `tests/Unit/Bioscore/Application/BioscoreConsumption/BioscoreConsumptionAnswersBuilderTest.php`: clasificación por flags (instalaciones, doble uso → instalaciones, solo transporte → vehículos, sin flags → omitido), gases, electricidad solo en Alcance 2, elemento sin mapear / ignorado / inexistente → `unmapped`, maestro eliminado → `target_missing`, unidad vacía → unidad del maestro, `lt` con maestro en `m3` → litros, `kg` con maestro en `m3` → `incompatible_unit`, consumo 0 no cuenta, secciones vacías ausentes.
- `.../LoadPropertyCarbonFootprintConsumptions/LoadPropertyCarbonFootprintConsumptionsServiceTest.php`: montaje real de handlers, sincronizador y servicio de sustitución con repositorios en memoria; carga de Alcance 1 y 2, borrado de las manuales previas, estado sin cambios, exclusión retirada o conservada, Bioscore vacío, omitidos, gateway en fallo, query enviada, `Owner` ajeno y `User` denegados sin llamar al gateway.
- `.../LoadCompanyCarbonFootprintConsumptions/LoadCompanyCarbonFootprintConsumptionsServiceTest.php`: solo la propiedad devuelta se sustituye, ambas, propiedad ajena ignorada, empresa sin propiedades, 150 propiedades → dos queries, fallo en la segunda query sin modificar nada, `Owner` ajeno denegado.
- `tests/Unit/Bioscore/Infrastructure/Gateway/{BioscoreApiAuthenticatorTest,HttpBioscoreConsumptionGatewayTest,InMemoryBioscoreConsumptionGatewayTest}.php` y `HttpBioscoreCatalogGatewayTest.php` (adaptado al autenticador).
- `tests/Unit/CarbonFootprint/Application/PropertyCarbonFootprint/ReplaceScopeAnswers/ReplacePropertyCarbonFootprintScopeAnswersServiceTest.php` y `.../CompanyCarbonFootprint/ReplaceScopeAnswers/ReplaceCompanyCarbonFootprintScopeAnswersServiceTest.php`: sustitución de las tres fuentes, alcance vaciado con respuestas vacías, inclusión de fuentes, estado inalterado, sección ajena al alcance, bloques ajenos o duplicados, inclusión única por fuente.

### Aceptación (`make test-acceptance`)

`tests/Acceptance/Bioscore/bioscore-consumptions-property.feature` (7 escenarios: carga de Alcance 1, elemento sin mapear omitido y avisado, carga de Alcance 2, ninguna propiedad vinculada, Bioscore no disponible, sin token → 403, otra cuenta → 403) y `bioscore-consumptions-company.feature` (solo se sustituye la propiedad devuelta, empresa sin propiedades). Pasos nuevos en `tests/Acceptance/Bioscore/BioscoreContext.php:148-182`: `Bioscore returns for property :propertyId in :year a :group consumption of :consumption :unit of item :bioscoreId named :name` (`:group` ∈ `facility_fuel|transport_fuel|dual_fuel|refrigerant_gas|electricity`), `Bioscore consumptions are unavailable`, `Bioscore was asked for consumptions of account :accountId and property :propertyId in :year`.

Huecos: no hay escenario de aceptación para el flash con unidad vacía (decisión del usuario en `review-fix.md`, B2) ni para la carga sobre una huella completada.

## Desviaciones y deuda

Declarado en `## Implementación` de `plan.md`:

- `InMemoryBioscoreConsumptionGateway::fail(int $fromQuery = 1)` admite fallar a partir de una query concreta.
- Las listas de `BioscorePropertyConsumptions` tienen valor por defecto `[]`.
- `amount_eur` ausente o no numérico → `0.0` en vez de descartar el grupo.
- `BioscoreConsumptionMother::fuelWithoutUse()` extra.
- Las features usan UUIDs válidos y combustible en `kg` (el paso Behat crea el maestro en `kg`).

`review.md` / `review-fix.md`:

- B1 (asumido): `templates/bioscore/translations/bioscoreConsumptionSkipReason.html.twig` existe en el código pero no en el inventario de `plan.md`.
- B2 (corregido): la cantidad del flash de omitidos se compone con `trim()` para no dejar un espacio cuando la unidad viene vacía.

Sin migración pendiente ni deuda técnica declarada.
