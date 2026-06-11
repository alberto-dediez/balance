# Supervisor — Workers de Symfony Messenger (producción)

Configuración de **Supervisor** para los workers de Messenger del proyecto Hydra en el servidor de producción (CentOS + Plesk, sin Docker).

El transporte es `doctrine://default` (tabla `messenger_messages`), con tres colas por prioridad definidas en [`config/packages/messenger.yaml`](../../config/packages/messenger.yaml):

| Transporte | `queue_name` | Reintentos | Worker dedicado |
|------------|--------------|------------|-----------------|
| `async_high` | `high` | 5 | `hydra-worker-high` (×2) |
| `async` | `medium` | 3 | `hydra-worker-medium` (×1) |
| `async_low` | `low` | 2 | `hydra-worker-low` (×1) |
| `failed` | `failed` | — | No (gestión manual, ver abajo) |

## Estrategia de consumo

Cada worker consume su cola en primer lugar y, si está vacía, ayuda con las de menor prioridad (`messenger:consume` procesa los transportes en el orden en que se pasan):

- `hydra-worker-high` (2 procesos): `async_high async async_low`
- `hydra-worker-medium` (1 proceso): `async async_low`
- `hydra-worker-low` (1 proceso): `async_low`

Así los mensajes `high` siempre tienen capacidad dedicada y ninguna cola queda sin consumidor (la cola `low` tiene su propio worker, por lo que no sufre inanición bajo carga sostenida de las colas superiores).

Opciones clave de cada `command`:

- `--time-limit=3600`: el worker termina solo cada hora y Supervisor lo reinicia (libera memoria, recarga código tras deploys).
- `--memory-limit=128M`: corta antes si la memoria crece demasiado.
- `--limit=200`: máximo de mensajes procesados antes de salir (defensa adicional).
- `stopwaitsecs=3600` + `stopsignal=TERM`: el worker termina el mensaje en curso antes de morir (sin pérdida ni duplicado).

## Instalación en el servidor

> Guía completa paso a paso (instalación del paquete, permisos Plesk, SELinux, hardening): ver el documento *Instalación de Supervisor en producción (CentOS + Plesk)*.

### 1. Instalar y habilitar Supervisor

```bash
dnf install -y epel-release supervisor   # (yum en CentOS 7)
systemctl enable supervisord
systemctl start supervisord
```

Verificar que `/etc/supervisord.conf` termina con:

```ini
[include]
files = supervisord.d/*.ini
```

### 2. Ajustar el fichero del repo

Antes del primer despliegue, editar `infra/supervisor/hydra-workers.ini` y sustituir en los **tres** programas:

- `user=hydra` → *system user* del subscription de Plesk
  (`plesk bin subscription --info <dominio> | grep -E "System user|Home directory"`)
- `directory=` y la ruta del proyecto en `command=` → ruta real (`/var/www/vhosts/...`)
- `/opt/plesk/php/8.4/bin/php` → ruta del PHP CLI de Plesk que use el dominio (verificar con `/opt/plesk/php/8.4/bin/php -v`; **no** usar `/usr/bin/php`)

### 3. Crear el directorio de logs

```bash
mkdir -p /var/log/supervisor/hydra
chmod 0755 /var/log/supervisor/hydra
```

### 4. Enlazar el fichero del repo (symlink)

```bash
PROJECT_DIR=/var/www/vhosts/example.com/hydra   # ruta real

sudo ln -s "$PROJECT_DIR/infra/supervisor/hydra-workers.ini" \
           /etc/supervisord.d/hydra-workers.ini

supervisorctl reread
supervisorctl update
supervisorctl status
```

Salida esperada:

```
hydra:hydra-worker-high_00     RUNNING   pid 12345, uptime 0:00:05
hydra:hydra-worker-high_01     RUNNING   pid 12346, uptime 0:00:05
hydra:hydra-worker-medium_00   RUNNING   pid 12347, uptime 0:00:05
hydra:hydra-worker-low_00      RUNNING   pid 12348, uptime 0:00:05
```

> Tras un `git pull` que modifique este `.ini`, ejecutar de nuevo `supervisorctl reread && supervisorctl update`.

## Post-deploy (obligatorio)

Tras cada deploy hay que reiniciar los workers para que carguen el nuevo código:

```bash
sudo supervisorctl restart hydra:*
```

Para automatizarlo sin contraseña desde el usuario de deploy:

```
# /etc/sudoers.d/hydra-supervisor
hydra ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart hydra\:*
hydra ALL=(root) NOPASSWD: /usr/bin/supervisorctl status hydra\:*
```

## Verificación

```bash
# Logs en vivo (uno por worker)
tail -f /var/log/supervisor/hydra/messenger-high.log
tail -f /var/log/supervisor/hydra/messenger-medium.log
tail -f /var/log/supervisor/hydra/messenger-low.log

# Estado de las colas
sudo -u <PROJECT_USER> /opt/plesk/php/8.4/bin/php <PROJECT_DIR>/bin/console messenger:stats --env=prod
```

Comprobar el reinicio automático: `kill -TERM <pid>` de un proceso y verificar que Supervisor lo levanta de nuevo.

## Cola de fallidos (`failed`)

`failure_transport: failed` ya está configurado en `messenger.yaml`. No necesita worker en Supervisor; se gestiona a demanda:

```bash
bin/console messenger:failed:show --env=prod
bin/console messenger:failed:retry --env=prod
```
