# Health check con comprobación de la base de datos y de las colas — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Revisión:** `review.md` (misma carpeta) · **Implementado:** 2026-10-17 (correcciones de la review: 2026-10-18) · **Bounded Contexts:** `Shared`
- **Migración:** no requiere.

## Qué hace y por qué

Los tres puntos de entrada del health check (`GET /api/health-check`, `GET /{_locale}/health-check` y el comando `health:check`) respondían `ok` sin comprobar nada. Ahora consultan un servicio de aplicación, `CheckHealthService`, que recorre una lista de **indicadores** enchufables por etiqueta y construye un informe: `status: ok` solo si todos los indicadores son saludables, y un mapa `checks` con `ok`/`error` por componente. La API y la página responden `503` cuando algo falla y el comando devuelve código `1`.

Esta iteración trae dos indicadores reales: `database` (un `SELECT 1` por la conexión DBAL) y `queue` (los transportes asíncronos de Messenger son alcanzables y, sobre el transporte Doctrine, ningún mensaje lleva esperando más de `HEALTH_CHECK_QUEUE_MAX_WAIT_MINUTES`). Se mide la **antigüedad** del mensaje pendiente más viejo y no el número de mensajes: un worker parado con tres mensajes es una caída, mil mensajes con workers al día no (decisión «Por qué la antigüedad y no el número» del plan).

El endpoint es público, así que la respuesta nunca incluye el mensaje de la excepción (puede contener host, usuario o base de datos): el indicador guarda un detalle genérico y el error real va al logger (decisión «Qué se expone del fallo»). El campo `status` conserva los valores `ok`/`error`, de modo que los consumidores que solo miran `status == ok` (la monitorización de APIs de los proyectos derivados, ver *Referencias* del plan) siguen funcionando.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Shared/Domain/Model/Health/HealthIndicator.php` | Puerto: `name(): string` y `check(): HealthIndicatorResult` | Nuevo |
| Dominio | `src/Shared/Domain/Model/Health/HealthIndicatorResult.php` | VO `final readonly` (`name`, `healthy`, `?detail`) con `healthy()` / `unhealthy()` | Nuevo |
| Dominio | `src/Shared/Domain/Model/Health/HealthReport.php` | Informe agregado: `isHealthy()`, `results()`, `toArray()` | Nuevo |
| Aplicación | `src/Shared/Application/Health/CheckHealthService.php` | Recorre los indicadores etiquetados `shared.health_indicator` | Nuevo |
| Infraestructura | `src/Shared/Infrastructure/Persistence/Doctrine/DatabaseHealthIndicator.php` | Indicador `database` | Nuevo |
| Infraestructura | `src/Shared/Infrastructure/Symfony/Messenger/QueueHealthIndicator.php` | Indicador `queue` | Nuevo |
| Infraestructura | `src/Shared/Infrastructure/Health/InMemoryHealthIndicator.php` | Doble de test `in-memory` (`#[When(env: 'test')]`) | Nuevo |
| Infraestructura | `src/Shared/Infrastructure/UI/Api/HealthCheckController.php` | `api_health_check`: JSON `200`/`503` y OpenAPI de ambas respuestas | Modificado |
| Infraestructura | `src/Shared/Infrastructure/UI/Web/HealthCheckController.php` | `app_health_check`: página `200`/`503` | Modificado |
| Infraestructura | `templates/shared/health-check.html.twig` | `<h1>ok|error</h1>` y lista `nombre: ok|error` | Modificado |
| Infraestructura | `src/Shared/Infrastructure/UI/Console/HealthCheckCommand.php` | `health:check`: salida por componente y código de salida | Modificado |
| Configuración | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros, etiquetas con `priority` y doble en `when@test` | Modificado |
| Configuración | `.env` | `HEALTH_CHECK_QUEUE_MAX_WAIT_MINUTES=15` | Modificado |
| Tests | `tests/Unit/Shared/Domain/Model/Health/HealthReportTest.php` | Agregación del informe | Nuevo |
| Tests | `tests/Unit/Shared/Application/Health/CheckHealthServiceTest.php` | Servicio, incluido el indicador que lanza | Nuevo |
| Tests | `tests/Unit/Shared/Infrastructure/Persistence/Doctrine/DatabaseHealthIndicatorTest.php` | SQLite accesible / inaccesible con log | Nuevo |
| Tests | `tests/Unit/Shared/Infrastructure/Symfony/Messenger/QueueHealthIndicatorTest.php` | Transportes y antigüedad de mensajes | Nuevo |
| Tests | `tests/Unit/Shared/Infrastructure/Symfony/Messenger/QueueTransportsConfigurationTest.php` | Coherencia del mapa de colas con `messenger.yaml` | Nuevo |
| Tests | `tests/Acceptance/Shared/SystemContext.php` | Paso `the health indicator :name is failing` y limpieza | Modificado |
| Tests | `tests/Acceptance/Shared/health-check.feature` | Seis escenarios (API, comando y página; ok y fallo) | Modificado |

Sin cambios: `config/packages/security.yaml:60` (ya `PUBLIC_ACCESS` para `/health-check$`), `src/Shared/Infrastructure/Symfony/Event/MaintenanceModeListener.php:19-24` (ya deja pasar `/health-check` y `/api/health-check`), `config/routes.yaml`, `config/packages/messenger.yaml`, `composer.json`.

## Modelo de dominio

No hay agregados ni eventos de dominio: son objetos de valor sin persistencia.

- **`HealthIndicatorResult`** (`HealthIndicatorResult.php:7-24`): propiedades públicas `name`, `healthy`, `detail`. `detail` solo lo rellenan los indicadores para uso interno; **ningún punto de entrada lo imprime** (`HealthReport::toArray()`, plantilla y comando solo usan `name` y `healthy`).
- **`HealthReport`** (`HealthReport.php`):
  - `isHealthy()` (líneas 14-23): `true` si y solo si todos los resultados son saludables; un informe vacío es saludable.
  - `toArray()` (líneas 32-44): `{"status": "ok"|"error", "checks": {"<name>": "ok"|"error", ...}}`. Los `checks` se indexan por nombre: dos indicadores con el mismo `name()` se pisarían en el JSON (no en `results()`).

| Indicador | Saludable cuando | No saludable (`detail`) |
|---|---|---|
| `database` | `SELECT 1` responde | cualquier `\Throwable` → `connection failed` |
| `queue` | cada transporte configurado responde a `getMessageCount()` (si lo implementa) y, si es `DoctrineTransport`, no tiene mensajes con `delivered_at IS NULL` y `available_at` anterior a `now - maxWaitMinutes` | excepción al instanciar o consultar → `transport unreachable`; mensaje antiguo → `messages waiting too long` |
| (cualquiera) | — | el indicador lanza fuera de su `try` → `check failed` (lo asigna `CheckHealthService`) |
| `in-memory` (solo `test`) | por defecto | tras `markUnhealthy()` → `forced failure` |

## Flujo de una operación

### `GET /api/health-check`

1. Ruta `api_health_check` (`src/Shared/Infrastructure/UI/Api/HealthCheckController.php:15`, prefijo `/api` en `config/routes.yaml:1-6`); pública por `config/packages/security.yaml:60`; atraviesa el modo mantenimiento (`MaintenanceModeListener.php:39-43`, `str_contains` sobre la ruta).
2. `__invoke(CheckHealthService $checkHealth)` (línea 41) llama al servicio.
3. `CheckHealthService::__invoke()` (`CheckHealthService.php:20-33`) recorre el iterable inyectado con `#[AutowireIterator('shared.health_indicator')]` (línea 16), en orden de `priority` descendente. Cada `check()` va en un `try`; si lanza, el resultado es `unhealthy($indicator->name(), 'check failed')` (línea 28). El servicio **no registra** esa excepción: cada indicador debe capturar y registrar las suyas.
4. `DatabaseHealthIndicator::check()` (`DatabaseHealthIndicator.php:24-35`): `executeQuery('SELECT 1')->fetchOne()`; en `catch (\Throwable)` registra `error` «Database health check failed» con la excepción y devuelve `connection failed`. Sin timeout propio: rige el de la conexión DBAL.
5. `QueueHealthIndicator::check()` (`QueueHealthIndicator.php:34-59`), por cada entrada `transporte => queue_name` del parámetro:
   - si el localizador no tiene el transporte (`has()`, línea 37), se ignora;
   - dentro del `try` (líneas 41-50): `get()` del receptor (una factoría que lanza cae en el `catch`, corrección B1 de la review); `getMessageCount()` si implementa `MessageCountAwareInterface`; consulta de antigüedad si es `DoctrineTransport`;
   - el `catch` (líneas 51-55) registra `error` «Queue health check failed» con `transport` y excepción y devuelve `transport unreachable`;
   - el primer transporte en fallo corta el bucle (devuelve en cuanto falla uno).
6. `hasMessagesWaitingTooLong()` (líneas 61-78): `SELECT MIN(available_at) FROM messenger_messages WHERE queue_name = :queue AND delivered_at IS NULL AND available_at <= :now`, con `now` en UTC tipado `Types::DATETIME_IMMUTABLE` (el transporte guarda `available_at` en UTC). Si el mínimo existe y es anterior a `now - maxWaitMinutes`, es antiguo. Por construcción no cuentan los mensajes programados para el futuro (`DeferredCommand` con retraso), los ya entregados a un worker ni la cola `failed` (no está en el mapa). El nombre de la tabla `messenger_messages` está fijado en la consulta.
7. El controlador devuelve `JsonResponse($report->toArray(), 200|503)` (línea 45).

### `GET /{_locale}/health-check`

`src/Shared/Infrastructure/UI/Web/HealthCheckController.php:12-24`: mismo servicio; renderiza `@shared/health-check.html.twig` con `report` y un `Response` de estado `200` o `503`. La plantilla extiende `@shared/base.html.twig` y no se traduce (página técnica).

### `health:check`

`src/Shared/Infrastructure/UI/Console/HealthCheckCommand.php:28-39`: escribe `status: ok|error` y una línea `<name>: ok|error` por resultado; devuelve `Command::SUCCESS` o `Command::FAILURE`. Se ejecuta con `make sf c="health:check"`.

Ejemplo de salida con la cola atascada:

```
status: error
database: ok
queue: error
```

## Autorización y ámbito

No hay authorizer ni usuario: el servicio no recibe `User` (mismo criterio que `GlobalSearchService`). El acceso lo resuelve el firewall (`config/packages/security.yaml:60`, `PUBLIC_ACCESS`). El endpoint no tiene limitador de peticiones; cada petición cuesta un `SELECT 1` más, por transporte Doctrine, un `COUNT` y un `MIN`. En modo mantenimiento el health check sigue respondiendo con el estado real de los componentes (no informa del mantenimiento).

## Persistencia y migración

Sin cambios de esquema. `QueueHealthIndicator` solo lee la tabla `messenger_messages` (creada por `migrations/Version20260609165655.php`; `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0`, `.env:123`). Si la tabla no existiera, `getMessageCount()` lanza y el indicador da `transport unreachable`. Ninguna comprobación escribe, consume mensajes ni publica eventos.

## Configuración y operación

- **`.env:136-138`** — `HEALTH_CHECK_QUEUE_MAX_WAIT_MINUTES=15`. Ajustable por entorno: un pico legítimo de más de ese tiempo marca `queue: error`.
- **`services.yaml:17-21`** — parámetros `shared.health_check.queue_max_wait_minutes` (`%env(int:...)%`) y `shared.health_check.queue_transports` (mapa `async_high: high`, `async: medium`, `async_low: low`). El mapa **duplica** los `queue_name` de `config/packages/messenger.yaml:6-29`; `QueueTransportsConfigurationTest` falla si divergen (corrección B3).
- **`services.yaml:98-105`** — `DatabaseHealthIndicator` (`priority: 20`) y `QueueHealthIndicator` (`priority: 10`, con los dos parámetros) etiquetados `shared.health_indicator`. La etiqueta es explícita, no hay `AutoconfigureTag` en la interfaz.
- **`services.yaml:107-118`** — en `when@test`, `_defaults` (autowire/autoconfigure, corrección B2) y `InMemoryHealthIndicator` público con `priority: 0`.
- **Consola** — `make sf c="health:check"` (código de salida 1 si algo falla; útil en sondas de contenedor o cron).
- **Logs** — `error` con mensajes «Database health check failed» y «Queue health check failed» (`transport` en el contexto).
- **API doc** — tag OpenAPI `Health Check` (`config/packages/nelmio_api_doc.yaml:8`), área `admin` (`#[Areas(['admin'])]`), con esquemas de `200` y `503`.
- **Colas** — que `queue` vuelva a `ok` depende de que haya workers consumiendo: `make queues` (o `queues-high` / `queues-medium` / `queues-low`).

## Cómo extenderlo

Para añadir un componente (caché, almacenamiento de la biblioteca, correo...):

1. Crear una clase en la capa de infraestructura que corresponda que implemente `App\Shared\Domain\Model\Health\HealthIndicator`, con un `name()` único (es la clave de `checks`).
2. En `check()`, envolver **todo** el trabajo, también la obtención de dependencias perezosas, en `try/catch (\Throwable)`; registrar la excepción con `LoggerInterface::error()` y devolver `HealthIndicatorResult::unhealthy($this->name(), '<texto genérico>')`. Nunca poner `$e->getMessage()` en el detalle. Si se escapa una excepción, `CheckHealthService` la convierte en `check failed` sin dejar rastro en el log.
3. Registrarla en el `services.yaml` de su contexto con `tags: [{ name: shared.health_indicator, priority: N }]`; la `priority` fija su posición en `checks`.
4. Actualizar los escenarios de `tests/Acceptance/Shared/health-check.feature`, que comparan el JSON con «equal to» y por tanto el conjunto y orden exactos de `checks`.

Trampas conocidas:

- **Añadir o renombrar una cola** en `messenger.yaml` exige actualizar `shared.health_check.queue_transports`; si no, `QueueTransportsConfigurationTest` falla.
- **Transportes no Doctrine** (Redis, AMQP): solo se comprueba la alcanzabilidad (`getMessageCount()`); la antigüedad está limitada a `DoctrineTransport`.
- **Tabla distinta de `messenger_messages`** (opción `table_name` en el DSN): la consulta de antigüedad está fijada a ese nombre y fallaría como `transport unreachable`.
- **Nombres duplicados**: dos indicadores con el mismo `name()` se pisan en `checks`.

## Tests

- **Unitarios**:
  - `HealthReportTest` — informe vacío saludable, todo `ok`, uno en `error`, indexación por nombre.
  - `CheckHealthServiceTest` — indicadores saludables, uno no saludable, y un indicador que lanza → `check failed`.
  - `DatabaseHealthIndicatorTest` — SQLite en memoria saludable; SQLite en un directorio inexistente → `connection failed` con entrada `error` en un logger anónimo sobre `Psr\Log\AbstractLogger`.
  - `QueueHealthIndicatorTest` — receptor con contador saludable; contador que lanza; factoría del localizador que lanza (`testFailingTransportFactoryIsUnreachable`); receptor sin contador; transporte ausente ignorado; y sobre un `DoctrineTransport` real en SQLite: cola vacía, mensaje con `available_at` 20 minutos en el pasado → `messages waiting too long`, y el mismo mensaje entregado → saludable.
  - `QueueTransportsConfigurationTest` — parsea `messenger.yaml` y `services.yaml` y compara el mapa transporte → `queue_name`.
- **Aceptación** (`tests/Acceptance/Shared/health-check.feature`): API `200` con `database`, `queue` e `in-memory` en `ok` (línea 4) y `503` con `in-memory: error` (línea 19); comando con código 0 y líneas `database: ok`/`queue: ok` (línea 35) y con código 1 y `status: error` (línea 43); página `200` con `ok` (línea 50) y `503` con `error` (línea 55). El paso `Given the health indicator :name is failing` (`SystemContext.php:42-50`) obtiene el doble público del contenedor, comprueba que el nombre es `in-memory` y lo marca; el `#[AfterScenario]` (líneas 31-35) lo restablece. En test los indicadores reales siguen activos: SQLite responde y los transportes `in-memory://` no implementan `MessageCountAwareInterface`, así que `queue` es `ok`.
- **Ejecutar**:
  - `make test-unit options="--filter Health"` (casa con las cinco clases: cuatro por nombre de clase y `QueueTransportsConfigurationTest` por su método `testHealthCheck…`).
  - `make test-acceptance options="tests/Acceptance/Shared/health-check.feature"`.
- **Huecos**: la comprobación de antigüedad contra MySQL/MariaDB en producción no tiene test (los unitarios usan SQLite); no hay test de que la respuesta omita `detail`, más allá de que los escenarios comparan el JSON exacto.

## Desviaciones y deuda

Según `## Implementación` de `plan.md`:

- `QueueHealthIndicator` usa `has()` antes de `get()` para ignorar transportes ausentes.
- `now` se pasa en UTC tipado `DATETIME_IMMUTABLE`, como guarda `available_at` el transporte.
- `DatabaseHealthIndicatorTest` usa un logger anónimo porque `Psr\Log\Test\TestLogger` no está en `vendor`.
- Deuda ajena detectada: `src/DataExchange/Infrastructure/UI/Console/PurgeExpiredExportationsCommand.php:9` importa `UserMother` de `tests/` desde producción.

Según `review.md`: tres hallazgos bajos (B1, B2, B3), todos aplicados y verificados en la revisión 2; ninguno abierto.
