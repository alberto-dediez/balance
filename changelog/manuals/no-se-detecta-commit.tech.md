# El webhook de despliegue informa el commit desplegado — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-28 · **Bounded Contexts:** ninguno (solo `bin/deploy` e `infra/supervisor/README.md`)

## Qué hace y por qué

`bin/deploy` notifica al ERP, al terminar, un JSON con el campo `commit`, y escribe en `var/log/deploy.log` una cabecera `######## Deploy <fecha> (<commit>) ########`. Antes llegaban siempre `desconocido` / `sin git`: Plesk Git guarda el repositorio como clon *bare* en `/var/www/vhosts/<dominio principal>/git/<repo>.git` y vuelca el árbol de trabajo en `PROJECT_DIR` sin carpeta `.git`, de modo que `git -C "$PROJECT_DIR" rev-parse` fallaba.

Ahora el commit se resuelve una sola vez en `main`, con la función `resolve_commit` (`bin/deploy:68`), y se reutiliza en la cabecera del log y en el webhook mediante la variable global `DEPLOY_COMMIT`. Orden de resolución: `.git` local de `PROJECT_DIR` → repositorio indicado en `DEPLOY_GIT_DIR` → `desconocido`. Las decisiones (sin autodetección del bare, sin campo `branch`, referencia configurable) están en la tabla «Decisiones tomadas» de `plan.md`.

No se toca PHP ni `src/`. El script sigue siendo idéntico entre plataformas; lo propio de cada servidor va en su `.env.local`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `bin/deploy` | Variables `DEPLOY_GIT_DIR`, `DEPLOY_GIT_REF`, `DEPLOY_COMMIT`; función `resolve_commit`; reorden de `main`; `notify_webhook` usa `DEPLOY_COMMIT` | Modificado |
| `infra/supervisor/README.md` | Tabla de variables, apartado «Commit del despliegue», cabecera del log, fila en «Problemas frecuentes» | Modificado |

## Flujo de una operación

1. Plesk ejecuta `bash bin/deploy` tras volcar el código. Declaración de variables: `DEPLOY_GIT_DIR` y `DEPLOY_GIT_REF` con valor por defecto vacío (`bin/deploy:27-28`) y `DEPLOY_COMMIT="desconocido"` (`bin/deploy:36`), para que `notify_webhook` nunca vea una variable sin definir bajo `set -u`.
2. `main` (`bin/deploy:139`): comprobaciones de root/PHP/Composer → `cd "$PROJECT_DIR"` → `exec > >(tee -a "$DEPLOY_LOG") 2>&1` (desde aquí todo, avisos incluidos, va a `deploy.log`) → lectura de `DEPLOY_WEBHOOK_URL`, `DEPLOY_GIT_DIR` y `DEPLOY_GIT_REF` con `env_file_value` si no vienen del entorno (`bin/deploy:149-151`) → `DEPLOY_COMMIT="$(resolve_commit desconocido)"` (`:152`) → cabecera del log (`:154`) → `trap on_exit EXIT` → pasos.
3. `resolve_commit` (`bin/deploy:68-88`), con `ref="${DEPLOY_GIT_REF:-HEAD}"`:
   - sin `git` en el PATH: aviso y fallback;
   - si existe `$PROJECT_DIR/.git`: `git -C "$PROJECT_DIR" rev-parse --short "$ref"`; si da hash, se devuelve;
   - si `DEPLOY_GIT_DIR` no está vacía: `git --git-dir="$DEPLOY_GIT_DIR" rev-parse --short "$ref"`; si falla, aviso con ruta y referencia y fallback. Se usa `--git-dir` explícito para no heredar `GIT_DIR` del proceso de Plesk;
   - si no: aviso «Sin commit legible en … (ref …) y sin DEPLOY_GIT_DIR…» y fallback.
   Todos los `git` van con `|| true` y la función siempre devuelve 0, así que `set -euo pipefail` no puede abortar el despliegue por esto. Los avisos van a stderr.
4. La cabecera muestra `sin git` cuando `DEPLOY_COMMIT` vale `desconocido` (texto documentado en el README). `notify_webhook` envía `json_escape "$DEPLOY_COMMIT"` en `commit` (`bin/deploy:112`), es decir `desconocido`, no `sin git`. El resto del JSON no cambia.

## Autorización y ámbito

No aplica (no hay roles ni authorizers). El script corre como el system user de la suscripción Plesk y solo **lee** el repositorio bare; no hace `fetch`, `checkout` ni escrituras.

## Configuración y operación

| Variable | Defecto | Dónde se define |
|---|---|---|
| `DEPLOY_GIT_DIR` | vacía (commit `desconocido` si no hay `.git` local) | Entorno o línea `DEPLOY_GIT_DIR=/var/www/vhosts/<dominio>/git/<repo>.git` en `.env.local` / `.env` |
| `DEPLOY_GIT_REF` | `HEAD` | Entorno o `.env.local` / `.env`; p. ej. `refs/heads/<rama>` |

- No están en `.env` ni las lee Symfony; igual que `DEPLOY_WEBHOOK_URL`, son configuración del servidor.
- Comprobar la ruta: `sudo -u <PROJECT_USER> -H git --git-dir=<ruta> rev-parse --short HEAD`. Rama a la que apunta el bare: `git --git-dir=<ruta> symbolic-ref HEAD`.
- Según `review.md` (Q1), en el servidor de ejemplo el `HEAD` del bare coincide con la rama desplegada, por lo que basta `DEPLOY_GIT_DIR`.
- Documentación operativa: `infra/supervisor/README.md` (tabla de variables, «Commit del despliegue», «Problemas frecuentes»).

## Cómo extenderlo

- Añadir otro dato del commit (autor, mensaje, rama): ampliar `resolve_commit` o crear una función hermana con el mismo patrón (`|| true`, retorno 0), guardarlo en una variable global inicializada y añadirlo al `printf` del payload en `notify_webhook`. Cambiar el JSON exige cambiar el receptor (ERP, otro proyecto).
- Trampas: (1) `git` bajo `set -e` aborta el despliegue si no va en condicional o con `|| true`; (2) el bare puede estar en una rama distinta de la desplegada si Plesk no actualiza su `HEAD`: usar `DEPLOY_GIT_REF`; (3) relanzar `bin/deploy` a mano tras un push aún no desplegado informa el commit nuevo del bare, no el de los ficheros volcados; (4) mantener el script idéntico entre plataformas (solo difiere `SUPERVISOR_GROUP`).

## Tests

No existe suite automatizada para `bin/deploy` (Bash, depende de Plesk/Supervisor). Verificación manual descrita en `plan.md` (sección Tests): `bash -n bin/deploy`; cargar `resolve_commit` aislada con `source <(sed -n '/^resolve_commit()/,/^}/p' bin/deploy)` y probar con `.git` local, bare clonado en un temporal, `DEPLOY_GIT_DIR` inexistente, `DEPLOY_GIT_REF` válida e inválida, comprobando siempre el código de salida 0. La validación estándar del proyecto (`make cs`, `make phpstan`, `make test-parallel`) no se ve afectada: no hay cambios en PHP.

## Desviaciones y deuda

- Sin desviaciones declaradas en `## Implementación` de `plan.md`.
- Tras la review (`review.md`, hallazgo B1, corregido), los avisos de `resolve_commit` incluyen la referencia usada y ya no afirman «Sin .git»; el texto de los avisos difiere del esbozo de `plan.md`. Manda el código.
- `review.md` no tiene hallazgos abiertos. Fuera de alcance por decisión: detección automática del bare, campo `branch`, comprobar que el commit del bare coincide con los ficheros volcados, tests automatizados.
- Los cambios de `bin/deploy` e `infra/supervisor/README.md` figuran sin commitear en el momento de documentar.
