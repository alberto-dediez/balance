# Fallback a disco local del almacén de la biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-14 · **Bounded Contexts:** `Library`, `Shared` (solo `CommandSchedule`)
- **Revisión:** `review.md` (rev. 2, 0 hallazgos abiertos) · **Triaje:** `review-fix.md`

## Qué hace y por qué

Desde el plan `2026-10-13-almacenamiento-con-ovh` los archivos de la biblioteca pueden vivir en un bucket de OVH (`MEDIA_STORAGE=ovh`). Antes de este cambio, si el bucket no respondía, una subida acababa en 500 y una descarga en 404. Ahora una caída temporal del almacén principal es transparente en subidas y lecturas: la escritura cae al disco local (`var/uploads/library`), se registra una tarea pendiente y un proceso programado cada 5 minutos vuelca lo pendiente al principal y borra la copia local. Los administradores reciben un aviso interno al activarse el fallback y otro cuando la cola vuelve a quedar vacía.

La solución es un **decorador del puerto `MediaStorage`** (`FallbackMediaStorage`), de modo que ni el agregado `Media` ni los casos de uso `Upload`, `Download`, `DownloadPublic` y `Remove` cambian (ver la decisión «Dónde vive el fallback» en `plan.md`). El borrado **no** tiene fallback salvo para archivos que nunca llegaron al principal. Todo el mecanismo se puede apagar con `MEDIA_STORAGE_FALLBACK_ENABLED=false` (necesario con varios nodos web sin volumen compartido).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Library/Domain/Model/StorageSync/StorageSyncTask.php` | Entidad plana `#[Exclude]` (sin `AggregateRoot`): un archivo pendiente de subir al principal | Nuevo |
| Dominio | `src/Library/Domain/Model/StorageSync/StorageSyncTaskRepository.php` | Puerto: `find`, `save`, `remove`, `findPending`, `countPending` | Nuevo |
| Dominio | `src/Library/Domain/Model/StorageSync/MediaStorageFallbackActivated.php` | Evento sin agregado (`path`, `error`) | Nuevo |
| Dominio | `src/Library/Domain/Model/StorageSync/MediaStorageSynchronized.php` | Evento sin agregado (`uploaded`) | Nuevo |
| Aplicación | `src/Library/Application/Media/MediaStorageFileCopier.php` | Copia un archivo entre dos `MediaStorage` vía fichero temporal | Nuevo (extraído de la migración) |
| Aplicación | `src/Library/Application/Media/SyncStorage/SyncMediaStorageRequest.php` / `Response.php` / `Service.php` | Caso de uso de sincronización (`CommandHandler`) | Nuevo |
| Aplicación | `src/Library/Application/Media/MigrateStorage/MigrateMediaStorageService.php` | Usa `MediaStorageFileCopier` en lugar de su `copy()` privado | Modificado |
| Persistencia | `src/Library/Infrastructure/Persistence/Doctrine/Mapping/StorageSync.StorageSyncTask.orm.xml` | Tabla `media_storage_sync_tasks` | Nuevo |
| Persistencia | `src/Library/Infrastructure/Persistence/Doctrine/Repository/DoctrineStorageSyncTaskRepository.php` | Adaptador `dev`/`prod` | Nuevo |
| Persistencia | `src/Library/Infrastructure/Persistence/InMemory/Repository/InMemoryStorageSyncTaskRepository.php` | Adaptador `test` | Nuevo |
| Persistencia | `migrations/Version20261014093032.php` | Crea la tabla (creada por el usuario) | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Storage/FallbackMediaStorage.php` | Decorador principal + respaldo | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Storage/MediaStorageSelector.php` | Factoría del servicio `MediaStorage` según el flag | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Storage/InMemoryMediaStorage.php` | `markUnavailable()` / `markAvailable()` | Modificado |
| Infraestructura | `src/Library/Infrastructure/Storage/LocalMediaStorage.php` | `@mkdir` / `@copy` (`:35`, `:39`) para que los fallos lleguen como `NotStoredMediaException` (hallazgo D1) | Modificado |
| Infraestructura | `src/Library/Infrastructure/Notification/MediaStorageFallbackActivatedNotifier.php` | Aviso interno de caída a `Admin`/`SuperAdmin` | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Notification/MediaStorageSynchronizedNotifier.php` | Aviso interno de recuperación | Nuevo |
| Infraestructura | `src/Library/Infrastructure/UI/Console/SyncStorageCommand.php` | `library:storage:sync [--list]` | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml` | Flag, `library.media_storage.primary`, decorador, selector, `when@test` | Modificado |
| Shared | `src/Shared/Application/CommandBus/CommandSchedule.php:44-48` | Programado `library:sync-media-storage` cada 5 minutos | Modificado |
| Plantillas | `templates/library/storage/fallback-activated.notification.html.twig`, `synchronized.notification.html.twig` | Cuerpo de las notificaciones | Nuevo |
| Plantillas | `templates/library/translations/storage.html.twig` | Plantilla de extracción de los asuntos (cadenas planas en PHP) | Nuevo |
| Config | `.env:149-150` | `MEDIA_STORAGE_FALLBACK_ENABLED=true` | Modificado |
| Traducciones | `translations/messages+intl-icu.{es,en}.xlf` | 4 `trans-unit` nuevos | Modificado |

## Modelo de dominio

### `StorageSyncTask`

Entidad plana identificada por la ruta relativa del archivo (`MediaPath`, p. ej. `shared/{id}.{ext}` o `{cuenta}/{id}.{ext}`). Constructor promovido (`StorageSyncTask.php:14-20`): `path`, `registeredAt` (por defecto ahora), `attempts = 0`, `lastAttemptAt`, `lastError`. Único comportamiento: `recordFailure(string $error, \DateTimeImmutable $now)` (`:47-52`) incrementa `attempts`, fija `lastAttemptAt` y recorta `lastError` a 1000 caracteres (`MAX_ERROR_LENGTH`).

No hay campo de estado: **existir es estar pendiente**. Se borra en cuanto el archivo llega al principal, se descarta o se elimina.

### Eventos

Ambos extienden `App\Library\Domain\DomainEvent`, devuelven `null` en `aggregateRootId()` (patrón de `EmailSent`) y se publican con `DomainEventPublisher::instance()->publish()`:

| Evento | `eventName` | Payload | Publicado por | Condición |
|---|---|---|---|---|
| `MediaStorageFallbackActivated` | `mediaStorageFallbackActivated` | `path`, `error` | `FallbackMediaStorage::enqueue()` (`:109-118`) | `countPending()` era 0 antes de guardar la tarea |
| `MediaStorageSynchronized` | `mediaStorageSynchronized` | `uploaded` | `SyncMediaStorageService` (`:71-75`) | La pasada subió ≥ 1 archivo y la cola queda vacía |

### Estados de un archivo

| Principal | Respaldo | Tarea | Situación |
|---|---|---|---|
| sí | no | no | Normal |
| no | sí | sí | Subido durante una caída; se lee del respaldo |
| sí | sí | sí | Ventana dentro de una pasada de sincronización (copiado, aún sin borrar la copia local) |
| no | no | no | Borrado, o archivo perdido (404 como siempre) |

## Flujo de una operación

### Escritura (`store()` / `write()`)

`UploadController` / `AjaxUploadController` → `UploadMediaService` → `MediaStorage::store()` = `FallbackMediaStorage::store()` (`:25-32`), que calcula la `MediaPath` y llama a `write()` (`:34-57`):

1. Si principal y respaldo son la misma instancia (`MEDIA_STORAGE=local`), delega y termina (`:36-40`).
2. Intenta `primary->write()`; si va bien, `forget($path)` elimina una tarea previa de esa ruta (`:42-46`, `:120-127`).
3. Solo ante `NotStoredMediaException` del principal intenta `fallback->write()`; si también falla, **relanza la excepción original del principal** (`:50-54`) → 500 + correo de `ErrorReportNotifier`, como antes.
4. Si el respaldo funcionó, `enqueue()` (`:109-118`) guarda `new StorageSyncTask($path)` y, si la cola estaba vacía, publica `MediaStorageFallbackActivated` con el mensaje de la excepción del principal.

Cualquier otra excepción (p. ej. de DBAL) no se captura.

### Lectura (`read()` / `exists()`)

- `read()` (`:59-70`): principal; ante `NotStoredMediaException`, respaldo; si tampoco, relanza la del principal → `ShowController` responde 404.
- `exists()` (`:97-107`): `true` si el principal lo tiene; si devuelve `false` **o lanza**, responde lo que diga el respaldo.

Con el principal caído cada lectura paga el timeout del principal antes de ir al respaldo.

### Borrado (`delete()`)

`RemoveController` → `RemoveMediaService` (marca `removed` y persiste **antes** de `delete()`, `RemoveMediaService.php:41-45`) → `FallbackMediaStorage::delete()` (`:72-95`):

1. Ruta vacía → nada. Misma instancia → delega.
2. Si la ruta tiene tarea: borra la copia local y la tarea, sin tocar el principal (`:84-91`).
3. Si no: `primary->delete()` (si lanza, se propaga) y después `fallback->delete()` para limpiar copias residuales.

Si el principal lanza, `RemoveController.php:40-42` captura `\Exception` y muestra el flash «No se ha podido eliminar el archivo {media}». Como el `Media` ya se persistió como eliminado, desaparece del listado aunque el objeto siga en el bucket (deuda preexistente, aceptada en el plan).

### Sincronización

`app:schedule:run` (cron) → `CommandSchedule` (`library:sync-media-storage`, `everyMinutes(…, 5)`, `DeferredCommand` en cola `LOW`) → `SyncMediaStorageService::__invoke()` (`SyncMediaStorageService.php:30-84`):

1. Si `primary === fallback` o `countPending() === 0` → respuesta con todo a 0 (`:32-39`).
2. `findPending($batchSize)` (100 por defecto, orden `registeredAt ASC, path ASC`). Por tarea:
   - archivo ausente del respaldo → `remove(task)`, `dropped++` (`:50-55`);
   - `copier->copy($path, fallback, primary)`, `fallback->delete()`, `remove(task)`, `uploaded++` (`:57-60`);
   - cualquier `\Throwable` → `recordFailure()`, `save()`, `failed = 1` y **`break`** (`:61-68`): se aborta la pasada; el resto espera al siguiente tick. Sin límite de intentos.
3. Si `uploaded > 0` y la cola queda vacía → `MediaStorageSynchronized` (`:71-75`).

El servicio no lanza, así que Messenger no reintenta el mensaje; la cadencia la marca el cron.

### Notificaciones

`MediaStorageFallbackActivatedNotifier` y `MediaStorageSynchronizedNotifier` (`src/Library/Infrastructure/Notification/`) calculan los destinatarios como la unión por id de `UserFilter(role: Role::Admin, limit: 0)` y `UserFilter(role: Role::SuperAdmin, limit: 0)` (`:64-75` / `:63-74`) y envían una `SystemNotification` **solo por `NotificationChannel::Internal`**, con `accountId` del destinatario:

| Notificador | Asunto | Plantilla | Params |
|---|---|---|---|
| `MediaStorageFallbackActivatedNotifier` | «El almacenamiento de la biblioteca no está disponible» | `@library/storage/fallback-activated.notification.html.twig` | `path`, `error` (la plantilla solo usa `error`) |
| `MediaStorageSynchronizedNotifier` | «El almacenamiento de la biblioteca se ha recuperado» | `@library/storage/synchronized.notification.html.twig` | `uploaded` |

El `error` es el `getMessage()` de `NotStoredMediaException`, que en los adaptadores solo lleva la ruta (sin credenciales); se escapa con Twig. Con `INTERNAL_NOTIFICATIONS_ENABLED=false` no hay aviso (`DispatchingSystemNotifier`).

## Autorización y ámbito

- No hay pantallas, rutas web ni formularios nuevos. Los casos de uso web siguen con sus `MediaAuthorizer` de siempre.
- `SyncMediaStorageService` no tiene `Authorizer` (operación de sistema, como `MigrateMediaStorageService`); solo es alcanzable desde `CommandSchedule` y la consola.
- `SyncStorageCommand` invoca el servicio con `UserMother::systemUser()` (`SyncStorageCommand.php:11,46-49`), mismo patrón que `MigrateStorageCommand` (deuda B4, descartada en el triaje).
- Las tareas y el respaldo son globales (no por cuenta): las rutas ya incluyen la cuenta o `shared`.

## Persistencia y migración

Tabla `media_storage_sync_tasks` (`StorageSync.StorageSyncTask.orm.xml`):

| Columna | Tipo | Nulo |
|---|---|---|
| `path` (PK, `generator NONE`) | `VARCHAR(512)` | no |
| `attempts` | `INT` | no |
| `last_attempt_at` | `DATETIME` (`datetime_immutable`) | sí |
| `last_error` | `LONGTEXT` | sí |
| `registered_at` | `DATETIME` (`datetime_immutable`) | no |

Índice `IDX_media_storage_sync_tasks_registered_at`. Migración: `migrations/Version20261014093032.php` (equivalente a `migration.sql` salvo el `DEFAULT 0` de `attempts`, sin impacto). Sin backfill. Según `review.md` estaba **creada pero sin aplicar** en `dev` en la revisión 2: hay que ejecutar `make migrate` antes de desplegar. Sin la tabla, con `MEDIA_STORAGE=ovh` falla toda escritura (el camino feliz llama a `forget()` → `find()`), y con `local` falla el programado cada 5 minutos (`countPending()`).

`DoctrineStorageSyncTaskRepository` hace `persist`/`remove` + `flush` inmediato en cada operación: la tarea queda guardada aunque la petición de subida falle después (el archivo sí está en disco).

## Configuración y operación

| Elemento | Detalle |
|---|---|
| `MEDIA_STORAGE_FALLBACK_ENABLED` | `.env:150`, `true` por defecto. Parámetro `app.media_storage_fallback_enabled` (`services.yaml:4`). Con `false`, `MediaStorageSelector::select()` devuelve el principal sin decorador. |
| `MEDIA_STORAGE` | Con `local` el principal **es** `LocalMediaStorage` y el decorador solo delega: no hay fallback disco a disco. |
| Wiring (`services.yaml:36-56`) | `library.media_storage.primary` = `MediaStorageRegistry::default()`; `FallbackMediaStorage(primary, LocalMediaStorage)`; servicio `MediaStorage` = `MediaStorageSelector::select()`; `SyncMediaStorageService(primary, LocalMediaStorage)`. |
| `when@test` (`services.yaml:58-102`) | `MediaStorage` → alias a `FallbackMediaStorage` (el flag no aplica); principal = `InMemoryMediaStorage` (driver `local`), respaldo = `library.media_storage.fallback_in_memory`; `_defaults` propios para que las redefiniciones reciban `StorageSyncTaskRepository`. |
| Programado | `library:sync-media-storage`, cada 5 minutos, cola `low`; requiere el cron de `app:schedule:run` y un worker (`make queues-low`). |
| Consola | `make sf c="library:storage:sync"` sincroniza y muestra la tabla Resultado/Archivos (`subidos`, `descartados`, `fallidos`, `pendientes`) y la lista de pendientes (Ruta, Intentos, Último intento, Último error) o «No hay archivos pendientes». Sale con `FAILURE` si `failed > 0`. `make sf c="library:storage:sync --list"` solo lista. |
| Varios nodos | El respaldo es el disco del nodo que atiende la petición; el worker y el cron deben ver ese disco. Con varios nodos: volumen compartido para `var/uploads/library` o desactivar el flag. |

Operación tras una caída larga: `--list` para ver lo acumulado y `library:storage:sync` para vaciar sin esperar al tick (procesa como mucho 100 por pasada).

Antes de pasar de `ovh` a `local` conviene vaciar la cola: con `primary === fallback` la sincronización no hace nada y las filas quedan visibles en `--list` (hallazgo B2, descartado; se reparan solas al volver a `ovh`).

## Cómo extenderlo

- **Otro almacén principal** (otro driver en `MediaStorageRegistry`): basta con que su adaptador convierta cualquier fallo de E/S en `NotStoredMediaException` (el decorador solo reacciona a esa excepción) y que `exists()` distinga «no existe» (`false`) de «no puedo saberlo» (lanzar).
- **Otro almacén de respaldo**: cambiar `$fallback` de `FallbackMediaStorage` **y** de `SyncMediaStorageService` en `services.yaml`; ambos deben apuntar a la misma instancia.
- **Fallback para otros almacenes** (`ExportStorage`, `SignatureFileStorage`, `ManualStorage`): fuera de alcance; reutilizar el patrón de decorador + `StorageSyncTask` exigiría una tarea por almacén o un campo de tipo.
- **Copias entre almacenes**: usar `MediaStorageFileCopier::copy($path, $source, $target)`; lo comparten la migración y la sincronización.

Trampas conocidas:

- `MediaStorageFallbackActivated` puede emitirse dos veces en la misma caída (subida → borrado de esa subida → otra subida, o dos subidas concurrentes con la cola vacía). Hallazgo B1, aceptado.
- `enqueue()` guarda siempre una instancia **nueva** de `StorageSyncTask`. `InMemoryStorageSyncTaskRepository` sustituye la tarea de la ruta; `DoctrineStorageSyncTaskRepository::save()` hace `persist()` de la instancia nueva, así que si alguna vez se encolara dos veces la misma ruta Doctrine intentaría insertar una PK duplicada. Hoy no ocurre (cada `Media` tiene una ruta única y `write()` sobre una ruta existente solo lo usan la migración y la sincronización, que no pasan por el decorador); no se ha comprobado en ejecución.
- `exists()` sobre un `InMemoryMediaStorage` marcado indisponible lanza: en Behat, comprobar el principal después de `the main media storage is available again`.
- Los asuntos de las notificaciones son cadenas planas en PHP; cualquier asunto nuevo debe añadirse a `templates/library/translations/storage.html.twig` para que `make translation` lo extraiga.

## Tests

Unitarios:

| Fichero | Cubre |
|---|---|
| `tests/Unit/Library/Domain/Model/StorageSync/StorageSyncTaskTest.php` (+ `StorageSyncTaskMother.php`) | `recordFailure()` y recorte a 1000 caracteres |
| `tests/Unit/Library/Infrastructure/Persistence/InMemory/InMemoryStorageSyncTaskRepositoryTest.php` | CRUD, orden y límite de `findPending()`, `countPending()` |
| `tests/Unit/Library/Infrastructure/Storage/InMemoryMediaStorageTest.php` | Modo indisponible |
| `tests/Unit/Library/Infrastructure/Storage/FallbackMediaStorageTest.php` | 15 casos: escritura con principal disponible/caído, un solo aviso por caída, ambos caídos, `forget`, lecturas, `exists`, borrados, misma instancia, e integración con `LocalMediaStorage` real (`testStoreFallsBackToTheLocalDiskWithThePrimaryUnavailable`) |
| `tests/Unit/Library/Infrastructure/Storage/LocalMediaStorageTest.php` | Adaptador de disco real en directorio temporal, incluidos los fallos de `mkdir`/`copy` |
| `tests/Unit/Library/Infrastructure/Storage/MediaStorageSelectorTest.php` | Flag activo/inactivo |
| `tests/Unit/Library/Application/Media/MediaStorageFileCopierTest.php` | Copia y origen inexistente |
| `tests/Unit/Library/Application/Media/SyncStorage/SyncMediaStorageServiceTest.php` | Subida + evento, descarte, parada en el primer fallo, cola vacía, `batchSize`, misma instancia |
| `tests/Unit/Library/Infrastructure/Notification/*NotifierTest.php` (+ `SpySystemNotifier.php`) | Destinatarios `Admin`/`SuperAdmin` sin duplicados, canal interno, asunto, plantilla y params |
| `tests/Unit/Shared/Application/CommandBus/CommandScheduleTest.php` | `library:sync-media-storage` cada 5 minutos |
| `tests/Unit/Library/Application/Media/MigrateStorage/MigrateMediaStorageServiceTest.php` | Sin cambios de casos; construye el copiador |

Aceptación: `tests/Acceptance/Library/media-storage-fallback.feature` (9 escenarios: subida con aviso, segunda subida sin aviso, descarga desde el respaldo, 404 de archivo solo en el principal, borrado fallido con flash, borrado de un pendiente, sincronización con aviso de recuperación, sincronización fallida con salida 1, `--list`). Pasos en `tests/Acceptance/Library/LibraryContext.php:238-320` (`the main media storage is unavailable`, `… is available again`, `the last uploaded media is (not) stored in the :storage storage`, `there are :count pending storage sync tasks`, …) y `tests/Acceptance/Notifications/NotificationsContext.php:58` (`user :username has :count notifications with subject :subject`).

Ejecución:

```bash
make test-unit options="--filter 'FallbackMediaStorage|SyncMediaStorage|StorageSyncTask|LocalMediaStorage|MediaStorageSelector|MediaStorageFileCopier'"
make test-acceptance options="tests/Acceptance/Library/media-storage-fallback.feature"
```

Huecos: el decorador en `dev`/`prod` con OVH real caído no se ha probado a mano (`plan.md`, `## Implementación`, paso 7); el `DoctrineStorageSyncTaskRepository` no tiene test propio (la suite usa el `InMemory`).

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`), `review.md` y `review-fix.md`:

- El escenario de borrado fallido comprueba el flash «No se ha podido eliminar el archivo …» (el controlador ya capturaba `\Exception`); la ruta real de borrado es `/library/media/remove/{mediaId}`.
- `LocalMediaStorage` se modificó (`@mkdir`, `@copy`) aunque el plan lo listaba sin cambios (D1, corregido).
- Migración creada (`Version20261014093032.php`); aplicar con `make migrate` antes de desplegar.
- Deuda aceptada: aviso duplicado posible (B1), tareas huérfanas al pasar de `ovh` a `local` (B2), `UserMother::systemUser()` desde `src/` (B4), `RemoveMediaService` persiste `removed` antes de borrar el archivo.
