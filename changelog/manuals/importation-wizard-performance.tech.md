# Asistente de importación rápido y aviso de finalización veraz — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-02 · **Bounded Contexts:** `DataExchange`

## Qué hace y por qué

El asistente de importación (subir fichero, emparejar campos, traducir valores, asignar valores por defecto) trabaja sobre una base SQLite auxiliar por importación (`var/sqlite/<id>.sqlite`, parámetro `storage.path` en `src/DataExchange/Infrastructure/Symfony/DependencyInjection/services.yaml:2`). El repositorio de esa base abría una conexión PDO nueva en cada operación, guardaba cada columna en su propia transacción con `fsync` y recargaba todo en cada paso. En el disco de producción cada apertura y cada `fsync` costaba milisegundos, multiplicados por centenares por petición.

Además, `ExecuteImportationService` marcaba cada fila como ejecutada en el momento de encolarla, de modo que el primer worker en procesar una fila encontraba `pendingRows() === 0` y publicaba `ImportationExecuted` (aviso «Importación de {file} finalizada») casi al instante.

La solución, con las decisiones del plan (tabla «Decisiones tomadas»):

- Una conexión PDO cacheada por importación, con `PRAGMA` de durabilidad reducida.
- Guardado de todo el emparejado en una única transacción (`saveConfigurations`).
- Carga de configuraciones en pocas consultas, sin cruce cuadrático de traducciones.
- Una fila solo cuenta como ejecutada cuando la procesa el worker (o cuando falla su encolado).
- Cierre de la importación atómico entre procesos mediante `claimExecution()` (añadido en la review, hallazgo A1) y `finishExecution()` idempotente.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/DataExchange/Domain/Model/Importation/Importation.php` | `finishExecution()` no hace nada si ya está `Executed` (`:187-190`) | Modificado |
| Dominio | `src/DataExchange/Domain/Model/Importation/ImportationRepository.php` | Puerto: añade `claimExecution(ImportationId): bool` (`:25`) | Modificado |
| Dominio | `src/DataExchange/Domain/Model/Importation/ImportationDataRepository.php` | Puerto: `saveConfiguration` pasa a `saveConfigurations(ImportationId, ImportationColumnConfiguration[])` | Modificado |
| Aplicación | `src/DataExchange/Application/Importation/Execute/ExecuteImportationService.php` | Ya no marca filas al encolar; cierra si no quedan pendientes (`:77-80`) | Modificado |
| Aplicación | `src/DataExchange/Application/Importation/ExecuteSingle/ExecuteSingleService.php` | El cierre exige además `claimExecution()` (`:66`) | Modificado |
| Infraestructura | `src/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepository.php` | Conexión y columnas cacheadas, `PRAGMA`, `saveConfigurations`, carga de configuraciones, `ResetInterface` | Modificado |
| Infraestructura | `src/DataExchange/Infrastructure/Persistence/Doctrine/Repository/DoctrineImportationRepository.php` | Existencia por `COUNT` (`add()`), `saveConfigurations`, `claimExecution()` con `UPDATE` condicional (`:112-124`) | Modificado |
| Infraestructura | `src/DataExchange/Infrastructure/Persistence/InMemory/Repository/InMemoryImportationRepository.php` | Doble de test: `saveConfigurations`, `claimExecution()` (`:105-118`) | Modificado |
| Tests | `tests/Unit/DataExchange/Domain/Model/Importation/ImportationTest.php` | Idempotencia de `finishExecution()` | Nuevo |
| Tests | `tests/Unit/DataExchange/Application/Importation/Execute/empty.csv` | CSV solo con cabecera | Nuevo |
| Tests | `tests/Unit/Shared/Application/CommandBus/ThrowingCommandHandler.php` | Handler que lanza para simular cola caída | Nuevo |
| Tests | `tests/Unit/DataExchange/Infrastructure/Persistence/PDO/Repository/file-to-import-wide.csv` | 40 columnas, prueba del límite de variables SQLite (cambio previo ajeno al plan, aceptado en review Q1) | Nuevo |
| Tests | tests de `Execute`, `ExecuteSingle`, `ImportationRepositoryTest`, `InMemoryImportationRepositoryTest`, `SqliteImportationDataRepositoryTest` | Cobertura de todo lo anterior | Modificados |

## Modelo de dominio

`ImportationStatus`: `Started`, `InProgress`, `Executed`. `startExecution()` lleva a `InProgress`; `ImportationExecuted` (aplicado por `applyImportationExecuted`) lleva a `Executed`.

Invariante nueva: `Importation::finishExecution()` (`Importation.php:187`) retorna sin publicar nada si `isExecuted()`, así que un agregado cargado antes de que otro worker lo cerrara no publica un segundo `ImportationExecuted`. Eso solo cubre el agregado en memoria; la carrera real entre procesos se cierra con `claimExecution()` (ver más abajo).

`ImportationExecuted` lo escuchan `src/DataExchange/Infrastructure/Notification/ImportationExecutedNotifier.php` (notificación interna y email «Importación de {file} finalizada») y `src/DataExchange/Domain/Event/Importation/MemorizeChoicesOnImportationExecuted.php`.

## Flujo de una operación

### Lanzar la importación

`GET /{code}/import/{id}/finish` (`FinishController`, ruta `import_finish`) → `ExecuteImportationService::__invoke`:

1. `find()`, comprobación del tipo y `Action::Edit` (`ExecuteImportationService.php:35-47`).
2. `startExecution()` + `add()` (`:49-50`).
3. Bucle `getData()` por fila: construye el comando y despacha un `DeferredCommand(ExecuteSingleRequest)`. **No** marca la fila. Solo si el despacho lanza, `saveResult($id, $rowNumber, $exception->getMessage())` la deja como ejecutada con error (`:70-72`).
4. Tras el bucle: si `pendingRows() === 0` **y** `claimExecution()` devuelve `true`, `finishExecution()` + `add()` (`:77-80`). Cubre importaciones sin filas o con todas las filas falladas al encolar.
5. Devuelve `ExecuteImportationResponse`; el controlador muestra «Importación en curso».

### Procesar una fila (worker)

`ExecuteSingleService::__invoke`:

1. `find()`, `Action::View`, tipo y `isAuthorized` (`:33-50`).
2. Despacha el comando de la fila; captura el error (`:54-60`).
3. `saveResult()` marca la fila como ejecutada con su error real (`:62`).
4. `pendingRows()` (`:64`). Con 0, `!isExecuted()` y `claimExecution()` verdadero (`:66`), llama a `finishExecution()` y `add()`.

El aviso de finalización solo se publica cuando la última fila real ha sido procesada.

### Cierre atómico entre workers

`claimExecution()` hace de cerrojo:

- Doctrine (`DoctrineImportationRepository.php:112-124`): un `UPDATE` DQL que pone `status` a `Executed` donde `id = :id AND status != Executed`; devuelve `true` solo si afectó a una fila. De dos workers que ven `pendingRows() === 0` a la vez, solo uno gana.
- In-memory (`InMemoryImportationRepository.php:105-118`): devuelve `false` si ya se reclamó o si la importación guardada está `Executed`.

Trampa conocida: el `UPDATE` pone el estado en base de datos antes de que se publique el evento. Si el proceso muere entre `claimExecution()` y `finishExecution()` no habrá notificación y ningún otro worker la emitirá.

## Autorización y ámbito

Sin cambios. `ExecuteImportationService` exige `Action::Edit` antes de modificar nada; `ExecuteSingleService` exige `Action::View` y `ImportationType::isAuthorized`. Los controladores del asistente usan `AuthorizeTrait` (`src/DataExchange/Infrastructure/UI/Web/Import/AuthorizeTrait.php`). La pantalla de resultados (`ViewController`) exige `Action::View`.

## Persistencia y migración

Sin migración y sin cambios de esquema (MySQL ni SQLite auxiliar: tablas `importation_data`, `importation_fields`, `importation_translations`).

`SqliteImportationDataRepository`:

- **Conexión** (`connect()`, `:441-465`): mapa `array<string, \PDO>` por id; al crearla ejecuta `PRAGMA journal_mode = TRUNCATE`, `PRAGMA synchronous = OFF` y `PRAGMA temp_store = MEMORY`. Se eligió `TRUNCATE` en lugar del `MEMORY` del plan (review M2): con varios workers escribiendo a la vez, un journal en memoria no protege ante un fallo a mitad de escritura. Se descartó `WAL` (ficheros `-wal`/`-shm` compartidos, sin ventaja para un fichero de uso puntual).
- **Durabilidad:** con `synchronous = OFF` un corte de corriente durante un `saveResult()` puede corromper la base de esa importación. Es aceptable porque es un fichero desechable, pero no existe una vía automática de regeneración: hay que volver a subir el fichero y relanzar la importación.
- **Columnas** (`getColumns()`, `:158-180`): cacheadas por importación en `$columns`.
- **Carga** (`getAllConfigurations()`, `:191-235`): una consulta de `importation_fields`, una de `importation_translations` y un `SELECT DISTINCT` por columna (`distinctValues()`, `:297`); las traducciones se indexan por valor (`buildConfiguration()`, `:275-294`) en lugar de filtrar el array por traducción. `getConfiguration()` (`:237-269`) usa los mismos privados. Los valores distintos se siguen cargando en cada paso del asistente (el plan descarta la carga perezosa).
- **Guardado** (`saveConfigurations()`, `:309-341`): un único `UPDATE importation_fields SET "col" = ?, ...` y los `INSERT OR REPLACE` de traducciones en una transacción; `rollBack()` y relanzado ante cualquier excepción. Los nombres de columna se citan con comillas dobles; es seguro porque `ColumnSanitizer` reduce las cabeceras a caracteres no conflictivos (`preg_replace('/[\s\W]+/', '_', …)`).
- **Ciclo de vida:** `forget()` (`:434`) elimina conexión y columnas de una importación; lo llaman `create()` y `remove()` (antes del `unlink`). `reset()` (`:423`) vacía ambas cachés; la clase implementa `ResetInterface` (review M1) para que los workers de Messenger y los procesos largos no acumulen conexiones entre mensajes. Comprobable con `make sf c="debug:container --tag=kernel.reset"`.
- **Inserción por lotes:** `createData()` limita el tamaño de lote a `intdiv(999, nº de columnas)` (`MAX_SQL_VARIABLES`, `:21`, `:70`) para no superar el límite de variables de SQLite con ficheros anchos.

`DoctrineImportationRepository::add()` comprueba la existencia con un `COUNT` en lugar de `find()` (que cargaba todas las configuraciones) y delega el guardado en `saveConfigurations()`; mantiene la conversión de cualquier `\Throwable` en `NotImportedException`.

## Configuración y operación

- Sin variables de entorno nuevas ni cambios en `services.yaml`. El repositorio SQLite sigue siendo un servicio compartido (`storage.path` por parámetro; en test lleva el sufijo `_test%env(default::TEST_TOKEN)%`, `services.yaml:33`), que es lo que hace útil la caché de conexiones.
- Producción: tres workers de Messenger sin FrankenPHP (confirmado en la review, Q2). Cada worker mantiene su propia caché de conexiones hasta `reset()`.
- Importaciones ya marcadas como `Executed` por el defecto anterior: sus filas pendientes siguen en la cola y se procesan; el usuario ya recibió el aviso adelantado, no se genera otro.
- Para proyectos derivados: buscar `saveConfiguration(` (rompe el puerto) y cualquier implementación propia de `ImportationRepository` (debe añadir `claimExecution()`).

## Cómo extenderlo

- **Persistir más datos de configuración:** añadirlos a `saveConfigurations()` dentro de la misma transacción; no abrir otra conexión ni transacción por columna.
- **Nueva lectura sobre la base auxiliar:** usar `connect()` y `getColumns()` (cacheados); no crear `\PDO` por tu cuenta.
- **Otro repositorio de `ImportationRepository`:** `claimExecution()` debe ser atómico (compare-and-set sobre el estado) y devolver `true` solo para quien cierra la importación.
- **Marcar filas como ejecutadas:** solo `ExecuteSingleService` (fila procesada) o `ExecuteImportationService` (fallo de encolado) deben llamar a `saveResult()`; marcarlas al encolar reintroduce el aviso prematuro.
- Fuera de alcance conocido: `ExecuteSingleService` carga la importación completa (con todos los valores distintos) en cada fila.

## Tests

- `ImportationTest`: `finishExecution()` publica un solo evento y no publica nada si ya está `Executed`.
- `ExecuteImportationServiceTest`: `testUseCase` (filas pendientes tras encolar, sin `ImportationExecuted`), `testAnImportationWithoutRowsIsExecutedAtOnce`, `testAnImportationAlreadyClosedByAnotherProcessDoesNotPublishTheExecutionAgain`, `testARowThatCannotBeQueuedIsRecordedAsFailed`.
- `ExecuteSingleServiceTest`: `testTheImportationFinishesOnlyWithTheLastRow`, `testAStaleImportationAlreadyClosedByAnotherProcessDoesNotPublishTheExecutionAgain`.
- `ImportationRepositoryTest` (abstracto) y `InMemoryImportationRepositoryTest`: `claimExecution()` solo una vez y nunca sobre una importación `Executed`.
- `SqliteImportationDataRepositoryTest`: reutilización de la conexión y `PRAGMA`, `remove()` libera y borra, `reset()`, guardado atómico con rollback, recuperación de configuraciones, ficheros anchos.
- Ejecución: `make test-unit options="--filter Importation"` y `make test-parallel`. No hay acceptance tests: Hydra no define ningún `ImportationType` en `src/`, así que el flujo completo no es ejecutable en Behat aquí.

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: `buildConfiguration()` no añade traducciones huérfanas (comportamiento real previo); `forget()` y `distinctValues()` compartidos; `ThrowingCommandHandler` genérico.
- Añadidos por la review (`review.md`, todos corregidos): `claimExecution()` (A1), `ResetInterface` (M1), `journal_mode = TRUNCATE` (M2), tests de `remove()`/rollback/doble en memoria (B1, B2). Sin hallazgos abiertos.
- Deuda no corregida (`plan.md`): `InMemoryImportationRepository::add()` al volver a añadir una importación existente guarda las configuraciones de la instancia almacenada, no las de la recibida; `ExecuteSingleService` carga la importación completa por fila.
