# Almacenamiento de la biblioteca en OVH Object Storage — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-13 (correcciones de review: 2026-10-14, ver `review-fix.md`) · **Bounded Contexts:** `Library`, `Shared`

## Qué hace y por qué

Los archivos de la biblioteca (`Media`) se guardaban siempre en disco, bajo `var/uploads/library`. Ahora el almacén físico es configurable con la variable `MEDIA_STORAGE`: `local` (disco, valor por defecto) u `ovh` (un bucket de OVH Object Storage accedido por su API S3). Cambiar de almacén es un cambio de configuración: ni el esquema ni los datos de la tabla `media` cambian, porque la ruta relativa que guarda `Media::path()` (`{accountId|shared}/{mediaId}.{ext}`) es la misma en ambos almacenes.

La solución es un adaptador nuevo (`OvhMediaStorage`) del puerto existente `MediaStorage`, un registro de adaptadores (`MediaStorageRegistry`) que elige el activo, y un comando de consola idempotente (`library:storage:migrate`) que copia los archivos de un almacén a otro en cualquier sentido. Los casos de uso de subida, descarga, descarga pública y borrado no cambian: siguen hablando con el puerto. Los archivos se siguen sirviendo a través de PHP (no hay URLs prefirmadas), así que la autorización y las URLs públicas son las mismas.

Las decisiones (librería `async-aws/s3`, alcance limitado a la biblioteca, sin columna `storage` en `Media`, procedimiento de corte con modo mantenimiento) están razonadas en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Library/Domain/Model/Media/MediaPath.php` | Deriva la ruta relativa `{accountId\|shared}/{mediaId}[.{ext}]` (`:11-16`) | Nuevo |
| Dominio | `src/Library/Domain/Model/Media/MediaStorage.php` | Puerto; gana `write(string $path, string $localPath): void` (`:13`) | Modificado |
| Aplicación | `src/Library/Application/Media/MigrateStorage/MigrateMediaStorageRequest.php` | `Command` con `source`, `target`, `deleteSource`, `dryRun`, `batchSize = 200` | Nuevo |
| Aplicación | `src/Library/Application/Media/MigrateStorage/MigrateMediaStorageResponse.php` | Contadores `migrated`, `alreadyInTarget`, `missingInSource`, `deletedFromSource` y `failures` (`list<string>`) | Nuevo |
| Aplicación | `src/Library/Application/Media/MigrateStorage/MigrateMediaStorageService.php` | `CommandHandler` que recorre los `Media` no eliminados y copia los archivos | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Storage/OvhMediaStorage.php` | Adaptador S3 (`#[When('dev')]`, `#[When('prod')]`) | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Storage/MediaStorageRegistry.php` | Registro `driver → MediaStorage`, `get()`, `default()`, `drivers()` | Nuevo |
| Infraestructura | `src/Library/Infrastructure/Storage/LocalMediaStorage.php` | `store()` = `MediaPath::from()` + `write()` | Modificado |
| Infraestructura | `src/Library/Infrastructure/Storage/InMemoryMediaStorage.php` | Igual que el local, en memoria (`#[When('test')]`) | Modificado |
| Infraestructura | `src/Library/Infrastructure/UI/Console/MigrateStorageCommand.php` | Comando `library:storage:migrate` | Nuevo |
| Infraestructura | `src/Shared/Infrastructure/Storage/Ovh/OvhS3ClientFactory.php` | Construye el `AsyncAws\S3\S3Client` de OVH | Nuevo |
| Configuración | `src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetro `app.media_storage`, registro, factory del puerto, consola, dobles de `test` | Modificado |
| Configuración | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros `ovh.s3.*` (`:11-16`), factoría (`:81-87`), `S3Client` `lazy` (`:89-91`) | Modificado |
| Configuración | `.env` (`:140-149`), `composer.json` | Bloque `media storage`; dependencia `async-aws/s3 ^3.5` (instalada 3.5.0 con `async-aws/core` 1.30.0) | Modificado |
| Tests | `tests/Unit/Library/Domain/Model/Media/MediaPathTest.php` | Derivación de rutas | Nuevo |
| Tests | `tests/Unit/Library/Infrastructure/Storage/{InMemoryMediaStorageTest,OvhMediaStorageTest,MediaStorageRegistryTest}.php` | Adaptadores y registro | Nuevo |
| Tests | `tests/Unit/Shared/Infrastructure/Storage/Ovh/OvhS3ClientFactoryTest.php` | Factoría | Nuevo |
| Tests | `tests/Unit/Library/Application/Media/MigrateStorage/MigrateMediaStorageServiceTest.php` | Servicio de migración | Nuevo |
| Tests | `tests/Unit/Library/Domain/Model/Media/MediaStorageMother.php` | `inMemoryWith(array $files)` | Modificado |
| Tests | `tests/Acceptance/Library/media-storage-migrate.feature`, `tests/Acceptance/Library/LibraryContext.php` | Escenarios del comando y tres pasos nuevos | Nuevo / modificado |

## Modelo de dominio

- **Sin cambios en el agregado.** `Media` no sabe en qué almacén vive su archivo; no hay eventos nuevos y la migración no registra ninguno (el servicio no toca el agregado).
- **`MediaPath::from(MediaId, ?AccountId, string $extension): string`** (`MediaPath.php:11-16`) es la única derivación de la ruta; la usan los tres adaptadores en `store()`. Con extensión vacía no añade el punto.
- **Puerto `MediaStorage`** (`MediaStorage.php`):
  - `store(...)`: deriva la ruta, guarda y la devuelve (la usa `UploadMediaService.php:42`).
  - `write($path, $localPath)`: guarda un fichero local bajo una ruta ya conocida, sobrescribiendo. La migración usa `write()` con `$media->path()` para no depender de que la derivación actual coincida con la histórica.
  - `read($path)`: devuelve un `resource` legible; `delete($path)`; `exists($path)`.
  - Todos los errores de almacenamiento se expresan como `NotStoredMediaException`.

### Contrato de `OvhMediaStorage`

| Método | Petición S3 | Error |
|---|---|---|
| `write()` (`:36-59`) | `PUT` con `Body` = stream del fichero, `ContentLength`, `ContentType` detectado con `finfo` (fallback `application/octet-stream`) | Fichero local ilegible o cualquier `AsyncAws\Core\Exception\Exception` → `NotStoredMediaException('Unable to store media file at …')`; el stream se cierra en `finally` |
| `read()` (`:61-71`) | `GET`; devuelve `getBody()->getContentAsResource()` (objeto completo volcado a `php://temp`, no streaming puro) | `NoSuchKey` y demás → `NotStoredMediaException('Unable to read media file …')` |
| `delete()` (`:73-87`) | `DELETE`; ruta vacía no hace nada | → `NotStoredMediaException('Unable to delete media file …')` |
| `exists()` (`:89-109`) | `HEAD` vía `objectExists()->getState()`, leído **una sola vez** | `STATE_SUCCESS` → `true`; `STATE_PENDING` (404) → `false`; `STATE_FAILURE` (403, 5xx…) → `NotStoredMediaException('Unable to check media file …')`; error de red (`NetworkException`) → `NotStoredMediaException` con la causa encadenada |

La clave del objeto es `{OVH_S3_PREFIX}/{ruta}` sin barras duplicadas; con prefijo vacío, la ruta sin `/` inicial (`key()`, `:111-116`).

> `exists()` distingue «no existe» de «error consultando el bucket» desde la corrección M1 de `review-fix.md`. Llamar a `isSuccess()` y luego a `isFailure()` relanzaría el `HEAD` (el estado `pending` no se cachea): por eso se usa `getState()`.

## Flujo de una operación

### Uso normal de la biblioteca

`UploadMediaService`, `DownloadMediaService`, `DownloadPublicMediaService` y `RemoveMediaService` reciben el servicio `MediaStorage`, que es `MediaStorageRegistry::default()` (`src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml:35-36`). El registro devuelve el adaptador del driver `%env(MEDIA_STORAGE)%` (`:3`, `:28-33`).

- **Descarga / ver** (`DownloadMediaService.php:33-38`, `DownloadPublicMediaService.php:27-32`): `exists()` falso → `NotStoredMediaException` → `ShowController.php:39` y `Public/ShowController.php:30` responden 404. Con OVH, un error del bucket en `exists()` también es `NotStoredMediaException`, así que también acaba en 404. El recurso de `read()` lo transmite `StreamMediaTrait.php:17-26` con los mismos `Content-Type` y `Content-Disposition` que con disco.
- **Borrado** (`RemoveMediaService.php:41-45`): marca el `Media` como eliminado, lo persiste y después llama a `delete()`.

### Migración (`library:storage:migrate`)

1. `MigrateStorageCommand::execute()` (`MigrateStorageCommand.php:41-102`): `--from` igual a `--to` → error y `Command::INVALID` (`:49-53`); resuelve ambos con `MediaStorageRegistry::get()` y un driver desconocido → `Command::INVALID` con el mensaje del registro (`:55-62`).
2. Invoca `MigrateMediaStorageService` con `UserMother::systemUser()` (`:64-72`); nunca pasa `batchSize`, así que se usa 200.
3. El servicio (`MigrateMediaStorageService.php:25-94`) pagina con `MediaFilter(limit: max(1, batchSize))` (`:33`; el `max` es la corrección B2) hasta un lote vacío. `MediaFilter` por defecto excluye los `Media` eliminados. Para cada `Media`:
   - destino ya lo tiene → `alreadyInTarget`; con `deleteSource`, sin `dryRun` y si el origen lo tiene, lo borra del origen (`:49-58`);
   - origen no lo tiene → `missingInSource` (`:60-64`);
   - `dryRun` → cuenta como `migrated` sin escribir (`:66-70`);
   - si no, `copy()` (`:101-127`): `read()` del origen → fichero temporal en `sys_get_temp_dir()` → `write()` en destino; el temporal se borra en `finally`. Con `deleteSource`, borra el origen después.
   - Cualquier `\Throwable` (incluidos los `exists()` que fallan) se registra como `"{mediaId}: {mensaje}"` en `failures` y se sigue con el siguiente (`:79-81`).
4. El comando pinta el título, una nota en `--dry-run`, la tabla `Resultado/Archivos` con `migrados`, `ya en destino`, `no encontrados en origen`, `borrados del origen`, `errores` (`:74-89`), un `warning` si hay ausentes en origen y la lista de `failures`. Sale con `Command::FAILURE` si hay `failures` y `Command::SUCCESS` en otro caso (`:91-101`): los ausentes en origen no hacen fallar el comando.

## Autorización y ámbito

- Los casos de uso existentes conservan su `MediaAuthorizer`; el almacén es transparente para ellos.
- `MigrateMediaStorageService` no tiene `Authorizer` (decisión del plan, con precedente en `PurgeExpiredExportationsService`): opera sobre archivos de todas las cuentas y solo es alcanzable desde consola con el usuario de sistema. No hay rutas web ni API nuevas.
- Impersonación, roles y feature flags no intervienen.

## Persistencia y migración

Sin cambios de esquema, mapping, repositorios ni datos. **No hace falta migración Doctrine.**

## Configuración y operación

### Variables de entorno (`.env:140-149`)

| Variable | Valor por defecto | Uso |
|---|---|---|
| `MEDIA_STORAGE` | `local` | Driver activo: `local` u `ovh` |
| `OVH_S3_ENDPOINT` | `https://s3.gra.io.cloud.ovh.net` | Endpoint S3 de OVH (`https://s3.{region}.io.cloud.ovh.net`) |
| `OVH_S3_REGION` | `gra` | Región |
| `OVH_S3_BUCKET` | vacío | Bucket (lo crea operaciones; la aplicación no lo crea) |
| `OVH_S3_ACCESS_KEY` / `OVH_S3_SECRET_KEY` | vacío | Credenciales del usuario S3 de OVH |
| `OVH_S3_PREFIX` | `library` | Prefijo de clave dentro del bucket |

- El `S3Client` es `lazy` y se construye con `OvhS3ClientFactory::create()` (`src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml:81-91`). Con `MEDIA_STORAGE=local` y sin ejecutar nada contra el driver `ovh`, el cliente no se instancia y las `OVH_S3_*` pueden estar vacías. Cuando se instancia con endpoint, región o credenciales vacíos lanza `\InvalidArgumentException('OVH S3 storage is enabled but OVH_S3_* variables are not configured')` (`OvhS3ClientFactory.php:22-24`). El bucket y el prefijo no se validan.
- La factoría usa `pathStyleEndpoint => 'true'` (cadena: `AsyncAws\Core\Configuration` solo admite `string|null`) y `$httpClient: null` explícito en YAML para que autowire no inyecte el `http_client` del framework.
- **Validación de `MEDIA_STORAGE`**: el constructor de `MediaStorageRegistry` (`MediaStorageRegistry.php:14-19`) lanza `\InvalidArgumentException('Unknown media storage "x", expected one of: local, ovh')`. Como el valor es `%env()%`, salta en el primer uso de `MediaStorage` (primera petición o comando de biblioteca), no al compilar el contenedor (desviación declarada; B1 asumido en `review-fix.md`).

### Comando de migración

```bash
make sf c="library:storage:migrate"                                   # local → ovh, sin borrar
make sf c="library:storage:migrate --dry-run"                         # solo informa
make sf c="library:storage:migrate --from=local --to=ovh --delete-source"
make sf c="library:storage:migrate --from=ovh --to=local"             # vuelta atrás
```

Es idempotente: copia solo lo que falta en destino. `--delete-source` solo borra del origen archivos que el destino ya tiene (copiados en esta pasada o presentes antes).

### Procedimiento de corte en producción (plan, «Decisiones tomadas»)

1. Desplegar con `MEDIA_STORAGE=local` y las `OVH_S3_*` informadas en `.env.local` del servidor.
2. `library:storage:migrate` (copia sin borrar).
3. `MAINTENANCE_MODE=true`.
4. Repetir la migración para recoger lo subido entre 2 y 3.
5. `MEDIA_STORAGE=ovh`, `make cc`, `MAINTENANCE_MODE=false`.
6. Días después, `--from=local --to=ovh --delete-source` (o borrar `var/uploads/library` a mano).

Antes del corte conviene probar subida y descarga con las credenciales reales: el `HEAD` de `exists()` necesita permiso de lectura.

## Cómo extenderlo

- **Otro backend para la biblioteca** (p. ej. otro proveedor S3): 1) clase en `src/Library/Infrastructure/Storage/` que implemente `MediaStorage`, use `MediaPath::from()` en `store()` y envuelva todos sus errores (también los de `exists()`) en `NotStoredMediaException`; 2) añadirla a `$storages` del `MediaStorageRegistry` en `services.yaml` con un nombre de driver nuevo; 3) si necesita un doble en `test`, añadir otra `InMemoryMediaStorage` con nombre de servicio propio en `when@test`; 4) el comando de migración la acepta sin cambios (`--to=<driver>`).
- **Otros BC sobre OVH** (exportaciones, documentos firmados, manuales del changelog): el cliente `AsyncAws\S3\S3Client` ya está registrado en `Shared`; basta un adaptador de su puerto (`ExportStorage`, `SignatureFileStorage`…) que reciba el cliente y su bucket/prefijo.
- **Trampas conocidas**
  - `read()` carga el objeto entero en `php://temp` antes de devolverlo; para archivos de cientos de MB habría que construir el recurso con `getBody()->getChunks()`.
  - `async-aws` reintenta 5xx y 429 automáticamente: en tests con `MockHttpClient`, simular fallos con 4xx o con error de transporte.
  - `RemoveMediaService` persiste el `Media` eliminado antes de llamar a `delete()` (`RemoveMediaService.php:41-45`) y no hay transacción alrededor. `LocalMediaStorage::delete()` no lanza, pero `OvhMediaStorage::delete()` sí: si el bucket falla, el archivo queda marcado como eliminado, el objeto queda huérfano en el bucket y `RemoveController.php:40-42` muestra «No se ha podido eliminar el archivo …».
  - En subida, el objeto se escribe antes de persistir el `Media` (`UploadMediaService.php:42`, antes de `Media::upload()` y del `add()` del repositorio); un fallo posterior deja un objeto huérfano. Un fallo del bucket en la subida no está capturado en `UploadController` y produce la página de error 500.
  - El comando importa `UserMother` de `tests/` desde `src/`, igual que los demás comandos de mantenimiento del proyecto.

## Tests

- **Unitarios**
  - `MediaPathTest`: con cuenta, sin cuenta (`shared/`), sin extensión.
  - `InMemoryMediaStorageTest`: `store()`, `write()`/`read()`, `delete()`, lectura de ruta inexistente.
  - `OvhMediaStorageTest`: `S3Client` real con `MockHttpClient` (endpoint `http://s3.test`); `PUT`/`GET`/`HEAD`/`DELETE`, `exists()` con 200, 404, 403 (`testExistsFailure`) y error de transporte (`testExistsNetworkFailure`), fallos de escritura y lectura, prefijo vacío y con barra final.
  - `MediaStorageRegistryTest`: `get()`, `default()`, driver desconocido en `get()` y en el constructor.
  - `OvhS3ClientFactoryTest`: cliente creado en estilo path; configuración incompleta lanza.
  - `MigrateMediaStorageServiceTest`: migración, segunda pasada, ausentes en origen, `Media` eliminados ignorados, `deleteSource`, `dryRun`, paginación (`batchSize: 1`), `batchSize` no positivo (`testNonPositiveBatchSize`, B2), errores recogidos en `failures` sin abortar ni borrar el origen del fallido (`testFailuresAreCollected`, B3), sin eventos de dominio.
- **Aceptación** (`tests/Acceptance/Library/media-storage-migrate.feature`, suite `default`): copia a `ovh`, repetición idempotente, `--delete-source`, aviso de ausentes en origen, origen igual a destino (código 2). En `test` los drivers `local` y `ovh` son dos `InMemoryMediaStorage` (`services.yaml:38-56`); pasos nuevos en `LibraryContext`: `media :id is stored in the :driver storage`, `media :id is not stored in the :driver storage`, `the file of media :id is missing in the :driver storage`.
- **Ejecutar**

  ```bash
  make test-unit options="--filter 'MediaPath|MediaStorage|OvhS3ClientFactory|MigrateMediaStorage'"
  make test-acceptance options="tests/Acceptance/Library/media-storage-migrate.feature"
  ```

- **Huecos**: no hay prueba contra un bucket OVH real (ni en implementación ni en review); el `Command::FAILURE` del comando no tiene escenario de aceptación (solo el servicio está cubierto); los dobles fijan rutas `shared/` también para `Media` con cuenta (B4, asumido).

## Desviaciones y deuda

Detalle en `plan.md` (`## Implementación`), `review.md` y `review-fix.md`.

- `async-aws/s3 ^3.5` en lugar de `^2` (misma API).
- La validación de `MEDIA_STORAGE` salta en el primer uso, no al arrancar (B1, asumido).
- `exists()` lanza `NotStoredMediaException` ante errores del bucket (M1, corregido el 2026-10-14); la descripción de `exists()` y la deuda declarada en `plan.md` describen el comportamiento anterior y están pendientes de corregir en el plan.
- `batchSize` se normaliza a ≥ 1 (B2, corregido).
- Dobles de test con rutas siempre `shared/` (B4, asumido; deuda preexistente).
- Sin comando de diagnóstico de conectividad con el bucket ni limpieza de objetos huérfanos (fuera de alcance).
