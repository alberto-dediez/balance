# Ejecución de los tests de aceptación en paralelo — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-05 · **Revisado:** 2026-10-05 (`review.md`, `review-fix.md`) · **Bounded Contexts:** ninguno funcional; toca configuración de `Shared` y `DataExchange` solo en `when@test`, más `Makefile`, `bin/` y `config/packages/cache.yaml`

## Qué hace y por qué

La suite Behat se ejecutaba en un único proceso PHP. En Hydra son 63 `.feature` / 312 escenarios (~12 s), pero los proyectos derivados (p. ej. `../events`, ~1.300 escenarios) heredan `Makefile`, `behat.yml.dist` y los contextos de Hydra, y ahí el tiempo de `make test` es el problema. Se añade un modo paralelo: un lanzador bash (`bin/behat-parallel`) reparte los ficheros `.feature` entre N procesos `behat` independientes, cada uno con su propia base SQLite y sus propios directorios bajo `var/`, y agrega el resultado (salida y código de salida). El modo secuencial (`make test-acceptance`, `make test`) no cambia.

Decisiones clave (tabla *Decisiones tomadas* de `plan.md`): lanzador propio sin dependencias de Composer; unidad de reparto = fichero `.feature`, equilibrado por nº de escenarios; una copia de `var/data.db` por worker vía `DATABASE_URL` real (el `dbname_suffix` de Doctrine no afecta a SQLite, `config/packages/doctrine.yaml:76-80`); identificador de worker `TEST_TOKEN` (1..N); 8 procesos por defecto; `make test` sigue siendo secuencial; PHPUnit no se paraleliza.

Mediciones del implementador (`plan.md`, `## Implementación`): secuencial 12,0 s; `processes=4` ~5,1 s; 8 workers ~3,6–3,8 s (39 escenarios por worker).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Herramienta | `bin/behat-parallel` | Lanzador: selección, reparto, arranque de workers, espera y resumen | Nuevo (ejecutable) |
| Herramienta | `Makefile:113-121` | Targets `test-parallel` y `test-acceptance-parallel` | Modificado |
| Configuración | `config/packages/cache.yaml:21-24` | `when@test`: `framework.cache.app: cache.adapter.array` | Modificado |
| Configuración | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml:75-77` | `when@test`: `app.uploads_dir` → `var/uploads_test%env(default::TEST_TOKEN)%` | Modificado |
| Configuración | `src/DataExchange/Infrastructure/Symfony/DependencyInjection/services.yaml:31-34` | `when@test`: `storage.path` → `var/sqlite_test{TOKEN}/`, `export.storage.path` → `var/export_test{TOKEN}/` | Modificado |
| Infraestructura (DataExchange) | `src/DataExchange/Infrastructure/Persistence/InMemory/Repository/InMemoryImportationRepository.php:20-25` | Acepta `?ImportationDataRepository $dataRepository` inyectado; si es `null`, cae a `var/sqlite-test/` (corrección M2 de la review) | Modificado |
| Infraestructura (DataExchange) | `src/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepository.php:20-25` | Guarda de `mkdir` corregida (`!mkdir`), ya no lanza al crear el directorio (corrección M1) | Modificado |
| Tests | `tests/Unit/DataExchange/Infrastructure/Persistence/InMemory/Repository/InMemoryImportationRepositoryTest.php` | Verifica que el doble usa el repositorio de datos inyectado | Nuevo |
| Tests | `tests/Unit/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepositoryTest.php:18-30` | Crea el directorio si no existe | Modificado |
| Documentación | `CLAUDE.md:22-23,150`, `.claude/ARCHITECTURE.md:220,243-244` | Comandos nuevos y variables por worker | Modificado |

Sin cambios (explícito en el plan): `behat.yml.dist`, `config/packages/doctrine.yaml`, `tests/Acceptance/**`, `phpunit.xml.dist`, `composer.json`, `.env`, `.env.test`.

## Recursos y aislamiento

Con N workers (`i` = `TEST_TOKEN`):

| Recurso | Ámbito | Ruta / mecanismo |
|---|---|---|
| Esquema plantilla | compartido, solo lectura | `var/data.db` (creada por `doc:sch:drop` + `doc:sch:upd`) |
| Base de datos | por worker | `var/test-parallel/data_i.db` (copia de la plantilla, `bin/behat-parallel:136`) |
| Lista de features | por worker | `var/test-parallel/worker_i.scenarios` |
| Log | por worker | `var/test-parallel/worker_i.log` |
| Uploads (firmas, ficheros, Biblioteca vía `app.media_dir`) | por worker | `var/uploads_test{i}` |
| Datos de importación | por worker | `var/sqlite_test{i}/` |
| Exportaciones | por worker | `var/export_test{i}/` |
| `cache.app` (throttling de login, limitadores, etc.) | por kernel (en memoria) | `cache.adapter.array` |
| Contenedor compilado, Twig, `cache.system`, `var/events.json` | compartido, calentado antes | `var/cache/test` |
| Singletons, transportes `in-memory://`, storages `InMemory*`, sesión `mock_file` | por proceso | memoria |

Con `TEST_TOKEN` vacío (ejecución secuencial) las rutas degeneran en `var/uploads_test`, `var/sqlite_test/` y `var/export_test/`: los tests ya no escriben en los directorios de desarrollo (`var/uploads`, `var/sqlite/`, `var/export/`).

Los tres parámetros se consumen como argumentos de constructor (`src/Security/Infrastructure/Symfony/DependencyInjection/services.yaml:76` `LocalSignatureFileStorage`, `:118` `LocalFileUploader`; `src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml:2` `app.media_dir`; `src/DataExchange/Infrastructure/Symfony/DependencyInjection/services.yaml:15-21`), de modo que `%env(default::TEST_TOKEN)%` se resuelve en ejecución y un único contenedor compilado sirve a todos los workers.

En test, `ImportationRepository` lo implementa `InMemoryImportationRepository` (`#[When(env: 'test')]`, `:14`). Desde la corrección M2 el contenedor le autowirea el servicio `SqliteImportationDataRepository` con `%storage.path%`, así que en aceptación el almacenamiento de importación también queda por worker. Los unitarios que hacen `new InMemoryImportationRepository()` siguen usando el fallback `var/sqlite-test/` (`:22-24`).

`cache.app` en memoria (desviación declarada en `plan.md`, alternativa prevista en *Riesgos*): con `framework.cache.directory` por worker el estado del `login_throttling` (`config/packages/security.yaml:22-23,47-48`, `max_attempts: 3`) persistía en ficheros entre ejecuciones consecutivas y `login-sso.feature` fallaba al relanzar en menos de un minuto. Con el adaptador `array` el estado vive en el kernel, que FriendsOfBehat reinicia en cada escenario.

## Flujo de una ejecución

`make test-acceptance-parallel [processes=N] [options="..."]` (`Makefile:115-121`):

1. `doc:sch:drop` + `doc:sch:upd` en `-e test`: recrea la plantilla `var/data.db` (el `DATABASE_URL` de `TEST_CONT`, `Makefile:7`).
2. `cache:warmup -e test`: compila el contenedor (y `var/events.json`) antes de arrancar ningún worker, para que no compilen a la vez.
3. `$(TEST_CONT) bash bin/behat-parallel [-j N] $(options)`: el script hereda todas las variables de `TEST_CONT`, incluidas las que añada cada proyecto derivado.

`bin/behat-parallel`:

1. Exige bash ≥ 5.1 (`:6-9`, por `wait -n -p`); `set -u`, `LC_ALL=C` (`:3-4`) y `cd` a la raíz del proyecto (`:11`).
2. Argumentos (`:20-39`): `-j N` o `-jN` fija el nº de procesos (validado como entero ≥ 1, `:41-44`). Cualquier otro argumento que exista en disco (`-e`) es una **ruta**; el resto son **flags** que se pasan tal cual a todos los workers.
3. Falla si no existe `var/data.db` (`:46-49`). Sin rutas, usa `tests/Acceptance` (`:51-53`).
4. Selección (`:55-65`): ficheros `.feature` (una ruta-fichero que no sea `.feature` se descarta), sin duplicados y en orden alfabético. Sin ninguno: error y salida 1.
5. Nº de procesos (`:72-82`): sin `-j`, `min(8, nproc)`; en todo caso, limitado al nº de ficheros seleccionados.
6. Reparto (`:87-123`): peso = nº de líneas `Scenario:`/`Scenario Outline:`/`Example:` (mínimo 1); ficheros por peso descendente y, a igual peso, alfabéticamente; cada uno al worker con menor carga acumulada (empate → índice menor). Cada `worker_i.scenarios` queda ordenado alfabéticamente. Es determinista para la misma lista y el mismo N.
7. Cabecera con ficheros y escenarios por worker (`:125-129`).
8. Arranque (`:135-141`): `cp var/data.db var/test-parallel/data_i.db` y, en segundo plano, `TEST_TOKEN=i DATABASE_URL=sqlite:///./var/test-parallel/data_i.db php ${BEHAT_PHP_OPTIONS:-} vendor/bin/behat --no-interaction --colors <flags> worker_i.scenarios > worker_i.log 2>&1`.
9. Espera (`:143-153`): `wait -n -p` por orden de finalización, línea `worker i: OK` / `worker i: FAILED (código)`.
10. Informe (`:155-172`): log completo de cada worker fallido; resumen de Behat de todos (escenarios, pasos, tiempo); tiempo total de pared.
11. Salida 0 si todos terminaron en 0; 1 en otro caso (`:174`).

`make test-parallel` (`Makefile:113`) = `test-acceptance-parallel` + `test-unit`.

## Configuración y operación

| Comando | Efecto |
|---|---|
| `make test-acceptance-parallel` | Toda la suite en `min(8, nproc, nº de .feature)` workers |
| `make test-acceptance-parallel processes=4` | 4 workers (solo se limita por nº de ficheros) |
| `make test-acceptance-parallel options="tests/Acceptance/Security"` | Solo ese subconjunto |
| `make test-acceptance-parallel options="--tags=@foo"` | El flag llega a todos los workers |
| `make test-parallel` | Aceptación en paralelo + unitarios |
| `make test-acceptance` / `make test` | Modo secuencial, sin cambios |

- `BEHAT_PHP_OPTIONS` (variable de entorno opcional) se antepone a `vendor/bin/behat` en cada worker, p. ej. `-e BEHAT_PHP_OPTIONS="-d memory_limit=512M"` en el `TEST_CONT` de un derivado.
- Salida no en vivo: para seguir un worker, `tail -f var/test-parallel/worker_N.log` desde `make sh`.
- `var/test-parallel` se borra y recrea en cada ejecución (`bin/behat-parallel:84-85`); `var/` está en `.gitignore`.
- No editar código mientras corre la suite: una caché obsoleta haría que varios workers recompilasen a la vez.

## Cómo extenderlo

**Aislar un recurso nuevo que se comparte entre procesos** (directorio bajo `var/`, pool de caché propio, fichero de lock):

1. Que la ruta sea un parámetro consumido como argumento de servicio (no una ruta fija en la clase ni en el contexto Behat).
2. En el `services.yaml` del BC, añadir un bloque `when@test` con el sufijo `%env(default::TEST_TOKEN)%`, como `src/DataExchange/Infrastructure/Symfony/DependencyInjection/services.yaml:31-34`.
3. Comprobar que el consumidor crea el directorio si no existe (el lanzador no lo crea).
4. Si en test el servicio lo sustituye un doble (`#[When(env: 'test')]`), el doble debe recibir la dependencia por inyección; si la construye él con una ruta fija, el override no le llega (caso corregido en `InMemoryImportationRepository`).

**Adopción en un proyecto derivado:** recibir por merge `bin/behat-parallel`, los targets del `Makefile` (sin perder las variables propias de su `TEST_CONT`) y los bloques `when@test`; definir `BEHAT_PHP_OPTIONS` si hace falta; revisar sus BCs con la regla anterior (incluidas rutas fijas en contextos Behat como `/tmp/nombre-fijo`); ejecutar la suite varias veces con distintos `processes=` para detectar dependencias de orden entre `.feature`. El contenedor necesita bash ≥ 5.1 (el script lo comprueba al arrancar).

**Trampas:**

- Un argumento de `options` se clasifica como ruta si existe en disco (`bin/behat-parallel:31-35`). Un flag con valor separado por espacio cuyo valor es un fichero existente (p. ej. `--config behat.yml.dist`) pierde el valor: el fichero se toma como ruta, se descarta por no ser `.feature` y el flag llega a Behat sin argumento. Usar la forma `--opcion=valor`.
- `options` se expande sin comillas en el `Makefile`: rutas con espacios no están soportadas.
- Las dependencias ocultas de orden entre `.feature` se corrigen en el `.feature` o en el contexto, no en el lanzador.

## Tests

- No hay `.feature` nuevos: la verificación es la propia suite ejecutada en paralelo (batería descrita en `plan.md`, *Tests › Aceptación*, y resultados en `## Implementación` y `review.md` › *Verificaciones*).
- Unitarios añadidos por las correcciones de la review:
  - `tests/Unit/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepositoryTest.php:18` — `testGivenANonExistentStoragePathWhenICreateTheRepositoryThenTheDirectoryIsCreated`.
  - `tests/Unit/DataExchange/Infrastructure/Persistence/InMemory/Repository/InMemoryImportationRepositoryTest.php:17` — `testGivenAnInjectedDataRepositoryWhenIAddAnImportationThenItsDataIsStoredThroughIt`.
- Ejecutar: `make test-acceptance-parallel`, `make test-parallel`; los unitarios de DataExchange con `make test-unit options="--filter ImportationDataRepository"` o `--filter InMemoryImportationRepositoryTest`.
- Sin cobertura automática: el propio script bash (incluida la rama de error con bash < 5.1, no probada en ejecución según `review-fix.md`, B1).

## Desviaciones y deuda

- `cache.app: cache.adapter.array` en lugar de `framework.cache.directory` por worker (ver *Recursos y aislamiento*; `plan.md`, `## Implementación`).
- El lanzador acepta `-jN`, hace `cd` a la raíz y espera con `wait -n -p` (bash ≥ 5.1), declarado en `plan.md`.
- Correcciones posteriores a la review, todas aplicadas (`review-fix.md`): M1 (`!mkdir` en `SqliteImportationDataRepository`), M2 (inyección en `InMemoryImportationRepository`), B1 (comprobación de versión de bash y documentación del nº de procesos por defecto). No queda ningún hallazgo abierto.
- Pendiente fuera del código (`review-fix.md`): corregir en `plan.md`, vía `feature-planner`, el *Hecho verificado* sobre `storage.path` en test y añadir el requisito de bash ≥ 5.1 a *Adopción en proyectos derivados*.
- Pools residuales `var/cache/test/pools/app1..app8` de la prueba de `cache.directory`; se limpian con `make reset-test-cache`.
