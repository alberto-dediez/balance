# Supervisor — Workers de Symfony Messenger y despliegue (producción)

Configuración de **Supervisor** para los workers de Messenger en el servidor de producción (CentOS + Plesk, sin Docker) y despliegue con Plesk Git + [`bin/deploy`](../../bin/deploy).

Este README y `bin/deploy` son comunes a todas las plataformas basadas en Hydra. Los valores propios de cada servidor se indican con marcadores:

| Marcador | Significado | Ejemplo |
|----------|-------------|---------|
| `<GRUPO>` | Nombre del `[group:...]` de Supervisor de la plataforma | `hydra` |
| `<PROJECT_DIR>` | Ruta de despliegue (raíz del proyecto, la que contiene `bin/`, `public/` y `src/`) | `/var/www/vhosts/example.com/app.example.com` |
| `<PROJECT_USER>` | System user de la suscripción de Plesk | `example.com_ab12cd34` |
| `<DOMINIO>` | Dominio del sitio en Plesk | `app.example.com` |

Rutas por defecto de `bin/deploy` (se pueden sobrescribir con variables de entorno):

| Dato | Valor | Variable |
|------|-------|----------|
| PHP CLI | `/opt/plesk/php/8.4/bin/php` | `PHP` |
| Composer | `/usr/lib64/plesk-9.0/composer.phar` | `COMPOSER_BIN` |
| `supervisorctl` | `/usr/bin/supervisorctl` | `SUPERVISORCTL` |
| Grupo de Supervisor | `hydra` | `SUPERVISOR_GROUP` |

## Adaptar a una plataforma

[`hydra-workers.ini`](hydra-workers.ini) es la plantilla; cada plataforma basada en Hydra tiene su propio fichero de Supervisor.

1. Copiar `hydra-workers.ini` a `infra/supervisor/<GRUPO>-workers.ini` y sustituir:
   - `hydra-worker-*` → `<GRUPO>-worker-*` en los `[program:...]` y en `programs=` del `[group:...]`.
   - `[group:hydra]` → `[group:<GRUPO>]`.
   - `user=hydra` → `user=<PROJECT_USER>` (`plesk bin subscription --info <suscripción> | grep -E "System user|Home directory"`).
   - `directory=` y la ruta del proyecto en cada `command=` → `<PROJECT_DIR>`.
   - `/opt/plesk/php/8.4/bin/php` → el PHP CLI de Plesk del dominio (`/opt/plesk/php/8.4/bin/php -v`; **no** usar `/usr/bin/php`).
   - `/var/log/supervisor/hydra/` → `/var/log/supervisor/<GRUPO>/`.
2. En `bin/deploy`, cambiar el valor por defecto de `SUPERVISOR_GROUP` (`hydra`) por `<GRUPO>`. Es la única línea propia de la plataforma: el resto del script se mantiene idéntico al de Hydra para que las fusiones desde `master` no generen conflictos.
3. Opcionalmente, sustituir en este README los marcadores por los valores reales del servidor.

## Colas y workers

El transporte es `doctrine://default` (tabla `messenger_messages`), con tres colas por prioridad definidas en [`config/packages/messenger.yaml`](../../config/packages/messenger.yaml):

| Transporte | `queue_name` | Reintentos | Worker dedicado |
|------------|--------------|------------|-----------------|
| `async_high` | `high` | 5 | `<GRUPO>-worker-high` (×2) |
| `async` | `medium` | 3 | `<GRUPO>-worker-medium` (×1) |
| `async_low` | `low` | 2 | `<GRUPO>-worker-low` (×1) |
| `failed` | `failed` | — | No (gestión manual, ver abajo) |

Cada worker consume su cola en primer lugar y, si está vacía, ayuda con las de menor prioridad (`messenger:consume` procesa los transportes en el orden en que se pasan):

- `<GRUPO>-worker-high` (2 procesos): `async_high async async_low`
- `<GRUPO>-worker-medium` (1 proceso): `async async_low`
- `<GRUPO>-worker-low` (1 proceso): `async_low`

Así los mensajes `high` siempre tienen capacidad dedicada y ninguna cola queda sin consumidor (la cola `low` tiene su propio worker, por lo que no sufre inanición bajo carga sostenida de las colas superiores).

Opciones clave de cada `command`:

- `--time-limit=3600`: el worker termina solo cada hora y Supervisor lo reinicia (libera memoria).
- `--memory-limit=128M`: corta antes si la memoria crece demasiado.
- `--limit=200`: máximo de mensajes procesados antes de salir (defensa adicional).
- `stopwaitsecs=3600` + `stopsignal=TERM`: al parar, el worker termina el mensaje en curso antes de morir (sin pérdida ni duplicado).

Las tareas programadas no dependen de Supervisor: `app:schedule:run` se lanza desde el cron con un usuario administrador y encola sus comandos en `async_low`.

## Instalación en el servidor (una sola vez, como `root`)

Antes de empezar, `<PROJECT_DIR>` debe tener ya el código con `infra/supervisor/<GRUPO>-workers.ini` y `bin/deploy` (un despliegue de Plesk Git **sin** acciones adicionales activadas).

No ejecutar como `root` comandos de `bin/console`, Composer ni `bin/deploy` dentro del proyecto: dejan en `var/` y `public/` ficheros de root que el usuario de Plesk no puede sobrescribir. Para actuar como el usuario: `sudo -u <PROJECT_USER> -H bash -c 'cd <PROJECT_DIR> && ...'`.

### 1. Instalar y habilitar Supervisor

```bash
dnf install -y epel-release supervisor      # yum en CentOS 7
systemctl enable --now supervisord
grep -A1 '^\[include\]' /etc/supervisord.conf   # debe incluir supervisord.d/*.ini
command -v supervisorctl                     # /usr/bin/supervisorctl
```

Si `supervisorctl` está en otra ruta, ajustarla en el sudoers del paso 5 y en la variable `SUPERVISORCTL` del despliegue.

### 2. Shell del usuario de Plesk

```bash
getent passwd <PROJECT_USER>
```

El último campo debe ser `/bin/bash`. Con `/bin/false` o `/usr/local/psa/bin/chrootsh` (enjaulado), `sudo` no funciona y Plesk ejecuta las acciones de despliegue dentro de una jaula chroot: cambiarlo en Plesk (*Sitios web y dominios → `<DOMINIO>` → Configuración de hosting → Acceso SSH → `/bin/bash`*, no la opción *chrooted*).

### 3. Directorio de logs

```bash
mkdir -p /var/log/supervisor/<GRUPO>
chmod 0755 /var/log/supervisor/<GRUPO>
```

### 4. Enlazar el fichero del repo y cargarlo

```bash
ln -s <PROJECT_DIR>/infra/supervisor/<GRUPO>-workers.ini \
      /etc/supervisord.d/<GRUPO>-workers.ini

supervisorctl reread
supervisorctl update <GRUPO>
supervisorctl status '<GRUPO>:*'
```

Salida esperada:

```
<GRUPO>:<GRUPO>-worker-high_00     RUNNING   pid 12345, uptime 0:00:05
<GRUPO>:<GRUPO>-worker-high_01     RUNNING   pid 12346, uptime 0:00:05
<GRUPO>:<GRUPO>-worker-medium_00   RUNNING   pid 12347, uptime 0:00:05
<GRUPO>:<GRUPO>-worker-low_00      RUNNING   pid 12348, uptime 0:00:05
```

A partir de aquí no se lanzan workers a mano.

### 5. Permitir al usuario de Plesk gestionar sus workers (sudoers)

`supervisorctl` habla con `supervisord` por un socket Unix que solo puede usar `root`. Supervisor no tiene permisos por programa, así que en lugar de abrir el socket (daría control sobre **todos** los programas del servidor, también los de otras plataformas) se autorizan con sudoers únicamente los comandos que necesita `bin/deploy` sobre el grupo `<GRUPO>`.

```bash
visudo -f /etc/sudoers.d/<GRUPO>-supervisor
```

Ejemplo con `<GRUPO>` = `hydra` (sustituir el grupo, el usuario y el nombre del alias):

```
# Workers de Messenger de hydra (infra/supervisor/hydra-workers.ini y bin/deploy)
Defaults:<PROJECT_USER> !requiretty

Cmnd_Alias HYDRA_WORKERS = /usr/bin/supervisorctl stop hydra\:\*, \
                           /usr/bin/supervisorctl start hydra\:\*, \
                           /usr/bin/supervisorctl restart hydra\:\*, \
                           /usr/bin/supervisorctl status hydra\:\*, \
                           /usr/bin/supervisorctl reread, \
                           /usr/bin/supervisorctl update hydra

<PROJECT_USER> ALL=(root) NOPASSWD: HYDRA_WORKERS
```

- El nombre del `Cmnd_Alias` solo admite mayúsculas, dígitos y `_` (`mi-app` → `MI_APP_WORKERS`) y debe ser único en el servidor.
- `\:` escapa los dos puntos (obligatorio en sudoers) y `\*` hace el asterisco **literal**: solo se permite exactamente `<GRUPO>:*`. Con un comodín (`hydra\:*`) también se aceptaría `restart hydra:x otro:*`, porque el `*` de sudoers incluye espacios.
- No usar comillas en las reglas: la shell las quita antes de que `sudo` vea el comando (`supervisorctl stop 'hydra:*'` llega como `hydra:*`).
- `reread` es global pero inofensivo: solo relee la configuración y no actúa sobre ningún programa. No restringirlo con `""` (solo sin argumentos): en la práctica `sudo` lo rechaza y pide contraseña.
- `update <GRUPO>` aplica cambios únicamente a este grupo, nunca un `update` global.
- `!requiretty` permite usar `sudo` sin terminal (CentOS 7 lo exige por defecto).
- Ninguna línea del `Cmnd_Alias` puede tener espacios tras la `\` final (`grep -n '\\ \+$' /etc/sudoers.d/<GRUPO>-supervisor` no debe devolver nada).

Siempre con `visudo`: un sudoers con errores de sintaxis puede dejar el servidor sin `sudo`.

### 6. Permisos y validación

```bash
chown root:root /etc/sudoers.d/<GRUPO>-supervisor
chmod 0440 /etc/sudoers.d/<GRUPO>-supervisor
visudo -c                                    # todo "parsed OK"
grep includedir /etc/sudoers                 # debe existir "#includedir /etc/sudoers.d"
```

### 7. Probar como el usuario de Plesk

Desde `root` (`su -` no sirve si el usuario no tiene contraseña o shell):

```bash
U=<PROJECT_USER>
sudo -u $U sudo -n -l                                                   # lista las 6 reglas
sudo -u $U sudo -n /usr/bin/supervisorctl status '<GRUPO>:*'            # 4 procesos
sudo -u $U sudo -n /usr/bin/supervisorctl reread                        # funciona
sudo -u $U sudo -n /usr/bin/supervisorctl update <GRUPO>                # funciona
sudo -u $U sudo -n /usr/bin/supervisorctl status 'otro:*'               # debe FALLAR
sudo -u $U sudo -n /usr/bin/supervisorctl update                        # debe FALLAR
```

Entrecomillar siempre `'<GRUPO>:*'` para que bash no expanda el `*`. `sudo -n` falla al instante si falta un permiso, en lugar de quedarse esperando contraseña.

## Despliegue (Plesk Git)

El código lo actualiza **Plesk Git**; [`bin/deploy`](../../bin/deploy) no hace nada con git y se lanza como acción de despliegue adicional, con el system user de la suscripción.

### Configuración del repositorio en Plesk

En *Sitios web y dominios → `<DOMINIO>` → Git → Configuración del repositorio*:

- **Ruta de despliegue**: `<PROJECT_DIR>` (la raíz del proyecto), no `public/`.
- **Modo de despliegue**: automático (en cada push) o manual (botón *Desplegar*).
- **Habilitar acciones de despliegue adicionales**: activado, con:

  ```bash
  bash bin/deploy
  ```

  `bash` explícito por si el despliegue no conserva el permiso de ejecución del fichero. Las acciones se ejecutan desde la ruta de despliegue.

La salida del script (y el error, si falla) aparece en el registro del despliegue de Plesk Git.

Para relanzar solo los pasos posteriores al código (por ejemplo, tras corregir un fallo) sin volver a desplegar, desde `root`:

```bash
sudo -u <PROJECT_USER> -H bash -c 'cd <PROJECT_DIR> && bash bin/deploy; echo "EXIT=$?"'
```

### Qué hace `bin/deploy`

En este orden:

1. **Para los workers** (`supervisorctl stop '<GRUPO>:*'`). Cada worker termina el mensaje en curso (hasta `stopwaitsecs=3600`, durante los que el despliegue de Plesk queda esperando) y Supervisor los deja parados hasta el `start`; los mensajes nuevos se acumulan en `messenger_messages`.
2. **Instala dependencias** (`composer install --optimize-autoloader --no-scripts`, con las dependencias de desarrollo) con el Composer y el PHP CLI de Plesk. Sin scripts para que Composer no limpie la caché por su cuenta; sus `auto-scripts` se ejecutan en los pasos siguientes.
3. **Limpia la caché** (`cache:clear --no-warmup`) y la **calienta** (`cache:warmup`).
4. **Ejecuta las migraciones**, después de la caché para que no fallen con un contenedor desactualizado.
5. **Compila los assets** (`assets:install`, `importmap:install`, `sass:build`, `asset-map:compile`); `public/assets/` no se versiona.
6. **Aplica la configuración de Supervisor** (`reread` + `update <GRUPO>`) en todos los despliegues: si `<GRUPO>-workers.ini` no ha cambiado no hace nada; si ha cambiado, aplica el cambio sea cual sea.
7. **Lanza los workers** (`start '<GRUPO>:*'`) y muestra su estado.

Los comandos de `bin/console` se ejecutan con `memory_limit=512M`: el PHP CLI de Plesk trae 128M y calentar la caché de Twig los supera.

Plesk Git actualiza los ficheros **antes** de lanzar la acción, así que los workers conviven unos segundos con el código nuevo hasta que el paso 1 los para; Plesk no ofrece una acción previa al despliegue.

No usar `restart` ni `messenger:stop-workers` en el despliegue: ambos hacen que Supervisor relance los workers al instante, en mitad del borrado de la caché.

Si un paso falla, el script se detiene y **deja los workers parados** a propósito (con dependencias a medias o una migración fallida es más seguro no procesar mensajes). Corregir el error y relanzar `bin/deploy`, o arrancarlos a mano con `supervisorctl start '<GRUPO>:*'`.

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---------|-------|----------|
| `/usr/bin/env: bad interpreter: No such file or directory` al desplegar | El usuario tiene shell `/bin/false` o `chrootsh` y Plesk ejecuta la acción en una jaula chroot | Shell `/bin/bash` (paso 2) |
| `sudo: a password is required` | Falta alguna regla de sudoers o está mal escrita (comillas, `reread ""`, espacios tras `\`) | Revisar el paso 5 y probar cada comando con el paso 7 |
| `Allowed memory size of 134217728 bytes exhausted` | `memory_limit` de 128M del PHP CLI de Plesk | Ya resuelto en `bin/deploy` (`-d memory_limit=512M`) |
| `Permission denied` / `Cannot open file` en `var/` o `public/assets/` | Ficheros creados por `root` al ejecutar comandos a mano | `chown -R <PROJECT_USER>:psacln <PROJECT_DIR>` (comprobar el grupo con `ls -ld <PROJECT_DIR>`) y `rm -rf <PROJECT_DIR>/public/assets` |
| La web falla con funciones o clases de paquetes recién añadidos (`Unknown "xxx" function`) | Un Composer distinto del de Plesk (p. ej. `/usr/bin/composer`) no instala bien el `composer.lock` | Usar `/usr/lib64/plesk-9.0/composer.phar` (valor por defecto de `COMPOSER_BIN`) |
| `550 The from address does not match a verified Sender Identity` | `MAILER_FROM` sin definir en el servidor (se usa el de ejemplo de `.env`) o remitente no verificado en el proveedor de correo | Verificar el remitente o el dominio en el proveedor y definir `MAILER_FROM` en `.env.local` |
| Avisos de deprecación en la salida del despliegue o de los workers | Canal `deprecation` enviado a stderr | En prod se escriben en `var/log/prod.deprecations.log` (`config/packages/monolog.yaml`) |

## Verificación

```bash
# Estado de los workers
supervisorctl status '<GRUPO>:*'

# Logs en vivo (uno por worker)
tail -f /var/log/supervisor/<GRUPO>/messenger-high.log
tail -f /var/log/supervisor/<GRUPO>/messenger-medium.log
tail -f /var/log/supervisor/<GRUPO>/messenger-low.log

# Estado de las colas
sudo -u <PROJECT_USER> /opt/plesk/php/8.4/bin/php <PROJECT_DIR>/bin/console messenger:stats --env=prod
```

Comprobar el reinicio automático: `kill -TERM <pid>` de un proceso y verificar que Supervisor lo levanta de nuevo.

## Cola de fallidos (`failed`)

`failure_transport: failed` ya está configurado en `messenger.yaml`. No necesita worker en Supervisor; se gestiona a demanda:

```bash
sudo -u <PROJECT_USER> /opt/plesk/php/8.4/bin/php <PROJECT_DIR>/bin/console messenger:failed:show --env=prod
sudo -u <PROJECT_USER> /opt/plesk/php/8.4/bin/php <PROJECT_DIR>/bin/console messenger:failed:retry --env=prod
```
