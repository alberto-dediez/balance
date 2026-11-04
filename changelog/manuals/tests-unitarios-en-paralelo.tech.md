# Tests unitarios en paralelo — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-04 · **Bounded Contexts:** ninguno (herramientas de desarrollo: `Makefile`, `bin/`, tests de `DataExchange` y `Shared`)

## Qué hace y por qué

`make test-parallel` lanzaba la suite de aceptación en paralelo (`bin/behat-parallel`) pero PHPUnit en un único proceso. Se añade `make test-unit-parallel`, que reparte los ficheros `*Test.php` entre N procesos PHPUnit independientes con el mismo modelo que Behat: una copia de la base SQLite por worker, `TEST_TOKEN` por worker, un log por worker y un código de salida agregado. `make test-parallel` encadena ahora `test-acceptance-parallel` + `test-unit-parallel` (`Makefile:114`). `make test` y `make test-unit` siguen siendo secuenciales.

Decisiones clave (ver tabla *Decisiones tomadas* del plan): lanzador propio en bash sin dependencias nuevas de Composer (no se usa ParaTest); la unidad de reparto es el fichero, no el método; se excluyen las clases `abstract`; el reparto se equilibra por número de métodos `test*`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `bin/phpunit-parallel` | Lanzador: descubre ficheros, reparte, genera un XML por worker, lanza, agrega resultados | nuevo |
| `Makefile` (`test-parallel` l.114, `test-unit-parallel` l.125, `test-unit` l.103) | Objetivos de make; `php_options` | modificado |
| `tests/Unit/Shared/TemporaryDirectory.php` | Trait con directorio temporal único por test (`temporaryDirectoryPath`, `removeTemporaryDirectory`) | usado por 10 tests |
| `tests/Unit/Shared/Application/CommandBus/FakeCommand.php` | `FakeCommand` extraído de `CommandBusDispatcherTest.php` para que se autocargue | nuevo |
| `tests/Unit/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepositoryTest.php`, `.../Doctrine/Repository/DoctrineImportationRepositoryTest.php` | Dejan de vaciar un directorio compartido; usan directorio temporal propio | modificados |
| `tests/Unit/DataExchange/Application/Importation/{Register,AssignValues,Execute,ExecuteSingle,PairFields}/*ServiceTest.php`, `.../ImportationProfile/{Memorize,Recall}/*ServiceTest.php`, `tests/Unit/DataExchange/Domain/Event/Importation/MemorizeChoicesOnImportationExecutedTest.php` | Pasan `dataRepository: new SqliteImportationDataRepository($this->storagePath)` en lugar de usar el directorio por defecto | modificados |
| `.claude/ARCHITECTURE.md` (sección *Entorno de Test*) | Describe el lanzador y la regla de clases auxiliares | modificado |

## Flujo de una operación

`make test-unit-parallel processes=N php_options="..." options="..."` (`Makefile:125-133`):

1. `rm -rf var/sqlite-test` dentro del contenedor (red de seguridad: los ficheros son de `root`).
2. `doc:sch:drop` + `doc:sch:upd` en `test` y `cache:warmup -e test`, para generar `var/data.db` (plantilla) antes del reparto.
3. `bash bin/phpunit-parallel [-j N] $(options)` con `TEST_CONT` (hereda `APP_ENV=test`, etc.). Si hay `php_options`, se antepone `env PHPUNIT_PHP_OPTIONS="..."`, porque `docker compose exec` no hereda el entorno del host.

Dentro de `bin/phpunit-parallel`:

1. **Argumentos** (l.30-50): `-j N` o `-jN` fija procesos; un argumento que existe como ruta es una ruta de tests; cualquier otro se pasa a PHPUnit tal cual (p. ej. `--filter X`). Sin rutas, `tests`. Exige bash >= 5.1 (l.6-9), `var/data.db` y `phpunit.xml` o, en su defecto, `phpunit.xml.dist` (l.20-27).
2. **Descubrimiento** (l.63-79): `find` de `*Test.php` (o el fichero si se pasa uno), `sort -u`, descartando los que contienen `^abstract class` (l.74). Los abstractos se cargan por autoload desde sus subclases.
3. **Nº de workers** (l.85-96): `-j`, o `min(8, nproc)`; nunca más que ficheros.
4. **Reparto** (l.98-130): peso = nº de `function test*(` del fichero, mínimo 1; orden descendente por peso y nombre; cada fichero va al worker con menos carga acumulada (determinista). Las subclases de tests abstractos quedan infraponderadas (sus métodos están en el padre). Genera `var/test-parallel-unit/worker_N.tests`.
5. **Configuración por worker** (l.145-): un script PHP embebido (DOMDocument) copia la configuración PHPUnit conservando `<php>`, `<listeners>`, `<extensions>` (DAMA) y `<coverage>`, absolutiza `bootstrap` y los `<directory>`/`<file>` de `coverage`/`source`, sustituye `<testsuites>` por una única suite `worker N` con un `<file>` absoluto por test asignado y escribe `worker_N.xml`.
6. **Lanzamiento** (l.206-215): `cp var/data.db var/test-parallel-unit/data_N.db`; en segundo plano `TEST_TOKEN=N DATABASE_URL=sqlite:///./var/test-parallel-unit/data_N.db php ${PHPUNIT_PHP_OPTIONS:-} bin/phpunit --configuration worker_N.xml --do-not-cache-result --colors=always <flags>`, salida a `worker_N.log`. `--do-not-cache-result` evita que los workers pisen `.phpunit.result.cache`.
7. **Agregación** (l.216-): `wait -n -p` imprime `worker N: OK|FAILED (code)` según terminan; de los fallidos se vuelca el log completo; el resumen filtra del log (sin códigos ANSI) las líneas `OK (`, `Tests:`, `Time:`, `No tests executed`, `ERRORS!`, `FAILURES!`, `WARNINGS!`; imprime el tiempo total y sale con `1` si algún worker falló.

`TEST_TOKEN` hace que `config/packages/doctrine.yaml` (`when@test`, l.78-81) sufije `dbname_suffix` y que los directorios de `var/` ya sufijados en `when@test` (ver *Entorno de Test* de `ARCHITECTURE.md`) no colisionen entre workers.

## Autorización y ámbito

No aplica: no hay roles, authorizers ni impersonación. Es solo herramienta de desarrollo.

## Persistencia y migración

Sin migración ni cambios de esquema. Artefactos en `var/test-parallel-unit/`, que `bin/phpunit-parallel` borra y recrea en cada ejecución (l.97-98): `worker_N.tests`, `worker_N.xml`, `data_N.db`, `worker_N.log`.

## Configuración y operación

- `make test-unit-parallel`, `make test-unit-parallel processes=4`, `make test-unit-parallel php_options="-d memory_limit=1G"`, `make test-unit-parallel options="tests/Unit/Security"`, `make test-unit-parallel options="--filter Account"`.
- Variable `PHPUNIT_PHP_OPTIONS` (opciones de PHP para los workers). Sin variables nuevas en `.env`.
- Un worker sin tests tras `--filter` sale en `No tests executed!` con código 0; no cuenta como fallo.
- Los logs se conservan tras la ejecución para diagnóstico.

## Cómo extenderlo

- **Cambiar el criterio de reparto:** el bloque de pesos (l.98-130). Mantener determinismo.
- **Aislar una nueva ruta bajo `var/`:** debe llegar como parámetro inyectado y sufijada por `TEST_TOKEN` en `when@test`, o el test debe usar un directorio temporal (`TemporaryDirectory`), nunca una ruta fija compartida.
- **Nuevos dobles o clases auxiliares de test:** un fichero PSR-4 por clase, nunca declaradas dentro de un `*Test.php`; con el reparto por fichero, otro worker no cargaría ese fichero y la clase no se autocargaría (es lo que rompió `FakeCommand`).
- **Nuevo test que escriba en disco o BD compartida:** usar `TemporaryDirectory` (`temporaryDirectoryPath()` en `setUp`, `removeTemporaryDirectory()` en `tearDown`).
- **Proyectos derivados:** el lanzador no depende del proyecto (lee `phpunit.xml[.dist]`); basta copiar `bin/phpunit-parallel` y el objetivo de `Makefile`.

## Tests

El lanzador no tiene tests propios; se verificó a mano (plan, `## Implementación`): `processes=1/4/200/300`, `--filter` sin coincidencias (exit 0), ruta de directorio y de fichero único, fallo inyectado (exit != 0 con volcado del log). Los cambios en tests de `DataExchange` y `Shared` los cubre la propia suite: `make test-unit` o `make test-unit-parallel`; para un subconjunto, `make test-unit options="--filter Xxx"`.

## Desviaciones y deuda

Ver `plan.md` (*Implementación*) y `review.md` (0 hallazgos abiertos tras la revisión 3).

- `FakeCommand` extraído a su propio fichero (dependencia oculta entre ficheros de test).
- El resumen elimina códigos ANSI antes del `grep`.
- Deuda conocida no corregida: `DeferredMessageHandlerTest`, `DeferredCommandMessengerTest`, `LocalizedCommandHandlerTest`, `ScheduledCommandTest`, `DomainEventPublisherTest`, `ExceptionListenerTest`, `FilterTraitTest` y `ExecuteImportationServiceTest` siguen declarando clases auxiliares dentro del propio `*Test.php` (hoy ninguna se usa desde otro fichero).
- El peso declarado (1033) es inferior a lo ejecutado (1228) por los métodos heredados de tests abstractos.
