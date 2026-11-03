# Filas de importación idempotentes ante reintentos de la cola — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-02 (correcciones de la review aplicadas 2026-11-03) · **Bounded Contexts:** `DataExchange`

## Qué hace y por qué

Una importación encola un mensaje por fila. Cada worker de Messenger procesa su fila en dos pasos no atómicos: despacha el comando del tipo de importación (escribe en MySQL) y después marca la fila como ejecutada en la SQLite auxiliar de la importación. Si el handler lanza una excepción entre ambos pasos, o después, Messenger reintenta el mensaje (`config/packages/messenger.yaml`, `retry_strategy`) y la fila se ejecutaba otra vez. En producción se confirmó un movimiento duplicado por este motivo.

La defensa vive en `DataExchange` y no en cada `ImportationType`, de modo que vale para todos los tipos y proyectos derivados (decisiones del plan, tabla «Decisiones tomadas»). Tiene dos piezas:

1. **Reclamo de fila** (`claimRow()`): antes de despachar el comando, la fila se marca `claimed = 1` con un `UPDATE` condicional. Solo el intento que lo gana despacha el comando. Un reintento ve la fila ya reclamada y, según su estado, devuelve el resultado guardado o la registra como interrumpida, sin volver a despachar.
2. **Reclamo del arranque** (`claimStart()`, añadido en la review, hallazgo M1): `ExecuteImportationService` solo encola las filas si gana el paso atómico `Started → InProgress`. Evita que dos peticiones simultáneas a «Finalizar» encolen cada fila dos veces.

Principio de diseño: se prefiere un falso error revisable («interrumpida») a un dato duplicado silencioso. No se debe «mejorar» re-ejecutando el comando.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/DataExchange/Domain/Model/Importation/ImportationDataRepository.php:32` | Puerto de la SQLite auxiliar: `claimRow(ImportationId, int): bool` | Modificado |
| Dominio | `src/DataExchange/Domain/Model/Importation/ImportationRepository.php:18,27` | Puerto usado por los servicios: `claimRow()` y `claimStart()` | Modificado |
| Aplicación | `src/DataExchange/Application/Importation/ExecuteSingle/ExecuteSingleService.php` | Constante `INTERRUPTED_ERROR` (`:25`), reclamo (`:53`), `executeRow()` (`:72`), `resultOfRetriedRow()` (`:90`) | Modificado |
| Aplicación | `src/DataExchange/Application/Importation/Execute/ExecuteImportationService.php:49` | Guarda `claimStart()` antes de `startExecution()` | Modificado |
| Infraestructura | `src/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepository.php` | Columna `claimed` (`:58`), `getColumns()` la excluye (`:168`), `claimRow()` (`:379`), `ensureClaimedColumn()` (`:391`), `hasClaimedColumn()` (`:407`), `getResult()` con columnas explícitas (`:419`) | Modificado |
| Infraestructura | `src/DataExchange/Infrastructure/Persistence/Doctrine/Repository/DoctrineImportationRepository.php` | `claimRow()` delega (`:93`); `claimStart()` es un `UPDATE` condicional (`:117`) | Modificado |
| Infraestructura | `src/DataExchange/Infrastructure/Persistence/InMemory/Repository/InMemoryImportationRepository.php` | Doble de test: `claimRow()` delega (`:97`); `claimStart()` en memoria (`:113`) | Modificado |

Sin cambios: `Importation.php`, `ImportationAuthorizer`, `view.html.twig`, `finish.html.twig`, `FinishController`, `messenger.yaml`, `DeferredMessageHandler`.

## Modelo de dominio

El agregado `Importation` no cambia. El estado de cada fila vive en la tabla `importation_data` de la SQLite de la importación y se rige por dos banderas:

| `claimed` | `executed` | Significado | Qué hace `ExecuteSingleService` |
|---|---|---|---|
| 0 | 0 | Pendiente, nunca intentada | Reclama, despacha el comando, `saveResult()` |
| 1 | 0 | Reclamada; el intento murió antes de guardar el resultado (o sigue en curso en otro worker) | No despacha; guarda `INTERRUPTED_ERROR` |
| 1 | 1 | Ejecutada (con o sin error) | No despacha; devuelve el error guardado |
| 0 | 1 | Solo por `saveResult()` directo (encolado fallido en `ExecuteImportationService`, o fichero anterior al despliegue) | Como «ejecutada» |

Invariantes:

- `claimRow()` devuelve `true` una sola vez por fila: `claimed` nunca vuelve a 0.
- El comando de una fila se despacha como máximo una vez, en el intento que gana `claimRow()`.
- `pendingRows()` cuenta `executed != 1` (`SqliteImportationDataRepository.php:442`, sin cambios): una fila reclamada y no ejecutada sigue pendiente hasta que un reintento la cierra.
- La importación pasa a `Executed` una sola vez (`claimExecution()` + `finishExecution()` idempotente), desde cualquier intento que vea `pendingRows() === 0`.
- La importación pasa a `InProgress` una sola vez por `claimStart()`.

## Flujo de una operación

### Ejecutar la importación (`/{code}/import/{id}/finish`)

1. `FinishController` llama a `ExecuteImportationService` síncronamente; cualquier `\Throwable` se convierte en un flash de error y redirección al listado.
2. `ExecuteImportationService.php:47`: `denyUnlessCanDoThat(Action::Edit)`. `ImportationAuthorizer::canEdit()` rechaza las importaciones `Executed` e `InProgress`, así que una recarga posterior da 403 (ver *Deuda*).
3. `:49`: `claimStart()`. Si pierde, `\RuntimeException('Importation already started')`. Solo cubre la carrera de dos peticiones que leyeron `Started`.
4. `startExecution()` + `add()`, y un `DeferredCommand(ExecuteSingleRequest)` por fila.

### Procesar una fila (worker)

`ExecuteSingleService.php:53`:

- `claimRow()` ganado: `executeRow()` despacha `$request->command` capturando `\Throwable`, y `saveResult()`.
- `claimRow()` perdido: `resultOfRetriedRow()` lee `getResult()`. Si no existe, `\RuntimeException('Importation row not found')`; si `executed` es falso, guarda `INTERRUPTED_ERROR` y lo devuelve; si es verdadero, devuelve el `error` guardado.
- En todos los caminos se ejecuta después el bloque de cierre (`:57-65`): con `pendingRows() === 0` y `claimExecution()` ganado, `finishExecution()` + `add()`. Así un reintento de la última fila cierra la importación si el intento anterior murió entre `saveResult()` y el cierre.
- La respuesta lleva `success: null === $errorMessage`.

## Autorización y ámbito

Sin cambios. `ExecuteSingleService` autoriza `Action::View` y `ImportationType::isAuthorized()`; `ExecuteImportationService` autoriza `Action::Edit`. El reclamo ocurre después de las autorizaciones.

## Persistencia y migración

No hay migración de MySQL. El único esquema que cambia es el de la SQLite auxiliar (`var/sqlite/<id>.sqlite`), que `createData()` genera ahora con `claimed BOOLEAN DEFAULT 0` entre las columnas del fichero y `executed`.

- `claimRow()`: `UPDATE importation_data SET claimed = 1 WHERE row_id = ? AND claimed = 0 AND executed = 0` y `1 === rowCount()` (`SqliteImportationDataRepository.php:379-389`). SQLite serializa las escrituras; `rowCount()` es por sentencia, no `changes()` global.
- **Importaciones anteriores al despliegue:** su SQLite no tiene `claimed`. `ensureClaimedColumn()` la añade con `ALTER TABLE` antes del `UPDATE`; si dos workers lo hacen a la vez y el segundo recibe «duplicate column name», `hasClaimedColumn()` (marcado `@phpstan-impure`) confirma que ya existe y continúa en lugar de propagar.
- `getResult()` selecciona `row_id`, las columnas del fichero, `executed` y `error` (ya no `SELECT *`), y `getColumns()` excluye `claimed`: la pantalla `import_view` no cambia.
- `claimStart()` en Doctrine: `UPDATE Importation SET status = InProgress WHERE id = :id AND status = Started`, `1 === $affectedRows` (mismo patrón que `claimExecution()`, `DoctrineImportationRepository.php:117-132`).
- No se fija `PDO::ATTR_TIMEOUT`: el valor por defecto de `pdo_sqlite` (60 s) absorbe la contención entre workers.

## Configuración y operación

Sin variables, servicios ni comandos nuevos. Lo relevante para operar:

- Los reintentos de Messenger siguen activos (`config/packages/messenger.yaml`); ahora son inocuos para las filas.
- Una fila con el error «Ejecución interrumpida en un intento anterior: comprueba si la fila se ha importado antes de repetirla» (`ExecuteSingleService::INTERRUPTED_ERROR`, texto fijo en castellano, guardado en SQLite y mostrado tal cual) puede haberse registrado o no: hay que comprobarlo a mano antes de repetirla.
- Para localizar la causa de un reintento: buscar en `var/log/prod.log` «Error thrown while handling message» / «Retrying», y en `messenger_messages` los mensajes de `queue_name = 'failed'`. Si el origen es un worker matado en un despliegue, conviene arrancarlos con `--time-limit` y pararlos con `messenger:stop-workers` antes de desplegar (no determinado en este plan).
- Los duplicados ya registrados antes del cambio no se corrigen: es una limpieza manual de datos en cada proyecto.

## Cómo extenderlo

- Un `ImportationType` nuevo no necesita hacer nada: la protección está en `ExecuteSingleService`.
- Una implementación propia de `ImportationRepository` o `ImportationDataRepository` en un proyecto derivado debe añadir `claimRow()` (y `claimStart()` en la primera). En dediez-erp no hay ninguna.
- Cualquier consulta nueva sobre `importation_data` que use `SELECT *` expondría `claimed` en la pantalla de resultados: usar `getColumns()`.
- Trampa: no mover el bloque de cierre dentro de la rama «reclamo ganado»; los reintentos deben poder cerrar la importación.
- Trampa: la rama «interrumpida» también se activa si la copia B de una fila llega mientras el worker A todavía la procesa; `claimStart()` elimina la fuente habitual (mensajes duplicados), pero un reintento real concurrente seguiría marcándola interrumpida.

## Tests

Unitarios (no hay Behat: Hydra no tiene ningún `ImportationType` en `src/`).

- `tests/Unit/DataExchange/Infrastructure/Persistence/PDO/Repository/SqliteImportationDataRepositoryTest.php`: `testARowCanOnlyBeClaimedOnce`, `testAnExecutedRowCannotBeClaimed`, `testTheResultOfARowDoesNotExposeTheClaim`, `testAnImportationCreatedWithoutTheClaimedColumnCanStillBeClaimed`.
- `tests/Unit/DataExchange/Application/Importation/ExecuteSingle/ExecuteSingleServiceTest.php:182-271`: fila ya ejecutada, error conservado, reclamada sin terminar, última fila que cierra la importación.
- `tests/Unit/DataExchange/Application/Importation/Execute/ExecuteImportationServiceTest.php:161-221`: importación `Executed` e `InProgress` (ambas lanzan por el authorizer) y la «caduca» (`Started` en memoria pero ya reclamada) que lanza sin encolar ni publicar `ImportationExecutionStarted`.
- `tests/Unit/DataExchange/Domain/Model/Importation/ImportationRepositoryTest.php`, `.../InMemory/Repository/InMemoryImportationRepositoryTest.php` y `.../Doctrine/Repository/DoctrineImportationRepositoryTest.php`: `claimRow()` y `claimStart()`.

Ejecución: `make test-unit options="--filter Importation"` (el filtro incluye otros tests de importación) o `make test-unit options="tests/Unit/DataExchange"`; suite completa con `make test-parallel`.

Hueco: la condición de carrera real entre dos workers no se prueba (los tests simulan los estados con llamadas secuenciales).

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`) y `review.md`.

- El criterio «ejecutar sobre una `InProgress` vuelve a encolar las filas» no se cumple: `ImportationAuthorizer::canEdit()` lo bloquea antes y se mantuvo sin cambios por decisión del usuario. Recuperar mensajes perdidos de la cola requeriría otra acción o cambiar ese authorizer.
- La review (M1, B1) está cerrada. Queda la duda Q1, fuera de alcance: recargar `/{code}/import/{id}/finish` con la importación ya iniciada da 403 en lugar de mostrar el progreso.
