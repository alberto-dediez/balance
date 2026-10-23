# Calculadora de viajes de cuenta — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-23 · **Bounded Contexts:** `CarbonFootprint` (principal), `Shared` (geolocalización), `Property` (filtro por varias cuentas), `Balance` (origen «Viaje»), `Security` (solo `security.yaml` y la política de consultoras, sin cambios de código)

## Qué hace y por qué

Hasta ahora la calculadora pública de viajes (QR/enlace con formulario de origen, tramos y noches, resultado y compensación) solo existía atada a una propiedad: el destino era siempre la propiedad. Una agencia de viajes, o una asociación hotelera (Greenhost, dada de alta como consultora de las cuentas de sus hoteles), necesita la misma calculadora sin propiedad detrás y con destino elegido por el visitante.

La solución no crea un agregado paralelo: `TripCalculationLink` (`src/CarbonFootprint/Domain/Model/TripCalculationLink/TripCalculationLink.php`) puede pertenecer a una propiedad (como antes) o directamente a una cuenta (`propertyId = null`, `isForProperty() === false`). Hay una única calculadora de cuenta por cuenta, creada de forma perezosa e idempotente. Todo el flujo público (tramos, distancias, emisiones, consentimiento, rate limiter, compensación, landing) se reutiliza; cambian el nombre mostrado, el origen del destino, la autorización y el origen de las aportaciones. Las decisiones están en la tabla *Decisiones tomadas* de `plan.md`.

Novedades de comportamiento:

- El destino es obligatorio en la calculadora de cuenta (también con alcance «solo pernoctación») y se guarda en cada `TripCalculation`. En las de propiedad se guarda la propiedad como destino.
- El Propietario restringe el destino con `TripDestinationMode`: `free`, `countries` (1–15 países), `region` (rectángulo + nombre de una región de Google), `all_properties` (propias y, si es consultora activa, las de sus cuentas gestionadas) y `selected_properties`.
- Pantallas privadas nuevas (configuración y respuestas), mapa de respuestas con flechas origen→destino, gráfico «Top de destinos» y exportación `account_trip_calculations`.
- Rutas públicas nuevas `/carbon-footprint/trip/{token}/…` junto a las existentes `/carbon-footprint/property-trip/{token}/…`.

## Mapa de piezas

### Shared (geolocalización)

| Fichero | Rol | |
|---|---|---|
| `src/Shared/Domain/Model/Geo/GeoBounds.php` | Rectángulo `low`/`high`; `contains()` soporta antimeridiano | nuevo |
| `src/Shared/Domain/Model/Geo/PlaceSearchArea.php` | Área de búsqueda (`regionCodes` ≤ 15, `bounds`) | nuevo |
| `src/Shared/Domain/Model/Geo/PlaceFinder.php`, `PlaceKind.php` (`Region`), `GeoPlace.php` (`name`, `viewport`, `areaNames`, `countryCode`) | Puerto ampliado | modificado |
| `src/Shared/Application/Place/CountryRegionCode.php` | Puerto: ISO del maestro `Country` (alfa-3 o alfa-2) → alfa-2 | nuevo |
| `src/Shared/Infrastructure/Geo/IntlCountryRegionCode.php` | Adaptador con `Symfony\Component\Intl\Countries` | nuevo |
| `src/Shared/Infrastructure/Geo/Google/GooglePlacesApiPlaceFinder.php` | `includedRegionCodes`, `locationRestriction.rectangle`, `(regions)`, máscara de detalle ampliada, `AREA_COMPONENT_TYPES` (`:25-38`) | modificado |
| `src/Shared/Infrastructure/Geo/CachedPlaceFinder.php` | Clave `cf_geo_place_v2_<locale>_<hash>` (`:38`) | modificado |
| `src/Shared/Infrastructure/Geo/NullPlaceFinder.php` | Firma nueva | modificado |

### Property y Balance

| Fichero | Rol | |
|---|---|---|
| `src/Property/Domain/Model/Property/PropertyFilter.php`, `DoctrinePropertyRepository.php`, `InMemoryPropertyRepository.php` | `accountIds` (lista vacía ⇒ ningún resultado) | modificado |
| `src/Balance/Domain/Model/ProjectContribution/ProjectContributionOrigin.php` | `TripCalculation = 'trip_calculation'` | modificado |
| `src/Balance/Infrastructure/Tcpdf/TcpdfContributionCertificatePdfGenerator.php` | Etiqueta «Viaje» en el `match` del origen | modificado |

### CarbonFootprint · Dominio

| Fichero | Rol | |
|---|---|---|
| `Domain/Model/TripCalculationLink/TripCalculationLink.php` | `registerForAccount()` (`:159`), `updateDestination()` (`:178`), `applyTripCalculationLinkDestinationUpdated()` (`:249`), accesores de destino | modificado |
| `…/TripCalculationLinkRegistered.php` | `propertyId` nulo (payload antiguo sigue reconstruyéndose) | modificado |
| `…/TripCalculationLinkDestinationUpdated.php` | Evento `tripCalculationLinkDestinationUpdated` | nuevo |
| `…/TripDestinationMode.php`, `TripDestinationPlaceKind.php` | Enums | nuevo |
| `…/TripCalculationLinkDestinationCountry(.Id).php`, `…DestinationProperty(.Id).php` | Entidades hijas | nuevo |
| `…/TripCalculationLinkDestinationNotConfigurableException.php`, `TripDestinationNotAllowedException.php` | Excepciones | nuevo |
| `…/TripCalculationLinkRepository.php` | `findForAccount(AccountId)` | modificado |
| `Domain/Model/TripCalculation/TripCalculation.php`, `TripCalculationRegistered.php` | Destino del cálculo (`destinationPlaceReference`, `destinationLabel`, `destinationLatitude`, `destinationLongitude`) | modificado |
| `Domain/Model/PublicLanding/PublicLandingOwnerType.php` | `TripCalculationLink = 'trip_link'` | modificado |

### CarbonFootprint · Aplicación (`src/CarbonFootprint/Application/`)

| Fichero | Rol | |
|---|---|---|
| `TripCalculationLink/TripCalculationLinkAuthorizer.php` | Rama de cuenta (`canManageAccountLink`, `:79`) y `canExport` | modificado |
| `TripCalculationLink/RegisterForAccount/*` | Alta idempotente de la calculadora de cuenta | nuevo |
| `TripCalculationLink/UpdateDestination/*` | Configuración de destino | nuevo |
| `TripCalculationLink/SuggestDestinations/*` | Sugerencias públicas de destino | nuevo |
| `TripCalculationLink/GetByToken/GetTripCalculationLinkByTokenService.php` + `Response` | Enlaces de cuenta: `property` nulo, `ownerName`, `asksForDestination` | modificado |
| `Trip/TripDestinationCandidates.php` | Propiedades candidatas y permitidas | nuevo |
| `Trip/TripDestinationSearchArea.php` | `PlaceSearchArea` según el modo | nuevo |
| `Trip/TripDestinationResolver.php` | Resuelve y valida el destino (`resolve()` `:30`) | nuevo |
| `Trip/TripCalculationContributionOrigin.php` | Origen de aportación según el tipo de enlace | nuevo |
| `Trip/OriginDestinationFlow.php`, `Trip/OriginFlowsCalculator.php` (`forTripCalculationsByDestination`) | Flujos por par origen–destino | nuevo / modificado |
| `TripCalculation/Register/RegisterTripCalculationRequest.php`, `…Service.php` | `destinationPlaceReference`; `destination()` (`:199`) | modificado |
| `TripCalculation/GetResults/TripCalculationResultsBuilder.php` | Lógica de resultados extraída; `topDestinations`, `originDestinationFlows`, `undestinedCalculations` | nuevo |
| `TripCalculation/GetResults/GetTripCalculationResultsService.php` + `Response` | Delegan en el builder; `ownerName`, `?Property` | modificado |
| `TripCalculation/GetAccountResults/*` | Resultados de la calculadora de la cuenta actual | nuevo |
| `TripCalculation/Export/AccountTripCalculationsExportFilter.php` | Filtro de exportación (cuenta fijada en servidor) | nuevo |
| `PublicLanding/PublicLandingOwnerLocator.php`, `PublicLandingAuthorizer.php` | Rama `TripCalculationLink` | modificado |
| `Dashboard/DashboardCompensationsCalculator.php` | `TripCalculation` cuenta como voluntaria | modificado |

### CarbonFootprint · Infraestructura

| Fichero | Rol | |
|---|---|---|
| `Infrastructure/Persistence/Doctrine/Mapping/TripCalculationLink.TripCalculationLink.orm.xml` y las dos `…DestinationCountry/Property.orm.xml` | Columnas y tablas hijas | modificado / nuevo |
| `Infrastructure/Persistence/Doctrine/Mapping/TripCalculation.TripCalculation.orm.xml` | Columnas `destination_*` | modificado |
| `Infrastructure/Persistence/Doctrine/Type/TripCalculationLinkDestinationCountryIdType.php`, `…PropertyIdType.php` + `config/packages/doctrine.yaml` | Tipos Doctrine | nuevo / modificado |
| `Infrastructure/UI/Web/AccountTripCalculator/{IndexController,ResultsController,TripCalculationDestinationForm,TripCalculationDestinationFormData}.php` | Pantallas privadas | nuevo |
| `Infrastructure/Symfony/Form/UpdateTripCalculationLinkDestinationType.php`, `AccountTripCalculationsExportType.php` | Formularios | nuevo |
| `Infrastructure/UI/Web/PropertyTripCalculation/TripCalculationScopeForm.php` (`handleForLink`), `ResultsDateRangeTrait.php`, `View/ResultsView.php` | Reutilización con la calculadora de propiedad | modificado / nuevo |
| `Infrastructure/UI/Web/Component/TripCalculationLinkQr.php` + `templates/components/CarbonFootprint/TripCalculationLinkQr.html.twig` | QR del enlace de cuenta | nuevo |
| `Infrastructure/UI/Web/Map/TripFlowsMapBuilder.php` (`originDestinationFlows`), `src/Shared/Infrastructure/UI/Web/Component/Map/Flows.php` (`arrows`), `templates/components/Map/Flows.html.twig`, `assets/controllers/flows_map_controller.js`, `templates/carbon_footprint/_origins_map.html.twig` | Mapa con destinos propios y flechas | modificado |
| `Infrastructure/UI/Web/Public/PropertyTrip/PublicTripRoutes.php` | Prefijo de rutas según el tipo de enlace | nuevo |
| `Infrastructure/UI/Web/Public/PropertyTrip/DestinationSuggestionsController.php` | Endpoint público de sugerencias | nuevo |
| `Infrastructure/UI/Web/Public/PropertyTrip/{IndexController,ResultController,PlaceSuggestionsController,Step/TripController,Contribution/*}.php` | Segunda ruta `trip`, `PublicTripRoutes`, `ownerName`, destino, origen de aportación | modificado |
| `Infrastructure/DataExchange/AccountTripCalculationsExportationType.php` | Exportación `account_trip_calculations` | nuevo |
| `templates/carbon_footprint/account_trip_calculator/{index,results}.html.twig`, `templates/carbon_footprint/public/property_trip/{layout,result,step/trip}.html.twig`, `templates/carbon_footprint/public/trip/_legs.html.twig`, `templates/carbon_footprint/property_trip_calculations/_stats.html.twig` | Plantillas | nuevo / modificado |
| `assets/controllers/trip_destination_mode_controller.js` | Muestra los campos según el modo | nuevo |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (nodo «Calculadora de viajes» en `toOwners()`, `:609`) | Menú | modificado |
| `config/packages/security.yaml` (`:77`) | `PUBLIC_ACCESS` para `^/[a-z]{2}/carbon-footprint/trip/` | modificado |
| `migrations/Version20261023110814.php` | Migración | nuevo |

## Modelo de dominio

**`TripCalculationLink`**: `propertyId` es ahora opcional; `isForProperty()` decide casi todo. Campos nuevos: `destinationMode`, `destinationPlaceKind`, seis columnas de región (`destinationRegionReference`, `…Label`, `…Low/HighLatitude/Longitude`, con accesor `destinationRegionBounds(): ?GeoBounds`) y las colecciones `destinationCountries` / `destinationProperties`.

**Invariantes de `updateDestination()`** (`TripCalculationLink.php:178`), por orden: enlace de propiedad ⇒ `TripCalculationLinkDestinationNotConfigurableException`; `countries` con 0 o más de 15 países ⇒ `InvalidArgumentException('Debes elegir entre 1 y 15 países.')`; `region` sin referencia, etiqueta o rectángulo ⇒ «Debes elegir una región.»; `selected_properties` vacío ⇒ «Debes elegir al menos una propiedad.». Descarta los datos que no corresponden al modo y los duplicados. Publica `TripCalculationLinkDestinationUpdated`.

`applyTripCalculationLinkDestinationUpdated()` **sincroniza** las colecciones hijas (conserva filas, quita sobrantes, añade nuevas) en lugar de vaciar y reañadir: Doctrine inserta antes de ejecutar las bajas por `orphan-removal` y reinsertar el mismo país/propiedad violaría los únicos `uniq_trip_link_destination_*`.

**`TripCalculation`** guarda el destino final (referencia, etiqueta, coordenadas), nulo en cálculos anteriores. `TripCalculationRegistered` lo serializa como `destination` y `rebuildFromPayload` tolera su ausencia.

**Eventos**: `TripCalculationLinkRegistered` (`propertyId` puede ser `null`), `TripCalculationLinkDestinationUpdated` (payload: `mode`, `placeKind`, `countryIds`, `propertyIds` y los datos de región).

### Reglas de validación del destino

| Modo | Datos | Sugerencias | Validación al registrar (`TripDestinationResolver`) |
|---|---|---|---|
| `free` | — | Google, `placeKind` | Se resuelve el lugar; si Google no lo resuelve, se acepta solo la referencia |
| `countries` | 1–15 países con ISO convertible | Google + `includedRegionCodes` (alfa-2 en minúsculas) | `GeoPlace::countryCode` ∈ alfa-2 de los países elegidos; lugar no resoluble ⇒ rechazo |
| `region` | referencia, etiqueta, rectángulo | Google + `locationRestriction.rectangle` | punto ∈ rectángulo y, si la región se resuelve con nombre, ese nombre ∈ `areaNames` del lugar (sin distinguir mayúsculas); si no se resuelve, basta el rectángulo (`:112`) |
| `all_properties` | — | Propiedades candidatas cuyo nombre contiene la consulta (máx. 10) | `property:<uuid>` ∈ candidatas |
| `selected_properties` | ≥ 1 propiedad | Las seleccionadas que siguen siendo candidatas | `property:<uuid>` ∈ seleccionadas ∩ candidatas |

**Candidatas** (`TripDestinationCandidates::candidatesFor`): propiedades con coordenadas (`PropertyLocator::locate`) cuya cuenta ∈ {cuenta} ∪ `ConsultantSupervisionPolicy::supervisedAccountIds()`, ordenadas por nombre. Una propiedad que pierde sus coordenadas deja de aceptarse.

`TripDestinationNotAllowedException` extiende `\InvalidArgumentException` («El destino elegido no está permitido en esta calculadora.»), por lo que la captura `\InvalidArgumentException` de `Step/TripController` la muestra como flash.

## Flujo de una operación

### Configurar la calculadora (Propietario)

1. `GET|POST /carbon-footprint/trip-calculator` (`carbon_footprint_trip_calculator`) → `AccountTripCalculator/IndexController`.
2. `RegisterAccountTripCalculationLinkService`: cuenta actual vía `CurrentAccountIdProvider` (nunca `$user->accountId()`), autoriza `Action::Register` sobre un enlace stub con esa cuenta, devuelve `findForAccount()` si existe o crea uno con `registerForAccount()`.
3. Tres formularios en la misma pantalla: alcance (`TripCalculationScopeForm::handleForLink`), destino (`TripCalculationDestinationForm` → `UpdateTripCalculationLinkDestinationService`) y landing (`PublicLandingForm` con `PublicLandingOwnerType::TripCalculationLink`). Al enviar uno válido se redirige a la misma ruta.
4. `UpdateTripCalculationLinkDestinationService`: autoriza `Action::Edit`; en `countries` exige ISO convertible a alfa-2 («El país elegido no tiene código ISO.»); en `region` resuelve la referencia con `PlaceFinder` y exige `viewport` (si no, «No se ha podido obtener la zona de la región elegida.»; referencia vacía ⇒ «Debes elegir una región.»); en `selected_properties` exige que cada propiedad sea candidata. Las `\InvalidArgumentException` se convierten en `FormError` sobre el campo del modo (`TripCalculationDestinationForm::fieldFor`).
5. El selector de propiedades distingue nombres repetidos: añade la cuenta propietaria entre paréntesis si la propiedad es de otra cuenta o el nombre se repite, y un índice ` (2)`, ` (3)`… si aun así coincide (`propertyLabel()`, `uniqueLabel()`). El de países solo ofrece países con ISO convertible (`hasIso()`).

### Visitante público

1. `GET /carbon-footprint/trip/{token}` (o la ruta `property-trip`; ambas aceptan cualquier token). `IndexController` muestra la landing (`ownerType` según `asksForDestination`) o redirige al formulario.
2. `Step/TripController` (`…/trip`): pasa `ownerName`, `asksForDestination`, `routePrefix`. En el `POST` valida CSRF y consentimiento y crea `RegisterTripCalculationRequest` con `destinationPlaceReference`.
3. Las sugerencias de destino salen de `GET …/destinations` (`DestinationSuggestionsController`, mismo rate limiting que `PlaceSuggestionsController`) → `SuggestTripDestinationsService` (público; enlace inexistente o de propiedad ⇒ `TripCalculationLinkTokenInvalidException`; consulta de menos de 3 caracteres ⇒ lista vacía).
4. `RegisterTripCalculationService` (`:52`): valida por alcance; `destination()` (`:199`) usa `TripDestinationResolver` para cuentas y la propiedad (nombre + coordenadas) para propiedades; el destino es el último waypoint de `TripLegsResolver`; guarda el cálculo con destino y concede el consentimiento. La pernoctación no depende del destino (`TripEmissionsCalculator` sin cambios).
5. Resultado y compensación reutilizan los controladores existentes. `CheckoutController` crea la aportación con `TripCalculationContributionOrigin::for($link)` (`PropertyTripCalculation` o `TripCalculation`) y `originName` = nombre de la propiedad, o etiqueta del destino, o nombre de la cuenta. `PropertyTripContributionTrait::resolveContribution` comprueba cuenta, origen y que el `originId` sea un cálculo del enlace (retorno, certificado y envío por correo devuelven 404 si no).

### Respuestas

`GET /carbon-footprint/trip-calculator/responses` → `AccountTripCalculator/ResultsController` → `GetAccountTripCalculatorResultsService` → `TripCalculationResultsBuilder::build()`. `ResultsView` arma el mapa con flechas (`mapArrows`) y `destinationsChart` («Top de destinos», 8 primeros). Los cálculos sin destino localizado se cuentan en `undestinedCalculations`.

## Autorización y ámbito

- `TripCalculationLinkAuthorizer`: enlace de propiedad ⇒ delega en `PropertyAuthorizer` (sin cambios). Enlace de cuenta (`canManageAccountLink`, `:79`) ⇒ `Owner` cuya cuenta actual (`currentAccountId`, respeta impersonación) es la del enlace, o `Admin`. Rige para `Register`, `View`, `List`, `Edit`, `Remove`; `canExport` = `canView`.
- Sin feature flag: visible para todas las cuentas (a diferencia de las propiedades, que dependen de `PROPERTIES_ENABLED`).
- Administradores acceden impersonando (ven el menú de Propietario); no hay entrada en el menú de Admin.
- La URL privada no lleva id, así que un Propietario siempre ve la calculadora de su cuenta; un `ROLE_USER` recibe 403 en configuración, respuestas y exportación.
- Rutas públicas: `PUBLIC_ACCESS` en `config/packages/security.yaml:76-77`. Sin la regla `trip` esas rutas exigen sesión.
- El botón «Compensar emisiones» de las respuestas solo se muestra a `ROLE_OWNER` y usa el origen existente `account` (`OwnerContributionContextResolver`).
- Exportación: `AccountTripCalculationsExportationType` implementa `FilterAuthorizedExportationType`; la cuenta se fija en el servidor desde `CurrentAccountIdProvider` al crear el filtro, el form type no tiene campo de cuenta y el worker solo lee la cuenta guardada en los criterios.

## Persistencia y migración

Migración **aplicada**: `migrations/Version20261023110814.php`.

- `trip_calculation_links`: `property_id` pasa a nulo (se conserva su único), `destination_mode VARCHAR(20)`, `destination_place_kind VARCHAR(10)`, seis columnas de región nulas, índice `idx_trip_calculation_links_account_id`. Doctrine genera las columnas como `NOT NULL` sin `DEFAULT`; la migración rellena con dos `UPDATE` (`'free'` y `'city'`) las filas existentes (`:81-82`). En entornos donde se ejecutó antes de añadir esos `UPDATE`, hay que lanzarlos a mano.
- Tablas nuevas `trip_calculation_link_destination_countries` y `trip_calculation_link_destination_properties`, con FK en cascada al enlace y único `(trip_calculation_link_id, country_id|property_id)`.
- `trip_calculations`: cuatro columnas `destination_*` nulas; sin backfill (cálculos anteriores quedan sin destino).
- `public_landings.owner_type` (`VARCHAR(10)`) admite `trip_link`; `project_contributions.origin` (`VARCHAR(40)`) admite `trip_calculation`.
- La unicidad «una calculadora de cuenta por cuenta» no está en base de datos (MySQL no permite el único parcial): la garantiza el servicio. Dos primeras visitas simultáneas podrían crear dos; `findForAccount` debe devolver siempre el mismo (el más antiguo).

## Configuración y operación

- Sin variables de entorno nuevas. Google Places: la máscara de detalle pasa a `id,displayName,formattedAddress,location,viewport,addressComponents`; conviene verificar en Google Cloud que el SKU de Place Details no sube.
- Caché de lugares: clave `cf_geo_place_v2_…`. Los `GeoPlace` cacheados con la clave anterior no se leen; si se amplía `GeoPlace` otra vez, hay que subir la versión de la clave.
- Colas y consola: ninguna nueva. La exportación usa el pipeline de `DataExchange` ya existente.
- Rutas: 12 nombres `public_carbon_footprint_trip*` (los 11 controladores previos más `DestinationSuggestionsController`), con las mismas terminaciones que `public_carbon_footprint_property_trip*`. Comprobar con `make sf c="debug:router"`.
- Rate limiting: los limitadores `property_trip_*` (`config/packages/rate_limiter.yaml`) sirven a ambas rutas.
- Greenhost se configura sin código nuevo: es una cuenta consultora de las cuentas de sus hoteles; sus Propietarios impersonan a las cuentas gestionadas.

## Cómo extenderlo

**Añadir un modo de restricción de destino**

1. Nuevo `case` en `TripDestinationMode` (y `usesPlaceFinder()`/`usesProperties()`), clave en `translations/tripDestinationMode+intl-icu.*.xlf` (y plantilla `templates/carbon_footprint/translations/tripDestinationMode.html.twig`).
2. Regla de coherencia y datos en `TripCalculationLink::updateDestination()`; persistencia (columna o entidad hija) y mapping.
3. Área de búsqueda en `TripDestinationSearchArea::forLink()` y validación en `TripDestinationResolver::isAllowed()`.
4. Validación de entrada en `UpdateTripCalculationLinkDestinationService`, campo en `UpdateTripCalculationLinkDestinationType` y `data-modes` en `account_trip_calculator/index.html.twig`.
5. Tests: resolver, servicio de actualización, dominio y un escenario Behat.

**Trampas**

- Nunca usar nombres de ruta literales del flujo público: construirlos con `PublicTripRoutes::for($link)->name('_xxx')` o con `routePrefix` en Twig. Los únicos literales aceptados son los `#[Route]`, `PublicTripRoutes` y `PropertyTripQr`.
- El `countryCode` de Google es alfa-2 y el maestro `Country` guarda ISO que puede ser alfa-3 u otro valor: toda comparación pasa por `CountryRegionCode::alpha2()`.
- Las plantillas públicas son compartidas con la calculadora de propiedad y la encuesta de evento: los textos específicos de cuenta cuelgan de `asksForDestination | default(false)`.
- `flows_map_controller.js`: las flechas son opcionales (`arrows`); los mapas de propiedad y de encuesta de evento no deben cambiar. La punta se recalcula en `zoomend` y se retrasa `ARROW_MARKER_OFFSET_PIXELS` para que no la tape el marcador.
- `Flows.html.twig` solo pinta `data-flows-map-destination-value` si hay destino (Stimulus no admite `null` en un valor `Object`).
- El mensaje de excepciones de dominio llega al flash traducido con `TranslatorInterface`: añadir cada mensaje nuevo a `templates/carbon_footprint/translations/tripDestinationMessages.html.twig` para que `make translation` lo extraiga.

## Tests

- Unitarios (`tests/Unit/`): `Shared/Domain/Model/Geo/{GeoBoundsTest,PlaceSearchAreaTest}`, `Shared/Infrastructure/Geo/{Google/GooglePlacesApiPlaceFinderTest,CachedPlaceFinderTest,IntlCountryRegionCodeTest}`, `CarbonFootprint/Domain/Model/TripCalculationLink/{TripCalculationLinkTest,TripCalculationLinkRepositoryTest}`, `CarbonFootprint/Domain/Model/TripCalculation/TripCalculationRegisteredTest` (payloads antiguos), `CarbonFootprint/Application/Trip/{TripDestinationCandidatesTest,TripDestinationResolverTest,OriginFlowsCalculatorTest}`, `…/TripCalculationLink/{RegisterForAccount,UpdateDestination,SuggestDestinations}/*Test`, `…/TripCalculationLinkAuthorizerTest`, `…/TripCalculation/Register/RegisterTripCalculationServiceTest`, `…/GetResults`, `…/GetAccountResults`, `CarbonFootprint/Infrastructure/DataExchange/AccountTripCalculationsExportationTypeTest`, `…/Persistence/Doctrine/Repository/DoctrineTripCalculationLinkRepositoryTest`, `Balance/Infrastructure/Tcpdf/TcpdfContributionCertificatePdfGeneratorTest` (etiqueta «Viaje»).
- Aceptación (`tests/Acceptance/CarbonFootprint/`): `account-trip-calculator.feature` (configuración, países, propiedades, nombres repetidos, resultados, 403) y `account-trip-public.feature` (destino libre, sin destino, países, región, propiedades, rutas `trip` y `property-trip`, solo pernoctación); `property-trip-public.feature` (la propiedad como destino) y `Balance/contribution-certificates.feature` (404 con aportaciones de otra calculadora).
- Ejecución: `make test-unit`, `make test-acceptance`, `make test-parallel` y `make test-this test=<ruta>`.
- Huecos: el camino Doctrine de las colecciones hijas está cubierto solo por el nuevo `DoctrineTripCalculationLinkRepositoryTest` y el de repositorio abstracto; el paso Behat `I submit a form with` trunca valores con dos puntos, por lo que el escenario público de «Solo estas propiedades» se limita a las sugerencias (hallazgo B14 de `review.md`, descartado). El mapa con flechas no tiene tests (JS sin tests en el proyecto).

## Desviaciones y deuda

Desviaciones respecto al plan (detalle en la sección *Implementación* de [`plan.md`](plan.md)): regla `PUBLIC_ACCESS` en `security.yaml`, 12 rutas públicas en vez de 11, `TripDestinationResolver` sin `TripDestinationSearchArea`, sincronización (no vaciado) de colecciones hijas, km medios en los flujos, `TripCalculationLinkQr` con etiqueta propia, mensajes de dominio traducidos en el flash.

Correcciones posteriores (de [`review.md`](review.md), ya aplicadas y reflejadas arriba): validación de países por ISO alfa-2 convertido (A1), nombres administrativos de más tipos para la región (M3), nombres de propiedad repetidos (M1), flechas del mapa (M2, B1, B7), textos de cuenta en las pantallas públicas (B3), mensajes traducidos (B4), comprobación del origen en retorno y certificado (B9), traducciones al turco (B12), países con ISO no convertible (B13), `UPDATE` de relleno en la migración (C1).

Abierto o descartado en `review.md`:

- **B10 (aplazado):** las sugerencias de propiedades cargan todas las candidatas en cada pulsación (`TripDestinationCandidates::candidatesFor`).
- **B5, B8 (descartados):** un Admin pasa la autorización de un enlace sin cuenta ni propiedad; `placeKind` no se valida al resolver el destino.
- Deuda declarada: `TopAction:Return` va el último en `property_trip_calculations/results.html.twig` (la convención lo pide primero); `trip.html.twig` conserva un script con `style.display` inline (previo).
- Pendiente de verificar en navegador: que los mapas de propiedad y de encuesta de evento se ven igual que antes.
