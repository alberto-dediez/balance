# Modo mantenimiento automático durante el despliegue — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-12 · **Bounded Contexts:** `Shared` (infraestructura), más `bin/deploy` e `infra/supervisor`

## Qué hace y por qué

Plesk Git sobrescribe `src/` antes de lanzar `bin/deploy`. Hasta que el script regenera la caché, PHP ejecuta código nuevo con el contenedor compilado de la versión anterior, y cualquier constructor cambiado rompe todas las rutas con un 500 (el kernel instancia handlers y suscriptores en cada petición). La ventana es larga porque antes de `cache:clear` el script espera a los workers y ejecuta `composer install`.

La solución es un modo mantenimiento **por fichero** que `public/index.php` comprueba antes de cargar `vendor/` y de arrancar el kernel. `bin/deploy` crea `var/maintenance.lock` como primer paso y lo borra cuando caché, migraciones y assets están al día. Mientras existe, la web responde 503 con una página HTML estática y la API con `{"status":"maintenance"}`. El `MAINTENANCE_MODE` por variable de entorno (`MaintenanceModeListener`) no sirve para esto porque vive dentro del contenedor desfasado; se mantiene sin cambios para el mantenimiento manual y ambos conviven.

Decisiones clave (tabla en `plan.md`, «Decisiones tomadas»): comprobación previa al autoload, fichero que se queda si el despliegue falla, solo `/webhooks/deployments` sigue pasando, `/health-check` en 503.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Shared/Infrastructure/Symfony/Maintenance/MaintenanceGate.php` | Lógica de la compuerta: `guard()` (efectos, `exit`) y `responseFor()` (pura, testeable) | Nuevo |
| `templates/maintenance.html` | Página estática 503 (sin Twig, sin assets, sin scripts), en castellano | Nuevo |
| `public/index.php` | `require_once` de `MaintenanceGate.php` y `MaintenanceGate::guard(dirname(__DIR__))` antes de `vendor/autoload_runtime.php` (líneas 8-12) | Modificado |
| `bin/deploy` | Variable `MAINTENANCE_FLAG` (l. 30), paso de activación (l. 164-166), de desactivación (l. 191-192) y aviso en `on_exit` (l. 138-141) | Modificado |
| `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` | Excluye `Infrastructure/Symfony/Maintenance/` del escaneo de servicios (l. 29-31) | Modificado |
| `infra/supervisor/README.md` | Pasos 1 y 7 del despliegue, ventana residual, fallo y nota de OPcache | Modificado |
| `tests/Unit/Shared/Infrastructure/Symfony/Maintenance/MaintenanceGateTest.php` | Tests de `responseFor()` | Nuevo |
| `CLAUDE.md` (fila `Shared/`) y `.claude/ARCHITECTURE.md` (l. 18 y 123) | Descripción del subsistema | Modificado |

Sin cambios: `MaintenanceModeListener.php`, `error503.html.twig`, `infra/supervisor/*-workers.ini`.

## Modelo de dominio

No hay agregados ni eventos: es infraestructura pura. `MaintenanceGate` es una clase `final` con métodos estáticos que **solo usa funciones nativas de PHP** (sin `vendor/` ni `App\`), porque se carga antes del autoload y Composer puede estar a medio instalar.

Constantes públicas: `FLAG_FILE = 'var/maintenance.lock'`, `PAGE_FILE = 'templates/maintenance.html'`, `RETRY_AFTER_SECONDS = 30` (`MaintenanceGate.php:9-11`). Privadas: `FALLBACK_BODY = 'Estamos en mantenimiento'` y `ALLOWED_PATHS = ['/webhooks/deployments']` (l. 13-16).

## Flujo de una operación

Petición con el fichero presente:

1. `public/index.php:8` carga `MaintenanceGate.php` con `require_once` y `:10` llama a `guard()`.
2. `guard()` (`MaintenanceGate.php:18-38`) extrae el path de `$_SERVER['REQUEST_URI']` con `parse_url` (si no es string o falla, usa `'/'`) y llama a `responseFor()`.
3. `responseFor()` (l. 41-74) decide:
   - sin `var/maintenance.lock` → `null` (l. 43): la petición sigue su curso normal;
   - path que **empieza por** `/webhooks/deployments` → `null` (l. 47-51; usa `str_starts_with`, a diferencia del listener que usa `str_contains`: una ruta como `/es/x/webhooks/deployments` recibe 503, ver `testItBlocksDeploymentWebhookPathsOutsideTheRoot`);
   - path que empieza por `/api` → 503, `Content-Type: application/json`, cuerpo `{"status":"maintenance"}` (idéntico a `MaintenanceModeListener.php:49`);
   - resto → 503, `Content-Type: text/html; charset=UTF-8`, cuerpo de `templates/maintenance.html` (o `FALLBACK_BODY` si el fichero no es legible, comprobado con `is_readable`).
   - Todas las respuestas llevan `Retry-After: 30` y `Cache-Control: no-store, no-cache, must-revalidate`.
4. Si hay respuesta, `guard()` emite código, cabeceras y cuerpo y hace `exit`; el kernel no llega a arrancar.

Despliegue (`bin/deploy`, `main()`): `trap on_exit EXIT` (l. 162) → «Activando el modo mantenimiento» (`mkdir -p var`, escribe la fecha en el fichero) → parar workers → `composer install` → limpiar y calentar caché → migraciones → assets → «Desactivando el modo mantenimiento» (`rm -f`, l. 191-192) → Supervisor `reread/update` → lanzar workers. Relanzar tras un fallo recrea el fichero (ya existente) y lo borra al terminar bien.

## Autorización y ámbito

No hay autorización: la compuerta actúa antes de cualquier firewall, sesión o rol y afecta a todos por igual. No hay impersonación ni feature flag; el interruptor es la existencia del fichero.

Rutas que **no** pasan (503): `/health-check`, `/api/health-check` y `/webhook/sendgrid` (a diferencia del listener, que deja pasar `/webhook`, `/health-check`, `/_wdt`, `/_profiler`). Es deliberado: no se arranca un kernel desfasado; SendGrid reintenta ante respuestas no 2xx. Nota: `.claude/ARCHITECTURE.md` (l. 135) dice que el webhook de SendGrid está «permitido en mantenimiento», cierto solo para `MAINTENANCE_MODE`, no para el fichero.

## Persistencia y migración

Sin base de datos ni migración. El estado es el fichero `var/maintenance.lock` (contiene la fecha y hora de activación; la compuerta solo comprueba que exista). `var/` no se versiona (`/var/` en `.gitignore`) y `bin/deploy` solo mueve `var/cache/prod`.

## Configuración y operación

- Sin variables de entorno nuevas. `MAINTENANCE_MODE` sigue siendo el modo manual dentro del kernel (`services.yaml:8` y `:105`).
- Activar a mano: `touch var/maintenance.lock`; desactivar: `rm var/maintenance.lock`.
- Si el despliegue falla, el fichero se queda (igual que los workers parados) y `on_exit` lo avisa con el comando para quitarlo (`bin/deploy:138-141`). Relanzar `bin/deploy` lo quita al terminar bien.
- El webhook de resultado del despliegue (`/webhooks/deployments`, implementado por `Development` en las plataformas ERP; no existe en Hydra) sigue llegando.
- Ventana no cubierta: los segundos entre la copia de Plesk y el primer paso del script.
- OPcache: `bin/deploy` no lo resetea. Comprobar `opcache.validate_timestamps=On` en el PHP-FPM de Plesk (`infra/supervisor/README.md`, nota «OPcache»).
- Monitorización externa: recibe 503 durante el despliegue; ajustar los umbrales de alerta.
- `public/index.php` se ejecuta en cada petición también en local (`frankenphp/Caddyfile` sin modo worker), así que la compuerta funciona igual en desarrollo.

## Cómo extenderlo

- **Nueva ruta exenta:** añadirla a `ALLOWED_PATHS` (`MaintenanceGate.php:16`) y un test en `MaintenanceGateTest`. Es comparación por prefijo.
- **Cambiar el texto o el aspecto:** editar `templates/maintenance.html`; debe seguir siendo autocontenido (sin Twig ni assets externos).
- **Trampas:** no añadir `use` de clases de `vendor/` o `App\` en `MaintenanceGate` (se carga antes del autoload); mantener la exclusión de `Infrastructure/Symfony/Maintenance/` en `services.yaml` para que el contenedor no la registre; `guard()` no se testea (hace `exit`), la lógica nueva debe vivir en `responseFor()`.
- **Propagación:** el cambio llega a cada plataforma al fusionar `master`; `bin/deploy` solo difiere en `SUPERVISOR_GROUP`.

## Tests

`MaintenanceGateTest` (usa el trait `TemporaryDirectory`) cubre: sin fichero devuelve `null`; página web 503 con su `Content-Type` y cuerpo; JSON para `/api`; health checks en 503 (data provider); `/webhooks/deployments/abc` pasa; `/es/x/webhooks/deployments` no pasa; `Retry-After` y `Cache-Control` en web y API (data provider); texto de respaldo si falta la página; la página real existe en el repositorio.

Ejecución: `make test-unit options="--filter MaintenanceGate"`.

No hay escenarios Behat: el navegador de Behat entra por el kernel y no pasa por `public/index.php`. `bin/deploy` no tiene tests automáticos; el plan solo exige `bash -n bin/deploy` y una prueba manual (hecha, ver `## Implementación` del plan).

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: `is_readable()` antes de leer la página y `FALLBACK_BODY`; `REQUEST_URI` solo si es string; pasos del README numerados 1 y 7; tests con prefijo `test` y `@dataProvider`.
- Cambios posteriores recogidos en `review.md` (B1-B4, todos corregidos, 0 abiertos): comparación por prefijo en lugar de `str_contains` para la ruta exenta, exclusión del escaneo de servicios, cobertura de cabeceras en la rama API y README alineado.
- Sin hallazgos abiertos. Ver `plan.md` y `review.md`.
