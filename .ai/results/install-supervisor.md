# Instalación de Supervisor en producción (CentOS + Plesk)

Este documento describe los pasos necesarios para instalar y configurar **Supervisor** en el servidor de producción (CentOS con Plesk, dedicado) para gestionar los *workers* de Symfony Messenger del proyecto Waste / Food-Loss.

El transporte actual configurado es `doctrine://default` (cola en BBDD vía la tabla `messenger_messages`), por lo que no se requiere RabbitMQ ni Redis. Los workers se lanzarán con `bin/console messenger:consume async`.

---

## 1. Resumen de arquitectura objetivo

- 1 grupo Supervisor: `food-loss-workers`
- 2 procesos `numprocs=2` consumiendo la cola `async`
- Reinicio automático del proceso cada hora (`--time-limit=3600`) y al alcanzar 128 MB de memoria (`--memory-limit=128M`) para evitar fugas de memoria.
- Logs centralizados en `/var/log/supervisor/food-loss/`
- Reinicio automático tras cada *deploy* mediante `supervisorctl restart food-loss-workers:*`.

---

## 2. Instalación de Supervisor en el servidor

> Ejecutar como `root` (o `sudo`) por SSH.

### 2.1. Instalar el paquete

CentOS 7 / 8 / Stream:

```bash
# Habilitar EPEL si no está habilitado
yum install -y epel-release

# Instalar supervisor
yum install -y supervisor
```

En CentOS 8/Stream/Rocky/AlmaLinux usar `dnf` en lugar de `yum`:

```bash
dnf install -y epel-release
dnf install -y supervisor
```

### 2.2. Habilitar y arrancar el servicio

```bash
systemctl enable supervisord
systemctl start  supervisord
systemctl status supervisord
```

### 2.3. Comprobar la versión y la ruta de configuración

```bash
supervisord -v
# La configuración principal vive en:
ls -l /etc/supervisord.conf
# Y los include en:
ls -l /etc/supervisord.d/
```

Verificar que `/etc/supervisord.conf` termina con un bloque `[include]` similar a:

```ini
[include]
files = supervisord.d/*.ini
```

Si no fuese así, añadirlo.

### 2.4. Crear el directorio de logs

```bash
mkdir -p /var/log/supervisor/food-loss
chown root:root /var/log/supervisor/food-loss
chmod 0755 /var/log/supervisor/food-loss
```

---

## 3. Usuario de ejecución y permisos (Plesk)

En servidores Plesk, cada dominio se ejecuta bajo un usuario del sistema (típicamente el *system user* del subscription). **Hay que ejecutar los workers con ese mismo usuario** para evitar problemas de permisos sobre `var/cache`, `var/log`, sesiones, etc.

Para localizar el usuario y la ruta del proyecto:

```bash
# Reemplazar <dominio> por el dominio del proyecto en Plesk
plesk bin subscription --info <dominio> | grep -E "System user|Home directory|Domain"
```

A modo de ejemplo asumiremos:

| Variable | Valor de ejemplo |
|----------|-------------------|
| `PROJECT_USER` | `food-loss` |
| `PROJECT_GROUP` | `psacln` |
| `PROJECT_DIR`  | `/var/www/vhosts/example.com/food-loss` |
| `PHP_BIN`      | `/opt/plesk/php/8.2/bin/php` |

Verificar la ruta del PHP CLI de Plesk:

```bash
ls /opt/plesk/php/
# Elegir la versión que coincide con la del proyecto (ver composer.json -> php 8.2)
/opt/plesk/php/8.2/bin/php -v
```

> **Importante:** no usar `/usr/bin/php` si Plesk gestiona el dominio con otra versión, ya que las extensiones podrían no coincidir.

---

## 4. Fichero de configuración de Supervisor (versionado en el repo)

El fichero de configuración vive en el propio repositorio en:

```
infra/supervisor/food-loss-workers.ini
```

De este modo cualquier cambio queda revisado en PR y viaja con el deploy. Antes del primer despliegue **ajustar `user`, `directory` y la ruta del binario PHP** del `command` con los valores reales del servidor (ver sección 3).

Contenido resumido del fichero (la versión completa está en el repo):

- `[program:food-loss-workers]` ejecutando `bin/console messenger:consume async --time-limit=3600 --memory-limit=128M --limit=200 -vv --env=prod --no-interaction`.
- `numprocs=2` consumidores en paralelo.
- `stopwaitsecs=3600` + `stopsignal=TERM` → el worker termina el mensaje actual antes de morir (sin pérdida ni duplicado).
- Logs en `/var/log/supervisor/food-loss/messenger.log`.
- `environment=APP_ENV="prod",APP_DEBUG="0"`.

Notas sobre las opciones clave:

- `--time-limit=3600`: el worker termina solo cada hora y Supervisor lo reinicia (libera memoria, recarga código tras deploys).
- `--memory-limit=128M`: corta antes si crece demasiado.
- `--limit=200`: máximo de mensajes procesados antes de salir (defensa adicional).
- `numprocs=2`: dos consumidores en paralelo. Subir si la carga lo requiere.

---

## 5. Enlazar el fichero del repo en Supervisor (symlink)

En lugar de copiar el fichero a `/etc/supervisord.d/`, se crea un **symlink** que apunta a la versión del repositorio. Así los cambios se aplican con un simple `git pull` + `supervisorctl update`.

```bash
# Ruta real del proyecto en el servidor
PROJECT_DIR=/var/www/vhosts/example.com/food-loss

sudo ln -s "$PROJECT_DIR/infra/supervisor/food-loss-workers.ini" \
           /etc/supervisord.d/food-loss-workers.ini

# Verificar
ls -l /etc/supervisord.d/food-loss-workers.ini
```

Supervisor corre como `root` y necesita poder atravesar el path hasta el fichero. Comprobar que cada directorio de la ruta tiene permiso de ejecución (`x`) para `root`; en un deploy estándar de Plesk esto se cumple por defecto. Si SELinux está activo y bloquea la lectura, ajustar el contexto:

```bash
sudo chcon -t etc_t "$PROJECT_DIR/infra/supervisor/food-loss-workers.ini"
```

Tras crear el symlink:

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

Salida esperada (aprox.):

```
food-loss:food-loss-workers_00   RUNNING   pid 12345, uptime 0:00:05
food-loss:food-loss-workers_01   RUNNING   pid 12346, uptime 0:00:05
```

> Tras un `git pull` que modifique `infra/supervisor/food-loss-workers.ini`, ejecutar de nuevo `supervisorctl reread && supervisorctl update` (o `restart food-loss:*` si sólo cambia el `command`/`environment`).

---

## 6. Verificación

1. Logs en vivo:

   ```bash
   tail -f /var/log/supervisor/food-loss/messenger.log
   ```

2. Encolar un mensaje de prueba desde la aplicación y verificar que el worker lo procesa.

3. Comprobar la tabla de mensajes:

   ```bash
   sudo -u food-loss /opt/plesk/php/8.2/bin/php /var/www/vhosts/example.com/food-loss/bin/console messenger:stats --env=prod
   ```

4. Comprobar el reinicio automático: matar manualmente uno de los procesos (`kill -TERM <pid>`) y verificar que Supervisor lo levanta de nuevo.

---

## 7. Integración con el proceso de despliegue

Tras cada deploy de la aplicación, **es obligatorio** reiniciar los workers para que carguen el nuevo código:

```bash
sudo supervisorctl restart food-loss:*
```

Opciones para automatizarlo:

- Añadir un *post-deploy hook* (script de deploy / Plesk Git / CI/CD) que ejecute el `supervisorctl restart`.
- Configurar `sudoers` para permitir al usuario de deploy ejecutar **únicamente** ese comando sin contraseña:

  ```
  # /etc/sudoers.d/food-loss-supervisor
  food-loss ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart food-loss\:*
  food-loss ALL=(root) NOPASSWD: /usr/bin/supervisorctl status food-loss\:*
  ```

---

## 8. Failure transport (opcional, recomendado)

Actualmente `config/packages/messenger.yaml` no define `failure_transport`. Para producción se recomienda:

1. Descomentar / añadir en `messenger.yaml`:

   ```yaml
   framework:
       messenger:
           failure_transport: failed
           transports:
               sync: 'sync://'
               async: '%env(MESSENGER_TRANSPORT_DSN)%'
               failed: 'doctrine://default?queue_name=failed'
   ```

2. Lanzar la migración necesaria (la tabla `messenger_messages` ya cubre ambos transportes, sólo cambia `queue_name`).

3. Inspección manual de fallidos:

   ```bash
   bin/console messenger:failed:show --env=prod
   bin/console messenger:failed:retry --env=prod
   ```

No es estrictamente necesario un proceso Supervisor adicional para `failed` (se gestiona a demanda).

---

## 9. Seguridad y hardening (Plesk)

- Restringir el acceso al socket de Supervisor a `root` (por defecto en `/var/run/supervisor/supervisor.sock`, permisos `0700`).
- **No** exponer la interfaz HTTP de Supervisor (`[inet_http_server]`); mantenerla deshabilitada en `/etc/supervisord.conf`.
- Asegurar que el fichero `.env.local` con secretos sólo es legible por `PROJECT_USER`.
- Verificar que `selinux` (si está activo) permite la ejecución; en caso de bloqueo:

  ```bash
  getenforce
  ausearch -m avc -ts recent | grep supervisord
  ```

  Y aplicar la política correspondiente (`audit2allow`) o ajustar contextos con `chcon`/`semanage`.

---

## 10. Checklist final

- [ ] `supervisor` instalado y `supervisord` habilitado en `systemctl`.
- [ ] `infra/supervisor/food-loss-workers.ini` ajustado con `user`, `directory` y ruta de PHP reales.
- [ ] Symlink `/etc/supervisord.d/food-loss-workers.ini` → `<PROJECT_DIR>/infra/supervisor/food-loss-workers.ini` creado.
- [ ] Directorio `/var/log/supervisor/food-loss/` creado.
- [ ] `supervisorctl status` muestra los 2 procesos en `RUNNING`.
- [ ] Procesamiento de un mensaje de prueba verificado.
- [ ] Hook de post-deploy ejecuta `supervisorctl restart food-loss:*`.
- [ ] (Opcional) `failure_transport` activado y documentado.
