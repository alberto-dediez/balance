# Spend-based: magnitud Divisa y tipos de cambio diarios del BCE — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `CarbonFootprint` (principal); `Shared` (`CommandSchedule`, menú lateral, `FilterBar`)
- **Revisión:** `review.md` (revisión 2, 0 hallazgos abiertos) · `review-fix.md`

## Qué hace y por qué

El método *spend-based* del GHG Protocol calcula emisiones a partir del gasto, con factores en kgCO₂e por euro. Para soportarlo, el dinero pasa a ser una magnitud más de `CarbonFootprint`: `Magnitude::Currency`, con `Unit::Euro` como unidad base y las 29 divisas que publica el Banco Central Europeo como unidades convertibles. A diferencia de las magnitudes físicas, el factor a base de una divisa no es constante: se guarda en el agregado `ExchangeRate` (una fila por divisa, último tipo publicado, sin histórico) y se refresca a diario descargando `eurofxref-daily.xml` del BCE.

La conversión de unidades deja de ser exclusiva del enum: las constantes físicas siguen en `Unit::fixedFactorToBase()` (1 t = 1000 kg es una invariante de dominio), y toda conversión desde la capa de aplicación pasa por el servicio de dominio `UnitConverter`, que resuelve las divisas con `ExchangeRateRepository` y delega lo físico en `Quantity::convertTo()` (decisión «Refactor de `factorToBase()`», opción A, en `plan.md`).

El alcance es solo la infraestructura monetaria: no hay maestros ni actividades spend-based nuevos. Cualquier maestro con factor de emisión puede declararse ya con unidad `EUR` y cualquier actividad puede introducirse en otra divisa. Los Administradores tienen una pantalla de solo lectura con los tipos vigentes y un botón para forzar la descarga, y reciben una notificación interna si la descarga falla.

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/Magnitude.php` | Caso `Currency`, base `Unit::Euro` | mod. |
| Dominio | `src/CarbonFootprint/Domain/Model/Unit.php` | 30 casos de divisa, `fixedFactorToBase(): ?float`, `isCurrency()`, `currencies()` | mod. |
| Dominio | `src/CarbonFootprint/Domain/Model/Quantity.php` | `convertTo()` rechaza divisas con `ExchangeRateRequiredException` | mod. |
| Dominio | `src/CarbonFootprint/Domain/Model/UnitConverter.php` | Servicio de conversión con tipos de cambio | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/ExchangeRateRequiredException.php` | Conversión de divisa sin pasar por `UnitConverter` | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/ExchangeRate/ExchangeRate.php` | Agregado | nuevo |
| Dominio | `.../ExchangeRate/{ExchangeRateId,ExchangeRateFilter,ExchangeRateRepository}.php` | Id, filtro, puerto de repositorio | nuevo |
| Dominio | `.../ExchangeRate/{ExchangeRateRegistered,ExchangeRateUpdated,ExchangeRateSyncFailed}.php` | Eventos | nuevo |
| Dominio | `.../ExchangeRate/{ExchangeRateGateway,ExchangeRateFeed,ExchangeRateFeedEntry}.php` | Puerto de la fuente y sus DTO | nuevo |
| Dominio | `.../ExchangeRate/{ExchangeRateNotFoundException,ExchangeRateSourceUnavailableException}.php` | Excepciones | nuevo |
| Aplicación | `src/CarbonFootprint/Application/Activity/EmissionsCalculator.php` | Recibe `UnitConverter` por constructor | mod. |
| Aplicación | `src/CarbonFootprint/Application/ExchangeRate/ExchangeRateAuthorizer.php` | Autorización | nuevo |
| Aplicación | `.../ExchangeRate/Register/RegisterExchangeRate{Request,Response,Service}.php` | Alta (único punto de creación) | nuevo |
| Aplicación | `.../ExchangeRate/Sync/SyncExchangeRates{Request,Response,Service}.php` | Sincronización con *upsert* | nuevo |
| Aplicación | `.../ExchangeRate/List/ListExchangeRates{Request,Response,Service}.php` | Listado | nuevo |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/ExchangeRate.ExchangeRate.orm.xml` | Mapping, tabla `exchange_rates` | nuevo |
| Persistencia | `.../Doctrine/Type/ExchangeRateIdType.php` + `config/packages/doctrine.yaml:93` | Tipo `exchange_rate_id` | nuevo / mod. |
| Persistencia | `.../Doctrine/Repository/DoctrineExchangeRateRepository.php` | Repositorio dev/prod | nuevo |
| Persistencia | `.../InMemory/Repository/InMemoryExchangeRateRepository.php` | Repositorio test | nuevo |
| Persistencia | `migrations/Version20261015094303.php` | Crea `exchange_rates` (creada por el usuario desde `migration.sql`) | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Gateway/EcbHttpExchangeRateGateway.php` | Descarga y parseo del XML del BCE (dev/prod) | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Gateway/InMemoryExchangeRateGateway.php` | Doble de test (`set/add/fail/reset`) | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Notification/ExchangeRateSyncFailedNotifier.php` + `templates/carbon_footprint/notification/exchange-rates-sync-failed.notification.html.twig` | Notificación interna de fallo | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Console/SyncExchangeRatesCommand.php` | `carbon-footprint:exchange-rates:sync` (primer comando de consola del BC) | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/Admin/ExchangeRate/{ListController,SyncController}.php` | Pantalla y acción de sincronizar | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/ListExchangeRatesType.php` | Filtro por divisa | nuevo |
| Infraestructura | `templates/carbon_footprint/admin/exchange-rates/list.html.twig` | Listado | nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/DependencyInjection/services.yaml:3,98-110` | Parámetro `app.ecb_exchange_rates_url` y argumento `$url` (`when@dev` / `when@prod`) | mod. |
| Shared | `src/Shared/Application/CommandBus/CommandSchedule.php:66-70` | Tarea diaria 17:00 UTC | mod. |
| Shared | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:435-439` | Nodo «Tipos de cambio» en «Administración» | mod. |
| Shared | `templates/components/List/FilterBar.html.twig:55-56` | Rama `currency` → dominio `unitType` | mod. |
| Config | `.env:164-166`, `.env.test:22` | `ECB_EXCHANGE_RATES_URL` | mod. |
| i18n | `templates/carbon_footprint/translations/unitType.html.twig`, `translations/unitType+intl-icu.*.xlf`, `translations/messages+intl-icu.*.xlf` | Nombres de divisas y textos de la pantalla | mod. |
| Assets | `assets/icons/solar/dollar-minimalistic-line-duotone.svg` | Icono del menú (importado con `make icon-import`) | nuevo |

## Modelo de dominio

### Unidades y magnitud

- `Unit` (`src/CarbonFootprint/Domain/Model/Unit.php:19-48`) añade `Euro = 'EUR'` y las 29 divisas del BCE con su código ISO como valor (USD, JPY, CZK, DKK, GBP, HUF, PLN, RON, SEK, CHF, ISK, NOK, TRY, AUD, BRL, CAD, CNY, HKD, IDR, ILS, INR, KRW, MXN, MYR, NZD, PHP, SGD, THB, ZAR). BGN no está: Bulgaria ya no figura en el fichero.
- `magnitude()` (`Unit.php:63-92`) agrupa los 30 casos en un único brazo `Magnitude::Currency`.
- `fixedFactorToBase(): ?float` (`Unit.php:96-109`) devuelve `null` para divisas y las constantes físicas para el resto (g 0.001, t 1000, m3 1000, resto 1.0). Sustituye a `factorToBase()`.
- `isCurrency()` (`Unit.php:111`) y `currencies()` (`Unit.php:117`, `list<self>` con los 30 casos, incluido `Euro`).
- Los valores persistidos de las unidades físicas no cambian; las columnas `unit` existentes (maestros, factores, actividades) son `VARCHAR` y aceptan los códigos nuevos sin cambio de esquema.

### Conversión

- `Quantity::convertTo()` (`Quantity.php:29-47`): misma unidad → `$this`; magnitudes distintas → `IncompatibleUnitsException`; si cualquiera de los dos factores fijos es `null` → `ExchangeRateRequiredException` (`\LogicException`, error de programación: hay que usar `UnitConverter`).
- `UnitConverter::convert()` (`UnitConverter.php:14-29`): misma unidad → la misma instancia; magnitudes distintas → `IncompatibleUnitsException`; divisa → `valor × rate(destino) / rate(origen)`; resto → `Quantity::convertTo()`.
- `UnitConverter::rate()` (`UnitConverter.php:31-38`): `Euro` → `1.0`; otra divisa → `findByCurrency()->rate()` o `ExchangeRateNotFoundException` (`\DomainException`). Siempre usa el último tipo almacenado, no el de la fecha de la actividad.
- `EmissionsCalculator` (`src/CarbonFootprint/Application/Activity/EmissionsCalculator.php:14-18`) convierte la cantidad a la unidad del factor con `UnitConverter`. Los servicios que lo inyectan no cambian (autowiring).

Ejemplo (criterio de aceptación): factor 0,5 kgCO₂e/EUR, actividad de 250 USD con `USD = 1.25` → 250 × 1 / 1.25 = 200 EUR → 100 kgCO₂e.

### Agregado `ExchangeRate`

`src/CarbonFootprint/Domain/Model/ExchangeRate/ExchangeRate.php`, `use AggregateRoot`:

| Campo | Tipo | Notas |
|---|---|---|
| `id` | `ExchangeRateId` | |
| `currency` | `Unit` | Por defecto `Unit::Euro` solo como valor de relleno del sujeto de autorización; `register()` rechaza `Euro` |
| `date` | `\DateTimeImmutable` | Fecha de publicación del BCE (atributo `time`) |
| `rate` | `string` | Unidades de divisa por 1 EUR, tal cual lo publica el BCE; `float` solo al calcular |
| `fetchedAt` | `\DateTimeImmutable` | Momento de la última descarga que cambió la fila |
| `updatedAt` | `?\DateTimeImmutable` | Igual a `fetchedAt` del último `ExchangeRateUpdated` |

Invariantes y comportamiento:

- `register()` (`ExchangeRate.php:62-79`): la unidad debe ser divisa y no `Euro` (`\InvalidArgumentException`); `rate` numérico y `> 0` (`assertValidRate()`, `ExchangeRate.php:122-127`). Publica `ExchangeRateRegistered`.
- `refresh()` (`ExchangeRate.php:81-99`): devuelve `false` sin publicar nada si la fecha coincide y el `rate` es numéricamente igual. La comparación es `(float) $rate === (float) $this->rate` (`:83`) porque MySQL hidrata `decimal(18,8)` con la escala completa (`'1.11950000'` frente a `'1.1195'` del XML; hallazgo A1 de `review.md`, corregido). Si cambia, valida y publica `ExchangeRateUpdated`; devuelve `true`.
- `factorToEuro()` (`:57-60`) = `1 / rate`; lo usa la columna «1 unidad en EUR» de la pantalla.
- Unicidad por divisa: restricción `uq_exchange_rates_currency` y comprobación previa en `RegisterExchangeRateService.php:32-34`.

Eventos (todos extienden `App\CarbonFootprint\Domain\DomainEvent`):

| Evento | `eventName` | Payload |
|---|---|---|
| `ExchangeRateRegistered` | `exchangeRateRegistered` | `exchangeRateId`, `currency` (código), `date` (`Y-m-d`), `rate`, `fetchedAt` (ATOM) |
| `ExchangeRateUpdated` | `exchangeRateUpdated` | `exchangeRateId`, `date`, `rate`, `fetchedAt` |
| `ExchangeRateSyncFailed` | `exchangeRateSyncFailed` | `reason`, `attemptedAt` (ATOM); sin agregado (`aggregateRootId()` → `null`) |

Excepciones:

| Excepción | Base | Cuándo |
|---|---|---|
| `ExchangeRateRequiredException` | `\LogicException` | `Quantity::convertTo()` con una divisa |
| `ExchangeRateNotFoundException` | `\DomainException` | `UnitConverter` sin tipo almacenado para la divisa (tabla vacía antes de la primera sincronización) |
| `ExchangeRateSourceUnavailableException` | `\RuntimeException` | Gateway: `fromUrl()` (HTTP ≠ 200 o error de transporte) y `malformed()` (XML inválido o sin `Cube[@time]`) |

## Flujo de una operación

### Sincronización (`SyncExchangeRatesService`)

1. Origen: tarea programada (`CommandSchedule.php:66-70`, `DeferredCommand` de prioridad `LOW` por defecto de `ScheduledCommand::dailyAt`), botón «Sincronizar ahora» (`SyncController`, `DeferredCommand` `MEDIUM`) o consola (síncrono).
2. Autoriza `SecurityAction::Admin` con un sujeto desechable (`SyncExchangeRatesService.php:41`).
3. `ExchangeRateGateway::fetch()` (`:43-51`). Si lanza `ExchangeRateSourceUnavailableException`: `warning` en el log, publica `ExchangeRateSyncFailed` y devuelve `failed = true` sin propagar la excepción.
4. Por cada entrada (`:58-90`):
   - Código desconocido, no divisa o `EUR` → `unknownCurrencies` y se salta (`:61-65`).
   - `rate` no numérico o `≤ 0` → `warning` y se salta (`:67-71`).
   - Sin fila → `RegisterExchangeRateService` (`registered++`, `:75-79`).
   - Con fila → `refresh()`; si cambió → `add()` y `updated++`; si no → `unchanged++` (`:82-89`).
5. `info` en el log con los contadores y respuesta con `date` del feed (`:96-110`).

En fin de semana y festivos TARGET el BCE repite la última fecha: todas las divisas quedan `unchanged` y no se escribe nada.

### Gateway HTTP (`EcbHttpExchangeRateGateway`)

- `GET $url` con `timeout` 15 s (`EcbHttpExchangeRateGateway.php:36`); estado ≠ 200 o cualquier `\Throwable` → `fromUrl()` (`:38-47`).
- Parseo con `\SimpleXMLElement` y errores de libxml internos (`:53-62`); el XML usa el namespace `http://www.ecb.int/vocabulary/2002-08-01/eurofxref` (`:19`, `:64`, `:73`).
- Sin atributo `time` → `malformed('Missing time cube')` (`:67-69`). Entradas sin `currency` o `rate` se saltan con `warning` (`:78-82`).

### Fallo de la fuente → notificación

`ExchangeRateSyncFailedNotifier` (`src/CarbonFootprint/Infrastructure/Notification/ExchangeRateSyncFailedNotifier.php`) se suscribe a `ExchangeRateSyncFailed` y envía una `SystemNotification` por `NotificationChannel::Internal` a cada `SuperAdmin` y `Admin` (`UserFilter(role:, excludeRole: null, limit: 0)`, `:59-65`), asunto «No se pudieron descargar los tipos de cambio del BCE» y plantilla `@carbon_footprint/notification/exchange-rates-sync-failed.notification.html.twig` (fecha del intento, motivo técnico en `<code>` y enlace a `admin_exchange_rates`). Una sincronización correcta no notifica.

### Pantalla de administración

- `GET /admin/exchange-rates` (`admin_exchange_rates`, `ListController.php:18`): `FilterTrait` con `ListExchangeRatesType` (campo `currency`, `UnitType` restringido a las 29 divisas sin `EUR`), `ListExchangeRatesService` (autoriza `List`; `canSync` = `canDoThat(..., Admin)`), `UnauthorizedActionException` → 403.
- Plantilla `templates/carbon_footprint/admin/exchange-rates/list.html.twig`: columnas «Divisa» (nombre traducido + código), «Divisa por 1 EUR», «1 unidad en EUR», «Fecha de publicación», «Descargado»; ordenables `currency`, `rate`, `date`; 4 decimales con coma; estado vacío «Todavía no se ha descargado ningún tipo de cambio. Lanza una sincronización.». `Link:Sync` solo si `canSync`, con confirmación «Se encolará la descarga de los tipos de cambio del BCE. ¿Desea continuar?».
- `GET /admin/exchange-rates/sync` (`admin_exchange_rates_sync`, `SyncController.php:23-24`): `#[CsrfProtected('exchange_rates_sync')]` (validado por `CsrfProtectedListener`, 403 sin token), autoriza `Admin`, encola `DeferredCommand(SyncExchangeRatesRequest, Priority::MEDIUM)`, flash «Sincronización de tipos de cambio encolada» y redirige al listado.

### Cálculo de una actividad en divisa

Las vistas de pasos pasan `Unit::cases()` como `units` (p. ej. `src/CarbonFootprint/Infrastructure/UI/Web/OrganizationalCarbonFootprint/View/ScopeStepView.php:34`), así que las 30 divisas aparecen en los selectores de unidad con su nombre del dominio `unitType`. `assets/controllers/activity_emissions_controller.js` (`restrictUnit()`) oculta las unidades de otra magnitud según `data-magnitude` del elemento elegido: un elemento cuyo maestro tiene unidad `EUR` solo admite divisas. La estimación en vivo y el guardado pasan por `EmissionsCalculator` → `UnitConverter`. Sin tipo almacenado, `ExchangeRateNotFoundException` no se captura en los controladores de actividad (solo se captura `\InvalidArgumentException`, p. ej. `src/CarbonFootprint/Infrastructure/UI/Web/EventCarbonFootprint/CalculateEmissionsController.php:90`): la estimación muestra «—» y el guardado falla con error.

## Autorización y ámbito

`ExchangeRateAuthorizer` (`src/CarbonFootprint/Application/ExchangeRate/ExchangeRateAuthorizer.php`):

| Acción | Admin / SuperAdmin | Owner / User |
|---|---|---|
| `List`, `View` | sí | no |
| `Register`, `Admin` (sincronizar) | sí (`canAdmin`) | no |
| `Edit`, `Remove`, `Import`, `Export`, `Autocomplete`, `History`, `Impersonate` | no | no |

Los tipos de cambio son globales: no hay *scoping* por cuenta. Los roles se resuelven con `userHasRole()` (respeta `CurrentRolesProvider`). El nodo de menú está en el árbol de `toAdmins()` de `NodeLister`. La tarea programada y el comando de consola necesitan un usuario Admin (`app:schedule:run <username>`, `carbon-footprint:exchange-rates:sync <username>`).

## Persistencia y migración

Tabla `exchange_rates` (`ExchangeRate.ExchangeRate.orm.xml`, `migrations/Version20261015094303.php`):

| Columna | Tipo | Nulable |
|---|---|---|
| `id` | `VARCHAR(36)` (`exchange_rate_id`) | no |
| `currency` | `VARCHAR(3)` con `enum-type` `Unit` | no |
| `date` | `DATE` | no |
| `rate` | `NUMERIC(18,8)` | no |
| `fetched_at` | `DATETIME` | no |
| `updated_at` | `DATETIME` | sí |

Índice único `uq_exchange_rates_currency (currency)`. Sin backfill: la tabla se llena con la primera sincronización. Hasta entonces cualquier actividad en una divisa distinta de EUR falla con `ExchangeRateNotFoundException`; tras desplegar hay que lanzar la sincronización por consola (ver abajo) o esperar a las 17:00 UTC.

`DoctrineExchangeRateRepository` (`#[AsAlias('repository.exchange_rate', public: true)]`, dev/prod): ordenación `date` / `rate` / por defecto `currency` (`:79-82`), paginación con `limit = 0` sin límite (`:45-48`). `InMemoryExchangeRateRepository` (`#[When('test')]`) con la misma semántica.

## Configuración y operación

- **Variable de entorno:** `ECB_EXCHANGE_RATES_URL` (`.env:165`, URL oficial del BCE; `.env.test:22`, `http://ecb.test/eurofxref-daily.xml`, nunca se llama en test). Se inyecta como `app.ecb_exchange_rates_url` → `$url` del gateway (`services.yaml:3,98-110`).
- **Tarea programada:** `carbon-footprint:sync-exchange-rates`, diaria a las 17:00 UTC (el BCE publica hacia las 16:00 CET), cola `async_low`. La despacha `app:schedule:run <username>`.
- **Botón de pantalla:** cola `async` (prioridad `MEDIUM`); requiere workers (`make queues` / `make queues-medium`).
- **Consola (síncrona):** `make sf c="carbon-footprint:exchange-rates:sync admin@dominio"`. Muestra una tabla `Date / Registered / Updated / Unchanged / Unknown currencies`; si la fuente falla, `warning` «Source unavailable: …» y código de salida 0; usuario inexistente → código 1.
- **Logs:** `warning` «ECB exchange rates unavailable», «Skipping invalid ECB exchange rate», «Skipping malformed ECB exchange rate entry»; `info` «Ignoring unknown ECB currencies» y «ECB exchange rates synchronized» con contadores.
- **Notificación:** interna a Admin y SuperAdmin solo en caso de fallo; si el BCE está caído varios días se recibe una por día (riesgo aceptado en `plan.md`).

## Cómo extenderlo

- **Añadir una divisa nueva publicada por el BCE:** 1) caso en `Unit` con el código ISO como valor; 2) añadirlo al brazo `Magnitude::Currency` de `Unit::magnitude()` (PHPStan exige exhaustividad); 3) línea `{{ 'XXX' | trans({}, 'unitType') }}` en `templates/carbon_footprint/translations/unitType.html.twig` y `make translation` (no se añaden claves a mano en los `.xlf`), rellenando los `target`; 4) ajustar `UnitTest` (cuenta 30 divisas). Hasta entonces la sincronización la ignora y la reporta en `unknownCurrencies`.
- **Conversión desde código nuevo:** inyectar `UnitConverter` y no llamar a `Quantity::convertTo()` si alguna unidad puede ser divisa (lanzaría `ExchangeRateRequiredException`).
- **Otra fuente de tipos:** implementar `ExchangeRateGateway` con `#[When]` del entorno correspondiente; el servicio de sincronización no conoce el BCE salvo por los mensajes de log.
- **Histórico o tipo por fecha de actividad:** fuera de alcance; requeriría pasar a una fila por divisa y fecha y que `UnitConverter` reciba la fecha.
- **Maestros spend-based:** basta con dar de alta el maestro con unidad «Euro»; el factor se aplica por euro y las actividades admiten cualquier divisa.
- **Trampas:**
  - Al comparar `rate` no se usa igualdad de strings (escala `decimal(18,8)` en MySQL; SQLite en test enmascara la diferencia).
  - Los `new ExchangeRate(id: ...)` en servicios y `SyncController` son solo sujetos de autorización; el alta real es siempre `RegisterExchangeRateService`.
  - En tests, `EmissionsCalculator` se construye con `EmissionsCalculatorMother::create(?ExchangeRateRepository)` (`tests/Unit/CarbonFootprint/Application/Activity/EmissionsCalculatorMother.php`), nunca con `new`.

## Tests

Unitarios:

- `tests/Unit/CarbonFootprint/Domain/Model/UnitTest.php`, `QuantityTest.php`, `UnitConverterTest.php`: magnitud de las 30 divisas, factores fijos, `ExchangeRateRequiredException`, conversiones USD↔EUR↔GBP, `ExchangeRateNotFoundException`, `IncompatibleUnitsException`.
- `tests/Unit/CarbonFootprint/Domain/Model/ExchangeRate/`: `ExchangeRateTest` (eventos, validaciones, `refresh` sin cambios incluso con distinta escala), `ExchangeRateSyncFailedTest`, repositorio abstracto + InMemory, `ExchangeRateMother`, `ExchangeRateRepositoryMother`.
- `tests/Unit/CarbonFootprint/Application/ExchangeRate/`: authorizer, `Register`, `Sync` (alta, divisa desconocida, feed repetido, actualización, `rate` 0, fuente caída, Owner), `List`.
- `tests/Unit/CarbonFootprint/Infrastructure/Gateway/EcbHttpExchangeRateGatewayTest.php` (`MockHttpClient` con XML real recortado) y `tests/Unit/CarbonFootprint/Infrastructure/Notification/ExchangeRateSyncFailedNotifierTest.php`.
- `tests/Unit/CarbonFootprint/Application/Activity/EmissionsCalculatorTest.php` (factor en EUR con cantidad en USD; tipo ausente) y `tests/Unit/Shared/Application/CommandBus/CommandScheduleTest.php` (`testSynchronizesExchangeRatesDailyAtFivePm`).

Aceptación (`tests/Acceptance/CarbonFootprint/`, contexto `ExchangeRateContext.php` registrado en `behat.yml.dist`):

- `exchange-rates-sync.feature`: alta desde el feed, actualización con una sola fila, divisa desconocida, fuente caída con notificación.
- `exchange-rates-list.feature`: listado para admin, 403 para owner, «Sincronizar ahora» con flash, 403 sin token.
- `exchange-rates-spend-based.feature`: combustible con factor por EUR y actividad de 250 USD en el alcance 1 de una huella de empresa → 100 kgCO₂e.

Ejecución (el `Makefile` no define `test-this`):

```bash
make test-unit options="tests/Unit/CarbonFootprint/Domain/Model/ExchangeRate"
make test-unit options="tests/Unit/CarbonFootprint/Application/ExchangeRate"
make test-acceptance options="tests/Acceptance/CarbonFootprint/exchange-rates-sync.feature"
make test-parallel
```

Hueco conocido: no hay test de ida y vuelta contra MySQL del repositorio Doctrine (solo implementación InMemory del test abstracto); el caso de escala está cubierto a nivel de agregado y de servicio.

## Desviaciones y deuda

Declaradas en `## Implementación` de `plan.md`:

- Constructor de `ExchangeRate` con valores por defecto no nulos (`Unit::Euro`, `NOW`) para cumplir `doctrine.columnType` de PHPStan.
- Traducciones `unitType` generadas desde `templates/carbon_footprint/translations/unitType.html.twig` con `make translation`, no a mano (el «hecho verificado» del plan era erróneo; B3 de `review.md`).
- Icono importado con `make icon-import`; contexto Behat registrado en `behat.yml.dist`; `exchange-rates-spend-based.feature` usa un combustible.
- Deuda ajena: `ListWastesType::configureOptions()` sin `: void`; aviso de PHPUnit por repository tests abstractos con sufijo `Test` (patrón de todo el repo).

De `review.md` / `review-fix.md` (0 abiertos):

- A1 (comparación de `rate` en `refresh()`) y B1 (`#[CsrfProtected]` en `SyncController`) corregidos; A2 (migración) creada por el usuario.
- B2 asumido: carrera check-then-insert si coinciden dos sincronizaciones con la tabla vacía; la restricción única protege la integridad y Messenger reintenta. Mitigación operativa: la primera sincronización tras desplegar se lanza por consola, sin pulsar «Sincronizar ahora» a la vez.
- Cada `ExchangeRateRegistered` / `ExchangeRateUpdated` queda también en el registro de auditoría por `RecordEventSubscriber` (observación de `review.md`, A1).
