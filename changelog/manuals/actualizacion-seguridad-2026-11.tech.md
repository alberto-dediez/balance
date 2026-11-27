# Actualización de seguridad 2026-11 (dependencias PHP, JS y entorno) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Revisión:** `review.md` · **Implementado:** 2026-11-26, correcciones de la review el 2026-11-27 · **Bounded Contexts:** ninguno (`src/`, `templates/` y `translations/` sin cambios)
- **Base de comparación:** commit `cd84b19e1`

## Qué hace y por qué

Cierra los avisos de seguridad acumulados a 2026-11-26: 57 en 21 paquetes PHP (`composer audit`) y 6 en 2 paquetes JS (`importmap:audit`). El detalle por paquete, con su relevancia en el proyecto, está en `plan.md`, *Hechos verificados del repo*.

La solución es una actualización dentro de las restricciones de `composer.json`: Symfony sigue en la rama 7.4 LTS y ninguna dependencia directa cambia de mayor. Se optó por un `composer update` completo, no dirigido a los paquetes con aviso, para no divergir de las plataformas derivadas (tabla de decisiones de `plan.md`). Además se sube el mínimo de PHP a 8.4, la imagen base de FrankenPHP a 1.14 y se adapta la configuración del hub Mercure que esa imagen exige.

No cambia ningún comportamiento propio de la aplicación: no hay agregados, rutas, permisos, traducciones ni migraciones nuevas.

## Mapa de piezas

| Fichero | Qué cambia | Estado |
|---|---|---|
| `composer.json` | `require.php` pasa a `>=8.4` (línea 7); se elimina el bloque `config.policy.advisories` | modificado |
| `composer.lock` | 133 actualizaciones, 3 altas y 1 baja | modificado |
| `importmap.php` | 18 versiones de entradas JS y CSS | modificado |
| `Dockerfile` | Base `dunglas/frankenphp:1.14-php8.4` (línea 4); se retira `ENV MERCURE_TRANSPORT_URL` | modificado |
| `frankenphp/Caddyfile` | Bloque `mercure`: `transport bolt` (líneas 28-30) y `protocol_version_compatibility 8` (línea 31) | modificado |
| `compose.override.yaml` | Se retira `MERCURE_EXTRA_DIRECTIVES: demo` | modificado |
| `config/reference.php` | Referencia de configuración regenerada por Symfony a partir de los paquetes instalados | modificado |
| `tests/Acceptance/Security/SecurityContext.php` | Paso del código de 2FA por email adaptado a PHPStan 2.3 (líneas 281-283) | modificado |
| `tests/Unit/Shared/Infrastructure/Storage/GoogleDrive/GoogleDriveAccessTokenProviderTest.php` | `testTokenIsCached` guarda cada llamada a `token()` en su variable (líneas 76-80) | modificado |

`symfony.lock`, `bin/deploy`, `config/packages/` y `.github/workflows/ci.yml` no cambian. `assets/vendor/` está ignorado por git (`.gitignore:30`) y se regenera con `make asset-install`.

## Dependencias PHP

### Paquetes con aviso

| Paquete | Antes | Ahora |
|---|---|---|
| `symfony/http-foundation`, `framework-bundle`, `routing`, `security-http`, `security-core`, `http-kernel`, `http-client` | 7.4.13 | 7.4.24 |
| `symfony/serializer`, `validator`, `config` | 7.4.10 | 7.4.24 |
| `symfony/mailer` | 7.4.12 | 7.4.24 |
| `symfony/mime`, `yaml` | 7.4.13 | 7.4.24 |
| `symfony/filesystem` | 7.4.11 | 7.4.24 |
| `symfony/doctrine-bridge` | 7.4.9 | 7.4.24 |
| `symfony/ux-live-component`, `ux-icons` | 2.36.0 | 2.36.3 |
| `twig/twig` | 3.27.1 | 3.31.1 |
| `twig/html-extra` | 3.29.0 | 3.31.1 |
| `league/commonmark` | 2.10.1 | 2.10.10 |
| `spomky-labs/otphp` | 11.5.0 | 11.5.1 |

No todos los componentes `symfony/*` quedan en 7.4.24: cada uno está en el parche que instaló el `update` (por ejemplo `symfony/uid` 7.4.21 o `symfony/sendgrid-mailer` 7.4.19). El kernel informa `Symfony v7.4.24`.

### Saltos de mayor (todos en transitivas o herramientas)

| Paquete | Antes → ahora | Superficie en este repo |
|---|---|---|
| `guzzlehttp/guzzle` | 7.15.5 → 8.6.0 | `src/` y `tests/` no lo usan directamente |
| `guzzlehttp/psr7` | 2.13.1 → 3.6.0 | ídem |
| `guzzlehttp/promises` | 2.5.3 → 3.2.0 | ídem |
| `doctrine/collections` | 2.6.0 → 3.1.1 | 6 ficheros de `src/` |
| `zircote/swagger-php` | 5.8.3 → 7.11.0 | 15 ficheros de `src/` con atributos `OpenApi\` |
| `ergebnis/agent-detector` | 1.2.0 → 2.2.0 | herramienta de desarrollo |

Altas: `radebatz/type-info-extras`, `symfony/polyfill-php86`, `cucumber/tag-expressions`. Baja: `ralouphie/getallheaders`.

Otras subidas con efecto en el día a día: `doctrine/orm` 3.8.0, `doctrine/doctrine-bundle` 2.19.4, `nelmio/api-doc-bundle` 5.14.0, `phpstan/phpstan` 2.3.7, `rector/rector` 2.9.2, `behat/behat` 3.35.1, `phpunit/phpunit` 9.6.39, `friendsofphp/php-cs-fixer` 3.95.32, `twbs/bootstrap` 5.3.9.

### Excepciones de auditoría

El bloque `config.policy.advisories` (`ignore: symfony/twilio-notifier` e `ignore-id: PKSA-fgw6-3k5j-cfkn`) se eliminó entero: sin él no aparece ningún aviso.

`setasign/fpdi-tcpdf` sigue marcado como abandonado, sin sustituto. Por eso la auditoría se lanza con `--abandoned=report`; sin esa opción el comando termina con código distinto de 0 (código 2 según `review.md`, *Notas sobre el plan*).

### `config/reference.php`

No es una receta de Flex: se reescribe en cada calentamiento de caché, así que descartar su diff no es estable. Entre lo que refleja está el nuevo valor por defecto basado en `%kernel.share_dir%` (`config/reference.php:257` para `session.save_path`, `:391` para el directorio de pools de caché). No tiene efecto aquí: `Kernel::getShareDir()` devuelve el directorio de caché mientras no se defina `APP_SHARE_DIR` (`vendor/symfony/framework-bundle/Kernel/MicroKernelTrait.php:127-139`, `vendor/symfony/http-kernel/Kernel.php:329-333`), y el proyecto no la define.

## Paquetes JS

| Entrada de `importmap.php` | Antes → ahora |
|---|---|
| `@toast-ui/editor` y su CSS | 3.2.2 → 3.2.3 |
| `prosemirror-view` y su CSS | 1.30.1 → 1.44.0 |
| `prosemirror-model` | 1.19.0 → 1.25.13 |
| `prosemirror-transform` | 1.12.0 → 1.12.7 |
| `prosemirror-state` | 1.4.2 → 1.4.4 |
| `prosemirror-keymap` | 1.2.1 → 1.2.3 |
| `prosemirror-commands` | 1.5.0 → 1.7.2 |
| `prosemirror-inputrules` | 1.2.0 → 1.7.0 |
| `prosemirror-history` | 1.3.0 → 1.5.2 |
| `orderedmap` / `w3c-keyname` / `rope-sequence` | 2.1.0 → 2.1.1 / 2.2.6 → 2.2.8 / 1.3.3 → 1.3.4 |
| `lodash-es/debounce.js` y `throttle.js` | 4.17.21 → 4.18.1 |
| `bootstrap` y su CSS | 5.3.7 → 5.3.9 (igualado al paquete Composer `twbs/bootstrap`) |

`apexcharts` y `tom-select` no se tocan.

El punto sensible es `prosemirror-view` 1.44 bajo `@toast-ui/editor`, que consume `assets/controllers/markdown_editor_controller.js`. Además de crear el editor, ese controlador registra un plugin de ProseMirror a través de `context.pmState.Plugin` (líneas 40-68) para que `Ctrl+Enter` / `Cmd+Enter` no inserte contenido cuando el formulario tiene botón de atajo. El editor se usa en el campo `body` de `RegisterAnnouncementType` y `UpdateAnnouncementType` (`src/Changelog/Infrastructure/Symfony/Form/`) mediante `MarkdownEditorType`. Ningún test automático ejecuta este JavaScript.

## Entorno Docker

Versiones comprobadas en el contenedor `php` (2026-11-27): FrankenPHP 1.14.0, PHP 8.4.28, Caddy 2.11.9, Composer 2.11.3.

El módulo Mercure incluido en FrankenPHP 1.14 rechaza la configuración que valía para 1.9 (`review.md`, M1):

| Configuración antigua | Síntoma con 1.14 | Estado actual |
|---|---|---|
| `MERCURE_EXTRA_DIRECTIVES: demo` en `compose.override.yaml` | `unknown mercure directive "demo"`, contenedor en bucle de reinicio | retirada |
| `publisher_jwt` / `subscriber_jwt` sin más | fallo de aprovisionamiento: esas directivas solo valen en modo de compatibilidad | `protocol_version_compatibility 8` |
| `ENV MERCURE_TRANSPORT_URL` en `Dockerfile` | `environment variable is not supported anymore` | sustituida por `transport bolt { path /data/mercure.db }` |

```caddyfile
mercure {
    transport bolt {
        path /data/mercure.db
    }
    protocol_version_compatibility 8
    ...
}
```

Consideraciones:

- La ruta Bolt es la misma que antes (`/data/mercure.db`, en el volumen `caddy_data`).
- `protocol_version_compatibility 8` mantiene las claves HS256 de `compose.yaml` y relaja la validación de tokens respecto al modo moderno. Pasar al bloque `issuer` queda para un plan aparte (`review.md`, M1, opciones de la revisión 3).
- `{$MERCURE_EXTRA_DIRECTIVES}` sigue en `frankenphp/Caddyfile:41`: cualquier valor que se inyecte ahí tiene que ser válido para esta versión.
- `Dockerfile` y `Caddyfile` son comunes a los targets `frankenphp_dev` y `frankenphp_prod` (`compose.prod.yaml`). La producción descrita en `bin/deploy` es Plesk y no usa estos ficheros.
- Hace falta reconstruir la imagen (`make start`) para que el cambio del `Dockerfile` tenga efecto; el `Caddyfile` además va montado en desarrollo (`compose.override.yaml`).

## Persistencia y migración

Ninguna migración. No cambian mappings ni esquema.

## Configuración y operación

### En local, tras recibir el cambio

```bash
make down
make start
make composer c="install"
make asset-install
make cc
make reset-test-cache
```

Al ejecutar Composer dentro del contenedor se ha observado el aviso `fatal: detected dubious ownership in repository at '/app'` (git). `audit` y `validate` completan igualmente.

### Producción (Plesk, tareas manuales fuera del repo)

- `/opt/plesk/php/8.4/bin/php -v` debe dar 8.4.28 o posterior.
- `/opt/plesk/php/8.4/bin/php /usr/lib64/plesk-9.0/composer.phar --version` debe dar 2.11.3 o posterior.
- `bin/deploy` no cambia: instala desde `composer.lock`, limpia y calienta la caché y ejecuta `importmap:install`, `sass:build` y `asset-map:compile`.
- Tras desplegar, revisar `TRUSTED_PROXIES` y `BASE_URL` (`config/packages/framework.yaml:4,18`) y comprobar el inicio de sesión y los enlaces de los emails: las versiones nuevas endurecen el tratamiento de hosts, `//` y cabeceras *forwarded* (`plan.md`, *Riesgos*).

### Plataformas derivadas

Reciben el cambio por merge. Resumen de `plan.md`, *Plataformas derivadas*:

- `composer.lock` dará conflicto: conservar el de la derivada y lanzar en ella `make composer c="update --with-all-dependencies"`.
- El bloque `config.policy.advisories` puede dar conflicto donde hay identificadores propios: retirar, auditar y restaurar solo lo imprescindible.
- Quien suba la imagen a 1.14 necesita también los cambios de `Caddyfile`, `Dockerfile` y `compose.override.yaml` descritos arriba.
- Validar en cada una: auditorías, `make phpstan`, `make test-parallel`, editor Markdown y `/openapi/documentation`.

## Cómo extenderlo: la próxima actualización de seguridad

1. Línea base: `make composer c="audit --abandoned=report"`, `make sf c="importmap:audit"` y `make test-parallel` en verde.
2. Simular: `make composer c="update --with-all-dependencies --dry-run"`. Revisar los saltos de mayor y fijar con `--with "vendor/paquete:^N"` los que no se quieran.
3. Aplicar sin `--dry-run`; después `make cc` y `make reset-test-cache`.
4. Recetas de Flex: solo listarlas (`make composer c="recipes"`). Conservar `config/reference.php` regenerado.
5. JS: `make sf c="importmap:update <entradas>"`, `make asset-install`, `make sf c="importmap:audit"`.
6. Validar: `make cs-fix`, `make phpstan`, `make test-parallel` y `make test-acceptance-parallel admin_prefix=/management`.
7. Probar a mano el editor Markdown en una pantalla real.

Trampas conocidas:

- `importmap:update` reescribe `importmap.php`: quita `declare(strict_types=1);` y reordena entradas. En este plan se rehízo sobre el original cambiando solo las versiones.
- El CSS de un paquete puede no subir con su JS (pasó con `@toast-ui/editor`): comprobar que coinciden.
- Fijar siempre el sufijo `-php8.4` en la imagen: sin él, FrankenPHP 1.14 usa PHP 8.6.
- Antes de subir FrankenPHP, validar el `Caddyfile` con la imagen nueva (`frankenphp validate --adapter caddyfile`).

## Tests

No se añaden tests: la validación es la batería existente.

```bash
make test-parallel
make test-acceptance-parallel admin_prefix=/management
make test-acceptance options="tests/Acceptance/Shared/api-doc.feature"
make test-acceptance options="tests/Acceptance/Security/login-two-factor.feature"
make test-unit options="--filter GoogleDriveAccessTokenProviderTest"
make sf c="nelmio:apidoc:dump --area=admin"
```

| Comprobación | Resultado | Fuente |
|---|---|---|
| `make phpstan`, `make cs`, `make test-parallel` (2347 unitarios, 615 escenarios) sobre FrankenPHP 1.14.0 / PHP 8.4.28 | OK | `review.md`, revisión 4 |
| `make test-acceptance-parallel admin_prefix=/management` (615 escenarios), con la imagen anterior | OK | `plan.md`, *Implementación* |
| `make composer c="audit --abandoned=report"` | sin avisos; solo el paquete abandonado | repetido al documentar (2026-11-27) |
| `make sf c="importmap:audit"` | sin vulnerabilidades | repetido al documentar |
| `make sf c="--version"` | Symfony 7.4.24 | repetido al documentar |
| `make composer c="validate --no-check-publish"` | válido; avisa de las restricciones `*` | repetido al documentar |
| Versiones del contenedor y ausencia de `MERCURE_TRANSPORT_URL` en su entorno | FrankenPHP 1.14.0, PHP 8.4.28; variable ausente | repetido al documentar |

Huecos de cobertura:

- El editor Markdown solo se ha probado en Chrome headless con una página aislada sobre `assets/vendor` (`plan.md`, *Implementación*).
- Los escenarios de `api-doc.feature` cubren `/openapi/documentation`; el área `admin` se ha verificado generando la especificación, no la página.
- No consta una prueba de extremo a extremo de publicación y suscripción contra el hub Mercure 1.14. `LIVE_UPDATES_ENABLED` está a `false` en `.env` y los unitarios usan `MockHub`.

## Desviaciones y deuda

Respecto al plan (`plan.md`, *Implementación*, y `review.md`):

- La imagen Docker quedó sin actualizar en la implementación; se resolvió después con la corrección M1. La nota de `## Implementación` que la da por pendiente ya no describe el estado actual.
- M1 toca `compose.override.yaml` y `frankenphp/Caddyfile`, que no figuraban en el resumen de ficheros del plan. `Dockerfile` lleva además un cambio incidental (espacio final en `sync;`).
- `config/reference.php` cambia aunque el plan no preveía cambios en `config/`.
- El aviso residual de `MERCURE_TRANSPORT_URL` citado en la revisión 4 requería reconstruir la imagen; el contenedor actual ya no define la variable.
- La deuda de la aserción duplicada en `SecurityContext.php` quedó corregida (B1).

Abierto:

- `review.md`, **Q1** (a confirmar): probar el editor Markdown en una pantalla real con sesión, pegando texto con formato y guardando.
- Producción: actualizar o confirmar PHP y Composer en Plesk.
- Modo moderno de Mercure (bloque `issuer`).
- `setasign/fpdi-tcpdf` abandonado y los mayores sin aviso listados en `plan.md`, *Fuera de alcance*.
