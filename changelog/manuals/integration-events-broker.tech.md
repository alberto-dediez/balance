# Eventos de integración entre plataformas (RabbitMQ) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-19 · **Bounded Contexts:** `Shared` (subsistema `Integration`); `Security` solo como dependencia (`UserRepository`, `LoggedUser`, `LoggedAccount`)

## Qué hace y por qué

Las plataformas Bioscore construidas sobre biohydra necesitan reaccionar a lo que ocurre en las demás. Los webhooks perdían avisos si el receptor estaba caído y obligaban a cada emisor a conocer a cada receptor. Este subsistema entrega **solo la infraestructura** para publicar y consumir **eventos de integración** a través de RabbitMQ de forma fiable, ordenada, idempotente y trazable. No hay todavía eventos de negocio: el único evento incluido es el técnico `platform.pinged` v1 (prueba de extremo a extremo).

Diseño (decisiones en la tabla *Decisiones tomadas* de `plan.md`):

- **Salida:** un `DomainEventSubscriber` de infraestructura traduce un evento de dominio a un `IntegrationEvent` y lo entrega al puerto `IntegrationEventPublisher`. El publisher lo acumula en memoria y solo lo escribe en la tabla `integration_outbox` si la petición, el comando o el mensaje terminan bien. Un *relay* (`integration:outbox:relay`) publica el outbox en RabbitMQ con *publisher confirms*.
- **Entrada:** el consumidor (`messenger:consume integration_in integration_retry`) recibe un sobre JSON, lo deduplica por `id` (inbox), lo ordena por `(source, aggregateId)` (positions), fuerza el usuario de integración y ejecuta los `IntegrationEventHandler` de la plataforma, todo en **una transacción**.
- Nunca viajan eventos de dominio por el broker ni nombres de clase PHP; el contrato es `type` + `version`.
- Todo está desactivado mientras `INTEGRATION_EVENTS_ENABLED=false` (valor por defecto) y `INTEGRATION_TRANSPORT_DSN` vale `in-memory://`, de modo que sin configuración no hay conexiones a ningún broker.

## Mapa de piezas

Todo es nuevo salvo lo marcado como modificado.

| Capa | Fichero | Rol |
|---|---|---|
| Dominio | `src/Shared/Domain/Model/Integration/IntegrationOutboxMessage.php` | Entidad técnica (sin `AggregateRoot`, `#[Exclude]`): mensaje pendiente/enviado; `routingKey()`, `markSent()`, `markFailedAttempt()` (error truncado a 1000) |
| Dominio | `.../IntegrationOutboxRepository.php`, `IntegrationOutboxFilter.php`, `IntegrationOutboxStatus.php` | Puerto: `add`, `save`, `pending(limit)`, `oldestPendingOccurredOn`, `list`, `purgeSentBefore` |
| Dominio | `.../IntegrationInboxMessage.php`, `IntegrationInboxRepository.php`, `IntegrationInboxFilter.php`, `IntegrationInboxStatus.php` | Inbox idempotente: `find`, `has`, `add`, `list`, `purgeReceivedBefore`; estados `processed`, `skipped_stale`, `skipped_unknown_type`, `skipped_hops` |
| Dominio | `.../IntegrationPosition.php`, `IntegrationPositionRepository.php` | Última `sequence` por `(source, aggregateId)`; `advance()` solo avanza con secuencia mayor |
| Aplicación | `src/Shared/Application/Integration/IntegrationEvent.php` | Interfaz del evento saliente (`type()`, `version()`, `aggregateId()`, `accountRef()`, `payload()`) |
| Aplicación | `.../IntegrationEventPublisher.php` | Puerto de publicación |
| Aplicación | `.../IntegrationEventHandler.php` | Interfaz del handler entrante (`supports`, `handle`), con `#[AutoconfigureTag('shared.integration.handler')]` |
| Aplicación | `.../ReceivedIntegrationEvent.php` | Evento recibido; accesores tipados `string`, `nullableString`, `int`, `bool`, `array` que lanzan `\InvalidArgumentException` |
| Aplicación | `.../Ping/PlatformPinged.php` | Evento técnico `platform.pinged` v1 |
| Aplicación | `.../Purge/PurgeIntegrationMessagesService.php` (+ Request/Response) | Purga de enviados/recibidos antiguos, sin authorizer |
| Infraestructura | `src/Shared/Infrastructure/Integration/IntegrationSettings.php` | Flag, plataforma (`^[a-z0-9-]+$`, valida en el constructor) y `maxHops` |
| Infraestructura | `.../IntegrationEnvelope.php`, `MalformedIntegrationEnvelope.php` | Sobre del cable: `fromOutbox`, `fromArray` (valida), `toArray`, `toReceivedEvent` |
| Infraestructura | `.../IntegrationContext.php` | Mensaje entrante en curso (`enter`/`leave`/`current`) |
| Infraestructura | `.../BufferedIntegrationEventPublisher.php` | Adaptador del puerto: buffer en memoria, `flush`/`markFailed`/`discard` |
| Infraestructura | `.../FlushIntegrationOutboxSubscriber.php` | Decide cuándo volcar o descartar el buffer |
| Infraestructura | `.../OutboxRelay.php` | Publica el outbox pendiente por el bus de Messenger |
| Infraestructura | `.../IntegrationUserProvider.php`, `IntegrationUserNotAvailable.php` | Resuelve el usuario `INTEGRATION_USERNAME` (debe ser `Admin` o `SuperAdmin`) |
| Infraestructura | `.../LogPlatformPingedHandler.php` | Handler de `platform.pinged` v1 (log `info`) |
| Infraestructura | `.../IntegrationOutboxHealthIndicator.php` | Indicador `integration_outbox` del health check |
| Infraestructura | `.../Messenger/IntegrationMessageSerializer.php` | Serializer JSON; `decode` nunca lanza |
| Infraestructura | `.../Messenger/OutgoingIntegrationMessage.php`, `IncomingIntegrationMessage.php`, `UndecodableIntegrationMessage.php` | Mensajes de Messenger |
| Infraestructura | `.../Messenger/IncomingIntegrationMessageHandler.php` | Consumidor transaccional |
| Infraestructura | `.../Messenger/UndecodableIntegrationMessageHandler.php` | Manda lo ilegible a reintento/fallidos |
| Persistencia | `src/Shared/Infrastructure/Persistence/Doctrine/Mapping/Integration.{IntegrationOutboxMessage,IntegrationInboxMessage,IntegrationPosition}.orm.xml` | Mappings (el mapping `Shared` de `doctrine.yaml` ya los carga) |
| Persistencia | `src/Shared/Infrastructure/Persistence/Doctrine/Repository/DoctrineIntegration{Outbox,Inbox,Position}Repository.php` | Repositorios Doctrine |
| Persistencia | `src/Shared/Infrastructure/Persistence/InMemory/Repository/InMemoryIntegration{Outbox,Inbox,Position}Repository.php` | Repositorios para tests |
| Persistencia | `migrations/Version20261119140835.php` | Crea las tres tablas |
| Consola | `src/Shared/Infrastructure/UI/Console/{RelayIntegrationOutbox,ListIntegrationOutbox,ListIntegrationInbox,InjectIntegrationMessage,PingIntegration}Command.php` | Relay y herramientas de diagnóstico |
| Config (modificado) | `config/packages/messenger.yaml` | Transportes `integration_out`, `integration_in`, `integration_retry`, `integration_failed`, routing y `when@test` |
| Config (modificado) | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` (líneas 46-51, 226-245) | Parámetros `shared.integration.*`, argumentos y alias del puerto |
| Config (modificado) | `src/Shared/Application/CommandBus/CommandSchedule.php` | Purga diaria `shared:purge-integration-messages` a las 03:40 |
| Config (nuevo/modificado) | `config/packages/lock.yaml`, `.env`, `.env.test`, `composer.json`, `Dockerfile` (extensión `amqp`), `Makefile` | Lock, variables, dependencias y targets |
| Infra | `infra/rabbitmq/` (`compose.yaml`, `compose.prod.yaml`, `rabbitmq.conf`, `rabbitmq.prod.conf`, `definitions.json`, `README.md`) | Broker local y de producción, topología y guía de contratos |
| Infra (modificado) | `infra/supervisor/hydra-workers.ini`, `infra/supervisor/README.md` | Programas `hydra-integration-consumer` y `hydra-integration-relay` |
| Tests (modificado) | `tests/Acceptance/Shared/health-check.feature` | Añade `"integration_outbox": "ok"` al JSON esperado |

## Modelo de dominio

No hay aggregate ni eventos de dominio: las tres entidades son técnicas para que `RecordEventSubscriber` no las lleve al Registro ni el subscriber de integración reaccione a ellas.

| Tabla | Clave | Columnas relevantes |
|---|---|---|
| `integration_outbox` | `sequence` (INT, autoincremental); único `message_id` | `type`, `version`, `aggregate_id`, `account_ref`, `correlation_id`, `causation_id`, `hops`, `occurred_on`, `payload` (texto con el JSON opaco), `sent_at`, `attempts`, `last_error`; índice `(sent_at, sequence)` |
| `integration_inbox` | `message_id` | `source`, `type`, `version`, `aggregate_id`, `sequence`, `correlation_id`, `causation_id`, `hops`, `status`, `received_at`; índices por `received_at` y `correlation_id` |
| `integration_positions` | `(source, aggregate_id)` | `last_sequence`, `updated_at` |

### Sobre (`IntegrationEnvelope`)

`id`, `type`, `version`, `source`, `sequence`, `occurredOn`, `aggregateId`, `accountRef` (reservado, siempre nulo), `correlationId`, `causationId`, `hops`, `payload`. Ejemplo y reglas por campo en `infra/rabbitmq/README.md` (*Guía de contratos*). Validaciones de `IntegrationEnvelope::fromArray()`: `type` contra `TYPE_PATTERN` (`IntegrationEnvelope.php:13`), `source` contra `IntegrationSettings::PLATFORM_PATTERN`, `version >= 1`, `sequence >= 1`, `hops >= 0`, UUID en `id`/`correlationId`/`causationId`, `occurredOn` en formato ATOM **sin fracciones de segundo** (se admite `Z`); claves desconocidas se ignoran. `toArray()` convierte `occurredOn` a UTC y serializa un payload vacío como `{}`.

### Invariantes del consumidor

Se evalúan en este orden en `IncomingIntegrationMessageHandler`:

1. `source` igual a la propia plataforma: se descarta sin registrar (`:60`, log `debug`).
2. `id` ya en el inbox (`inbox->has`, `:88`): se descarta.
3. `hops > INTEGRATION_MAX_HOPS`: inbox `skipped_hops` (`:95`).
4. Ningún handler soporta `(type, version)`: inbox `skipped_unknown_type` (`:103`).
5. `aggregateId` no nulo y `sequence <= lastSequence`: inbox `skipped_stale` (`:111`).
6. En otro caso se ejecutan los handlers y se registra `processed`; si hay `aggregateId`, la posición avanza.

## Flujo de una operación

### Salida (publicar un evento)

```mermaid
sequenceDiagram
    participant D as Dominio / servicio
    participant S as DomainEventSubscriber (BC)
    participant P as BufferedIntegrationEventPublisher
    participant F as FlushIntegrationOutboxSubscriber
    participant O as integration_outbox
    participant R as OutboxRelay
    participant B as RabbitMQ
    D->>S: evento de dominio
    S->>P: publish(IntegrationEvent)
    P->>P: buffer en memoria (hereda correlation/hops)
    Note over F: kernel.terminate / console.terminate (0) / WorkerMessageHandled
    F->>P: flush()
    P->>O: add() por mensaje, en orden
    R->>O: pending(batch)
    R->>B: dispatch OutgoingIntegrationMessage + AmqpStamp(routingKey)
    R->>O: markSent + save
```

1. `BufferedIntegrationEventPublisher::publish()` (`BufferedIntegrationEventPublisher.php:30`): si el flag está apagado no hace nada. Genera el id con `UuidGenerator::v7()` (`:36`). Con un mensaje entrante en curso hereda su `correlationId`, usa su `id` como `causationId` y `hops + 1`; sin él, `correlationId = messageId`, `causationId = null`, `hops = 0` (`:37-39`). Si `hops > maxHops` registra un `warning` y no acumula (`:41`). El payload se serializa con `json_encode` (`JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`).
2. `FlushIntegrationOutboxSubscriber::getSubscribedEvents()`: `KernelEvents::EXCEPTION` y `ConsoleEvents::ERROR` marcan el buffer como fallido; `KernelEvents::TERMINATE` hace `flush()` y luego `discard()`; `ConsoleEvents::TERMINATE` descarta si el código de salida no es 0 y vuelca si lo es; `WorkerMessageHandledEvent` vuelca; `WorkerMessageFailedEvent` descarta. Un buffer marcado como fallido se descarta en `flush()`.
3. `OutboxRelay::relay()` (`OutboxRelay.php:10`): para cada pendiente (orden `sequence ASC`) despacha `OutgoingIntegrationMessage` con `AmqpStamp(routingKey, AMQP_NOPARAM, ['content_type' => 'application/json', 'delivery_mode' => 2])` y marca como enviado. Ante `\Throwable` registra el fallo (`attempts`, `lastError`), log `error` y **corta el lote** para no desordenar.
4. `RelayIntegrationOutboxCommand` (`integration:outbox:relay`): obtiene el lock `integration-outbox-relay` (`LockFactory`; si otro lo tiene, espera y reintenta hasta `--time-limit`), llama a `relay($batch)` en bucle, hace `EntityManager::clear()` tras cada vuelta (`:79`, evita acumular entidades en el proceso largo) y duerme `--sleep` ms si no hubo mensajes. Atiende `SIGTERM`/`SIGINT` terminando el lote en curso. Con el flag apagado espera sin trabajar hasta `--time-limit` (o termina al momento con `--once`) para que Supervisor no lo marque como FATAL.
5. `IntegrationMessageSerializer::encode()` produce el cuerpo JSON y las cabeceras `content_type`, `x-integration-type`, `x-integration-version`, `x-integration-source`; rechaza cualquier mensaje que no sea `OutgoingIntegrationMessage`.

### Entrada (consumir un mensaje)

1. `messenger:consume integration_in integration_retry` lee la cola `integration.<plataforma>.inbox`. `IntegrationMessageSerializer::decode()` devuelve `IncomingIntegrationMessage` o, ante cualquier fallo, `UndecodableIntegrationMessage` (nunca lanza, para que el receptor AMQP no rechace y pierda el mensaje). `UndecodableIntegrationMessageHandler` lanza `UnrecoverableMessageHandlingException`: el mensaje va directo a `integration_retry` y de ahí a `integration_failed`.
2. `IncomingIntegrationMessageHandler::__invoke()` (`IncomingIntegrationMessageHandler.php:47`): con el flag apagado (`:49`) solo registra un `warning` y **confirma el mensaje, que se pierde** (hallazgo A2 de la review, descartado por el usuario). Ignora los mensajes de la propia plataforma y envuelve el resto en `entityManager->wrapInTransaction()` (`:67`).
3. Dentro de la transacción, tras superar los descartes de la lista de invariantes: `IntegrationUserProvider::user()`, `LoggedUser::forceUser($user)` (`:117`), `LoggedAccount::reset()`, `IntegrationContext::enter()`, `DomainEventPublisher::instance()->lock()` (`:120`), cada handler (`handle($event, $user)`), `unlock()` (`:126`, corren aquí los subscribers de dominio, incluido el Registro), `publisher->flush()` (outbox dentro de la transacción), avance de la posición y registro en el inbox.
4. Si algo lanza: `DomainEventPublisher::clearPending()` (`:71`), `publisher->discard()`, `ManagerRegistry::resetManager()` si el EntityManager quedó cerrado (`:75`) y se relanza la excepción; rollback completo, de modo que no queda nada en inbox, posiciones ni outbox. En `finally` se limpia el contexto, el usuario forzado (`forceUser(null)`, `:81`) y la cuenta.
5. Reintentos: `integration_in` tiene `max_retries: 0` y `failure_transport: integration_retry` (Doctrine, 5 reintentos, retraso 2 s, multiplicador 3), cuyo `failure_transport` es `integration_failed` (Doctrine, manual). Así Messenger no crea colas de retraso en RabbitMQ, que exigirían permiso de configuración (`config/packages/messenger.yaml`).

### Herramientas de consola

| Comando | Uso |
|---|---|
| `make sf c="integration:ping hola"` | Publica `platform.pinged` (el volcado al outbox lo hace `console.terminate`). Con el flag apagado avisa y devuelve 1 |
| `make sf c="integration:outbox:list --status=pending --type= --limit=20"` | Lista el outbox |
| `make sf c="integration:inbox:list --status= --source= --type= --limit=20"` | Lista el inbox |
| `make sf c="integration:inject fichero.json"` | Decodifica un sobre de un fichero y lo procesa en el acto con el transporte `sync`, mostrando el estado resultante del inbox (ejemplo en `tests/Unit/Shared/Infrastructure/Integration/fixtures/platform-pinged-v1.json`) |
| `make integration-relay` / `make integration-consume` | Relay y consumidor en local |

## Autorización y ámbito

No hay pantallas, API ni `Authorizer`: el subsistema solo se maneja por consola y por procesos. `PurgeIntegrationMessagesService` no autoriza porque solo lo lanza el planificador (`app:schedule:run`).

- Los cambios provocados por un mensaje entrante se ejecutan con el usuario `INTEGRATION_USERNAME` (`IntegrationUserProvider.php`): debe existir y tener rol `Admin` o `SuperAdmin` (se comprueba el rol almacenado con `hasRole`, sin suplantación). Si no, `IntegrationUserNotAvailable` hace fallar el mensaje (reintento y, después, `integration_failed`). Se recomienda un usuario dedicado por plataforma para que el Registro no atribuya cambios a una persona.
- Cuenta: fuera de alcance mientras no exista una identidad única de cuenta. El sobre reserva `accountRef` (nulo). El handler resetea `LoggedAccount` y no fuerza ninguna suplantación; un handler de plataforma que actúe sobre una cuenta debe pasarla explícitamente en la Request del servicio.
- Feature flag: `INTEGRATION_EVENTS_ENABLED`. Apagado: el publisher no acumula, el relay y el consumidor esperan o descartan (ver arriba) y el health check devuelve sano.

## Persistencia y migración

- Migración aplicada por el usuario a partir de `migration.sql`: `migrations/Version20261119140835.php` crea `integration_inbox`, `integration_outbox` e `integration_positions` (sin backfill). `sequence` y `last_sequence` son `INT` (no `bigint`: con DBAL 3 `bigint` se hidrata como string y choca con las propiedades `int`). `payload` es `LONGTEXT`; es una excepción motivada a la regla de no guardar texto serializado (cuerpo opaco de transporte, reenviado byte a byte).
- Las colas `integration_retry` e `integration_failed` usan la tabla `messenger_messages` existente con otro `queue_name`.
- Fechas: se guardan en la zona por defecto de PHP, como el resto del proyecto; solo en el cable (`toArray`/`fromOutbox`) se convierten a UTC (corrección B1 de la review; producción está en UTC+1).
- Retención: `PurgeIntegrationMessagesService` borra los enviados del outbox y los del inbox con más de `INTEGRATION_RETENTION_DAYS` (30). Los pendientes del outbox y `integration_positions` no se purgan.

## Configuración y operación

### Variables de entorno (`.env`, bloque `integration`)

| Variable | Por defecto | Significado |
|---|---|---|
| `INTEGRATION_EVENTS_ENABLED` | `false` | Activa publisher, relay, consumidor e indicador |
| `INTEGRATION_PLATFORM` | `biohydra` | `source`, exchange `integration.<p>` y cola `integration.<p>.inbox` |
| `INTEGRATION_TRANSPORT_DSN` | `in-memory://` | `amqp://` en local, `amqps://` en producción |
| `INTEGRATION_USERNAME` | vacío | Usuario administrador de los cambios entrantes |
| `INTEGRATION_MAX_HOPS` | `5` | Saltos máximos de una cadena |
| `INTEGRATION_RETENTION_DAYS` | `30` | Retención de mensajes enviados/recibidos |
| `INTEGRATION_OUTBOX_MAX_WAIT_MINUTES` | `10` | Espera máxima de un pendiente antes de marcar el health check como `error` |
| `LOCK_DSN` | `flock` | Lock del relay (`symfony/lock`) |

En `.env.test`: `INTEGRATION_EVENTS_ENABLED=true`, `INTEGRATION_PLATFORM=biohydra-test`, `INTEGRATION_USERNAME=admin@dediez.software`, y los cuatro transportes son `in-memory://`.

### Health check

`IntegrationOutboxHealthIndicator` (nombre `integration_outbox`, tag `shared.health_indicator`): sano si está apagado o no hay pendientes; no sano (`outbox messages waiting too long`) si el pendiente más antiguo supera `INTEGRATION_OUTBOX_MAX_WAIT_MINUTES`.

### Topología RabbitMQ (`infra/rabbitmq/definitions.json`)

- vhost `bioscore`; ocho plataformas: `biohydra` (solo pruebas), `data-platform`, `balance`, `waste`, `dont-waste-food`, `reporting`, `compliance`, `bioscore-erp`.
- Un exchange `topic` durable `integration.<p>` por plataforma y una cola quorum durable `integration.<p>.inbox`, enlazada con routing key `#` a los exchanges de todas las demás. Cada plataforma descarta como `skipped_unknown_type` lo que no maneja, así que empezar a consumir un tipo nuevo no exige tocar RabbitMQ.
- Sin `x-single-active-consumer` (RabbitMQ no admite `basic.get` en colas quorum con esa opción y el receptor AMQP de Messenger lo usa). El orden lo garantizan un único consumidor por plataforma (`numprocs=1`), `integration_positions` y el inbox: **no arrancar más de un consumidor por plataforma**.
- Policy `integration-inbox-retention`: 14 días de TTL y 512 MB por cola con `drop-head`, para que una plataforma sin consumidor no llene el disco del broker. Se pierden los mensajes que superen esos límites; los eventos con `aggregateId` llevan estado completo y el siguiente lo recupera.
- Usuario `integration-<p>` por plataforma: `configure ^$`, `write ^integration\.<p>$`, `read ^integration\.<p>\.inbox$`. Usuario `admin`/`admin` solo para local.
- Las aplicaciones no declaran nada (`auto_setup: false`).

### Supervisor (producción, sin FrankenPHP)

Dos programas por plataforma dentro de su grupo (`infra/supervisor/hydra-workers.ini`): `hydra-integration-consumer` (`messenger:consume integration_in integration_retry --time-limit=3600 --memory-limit=192M --limit=500`, `stopwaitsecs=3600`) y `hydra-integration-relay` (`integration:outbox:relay --time-limit=3600`, `stopwaitsecs=60`), ambos `numprocs=1`. Pueden quedar instalados con el flag apagado. Mensajes fallidos:

```bash
php bin/console messenger:failed:show --transport=integration_failed --env=prod
php bin/console messenger:failed:retry --transport=integration_failed --env=prod
```

`make queues` excluye los cuatro transportes de integración (`Makefile`).

La instalación en producción (`ext-amqp`, broker RabbitMQ en Docker sobre Plesk, cortafuegos, TLS, usuarios) y la configuración de cada plataforma están en las secciones *Guía de instalación en producción (servidor Plesk)* y *Configuración por plataforma*.

### Entorno local

`make broker-up` / `make broker-down` / `make broker-logs` (compose `infra/rabbitmq/compose.yaml`, proyecto fijo `bioscore-broker`, red externa `bioscore` con alias `rabbitmq.local`, management en `http://localhost:15672`, `admin`/`admin`). Una sola instancia aunque la lancen varios checkouts; si difieren en `definitions.json`, gana el último. Prueba entre dos plataformas: `INTEGRATION_*` en el `.env.local` de cada una (DSN `amqp://integration-<p>:integration-<p>@rabbitmq.local:5672/bioscore`), `make integration-relay` y `make integration-consume` en ambas, `make sf c="integration:ping hola"` en una y `make sf c="integration:inbox:list"` en la otra.

## Guía de instalación en producción (servidor Plesk)

Guía paso a paso para poner en marcha el servicio de eventos: la extensión `ext-amqp` en todos los servidores, el broker RabbitMQ en Docker sobre un servidor Plesk y la activación de cada plataforma. Todo se hace como `root` salvo que se indique otra cosa. Las órdenes de `bin/console` se lanzan siempre con el usuario de la suscripción de Plesk (`sudo -u <PROJECT_USER> -H bash -c 'cd <PROJECT_DIR> && ...'`), nunca como `root`.

### Marcadores

| Marcador | Significado | Ejemplo |
|----------|-------------|---------|
| `<BROKER_HOST>` | Subdominio del broker, con certificado Let's Encrypt de Plesk | `mq.bioscore.info` |
| `<p>` | Identificador de la plataforma (`INTEGRATION_PLATFORM`) | `balance` |
| `<GRUPO>` | Grupo de Supervisor de la plataforma (ver `infra/supervisor/README.md`) | `balance` |
| `<PROJECT_DIR>` | Raíz del proyecto en el servidor de la plataforma | `/var/www/vhosts/bioscore.info/balance.bioscore.info` |
| `<PROJECT_USER>` | System user de la suscripción de Plesk | `bioscore.info_ab12cd34` |
| `<IPS_PLATAFORMAS>` | IPs públicas de los servidores de las plataformas que se conectan al broker | `203.0.113.10 203.0.113.11` |
| `<CA_BUNDLE>` | Almacén de CA del sistema en el servidor de la plataforma | `/etc/pki/tls/certs/ca-bundle.crt` (RHEL, CentOS, AlmaLinux) · `/etc/ssl/certs/ca-certificates.crt` (Debian, Ubuntu) |

### Orden del despliegue

1. **Fase 1 — `ext-amqp`** en el PHP CLI y en el PHP-FPM de **todos** los servidores de **todas** las plataformas.
2. **Fase 2 — Código:** merge y despliegue de esta versión en cada plataforma con `INTEGRATION_EVENTS_ENABLED=false`. `bin/deploy` aplica la migración `Version20261119140835` (`integration_inbox`, `integration_outbox`, `integration_positions`). La plataforma funciona igual que antes.
3. **Fase 3 — Broker** en el servidor elegido.
4. **Fase 4 — Activación** plataforma a plataforma (sección *Configuración por plataforma*).
5. **Fase 5 — Prueba de extremo a extremo** entre dos plataformas ya activadas.

Las fases 1 y 2 no dependen del broker y no cambian nada visible. La 1 es obligatoria antes de la 2: sin la extensión, `composer install` falla y el despliegue se queda a medias (con la web en mantenimiento).

### Fase 1 — `ext-amqp` en cada servidor de plataforma

`symfony/amqp-messenger` exige la extensión en `composer install` aunque el flag esté apagado.

```bash
cat /etc/os-release                                         # familia del sistema
dnf install -y epel-release                                 # yum en CentOS 7
dnf install -y plesk-php84-devel gcc make librabbitmq-devel # librabbitmq-devel viene de EPEL
printf "\n" | /opt/plesk/php/8.4/bin/pecl install amqp      # Intro = autodetectar librabbitmq
echo "extension=amqp.so" > /opt/plesk/php/8.4/etc/php.d/40-amqp.ini
systemctl restart plesk-php84-fpm
supervisorctl restart all
```

En Debian/Ubuntu con Plesk los paquetes son `plesk-php84-dev`, `build-essential` y `librabbitmq-dev` (`apt-get install`).

Comprobar:

```bash
/opt/plesk/php/8.4/bin/php -m | grep amqp                   # CLI: "amqp"
/opt/plesk/php/8.4/bin/php --ri amqp | head -5              # versión de la extensión y de librabbitmq
```

y en el PHP-FPM del dominio con un `phpinfo()` temporal (borrarlo después), o simplemente desplegando: si `composer install` termina, la extensión está cargada en el CLI.

- Si `pecl` falla por la versión de `librabbitmq` (repositorios antiguos), compilar `rabbitmq-c` desde su release con `cmake` e indicar su prefijo cuando `pecl` lo pida en lugar de pulsar Intro.
- Si el dominio usa otra versión de PHP de Plesk, repetir con su ruta (`/opt/plesk/php/<versión>/`) y su servicio (`plesk-php<versión>-fpm`).
- Vuelta atrás: borrar `40-amqp.ini` y reiniciar FPM; solo es posible mientras no se haya desplegado esta versión.

### Fase 3 — Broker RabbitMQ en Docker sobre Plesk

Un solo broker para todas las plataformas. Puede vivir en el servidor de una de ellas o en uno propio.

#### 3.1. Requisitos

- Sistema soportado por Docker Engine y por la extensión *Docker* de Plesk (AlmaLinux/Rocky 8 o 9, Debian, Ubuntu). **CentOS 7 no sirve**: Docker no publica paquetes para él.
- 2 GB de RAM libres (el contenedor está limitado a 1 GB y RabbitMQ usa hasta el 60 %), 5 GB libres de disco como mínimo (RabbitMQ bloquea a los publicadores por debajo de 2 GB libres).
- Un subdominio `<BROKER_HOST>` con registro DNS `A` (y `AAAA` si hay IPv6) apuntando al servidor.
- Las IPs públicas de los servidores de las plataformas (`<IPS_PLATAFORMAS>`) y salida al puerto `5671` desde cada uno.

#### 3.2. Docker

1. En Plesk, *Extensiones → Docker → Instalar*.
2. Comprobar:

   ```bash
   systemctl enable --now docker
   docker version
   docker compose version
   ```

#### 3.3. Directorio del broker

`/opt/bioscore-broker/` queda fuera de los despliegues de las plataformas: un despliegue nunca lo toca. Los tres ficheros de `infra/rabbitmq/` se copian de un checkout con esta versión (`<PROJECT_DIR>/infra/rabbitmq/` en un servidor de plataforma, o por `scp`).

```bash
mkdir -p /opt/bioscore-broker/{data,tls}
cp <checkout>/infra/rabbitmq/compose.prod.yaml  /opt/bioscore-broker/
cp <checkout>/infra/rabbitmq/rabbitmq.prod.conf /opt/bioscore-broker/
chown -R 999:999 /opt/bioscore-broker/data        # usuario rabbitmq de la imagen
chmod 0750 /opt/bioscore-broker/data /opt/bioscore-broker/tls
```

`compose.prod.yaml` monta `./rabbitmq.prod.conf` con ruta relativa: `compose.prod.yaml` y `rabbitmq.prod.conf` tienen que estar en el mismo directorio y el stack se arranca siempre desde la línea de comandos con `-f /opt/bioscore-broker/compose.prod.yaml` (los *Stacks* de la extensión de Plesk guardan el compose en otra ruta y no encontrarían el `.conf`).

#### 3.4. Certificado TLS

1. En Plesk, crear el subdominio `<BROKER_HOST>` (*Sitios web y dominios → Añadir subdominio*) y emitir su certificado con *SSL/TLS Certificates → Let's Encrypt*.
2. Localizar el fichero del certificado que usa nginx para ese subdominio:

   ```bash
   grep -E 'ssl_certificate\s' /var/www/vhosts/system/<BROKER_HOST>/conf/nginx.conf
   ```

   Plesk guarda en ese fichero (`/usr/local/psa/var/certificates/scf…`) la clave privada, el certificado y la cadena intermedia en un único PEM.

3. Crear `/opt/bioscore-broker/renew-tls.sh`, que separa el PEM en los tres ficheros que espera `rabbitmq.prod.conf` y hace que RabbitMQ los relea sin reiniciar:

   ```bash
   #!/usr/bin/env bash
   set -euo pipefail

   DOMAIN=<BROKER_HOST>
   TLS_DIR=/opt/bioscore-broker/tls
   CONTAINER=bioscore-broker-rabbitmq-1

   SRC=$(grep -oP 'ssl_certificate\s+\K[^;]+' "/var/www/vhosts/system/$DOMAIN/conf/nginx.conf" | head -1)
   TMP=$(mktemp -d)
   trap 'rm -rf "$TMP"' EXIT

   openssl pkey -in "$SRC" -out "$TMP/key.pem"
   awk -v d="$TMP" '/-----BEGIN CERTIFICATE-----/{n++} n{print > (n == 1 ? d "/cert.pem" : d "/chain.pem")}' "$SRC"
   [ -s "$TMP/chain.pem" ] || { echo "Sin cadena intermedia en $SRC" >&2; exit 1; }

   if ! cmp -s "$TMP/cert.pem" "$TLS_DIR/cert.pem"; then
       install -o 999 -g 999 -m 0600 "$TMP/key.pem"   "$TLS_DIR/key.pem"
       install -o 999 -g 999 -m 0644 "$TMP/cert.pem"  "$TLS_DIR/cert.pem"
       install -o 999 -g 999 -m 0644 "$TMP/chain.pem" "$TLS_DIR/chain.pem"
       docker exec "$CONTAINER" rabbitmqctl eval 'ssl:clear_pem_cache().' >/dev/null 2>&1 || true
   fi
   ```

   ```bash
   chmod 0700 /opt/bioscore-broker/renew-tls.sh
   /opt/bioscore-broker/renew-tls.sh
   ls -l /opt/bioscore-broker/tls                                      # cert.pem, chain.pem, key.pem de 999
   openssl x509 -in /opt/bioscore-broker/tls/cert.pem -noout -subject -enddate
   echo '17 4 * * * root /opt/bioscore-broker/renew-tls.sh' > /etc/cron.d/bioscore-broker-tls
   ```

   El cron diario recoge las renovaciones automáticas de Plesk (solo copia si el certificado ha cambiado). La primera ejecución tiene que ser **antes** del primer arranque: sin los tres ficheros RabbitMQ no abre el 5671.

#### 3.5. Arranque

```bash
docker compose -f /opt/bioscore-broker/compose.prod.yaml up -d
docker ps --filter name=bioscore-broker                              # STATUS "(healthy)" tras ~30 s
docker exec bioscore-broker-rabbitmq-1 hostname                      # debe ser "rabbitmq"
docker exec bioscore-broker-rabbitmq-1 rabbitmq-diagnostics listeners   # 5671 (amqp/ssl) y 15672 (http)
```

RabbitMQ guarda los datos por nombre de nodo (`rabbit@rabbitmq`): si el contenedor se recrea con otro hostname arranca vacío. No quitar `hostname: rabbitmq` del compose.

#### 3.6. Cortafuegos

Docker publica sus puertos con reglas propias de `iptables` que **se saltan el Firewall de Plesk y `firewalld`**. El 5671 se restringe en la cadena `DOCKER-USER`, que Docker evalúa antes que las suyas y no modifica.

1. Script `/opt/bioscore-broker/firewall.sh` (las IPs, separadas por espacios):

   ```bash
   #!/usr/bin/env bash
   set -euo pipefail

   ALLOWED="<IPS_PLATAFORMAS>"

   ipset create -exist bioscore-broker hash:ip
   ipset flush bioscore-broker
   for ip in $ALLOWED; do ipset add bioscore-broker "$ip"; done

   iptables -D DOCKER-USER -p tcp -m conntrack --ctorigdstport 5671 --ctdir ORIGINAL -m set ! --match-set bioscore-broker src -j DROP 2>/dev/null || true
   iptables -I DOCKER-USER -p tcp -m conntrack --ctorigdstport 5671 --ctdir ORIGINAL -m set ! --match-set bioscore-broker src -j DROP
   ```

2. Unidad `systemd` para reaplicarlo tras cada arranque de Docker, en `/etc/systemd/system/bioscore-broker-firewall.service`:

   ```ini
   [Unit]
   Description=Restringe el puerto 5671 del broker RabbitMQ a las plataformas
   After=docker.service
   Requires=docker.service
   PartOf=docker.service

   [Service]
   Type=oneshot
   RemainAfterExit=yes
   ExecStart=/opt/bioscore-broker/firewall.sh

   [Install]
   WantedBy=docker.service
   ```

   ```bash
   dnf install -y ipset                      # apt-get install ipset en Debian/Ubuntu
   chmod 0700 /opt/bioscore-broker/firewall.sh
   systemctl daemon-reload
   systemctl enable --now bioscore-broker-firewall
   iptables -L DOCKER-USER -n --line-numbers  # la regla DROP aparece antes del RETURN
   ```

3. IPv6: si `ss -ltnp | grep 5671` muestra también `[::]:5671`, aplicar la misma regla con `ip6tables` y un `ipset` `hash:ip family inet6`, o desactivar IPv6 en Docker.
4. Verificar **desde fuera** (desde una IP no permitida y desde un servidor de plataforma):

   ```bash
   nc -vz -w 5 <BROKER_HOST> 5671      # solo conecta desde las IPs permitidas
   nc -vz -w 5 <BROKER_HOST> 5672      # nunca conecta (no se publica)
   nc -vz -w 5 <BROKER_HOST> 15672     # nunca conecta (solo 127.0.0.1)
   ```

Para añadir una plataforma con IP nueva: editar `ALLOWED` y `systemctl restart bioscore-broker-firewall`.

#### 3.7. Topología, usuarios y contraseñas

`rabbitmq.prod.conf` **no** carga `definitions.json` al arrancar: así un reinicio no devuelve las contraseñas a las de desarrollo. La topología se importa a mano, una vez, con contraseñas generadas.

```bash
dnf install -y jq
umask 077
cp <checkout>/infra/rabbitmq/definitions.json /root/bioscore-definitions.json

for u in admin integration-biohydra integration-data-platform integration-balance integration-waste \
         integration-dont-waste-food integration-reporting integration-compliance integration-bioscore-erp; do
    printf '%s %s\n' "$u" "$(openssl rand -hex 24)"
done > /root/bioscore-broker-passwords.txt

while read -r u p; do
    jq --arg u "$u" --arg p "$p" '(.users[] | select(.name == $u) | .password) = $p' \
        /root/bioscore-definitions.json > /root/bioscore-definitions.tmp
    mv /root/bioscore-definitions.tmp /root/bioscore-definitions.json
done < /root/bioscore-broker-passwords.txt

jq -r '.users[] | select(.password == .name or .password == "admin") | .name' /root/bioscore-definitions.json   # vacío

docker cp /root/bioscore-definitions.json bioscore-broker-rabbitmq-1:/tmp/definitions.json
docker exec bioscore-broker-rabbitmq-1 rabbitmqctl import_definitions /tmp/definitions.json
docker exec bioscore-broker-rabbitmq-1 rm /tmp/definitions.json
docker exec bioscore-broker-rabbitmq-1 rabbitmqctl delete_user guest
```

Las contraseñas son hexadecimales para que vayan en el DSN sin codificar. Guardar `/root/bioscore-broker-passwords.txt` en el gestor de contraseñas del equipo y **borrar** las dos copias del servidor:

```bash
shred -u /root/bioscore-definitions.json /root/bioscore-broker-passwords.txt
```

Verificar:

```bash
R="docker exec bioscore-broker-rabbitmq-1 rabbitmqctl"
$R list_users                                   # admin [administrator] y ocho integration-*; sin guest
$R list_permissions -p bioscore
$R list_exchanges -p bioscore name type | grep integration     # 8 exchanges topic
$R list_queues -p bioscore name type messages                  # 8 colas quorum
$R list_bindings -p bioscore source_name destination_name routing_key | grep -c integration   # 56 (8 × 7)
$R list_policies -p bioscore                    # integration-inbox-retention
```

`import_definitions` es aditivo: volver a importar un `definitions.json` más nuevo (por ejemplo, con una plataforma añadida) crea lo que falta, pero **sobrescribe la contraseña** de todo usuario que aparezca en el fichero. Para añadir una plataforma más adelante, importar un fichero que contenga solo sus `users`, `permissions`, `exchanges`, `queues` y `bindings`, con su contraseña generada.

#### 3.8. Interfaz de administración

Publicada solo en `127.0.0.1:15672`. Dos accesos posibles:

- **Túnel SSH** (recomendado): `ssh -L 15672:127.0.0.1:15672 root@<BROKER_HOST>` y abrir `http://localhost:15672` con el usuario `admin`.
- **Proxy en Plesk:** en *`<BROKER_HOST>` → Configuración de Apache y nginx → Directivas adicionales de nginx*:

  ```nginx
  location / {
      allow <IP_DE_LA_OFICINA>;
      deny all;
      proxy_pass http://127.0.0.1:15672;
      proxy_set_header Host $host;
  }
  ```

  Si Plesk avisa de `duplicate location "/"`, desactivar *Modo proxy* en esa misma pantalla. Comprobar después que la renovación de Let's Encrypt del subdominio sigue funcionando (Plesk atiende `/.well-known/acme-challenge/` con su propia `location`).

#### 3.9. Operación del broker

| Tarea | Cómo |
|-------|------|
| Estado | `docker exec bioscore-broker-rabbitmq-1 rabbitmq-diagnostics status`, `check_local_alarms`, `check_port_connectivity` |
| Logs | `docker logs --tail 200 -f bioscore-broker-rabbitmq-1` (rotación de 5 × 20 MB en el compose) |
| Alerta | `/etc/cron.d/bioscore-broker-health`: `*/5 * * * * root [ "$(docker inspect -f '{{.State.Health.Status}}' bioscore-broker-rabbitmq-1 2>/dev/null)" = healthy ] \|\| echo "bioscore-broker no está healthy"` (cron envía la salida a `MAILTO`) |
| Copia de las definiciones | Diaria: `docker exec bioscore-broker-rabbitmq-1 rabbitmqctl export_definitions - > /root/backups/bioscore-definitions-$(date +\%F).json` con `chmod 600` (lleva los hashes de las contraseñas), copiada fuera del servidor |
| Copia de los datos | `/opt/bioscore-broker/` completo en las copias de seguridad del servidor |
| Colas | `rabbitmqctl list_queues -p bioscore name messages consumers`: cada cola de una plataforma activada debe tener `consumers = 1`; `messages` creciendo sin parar indica un consumidor caído |
| Rotar la contraseña de `<p>` | `rabbitmqctl change_password integration-<p> "$(openssl rand -hex 24)"`, actualizar el DSN en el `.env.local` de la plataforma y `sudo supervisorctl restart '<GRUPO>:*'`; mientras tanto su outbox acumula |
| Actualizar RabbitMQ | En ventana de mantenimiento: cambiar la etiqueta de la imagen en `compose.prod.yaml` (dentro de 4.x, de una versión menor en una menor), `docker compose -f /opt/bioscore-broker/compose.prod.yaml pull` y `up -d`. Vuelta atrás: la etiqueta anterior |

Si el broker cae, las plataformas siguen funcionando: los eventos se acumulan en sus outbox, el health check marca `integration_outbox` como `error` pasados `INTEGRATION_OUTBOX_MAX_WAIT_MINUTES` y todo se envía al volver.

## Configuración por plataforma

### Datos de cada plataforma

| Plataforma | `INTEGRATION_PLATFORM` | Usuario del broker | Publica en | Consume de | Base |
|------------|------------------------|--------------------|------------|------------|------|
| biohydra | `biohydra` | `integration-biohydra` | `integration.biohydra` | `integration.biohydra.inbox` | biohydra — **solo pruebas**, no activar en producción |
| Bioscore (data platform) | `data-platform` | `integration-data-platform` | `integration.data-platform` | `integration.data-platform.inbox` | Propia, **no** biohydra |
| Balance | `balance` | `integration-balance` | `integration.balance` | `integration.balance.inbox` | biohydra |
| Waste | `waste` | `integration-waste` | `integration.waste` | `integration.waste.inbox` | biohydra |
| Don't Waste Food | `dont-waste-food` | `integration-dont-waste-food` | `integration.dont-waste-food` | `integration.dont-waste-food.inbox` | biohydra |
| Reporting | `reporting` | `integration-reporting` | `integration.reporting` | `integration.reporting.inbox` | biohydra |
| Compliance | `compliance` | `integration-compliance` | `integration.compliance` | `integration.compliance.inbox` | biohydra |
| Bioscore ERP | `bioscore-erp` | `integration-bioscore-erp` | `integration.bioscore-erp` | `integration.bioscore-erp.inbox` | biohydra |

La cola de cada plataforma recibe **todo** lo que publican las otras siete; lo que no maneja se descarta como `skipped_unknown_type`. El identificador `INTEGRATION_PLATFORM` debe coincidir exactamente con la tabla: si no, el usuario del broker no tiene permiso sobre el exchange ni sobre la cola y el relay y el consumidor fallan con `ACCESS_REFUSED`.

### Plataformas basadas en biohydra

Para cada una de `balance`, `waste`, `dont-waste-food`, `reporting`, `compliance` y `bioscore-erp`, después de las fases 1 a 3:

#### 1. Programas de Supervisor

El merge desde biohydra trae los programas en la plantilla `infra/supervisor/hydra-workers.ini`, pero cada plataforma tiene su propio `infra/supervisor/<GRUPO>-workers.ini`. Añadir en él los dos programas, con los valores de la plataforma (los mismos `user`, `directory`, PHP y directorio de logs que sus `<GRUPO>-worker-*`):

```ini
[program:<GRUPO>-integration-consumer]
command=/opt/plesk/php/8.4/bin/php -d memory_limit=256M <PROJECT_DIR>/bin/console messenger:consume integration_in integration_retry --time-limit=3600 --memory-limit=192M --limit=500 -vv --env=prod --no-interaction
process_name=%(program_name)s_%(process_num)02d
directory=<PROJECT_DIR>
user=<PROJECT_USER>
numprocs=1
autostart=true
autorestart=true
startsecs=5
startretries=3
stopasgroup=true
killasgroup=true
stopwaitsecs=3600
stopsignal=TERM
redirect_stderr=true
stdout_logfile=/var/log/supervisor/<GRUPO>/integration-consumer.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=10
environment=APP_ENV="prod",APP_DEBUG="0"

[program:<GRUPO>-integration-relay]
command=/opt/plesk/php/8.4/bin/php -d memory_limit=256M <PROJECT_DIR>/bin/console integration:outbox:relay --time-limit=3600 -vv --env=prod --no-interaction
process_name=%(program_name)s_%(process_num)02d
directory=<PROJECT_DIR>
user=<PROJECT_USER>
numprocs=1
autostart=true
autorestart=true
startsecs=5
startretries=3
stopasgroup=true
killasgroup=true
stopwaitsecs=60
stopsignal=TERM
redirect_stderr=true
stdout_logfile=/var/log/supervisor/<GRUPO>/integration-relay.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=10
environment=APP_ENV="prod",APP_DEBUG="0"
```

y añadirlos al grupo:

```ini
[group:<GRUPO>]
programs=<GRUPO>-worker-high,<GRUPO>-worker-medium,<GRUPO>-worker-low,<GRUPO>-integration-consumer,<GRUPO>-integration-relay
```

`numprocs=1` es obligatorio en el consumidor: el orden de los eventos depende de que haya uno solo por plataforma. Este cambio puede ir en el mismo despliegue de la fase 2: con el flag apagado los dos programas arrancan y esperan sin trabajar. `bin/deploy` aplica el `.ini` (`reread` + `update <GRUPO>`) y el sudoers de la plataforma no cambia (ya autoriza `<GRUPO>:*`).

Comprobar tras el despliegue:

```bash
supervisorctl status '<GRUPO>:*'     # también <GRUPO>-integration-consumer_00 y <GRUPO>-integration-relay_00 en RUNNING
```

#### 2. Usuario de integración

Los cambios provocados por eventos entrantes se ejecutan y se registran en *Registro* a nombre de un usuario reservado. Crearlo en la plataforma desde la gestión de usuarios de administración, con rol **Administrador**, con un email propio (por ejemplo `integracion@<dominio de la plataforma>`) y una contraseña larga que nadie use para entrar. No debe ser el usuario de una persona: si se le quita el rol o se borra, los eventos dejan de aplicarse y quedan en `integration_failed`.

#### 3. `.env.local` del servidor

En `<PROJECT_DIR>/.env.local`, con la contraseña de `integration-<p>` del paso 3.7:

```dotenv
###> integration ###
INTEGRATION_EVENTS_ENABLED=true
INTEGRATION_PLATFORM=<p>
INTEGRATION_TRANSPORT_DSN=amqps://integration-<p>:<contraseña>@<BROKER_HOST>:5671/bioscore?cacert=<CA_BUNDLE>
INTEGRATION_USERNAME=<email del usuario de integración>
###< integration ###
```

- `cacert` es **obligatorio** con `amqps://`: sin él Symfony lanza `No CA certificate has been provided`. Como el certificado es de Let's Encrypt, basta el almacén de CA del sistema. La verificación del certificado está activa, así que el host del DSN tiene que ser `<BROKER_HOST>` (el nombre del certificado), nunca la IP.
- `INTEGRATION_MAX_HOPS`, `INTEGRATION_RETENTION_DAYS` e `INTEGRATION_OUTBOX_MAX_WAIT_MINUTES` se dejan con los valores de `.env` salvo motivo concreto.
- El fichero pertenece a `<PROJECT_USER>` y no debe ser legible por otros (`chmod 0640`).

Ejemplo para Balance:

```dotenv
INTEGRATION_EVENTS_ENABLED=true
INTEGRATION_PLATFORM=balance
INTEGRATION_TRANSPORT_DSN=amqps://integration-balance:3f9c…@mq.bioscore.info:5671/bioscore?cacert=/etc/pki/tls/certs/ca-bundle.crt
INTEGRATION_USERNAME=integracion@balance.bioscore.info
```

#### 4. Aplicar y comprobar

`.env.local` se lee en cada petición, pero los workers lo leen al arrancar:

```bash
sudo -u <PROJECT_USER> sudo -n /usr/bin/supervisorctl restart '<GRUPO>:*'
tail -n 50 /var/log/supervisor/<GRUPO>/integration-consumer.log    # sin errores de conexión
tail -n 50 /var/log/supervisor/<GRUPO>/integration-relay.log
```

En el broker, la cola de la plataforma debe tener ahora un consumidor:

```bash
docker exec bioscore-broker-rabbitmq-1 rabbitmqctl list_queues -p bioscore name messages consumers | grep '<p>'
```

Y el health check de la plataforma debe incluir `"integration_outbox": "ok"`:

```bash
curl -s https://<dominio de la plataforma>/api/health-check
```

#### 5. Vuelta atrás

`INTEGRATION_EVENTS_ENABLED=false` en `.env.local` y `supervisorctl restart '<GRUPO>:*'`. Lo pendiente en el outbox se conserva y se envía al reactivar. Mientras esté apagada, **los mensajes que lleguen a su cola se confirman y se pierden**; si la desactivación va a ser larga, parar solo el consumidor (`supervisorctl stop <GRUPO>:<GRUPO>-integration-consumer_00`, como `root`) para que se queden en la cola del broker, que los conserva hasta 14 días o 512 MB.

### Bioscore (data platform)

No está construida sobre biohydra, así que no tiene `INTEGRATION_*`, ni outbox, ni Supervisor de esta guía. Desde el broker solo necesita:

- el host `<BROKER_HOST>`, puerto `5671` con TLS, vhost `bioscore`;
- el usuario `integration-data-platform` y su contraseña (paso 3.7);
- la IP pública de su servidor en `ALLOWED` del cortafuegos (paso 3.6).

Su implementación debe publicar en el exchange `integration.data-platform` y consumir de la cola `integration.data-platform.inbox` respetando el sobre, las cabeceras, la routing key y las reglas de la *Guía de contratos* de `infra/rabbitmq/README.md`. El usuario no puede declarar recursos (`configure ^$`): su cliente AMQP no debe declarar exchanges ni colas al conectar, solo usarlos.

### biohydra

Es la base común: su identificador existe en el broker para las pruebas entre plataformas en local. En producción no se activa; si hay un entorno de staging de biohydra, se configura como una plataforma basada en biohydra con `INTEGRATION_PLATFORM=biohydra`.

### Fase 5 — Prueba de extremo a extremo

Con dos plataformas activadas (por ejemplo, A = `balance` y B = `waste`), como el usuario de Plesk de cada una:

```bash
# A
sudo -u <PROJECT_USER_A> -H bash -c 'cd <PROJECT_DIR_A> && /opt/plesk/php/8.4/bin/php bin/console integration:ping "prueba de instalación" --env=prod'
sudo -u <PROJECT_USER_A> -H bash -c 'cd <PROJECT_DIR_A> && /opt/plesk/php/8.4/bin/php bin/console integration:outbox:list --env=prod'   # la fila con sentAt

# B (en unos segundos)
sudo -u <PROJECT_USER_B> -H bash -c 'cd <PROJECT_DIR_B> && /opt/plesk/php/8.4/bin/php bin/console integration:inbox:list --env=prod'    # platform.pinged, processed
grep platform.pinged /var/log/supervisor/<GRUPO_B>/integration-consumer.log
```

Todas las demás plataformas activadas también lo reciben y lo marcan `processed`.

### Problemas frecuentes

| Síntoma | Causa | Solución |
|---------|-------|----------|
| `composer install` falla con `ext-amqp * is missing` | Fase 1 no aplicada en ese servidor | Instalar la extensión y relanzar `bin/deploy` |
| `No CA certificate has been provided` en los logs del relay o del consumidor | DSN `amqps://` sin `cacert` | Añadir `?cacert=<CA_BUNDLE>` al DSN |
| `SSL connection failed` / error de verificación del certificado | Host del DSN distinto del certificado, cadena intermedia ausente en `chain.pem` o certificado caducado | Usar `<BROKER_HOST>`; `openssl s_client -connect <BROKER_HOST>:5671 -servername <BROKER_HOST> -verify_return_error </dev/null` desde el servidor de la plataforma debe acabar en `Verify return code: 0 (ok)`; relanzar `renew-tls.sh` |
| `ACCESS_REFUSED - Login was refused` | Contraseña errónea o con caracteres que hay que codificar en la URL | Revisar el DSN; regenerar con `rabbitmqctl change_password` y hexadecimal |
| `ACCESS_REFUSED … access to exchange/queue … refused` | `INTEGRATION_PLATFORM` no coincide con el usuario del DSN | Igualarlos según la tabla de plataformas |
| `NOT_FOUND - no exchange` / `no queue` | Topología no importada o plataforma ausente de `definitions.json` | Paso 3.7 |
| Conexión que se queda colgada o `Connection timed out` | IP del servidor de la plataforma fuera de `ALLOWED`, o salida al 5671 bloqueada en ese servidor | Paso 3.6; `nc -vz <BROKER_HOST> 5671` desde la plataforma |
| El broker arranca vacío tras recrear el contenedor | Hostname distinto de `rabbitmq` | Restaurar `hostname: rabbitmq` en el compose y `up -d` |
| El contenedor reinicia en bucle con `eacces` | `data/` o `tls/` no legibles por el UID 999 | `chown -R 999:999 /opt/bioscore-broker/data` y relanzar `renew-tls.sh` |
| El 5671 responde desde cualquier IP | Falta la regla de `DOCKER-USER` (o se perdió al reiniciar) | `systemctl status bioscore-broker-firewall`; paso 3.6 |
| Los eventos entrantes acaban en `integration_failed` con `IntegrationUserNotAvailable` | `INTEGRATION_USERNAME` vacío, usuario inexistente o sin rol Administrador | Revisar el usuario de integración; `messenger:failed:retry --transport=integration_failed` |
| `integration_outbox` en `error` en el health check | Relay parado o broker inaccesible | `supervisorctl status '<GRUPO>:*'`, log del relay y estado del broker |
| La cola de una plataforma crece y tiene `consumers = 0` | Consumidor parado, en `FATAL` o flag apagado | `supervisorctl status`, log del consumidor |

## Cómo extenderlo

**Añadir un evento saliente**

1. Clase que implemente `App\Shared\Application\Integration\IntegrationEvent`, en la capa `Application` del BC emisor (`type()` con el patrón `a.b`, `version()` ≥ 1, `payload()` con ids y lo mínimo imprescindible).
2. `DomainEventSubscriber` en `<BC>/Infrastructure/Integration/` que traduzca el evento de dominio y llame a `IntegrationEventPublisher::publish()`. Nunca publicar eventos de dominio en el broker.
3. Si lleva `aggregateId`, el payload debe contener el **estado completo** relevante (el último gana).
4. Añadirlo al catálogo de `infra/rabbitmq/README.md`.

**Añadir un handler entrante**

1. Clase que implemente `IntegrationEventHandler` (se etiqueta sola con `shared.integration.handler`): `supports()` y `handle()`, leyendo el payload con los accesores de `ReceivedIntegrationEvent` y delegando en un servicio de aplicación con el usuario de integración.
2. Varios handlers pueden soportar el mismo tipo; se ejecutan todos en la misma transacción.

**Trampas conocidas**

- Un handler entrante **no** debe delegar en un `DeferredCommand` trabajo que publique eventos de integración: `IntegrationContext` solo vive dentro del `__invoke`, así que el worker publica con `hops = 0` y `correlationId` nuevo, y un ciclo entre plataformas no se cortaría (hallazgo M1, aceptado como restricción documentada).
- El handler no debe abrir otra conexión ni hacer commit explícito: rompería la atomicidad.
- Un comando de consola largo acumula todos sus eventos y los escribe al terminar; si falla a mitad no publica nada aunque parte de los cambios ya estén guardados.
- En HTTP los repositorios hacen `flush()` en cada `save()` y el outbox se escribe en `kernel.terminate`, en otra transacción: si el proceso muere entre medias el cambio queda y el evento se pierde (ventana aceptada por el usuario).
- `sequence` es el autoincremental del outbox: dos transacciones concurrentes sobre el mismo aggregate podrían confirmarse en orden inverso y la posterior se descartaría como `skipped_stale`.
- Un cambio incompatible en un evento es una versión nueva publicada en paralelo; dentro de una versión solo cambios aditivos.
- Otras plataformas que no usen biohydra (Bioscore *data platform*) deben implementar el sobre según `infra/rabbitmq/README.md`.

## Tests

- **Unitarios** (`tests/Unit/Shared/`): entidades y repositorios InMemory (`Domain/Model/Integration/`), `IntegrationEnvelopeTest` (con el fixture `fixtures/platform-pinged-v1.json`), `IntegrationMessageSerializerTest` (sin nombres de clase en cuerpo ni cabeceras), `BufferedIntegrationEventPublisherTest`, `FlushIntegrationOutboxSubscriberTest`, `OutboxRelayTest`, `RelayIntegrationOutboxCommandTest` (flag apagado, lock ocupado, un lote con `clear()`, liberación del lock), `IntegrationUserProviderTest`, `IncomingIntegrationMessageHandlerTest` (todos los descartes, éxito, rollback, contexto, usuario forzado), `UndecodableIntegrationMessageHandlerTest`, `LogPlatformPingedHandlerTest`, `IntegrationOutboxHealthIndicatorTest`, `IntegrationSettingsTest`, `ReceivedIntegrationEventTest` y `Purge/PurgeIntegrationMessagesServiceTest`. Mothers y dobles: `IntegrationEnvelopeMother`, `ReceivedIntegrationEventMother`, `IntegrationSettingsMother`, `FakeIntegrationMessageBus`, `SpyIntegrationEventHandler`.
- **Aceptación:** solo `tests/Acceptance/Shared/health-check.feature` (el JSON esperado incluye `"integration_outbox": "ok"`); no hay pantallas ni API. El extremo a extremo se probó a mano con RabbitMQ local (`biohydra` → `waste`).
- **Ejecutar:** `make test-unit options="--filter Integration"`, `make test-acceptance options="tests/Acceptance/Shared/health-check.feature"` y `make test-parallel` para todo.

## Desviaciones y deuda

Detalle en `plan.md` (`## Implementación`) y `review.md`; ningún hallazgo queda abierto.

- Sin `x-single-active-consumer` en las colas (decisión del usuario).
- `sequence` y `last_sequence` como `INT` en lugar de `bigint` (decisión del usuario).
- `InjectIntegrationMessageCommand` recibe el serializer como `SerializerInterface` con `#[Autowire(service: IntegrationMessageSerializer::class)]` porque Symfony 7.4 lo decora con `SigningSerializer`.
- Añadida la policy de retención de colas (review M2) y `clear()` del EntityManager en el relay (review M3).
- Aceptado: con el flag apagado el consumidor confirma y pierde los mensajes que reciba (review A2, descartado por el usuario); la protección contra bucles no atraviesa comandos diferidos (review M1, documentado en la guía).
- Deuda previa no corregida: `src/Security/Domain/Event/RecordEventSubscriber.php` importa `UserMother` desde `tests/`.
