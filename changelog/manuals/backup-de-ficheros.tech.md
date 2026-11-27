# Copia de seguridad de los archivos de la biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-24 · **Bounded Contexts:** `Library`, `Shared` (`CommandSchedule`, puerto `UnitOfWorkReleaser`)

## Qué hace y por qué

Con `MEDIA_STORAGE=ovh` los archivos de la biblioteca no tienen más copia que el propio bucket. Una tarea diaria (04:00 UTC, cola `LOW`) copia a un segundo almacén (el *almacén de copia*) cada archivo no eliminado que aún no esté copiado, conserva la copia de los archivos eliminados de la biblioteca durante `MEDIA_BACKUP_RETENTION_DAYS` días y después la purga. Si la pasada tiene errores, los `SuperAdmin` reciben un aviso interno. La restauración es solo por consola. No hay pantallas, rutas ni menús.

Decisiones clave (tabla «Decisiones tomadas» de `plan.md`): el almacén de copia es otro adaptador de `MediaStorage` elegido por configuración (`local` u `ovh`, cualquier combinación con el principal); el estado «qué está copiado» vive en una tabla propia `media_backups` (no en `media`, el agregado `Media` no conoce almacenes); los eliminados se siguen por el evento `MediaRemoved`; los servicios no tienen `Authorizer` porque solo son alcanzables desde el planificador y la consola.

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/Library/Domain/Model/Backup/MediaBackup.php` | Entidad plana `#[Exclude]` (clave `path`, `mediaId`, `backedUpAt`, `removedAt`) | nuevo |
| Dominio | `src/Library/Domain/Model/Backup/MediaBackupRepository.php` | Puerto con consultas con nombre (sin `Filter`) | nuevo |
| Dominio | `src/Library/Domain/Model/Backup/MediaBackupFailed.php` | Evento sin agregado (`failed`, `error`) | nuevo |
| Dominio | `src/Library/Domain/Model/Backup/MediaBackupNotFoundException.php` | La lanza la extracción | nuevo |
| Dominio | `src/Library/Domain/Event/Backup/MarkMediaBackupRemovedOnMediaRemoved.php` | Suscriptor de `MediaRemoved`: anota `removedAt` | nuevo |
| Aplicación | `src/Library/Application/Media/RunBackup/{RunMediaBackupRequest,Response,Service}.php` | Pasada de copia + purga | nuevo |
| Aplicación | `src/Library/Application/Media/RestoreBackup/{…Request,Response,Service}.php` | Restauración al almacén principal | nuevo |
| Aplicación | `src/Library/Application/Media/ExtractBackup/{…Request,Response,Service}.php` | Extracción de una copia a un fichero local | nuevo |
| Shared | `src/Shared/Application/Persistence/UnitOfWorkReleaser.php` | Puerto `release(): void` | nuevo |
| Shared | `src/Shared/Infrastructure/Persistence/Doctrine/DoctrineUnitOfWorkReleaser.php` | `EntityManager::clear()` | nuevo |
| Shared | `src/Shared/Infrastructure/Persistence/InMemory/NullUnitOfWorkReleaser.php` | No hace nada (`test`) | nuevo |
| Shared | `src/Shared/Application/CommandBus/CommandSchedule.php` | Tarea `library:run-media-backup` | modificado |
| Infra | `src/Library/Infrastructure/Persistence/Doctrine/Mapping/Backup.MediaBackup.orm.xml` | Tabla `media_backups` | nuevo |
| Infra | `src/Library/Infrastructure/Persistence/Doctrine/Repository/DoctrineMediaBackupRepository.php` | `#[When('dev')]`/`#[When('prod')]` | nuevo |
| Infra | `src/Library/Infrastructure/Persistence/InMemory/Repository/InMemoryMediaBackupRepository.php` | `#[When('test')]` | nuevo |
| Infra | `src/Library/Infrastructure/Storage/MediaBackupStorageFactory.php` | Resuelve y valida el almacén de copia | nuevo |
| Infra | `src/Library/Infrastructure/UI/Console/{RunBackupCommand,RestoreBackupCommand}.php` | `library:backup:run`, `library:backup:restore` | nuevo |
| Infra | `src/Library/Infrastructure/Notification/MediaBackupFailedNotifier.php` | Aviso interno a `SuperAdmin` | nuevo |
| Infra | `templates/library/backup/failed.notification.html.twig`, `templates/library/translations/backup.html.twig` | Cuerpo del aviso y clave de extracción del asunto | nuevo |
| Config | `src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml`, `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml`, `.env` | Cableado y variables | modificado |
| Migración | `migrations/Version20261124161158.php` | Crea `media_backups` | nuevo |

## Modelo de dominio

`MediaBackup` no es un agregado: una fila de `media_backups` significa «este archivo está en el almacén de copia».

| Biblioteca | Fila | Situación |
|---|---|---|
| activo | no | Pendiente, se copia en la siguiente pasada |
| activo | sí, `removedAt = null` | Copiado |
| eliminado | sí, `removedAt = fecha` | En retención hasta `removedAt + retentionDays` |
| eliminado | no | Purgado, o eliminado antes de copiarse nunca |

Invariantes: la fila se guarda **después** del `write()` y se borra **después** del `delete()` (`RunMediaBackupService.php:136-137,167-168`); `markRemoved()` no sobrescribe un `removedAt` ya informado (`MediaBackup.php:44-52`). La fecha límite de retención se calcula en el servicio, no en la entidad.

`MediaBackupFailed` (`MediaBackupFailed.php`): `$eventName = 'mediaBackupFailed'`, `aggregateRootId()` devuelve `null`. `MediaBackupNotFoundException` lleva el `mediaId`.

## Flujo de una operación

### Pasada programada o `library:backup:run`

1. `CommandSchedule.php:50-54` programa `RunMediaBackupRequest` con `ScheduledCommand::dailyAt` (envuelve en `DeferredCommand`, cola `LOW`); lo ejecuta el worker `async_low`. La consola lo invoca directamente (`RunBackupCommand.php:29`).
2. `RunMediaBackupService::__invoke` (`:52`): con `enabled=false` devuelve la respuesta a cero sin tocar nada.
3. **Copia** (`copy()`, `:100`): pagina todos los `Media` con `MediaFilter(limit: batchSize)`, `findExistingPaths()` por lote (una consulta, sin tocar el almacén de copia) y, para cada ruta sin fila: `source->exists()` (si no existe, `missingInSource`), `copier->copy()` (`MediaStorageFileCopier`) y `backups->save(new MediaBackup(...))`. El origen es el servicio `MediaStorage` (decorado con fallback), así que un archivo que solo esté en el disco de respaldo también se copia (`services.yaml` Library, `$source`).
4. Un fallo por archivo se acumula (`recordFailure`, `:181`) y se sigue; al llegar a `maxFailures` (20) `copy()` devuelve `false`, se aborta y **no se purga** (`:65-67,143`).
5. **Purga** (`purge()`, `:154`), solo con `retentionDays > 0`: `findRemovedBefore(now − retentionDays días, batchSize)` en bucle; por fila `backup->delete()` y `backups->remove()`. Se detiene en el primer fallo (evita un bucle sobre una fila que no se puede borrar).
6. Tras cada lote se llama a `UnitOfWorkReleaser::release()` (`:148,:177`), que en producción hace `EntityManager::clear()` para que la memoria no crezca con el tamaño de la biblioteca.
7. Cualquier `\Throwable` fuera del tratamiento por archivo (base de datos, configuración del almacén) se registra, publica `MediaBackupFailed(count(fallos)+1, mensaje)` y se relanza (`:70-78`). Si solo hubo fallos por archivo, se publica un `MediaBackupFailed(count, primer fallo)` (`:80-82`).
8. `MediaBackupFailedNotifier` (`:43-58`) crea una `SystemNotification` interna por cada usuario con `Role::SuperAdmin` (`UserFilter(role: SuperAdmin, limit: 0)`); los `Admin` no la reciben.

La consola devuelve `FAILURE` si hay `failures` (`RunBackupCommand.php:62`). Si la copia está desactivada muestra un aviso y sale con éxito.

### Eliminación en la biblioteca

`RemoveMediaService` borra el archivo del almacén principal y publica `MediaRemoved`; `MarkMediaBackupRemovedOnMediaRemoved::handle` busca la fila por ruta y le pone `removedAt = occurredOn()`. Sin fila o con ruta vacía no hace nada. La copia sigue intacta en el almacén de copia hasta la purga. El suscriptor consulta `media_backups` en **cada** eliminación, también con la copia desactivada.

### Restauración (`library:backup:restore`)

`RestoreMediaBackupService` recorre los `Media` no eliminados (o uno, con `--media`; id inexistente lanza `MediaNotFoundException`). Destino: `library.media_storage.primary` directamente, no el decorador. Por archivo: ya en principal → `alreadyInPrimary`; ausente en la copia → `missingInBackup`; con `--dry-run` cuenta sin escribir; si no, copia de la copia al principal. No toca `media_backups` ni publica eventos.

### Extracción (`library:backup:restore --media=<id> --output=<ruta>`)

`ExtractMediaBackupService` busca la fila con `findByMediaId` (sin `MediaRepository`, así que funciona con archivos eliminados mientras dure la retención), falla si `outputPath` ya existe y vuelca el flujo con `stream_copy_to_stream` (`fopen` modo `xb`). `--output` sin `--media` sale con `Command::INVALID` (`RestoreBackupCommand.php:54`). Un `InvalidArgumentException` o `MediaBackupNotFoundException` da `FAILURE`.

## Autorización y ámbito

Sin `Authorizer`: los tres servicios son operaciones de sistema, alcanzables solo desde `CommandSchedule` y consola, con el precedente de `MigrateMediaStorageService` y `SyncMediaStorageService`. Los comandos usan `UserMother::systemUser()` importado de `tests/` (`RunBackupCommand.php:9`, `RestoreBackupCommand.php:14`); deuda aceptada. No intervienen impersonación ni roles, salvo el destinatario del aviso (`SuperAdmin`). Activación global con `MEDIA_BACKUP_ENABLED`.

## Persistencia y migración

Tabla `media_backups` (`Backup.MediaBackup.orm.xml`): `path` `VARCHAR(512)` PK (`generator NONE`), `media_id` `VARCHAR(36)`, `backed_up_at` y `removed_at` (`datetime_immutable`, esta nula); índices `IDX_media_backups_media_id` e `IDX_media_backups_removed_at`. Migración **creada y presente**: `migrations/Version20261124161158.php` (coincide con `migration.sql`). Sin backfill: la primera pasada rellena la tabla. Debe aplicarse antes de desplegar el código.

`DoctrineMediaBackupRepository`: `save`/`remove` con `flush` inmediato; `findExistingPaths` devuelve las rutas con fila (lista vacía → `[]` sin consultar); `findRemovedBefore` filtra `removedAt <= límite`, ordena `removedAt ASC, path ASC` y aplica `max(1, $max)`. `InMemoryMediaBackupRepository` tiene la misma semántica.

Cambio relacionado fuera del BC de la copia: `DoctrineMediaRepository` añade un desempate `addOrderBy('m.id', …)` (línea 183) para que la paginación por `registeredAt` (precisión de segundo, subidas múltiples) no salte archivos; beneficia también a `RestoreBackup` y `MigrateStorage`.

## Configuración y operación

Variables (`.env`, bloque `media backup`):

| Variable | Defecto | Efecto |
|---|---|---|
| `MEDIA_BACKUP_ENABLED` | `false` | Desactivada, la tarea no copia ni purga |
| `MEDIA_BACKUP_STORAGE` | `local` | `local` u `ovh`; desconocido → `InvalidArgumentException` en el primer uso |
| `MEDIA_BACKUP_DIR` (comentada) | `var/backups/library` | Directorio de la copia `local`; debe quedar **sin definir**, no vacía (`env(default:…)`) |
| `MEDIA_BACKUP_OVH_BUCKET` (comentada) | `OVH_S3_BUCKET` | Bucket de la copia `ovh` |
| `MEDIA_BACKUP_OVH_PREFIX` | `library-backup` | Prefijo de la copia `ovh` |
| `MEDIA_BACKUP_RETENTION_DAYS` | `30` | `0` = no purgar nunca |

Cableado (`src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml`): `library.media_storage.backup_local` y `backup_ovh` (segundas instancias de los adaptadores, mismo `S3Client`), registro `library.media_backup_storage.registry` y `MediaBackupStorageFactory`. El servicio `library.media_storage.backup` se crea con esa factoría y es `lazy: true`: los `CommandHandler` se instancian en cada petición y comando, y sin proxy una configuración errónea tiraría toda la aplicación aunque la copia esté desactivada; así el error aparece en el primer uso.

`MediaBackupStorageFactory::create()` lanza `\InvalidArgumentException('The backup storage must not use the same location as the main storage')` si principal y copia usan el mismo driver y la misma ubicación (comparación tras `rtrim('/')`), o si la copia es `local` y su directorio es igual, anida o contiene el directorio del fallback (`app.media_dir`, `var/uploads/library`), normalizando con `realpath()` o resolviendo `.`/`..` (`MediaBackupStorageFactory.php:23-86`). Motivo: `FallbackMediaStorage` borra de ese directorio al eliminar y al sincronizar.

Combinaciones (`MEDIA_STORAGE` / `MEDIA_BACKUP_STORAGE`): `ovh`/`local` (copia en el servidor), `local`/`ovh`, `ovh`/`ovh` (misma cuenta: protege del borrado, no de la pérdida de la cuenta; requiere otro bucket o prefijo) y `local`/`local` (otro directorio o disco).

Comandos:

- `make sf c="library:backup:run"` — pasada manual. Lanzarla a mano la primera vez en bibliotecas grandes (es reanudable: cada archivo copiado deja su fila) y no coincidir con las 04:00 UTC.
- `make sf c="library:backup:restore --dry-run"` / `make sf c="library:backup:restore"` / `make sf c="library:backup:restore --media=<id>"`.
- `make sf c="library:backup:restore --media=<id> --output=/tmp/archivo.pdf"`.

Parámetros del servicio no expuestos como variable: `batchSize` 200 y `maxFailures` 20 (`RunMediaBackupRequest.php`). Cambiar de `MEDIA_BACKUP_STORAGE` no traslada las copias existentes: hay que vaciar `media_backups` (`DELETE FROM media_backups`) para que la siguiente pasada copie todo al nuevo destino.

Traducciones: el asunto «La copia de seguridad de la biblioteca ha fallado» y el cuerpo están en `translations/messages+intl-icu.{es,en}.xlf`; el asunto se extrae vía `templates/library/translations/backup.html.twig`.

## Cómo extenderlo

Añadir un destino nuevo (p. ej. Google Drive):

1. Clase que implemente `MediaStorage` (`store()` = `MediaPath::from()` + `write()`, como los actuales).
2. Una entrada en `$storages` de `library.media_backup_storage.registry` y su ubicación en `$backupLocations` de `MediaBackupStorageFactory` (para que se detecte la ubicación repetida).
3. Valor nuevo para `MEDIA_BACKUP_STORAGE`.

Trampas: el origen debe seguir siendo el `MediaStorage` decorado; no mover la copia `local` a `var/uploads/library`; cualquier recorrido nuevo de todos los `Media` debe llamar a `UnitOfWorkReleaser::release()` por lote y no mantener entidades gestionadas entre lotes.

## Tests

- Unitarios: `tests/Unit/Library/Domain/Model/Backup/MediaBackupTest.php`, `tests/Unit/Library/Domain/Event/Backup/…`, `tests/Unit/Library/Application/Media/{RunBackup,RestoreBackup,ExtractBackup}/…ServiceTest.php`, `tests/Unit/Library/Infrastructure/Persistence/InMemory/InMemoryMediaBackupRepositoryTest.php`, `tests/Unit/Library/Infrastructure/Notification/MediaBackupFailedNotifierTest.php`, `tests/Unit/Library/Infrastructure/Storage/MediaBackupStorageFactoryTest.php`, `tests/Unit/Shared/Infrastructure/Persistence/Doctrine/DoctrineUnitOfWorkReleaserTest.php`, el caso de `CommandScheduleTest` y `MediaRepositoryTest::testPaginatingOneByOneRecoversEveryMediaRegisteredAtTheSameTime`.
- Aceptación: `tests/Acceptance/Library/media-backup.feature` (9 escenarios: copia, segunda pasada, eliminación conserva la copia, purga a 31 días, retención a 10 días, aviso a `SuperAdmin`, restauración, extracción, `--output` sin `--media`). En `when@test` el servicio se cablea con `$enabled: true` y `$retentionDays: 30` literales, el almacén de copia es `library.media_storage.backup_in_memory` y el liberador es `NullUnitOfWorkReleaser`, por lo que no hace falta tocar `TEST_CONT` del `Makefile`.
- Ejecución: `make test-unit options="--filter Backup"`, `make test-acceptance options="tests/Acceptance/Library/media-backup.feature"`.
- Huecos: `DoctrineMediaBackupRepository` no tiene test de integración propio (tampoco `DoctrineStorageSyncTaskRepository`); no hay test del cableado real de `library.media_storage.backup` fuera de `test`.

## Desviaciones y deuda

- Desviaciones declaradas en `plan.md` (`## Implementación`): `lazy: true` en `library.media_storage.backup`, pasos de Behat renombrados, petición web previa en el escenario 6 y `backupPaths` en `LibraryContext`.
- Cambios de la revisión (`review.md`, todos corregidos, sin hallazgos abiertos) que se apartan del plan: desempate por id en `DoctrineMediaRepository` (A1), comprobación de ubicación contra el directorio del fallback y anidamiento (M1), puerto `UnitOfWorkReleaser` en `Shared` y `try/catch` de pasada que avisa y relanza (M2).
- Limitaciones conocidas: un archivo subido y eliminado entre dos pasadas (hasta 24 h) no llega a copiarse; si el fallo es un `flush` que cierra el `EntityManager`, el aviso tampoco puede persistirse; `OvhMediaStorage::read()` carga el objeto entero en `php://temp`; dos pasadas simultáneas pueden contar como error una clave duplicada; `UserMother` de `tests/` en los comandos.
- Enlaces: `plan.md`, `review.md`.
