# RabbitMQ — Eventos de integración entre plataformas

Las plataformas Bioscore publican **eventos de integración** en un broker RabbitMQ y consumen los de las demás. Cada plataforma escribe sus eventos en una tabla `integration_outbox` cuando termina bien la petición, el comando o el mensaje que los provoca: en el consumidor, dentro de la misma transacción que el cambio; en una petición HTTP, en `kernel.terminate`, en otra transacción posterior a la del cambio, de modo que si el proceso muere entre las dos el cambio queda guardado y el evento se pierde. Un relay los publica después en el broker, y el consumidor de cada plataforma los procesa una sola vez (`integration_inbox`), en orden por aggregate (`integration_positions`) y con el usuario de integración configurado.

El código vive en `src/Shared/{Domain,Application,Infrastructure}/…/Integration/`. Todo está desactivado mientras `INTEGRATION_EVENTS_ENABLED=false`.

| Fichero | Uso |
|---------|-----|
| [`compose.yaml`](compose.yaml) | Broker local (`make broker-up`), proyecto `bioscore-broker` en la red Docker `bioscore` |
| [`rabbitmq.conf`](rabbitmq.conf) | Configuración local: carga `definitions.json` al arrancar |
| [`definitions.json`](definitions.json) | Topología (vhost, usuarios, permisos, exchanges, colas y bindings), idéntica en local y producción |
| [`compose.prod.yaml`](compose.prod.yaml) | Broker de producción (Docker en el servidor Plesk) |
| [`rabbitmq.prod.conf`](rabbitmq.prod.conf) | Configuración de producción: solo TLS (5671), sin carga automática de definiciones |

## Variables de entorno

| Variable | Por defecto | Significado |
|----------|-------------|-------------|
| `INTEGRATION_EVENTS_ENABLED` | `false` | Activa el publisher, el relay, el consumidor y el indicador de salud |
| `INTEGRATION_PLATFORM` | `biohydra` | Identificador de la plataforma (`[a-z0-9-]+`): `source` del sobre, exchange `integration.<p>` y cola `integration.<p>.inbox` |
| `INTEGRATION_TRANSPORT_DSN` | `in-memory://` | DSN del broker (`amqp://…` en local, `amqps://…` en producción) |
| `INTEGRATION_USERNAME` | *(vacío)* | Usuario con rol `Admin` o `SuperAdmin` que ejecuta los cambios provocados por eventos entrantes |
| `INTEGRATION_MAX_HOPS` | `5` | Saltos máximos de una cadena de eventos (protección contra bucles) |
| `INTEGRATION_RETENTION_DAYS` | `30` | Días que se conservan los mensajes enviados del outbox y los del inbox |
| `INTEGRATION_OUTBOX_MAX_WAIT_MINUTES` | `10` | Minutos que puede esperar un mensaje pendiente antes de que el health check marque `integration_outbox` como `error` |

## Entorno local

```bash
make broker-up      # una sola instancia en la red bioscore, aunque se lance desde varios checkouts
make broker-logs
make broker-down
```

- Management: <http://localhost:15672> (`admin` / `admin`).
- Desde los contenedores de las plataformas el broker es `rabbitmq.local:5672`, vhost `bioscore`.
- Cada plataforma usa su usuario: `integration-<p>`, con contraseña igual al nombre de usuario en local.
- El compose tiene nombre de proyecto fijo (`bioscore-broker`): si dos checkouts con versiones distintas de `definitions.json` ejecutan `make broker-up`, gana el último, que recrea el contenedor.

### Prueba entre dos plataformas

En el `.env.local` de cada checkout (ejemplo para biohydra; en la otra, su identificador y su usuario):

```dotenv
INTEGRATION_EVENTS_ENABLED=true
INTEGRATION_PLATFORM=biohydra
INTEGRATION_TRANSPORT_DSN=amqp://integration-biohydra:integration-biohydra@rabbitmq.local:5672/bioscore
INTEGRATION_USERNAME=<usuario administrador de la plataforma>
```

Con la migración de las tablas `integration_*` aplicada en las dos plataformas:

```bash
make integration-relay        # en las dos plataformas
make integration-consume      # en las dos plataformas
make sf c="integration:ping hola"            # en la plataforma A
make sf c="integration:outbox:list"          # A: la fila aparece con sentAt
make sf c="integration:inbox:list"           # B: platform.pinged con estado processed
```

Para probar una plataforma sin las demás: `make sf c="integration:inject <fichero.json>"` procesa un sobre leído de un fichero con el mismo handler que en producción (hay un ejemplo en `tests/Unit/Shared/Infrastructure/Integration/fixtures/platform-pinged-v1.json`).

## Topología

- vhost `bioscore`.
- Un exchange `topic` durable por plataforma emisora: `integration.<p>`.
- Una cola de entrada por plataforma consumidora: `integration.<p>.inbox` (quorum, durable). Sin `x-single-active-consumer`: RabbitMQ no admite `basic.get` en colas quorum con esa opción y el receptor AMQP de Messenger consume con `basic.get`. El orden lo garantizan un único consumidor por plataforma (`numprocs=1` en Supervisor), `integration_positions` y el inbox; no arrancar más de un `integration-consumer` por plataforma.
- Bindings de `integration.<q>` → `integration.<p>.inbox` con routing key `#` para toda plataforma `q ≠ p`: cada plataforma recibe todo lo de las demás y descarta como `skipped_unknown_type` lo que no maneja, así que empezar a consumir un tipo nuevo no exige tocar RabbitMQ.
- Routing key de cada mensaje: `<type>.v<version>` (`platform.pinged.v1`).
- Retención: la policy `integration-inbox-retention` (`^integration\..+\.inbox$`) limita cada cola de entrada a 14 días por mensaje (`message-ttl`) y a 512 MB (`max-length-bytes`, `overflow: drop-head`). Una cola sin consumidor (una plataforma aún no activada o un consumidor parado) no crece sin fin ni llega a la alarma de disco del broker, que bloquearía a todos los publicadores; a cambio, los mensajes de una plataforma que no consume durante más de 14 días, o los más antiguos cuando la cola supera 512 MB, se pierden. Como los eventos con `aggregateId` llevan el estado completo, el siguiente evento de cada aggregate lo recupera. En producción la policy se importa con el resto de `definitions.json` (`import_definitions` es aditivo) y se comprueba con `rabbitmqctl list_policies -p bioscore`.
- Las aplicaciones no declaran nada (`auto_setup: false`): la topología solo se define en `definitions.json`.

Plataformas: `biohydra` (solo pruebas), `data-platform` (Bioscore), `balance`, `waste`, `dont-waste-food`, `reporting`, `compliance` y `bioscore-erp` (Bioscore ERP).

### Usuarios y permisos

| Usuario | configure | write | read |
|---------|-----------|-------|------|
| `integration-<p>` | `^$` (ninguno) | `^integration\.<p>$` (su exchange) | `^integration\.<p>\.inbox$` (su cola) |
| `admin` | `.*` | `.*` | `.*` |

Ninguna plataforma puede declarar ni borrar recursos. Por eso el transporte de entrada no reintenta en RabbitMQ (`max_retries: 0`): los reintentos van a la cola Doctrine `integration_retry` (5 intentos con backoff) y, agotados, a `integration_failed`, sin las colas de retraso que Messenger crearía en el broker.

**Rotación de contraseñas** de una plataforma: `rabbitmqctl change_password integration-<p> '<nueva>'` en el broker, actualizar `INTEGRATION_TRANSPORT_DSN` en el `.env.local` de la plataforma y reiniciar sus programas de Supervisor (`<GRUPO>-integration-consumer` y `<GRUPO>-integration-relay`). Mientras tanto, el outbox de la plataforma acumula y se vacía al reconectar.

## Producción

Resumen; la guía paso a paso está en el manual técnico del plan `integration-events-broker`.

### `ext-amqp` en el PHP de Plesk

`symfony/amqp-messenger` exige `ext-amqp` en `composer install`, **aunque el flag esté apagado**. Hay que instalarla en todos los servidores de todas las plataformas antes de desplegar esta versión:

```bash
dnf install -y plesk-php84-devel gcc make librabbitmq-devel   # librabbitmq-devel desde EPEL
/opt/plesk/php/8.4/bin/pecl install amqp
echo "extension=amqp.so" > /opt/plesk/php/8.4/etc/php.d/40-amqp.ini
systemctl restart plesk-php84-fpm && supervisorctl restart all
/opt/plesk/php/8.4/bin/php -m | grep amqp
```

Orden de despliegue: extensión en todos los servidores → merge con `INTEGRATION_EVENTS_ENABLED=false` → broker en producción → activación plataforma a plataforma.

### Broker en Docker sobre Plesk

- Requisitos: sistema compatible con Docker Engine y con la extensión *Docker* de Plesk (Docker no publica paquetes para CentOS 7), 2 GB de RAM libres, IPs públicas de los servidores de las plataformas.
- Ficheros en `/opt/bioscore-broker/`: `compose.prod.yaml` y `rabbitmq.prod.conf` copiados de este directorio, `data/` (propietario UID/GID 999, el usuario `rabbitmq` de la imagen) y `tls/`. El directorio queda fuera de los despliegues de las plataformas.
- TLS: subdominio de Plesk (p. ej. `mq.<dominio>`) con Let's Encrypt; un script diario (`/opt/bioscore-broker/renew-tls.sh`) copia certificado, clave y cadena a `tls/cert.pem`, `tls/key.pem` y `tls/chain.pem` (legibles por el UID 999) y ejecuta `docker exec bioscore-broker-rabbitmq-1 rabbitmqctl eval 'ssl:clear_pem_cache().'`.
- Arranque: `docker compose -f /opt/bioscore-broker/compose.prod.yaml up -d` (o un stack de *Docker → Stacks*). Comprobar `docker ps` (estado `healthy`) y que el hostname del contenedor es `rabbitmq`: RabbitMQ guarda los datos por nombre de nodo y, con otro hostname, arranca vacío.
- Cortafuegos: Docker publica sus puertos saltándose el Firewall de Plesk y `firewalld`. Restringir el 5671 con reglas persistentes en la cadena `DOCKER-USER` (`iptables -I DOCKER-USER -p tcp --dport 5671 ! -s <ips> -j DROP`, o con `ipset`) y verificar desde fuera que 5671 solo responde a las IPs permitidas y que 5672 y 15672 no responden.
- Management: publicado solo en `127.0.0.1:15672`; acceso por un proxy de Plesk en el subdominio (`location / { proxy_pass http://127.0.0.1:15672; }`) con restricción por IP, o por túnel SSH.
- Topología: `rabbitmq.prod.conf` no carga definiciones al arrancar, para que reiniciar el contenedor no sobrescriba las contraseñas. Se importa una vez: copiar `definitions.json` a un fichero temporal, sustituir las contraseñas locales (también la de `admin`) por contraseñas generadas, `docker cp` al contenedor, `docker exec … rabbitmqctl import_definitions /tmp/definitions.json`, verificar con `rabbitmqctl list_exchanges|list_queues|list_bindings -p bioscore` y borrar las copias con contraseñas.
- DSN de cada plataforma en su `.env.local`: `INTEGRATION_TRANSPORT_DSN=amqps://integration-<p>:<pass>@mq.<dominio>:5671/bioscore?cacert=<almacén de CA del sistema>` (`/etc/pki/tls/certs/ca-bundle.crt` en RHEL/AlmaLinux); `cacert` es obligatorio con `amqps://` y el host debe ser el del certificado.
- Operación: copia diaria de las definiciones (`rabbitmqctl export_definitions -`) fuera del servidor, `/opt/bioscore-broker/data` en las copias de seguridad, logs con `docker logs` (rotación del compose), comprobaciones con `rabbitmq-diagnostics status`, `check_local_alarms` y `check_port_connectivity`, y alerta por cron si el contenedor no está `healthy`.
- Actualizaciones: cambiar la etiqueta de la imagen en `compose.prod.yaml` (dentro de 4.x, de una en una versión menor), `docker compose pull` y `up -d` en ventana de mantenimiento. Vuelta atrás: la etiqueta anterior.
- Si el broker cae, las plataformas siguen funcionando: los eventos se acumulan en sus outbox, `integration_outbox` sale `error` en el health check pasados `INTEGRATION_OUTBOX_MAX_WAIT_MINUTES` y todo se envía al volver.

Errores típicos: contenedor recreado con otro hostname (parece vacío), volumen sin permisos para el UID 999, certificado no legible dentro del contenedor, 5671 abierto a Internet por no aplicar `DOCKER-USER`.

### Supervisor

Dos programas por plataforma dentro de su grupo (ver [`infra/supervisor/README.md`](../supervisor/README.md)): `<GRUPO>-integration-consumer` (`messenger:consume integration_in integration_retry`) y `<GRUPO>-integration-relay` (`integration:outbox:relay`). Los mensajes que agotan los reintentos se gestionan con `messenger:failed:show|retry --transport=integration_failed`.

## Guía de contratos

Esta es la especificación que cumple biohydra y que debe cumplir cualquier otra implementación (por ejemplo, Bioscore *data platform*, que no está construida sobre biohydra).

### Sobre

Cuerpo JSON (`content_type: application/json`), sin nombres de clase en el cuerpo ni en las cabeceras:

```json
{
  "id": "0193a1c2-7f00-7c3e-9b1d-2f6c1a9e4b10",
  "type": "platform.pinged",
  "version": 1,
  "source": "waste",
  "sequence": 1234,
  "occurredOn": "2026-11-19T10:15:00+00:00",
  "aggregateId": null,
  "accountRef": null,
  "correlationId": "0193a1c2-7f00-7c3e-9b1d-2f6c1a9e4b10",
  "causationId": null,
  "hops": 0,
  "payload": { "message": "hola" }
}
```

| Campo | Tipo | Regla |
|-------|------|-------|
| `id` | UUID | Único por mensaje; clave de idempotencia del consumidor |
| `type` | string | `^[a-z][a-z0-9-]*(\.[a-z][a-z0-9-]*)+$` |
| `version` | int | ≥ 1 |
| `source` | string | Identificador de la plataforma emisora, `^[a-z0-9-]+$` |
| `sequence` | int | ≥ 1, creciente en el emisor |
| `occurredOn` | string | Fecha ATOM en UTC con el formato exacto `YYYY-MM-DDTHH:MM:SS+00:00`, sin fracciones de segundo (se admite `Z` en lugar de `+00:00`); un valor con fracciones se rechaza como sobre mal formado |
| `aggregateId` | string o null | Si no es nulo, el evento lleva el estado completo relevante y se ordena |
| `accountRef` | string o null | Reservado; nulo hasta que exista una identidad única de cuenta |
| `correlationId` | UUID | Igual a `id` si el evento no lo provoca otro; si no, el `correlationId` del que lo provocó |
| `causationId` | UUID o null | `id` del evento que lo provocó |
| `hops` | int | 0 en el origen; +1 por cada evento encadenado |
| `payload` | objeto | Contenido del evento; los campos desconocidos se ignoran |

Cabeceras AMQP: `x-integration-type`, `x-integration-version`, `x-integration-source`. Routing key `<type>.v<version>`.

### Reglas

- **Versionado:** dentro de una versión solo se admiten cambios aditivos. Un cambio incompatible es una versión nueva que se publica en paralelo durante la transición. Un tipo o versión sin handler se marca `skipped_unknown_type` y se confirma.
- **Orden:** el consumidor guarda la última `sequence` procesada por `(source, aggregateId)` y descarta como `skipped_stale` lo que llegue con secuencia menor o igual. Por eso un evento con `aggregateId` debe llevar el **estado completo** relevante del aggregate: el último gana. Los eventos con `aggregateId = null` no se ordenan. La secuencia es el autoincremental del outbox; dos transacciones concurrentes sobre el mismo aggregate podrían confirmarse en orden inverso al de su secuencia (poco probable, pero posible).
- **Idempotencia:** un `id` ya registrado en el inbox se confirma sin ejecutar nada. Un mensaje puede llegar más de una vez (por ejemplo, si el relay muere tras publicar y antes de marcarlo).
- **Bucles:** un consumidor no publica eventos con `hops` por encima de `INTEGRATION_MAX_HOPS` y descarta como `skipped_hops` lo que llegue por encima. Los mensajes cuyo `source` es la propia plataforma se ignoran.
- **Sin comandos diferidos que publiquen:** `correlationId`, `causationId` y `hops` solo se heredan cuando el evento de integración se publica de forma síncrona dentro del handler entrante. Un handler entrante no puede delegar en un comando diferido (`DeferredCommand`) ningún trabajo que publique eventos de integración: el worker que lo ejecuta publica sin contexto (`hops = 0`, `correlationId` nuevo, `causationId` nulo), así que un ciclo entre plataformas que pase por él no se corta nunca y la cadena pierde la trazabilidad.
- **Protección de datos:** solo identificadores y lo mínimo imprescindible para que el receptor actúe; nada de datos personales que el receptor no necesite.
- **Atomicidad:** el handler de una plataforma no debe abrir otra conexión ni hacer commit explícito: todo lo que hace el consumidor (inbox, posición, cambios de la plataforma, eventos de dominio y outbox) va en una sola transacción.

### Añadir un evento saliente

1. Una clase que implemente `App\Shared\Application\Integration\IntegrationEvent` (`type()`, `version()`, `aggregateId()`, `accountRef()`, `payload()`), en la capa `Application` del BC que lo emite.
2. Un `DomainEventSubscriber` en `<BC>/Infrastructure/Integration/` que traduzca el evento de dominio y llame al puerto `IntegrationEventPublisher::publish()`. El dominio no sabe nada de la integración: nunca se publican eventos de dominio en el broker.
3. Añadir el evento al catálogo de abajo.

El publisher acumula los eventos en memoria y los escribe en el outbox solo si la petición, el comando o el mensaje terminan bien. Un comando de consola largo (por ejemplo, una importación) escribe todos sus eventos al terminar y no publica nada si falla a mitad.

### Añadir un handler entrante

Una clase que implemente `App\Shared\Application\Integration\IntegrationEventHandler` (se etiqueta sola con `shared.integration.handler`):

- `supports(string $type, int $version): bool`;
- `handle(ReceivedIntegrationEvent $event, User $user): void`, que lee el `payload` con `$event->string()`, `nullableString()`, `int()`, `bool()` o `array()` (lanzan excepción si falta el campo o tiene otro tipo) y delega en el servicio de aplicación que corresponda con el usuario de integración.

Varios handlers pueden soportar el mismo tipo; se ejecutan todos en la misma transacción.

### Catálogo de eventos

| Tipo | Versión | Emisor | `aggregateId` | Payload | Uso |
|------|---------|--------|---------------|---------|-----|
| `platform.pinged` | 1 | Todas (`integration:ping`) | — | `message: string` | Prueba de extremo a extremo; el receptor solo escribe un log |
