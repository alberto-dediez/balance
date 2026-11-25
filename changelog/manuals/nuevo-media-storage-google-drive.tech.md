# Google Drive como almacén de la biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-24 · **Bounded Contexts:** `Library`, `Shared`

## Qué hace y por qué

Añade `google_drive` como tercer driver del puerto `MediaStorage` (`src/Library/Domain/Model/Media/MediaStorage.php`), junto a `local` y `ovh`. Se puede usar como destino de la copia de seguridad (`MEDIA_BACKUP_STORAGE=google_drive`, uso previsto) o como almacén principal (`MEDIA_STORAGE=google_drive`). No hay cambios de interfaz, de dominio ni de esquema: es un adaptador más, como preveía el plan de copia de seguridad.

Drive direcciona por identificador, no por ruta. El adaptador traduce la ruta relativa de `Media` (`{cuenta|shared}/{id}.{ext}`) a un archivo **hijo directo** de una única carpeta cuyo **nombre** es la ruta (`shared/<id>.pdf`), y lo localiza por nombre en cada operación. No se guarda el identificador de Drive en base de datos (decisión «Modelo de datos» del plan).

Decisiones clave (tabla «Decisiones tomadas» del plan): sin librería cliente nueva (REST v3 sobre `symfony/http-client`), cuenta de servicio sobre **unidad compartida** de Google Workspace, una sola cuenta de servicio para principal y copia (solo cambia la carpeta), `delete()` envía a la **papelera** y nunca borra definitivamente.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Shared/Infrastructure/Storage/GoogleDrive/GoogleDriveAccessTokenProvider.php` | Firma un JWT RS256 con la clave de la cuenta de servicio, lo cambia por un token de acceso y lo cachea | nuevo |
| `src/Shared/Infrastructure/Storage/GoogleDrive/GoogleDriveClient.php` | Cliente REST mínimo: `findFileIds`, `upload`, `download`, `trash` | nuevo |
| `src/Shared/Infrastructure/Storage/GoogleDrive/GoogleDriveException.php` | Fallo en tiempo de ejecución contra Drive (`\RuntimeException`) | nuevo |
| `src/Shared/Infrastructure/Storage/GoogleDrive/GoogleDriveFileNotFoundException.php` | 404 sobre un identificador de archivo | nuevo |
| `src/Library/Infrastructure/Storage/GoogleDriveMediaStorage.php` | Adaptador de `MediaStorage` (`#[When(env: 'dev')]`/`'prod'`) | nuevo |
| `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros `google_drive.credentials_file` y `google_drive.folder_id`; servicio del proveedor de tokens | modificado |
| `src/Library/Infrastructure/Symfony/DependencyInjection/services.yaml` | Adaptador principal, adaptador de copia `library.media_storage.backup_google_drive`, entradas en los dos registros y en `$primaryLocations`/`$backupLocations`, dobles de `when@test` | modificado |
| `.env`, `composer.json` (`ext-openssl`), `composer.lock` | Variables y requisito de extensión | modificado |
| `tests/Unit/Shared/Infrastructure/Storage/GoogleDrive/{GoogleDriveAccessTokenProviderTest,GoogleDriveClientTest,GoogleDriveMother}.php` | Tests del cliente y helper (clave RSA, credenciales temporales) | nuevo |
| `tests/Unit/Library/Infrastructure/Storage/GoogleDriveMediaStorageTest.php` | Tests del adaptador | nuevo |
| `tests/Unit/Library/Infrastructure/Storage/{MediaBackupStorageFactoryTest,MediaStorageRegistryTest}.php`, `tests/Acceptance/Library/media-storage-migrate.feature` | Casos del driver nuevo | modificado |

Sin cambios, por ser genéricos respecto al driver: `MediaStorageRegistry`, `MediaBackupStorageFactory`, `FallbackMediaStorage`, `MediaStorageFileCopier` y los comandos `library:storage:migrate|sync`, `library:backup:run|restore`.

## Modelo de dominio

No hay agregados ni eventos nuevos. Invariantes del adaptador:

- Nombre en Drive = `ltrim($path, '/')` (`GoogleDriveMediaStorage.php:139`). Sin subcarpetas creadas por Hydra.
- Un archivo «existe» si hay al menos uno con ese nombre, `trashed = false` y la carpeta configurada como padre (`GoogleDriveClient.php:27-55`).
- `write()` sobre una ruta existente conserva el identificador y sustituye el contenido (`PATCH`); si no existe, crea (`POST`) (`GoogleDriveMediaStorage.php:40-55`).
- `read()` usa el más antiguo (`orderBy=createdTime`); `delete()` envía a la papelera **todas** las coincidencias (`:82-100`).
- Principal y copia no pueden compartir carpeta: lo impone `MediaBackupStorageFactory` (`src/Library/Infrastructure/Storage/MediaBackupStorageFactory.php:29-32`, mensaje «The backup storage must not use the same location as the main storage»).

Contrato de errores (igual que `OvhMediaStorage`):

| Situación | Excepción |
|---|---|
| Red, 401/403/5xx, cuota, búsqueda incompleta, descarga cortada | `GoogleDriveException` en el cliente, convertida a `NotStoredMediaException` en el adaptador (lo único que captura `FallbackMediaStorage`) |
| `GOOGLE_DRIVE_*` vacías, credenciales ilegibles/inválidas, carpeta vacía | `\InvalidArgumentException`, no se captura (`GoogleDriveAccessTokenProvider.php:56-79`, `GoogleDriveMediaStorage.php:124-127`) |
| `exists()` | `false` solo si Drive responde sin coincidencias; cualquier fallo es excepción |

## Flujo de una operación

Obtención del token (`GoogleDriveAccessTokenProvider::token()`, `:29-51`): lee y valida el JSON (`client_email`, `private_key`, `token_uri`); busca en `cache.app` la clave `google_drive.access_token.<sha1(client_email)>`; si no hay acierto firma un JWT (scope `https://www.googleapis.com/auth/drive`, `exp` = ahora + 3600; `:84-105`), hace `POST` al `token_uri` con `grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer` y guarda el token con `expiresAfter(max(60, expires_in - 60))`. Se llama en cada petición del cliente (`GoogleDriveClient::request()`, `:159`), que añade `auth_bearer` y `supportsAllDrives=true`.

- **Subir (`store`/`write`)**: `MediaPath::from()` → `find()` (búsqueda por nombre) → `upload()` en dos peticiones: sesión reanudable (`POST`/`PATCH …/upload/drive/v3/files?uploadType=resumable`, espera 200 y cabecera `Location`) y `PUT` del fichero a esa `Location` (200/201 con `id`) (`GoogleDriveClient.php:57-116`). Cada fichero se sube en una única petición `PUT`, sin reanudación tras un corte.
- **Leer (`read`)**: usa el identificador memorizado en la instancia si lo hay; ante `GoogleDriveFileNotFoundException` lo olvida y repite la búsqueda; si no hay archivo, `NotStoredMediaException` (`GoogleDriveMediaStorage.php:57-80`). `download()` baja el **cuerpo entero** a un `php://temp/maxmemory:2097152` (2 MB en memoria, el resto a disco temporal) y devuelve el recurso rebobinado; un corte a mitad lanza `GoogleDriveException` (`GoogleDriveClient.php:118-145`). Así `MediaStorageFileCopier` nunca copia un archivo truncado (hallazgo C1 de `review.md`). Consecuencia: como principal, una descarga web espera a tener el archivo completo.
- **Existe (`exists`)**: siempre busca, nunca responde de memoria (`:102-117`). Las descargas web llaman a `exists()` y luego a `read()`: dos peticiones a Google más el token si no está en caché.
- **Borrar (`delete`)**: busca, olvida la ruta y hace `PATCH {"trashed": true}` a cada coincidencia; ignora un 404 por archivo (`:82-100`). El cliente no expone borrado definitivo.
- **Búsqueda incompleta**: `findFileIds()` pide `fields=incompleteSearch,files(id)` y lanza `GoogleDriveException` si `incompleteSearch` es `true` (`GoogleDriveClient.php:30-44`), para no confundirla con «no existe» (hallazgo M1). Usa `corpora=allDrives` e `includeItemsFromAllDrives=true`; `q` escapa `\` y `'` en nombre y carpeta (`:231-234`).

Descargas, copia, restauración y migración llegan al adaptador por el registro (`MediaStorageRegistry`) o por `library.media_storage.backup`, sin cambios.

## Autorización y ámbito

No hay `Authorizer`, rutas ni acciones: solo infraestructura. Los permisos reales son los de la cuenta de servicio en Drive: debe ser miembro de la unidad compartida con rol **Gestor de contenido** (crear, modificar, enviar a la papelera). Con un rol menor `delete()` recibe 403 → `NotStoredMediaException`.

## Persistencia y migración

Sin cambios de esquema ni migración. `Media` no sabe en qué almacén está.

## Configuración y operación

| Variable | Uso |
|---|---|
| `MEDIA_STORAGE`, `MEDIA_BACKUP_STORAGE` | Admiten `google_drive` (`.env`, comentarios `# local \| ovh \| google_drive`) |
| `GOOGLE_DRIVE_CREDENTIALS_FILE` | Ruta absoluta de la clave JSON de la cuenta de servicio, fuera del repositorio (parámetro `google_drive.credentials_file`) |
| `GOOGLE_DRIVE_FOLDER_ID` | Carpeta del almacén principal (`google_drive.folder_id`) |
| `MEDIA_BACKUP_GOOGLE_DRIVE_FOLDER_ID` | Carpeta de la copia; sin valor por defecto y distinta de la anterior (`app.media_backup_google_drive_folder_id`) |

Con otros drivers elegidos las variables pueden estar vacías y no se hace ninguna petición a Google: el constructor del adaptador no valida (el registro los instancia todos) y el error de configuración sale en la primera operación. `GoogleDriveAccessTokenProvider` se declara en `Shared/.../services.yaml` con `$credentialsFile`; `GoogleDriveClient` se autoinyecta. El adaptador de copia es el servicio `library.media_storage.backup_google_drive`, que apunta a `app.media_backup_google_drive_folder_id` (`Library/.../services.yaml`).

Puesta en marcha:

1. Google Cloud: proyecto, activar «Google Drive API», cuenta de servicio y descarga de su clave JSON al servidor, con lectura solo para el usuario de PHP. Su ruta va en `GOOGLE_DRIVE_CREDENTIALS_FILE` (`.env.local`).
2. Drive: carpeta(s) en una **unidad compartida** (las cuentas de servicio no tienen cuota propia; no sirve una cuenta personal) y añadir el `client_email` como miembro con rol Gestor de contenido.
3. El identificador de carpeta es el último segmento de `https://drive.google.com/drive/folders/<id>`.
4. `MEDIA_BACKUP_STORAGE=google_drive` (o `MEDIA_STORAGE=google_drive`) y `make cc`.
5. Validación manual contra un Drive real (los tests no hablan con Google): `make sf c="library:storage:migrate --from=local --to=google_drive --dry-run"`, una migración real sobre una carpeta de pruebas, `make sf c="library:backup:restore --dry-run"` y una eliminación comprobando que el archivo aparece en la papelera de la unidad.
6. Primera pasada de copia: lanzarla por consola (`make sf c="library:backup:run"`), no esperar a la tarea programada de las 04:00 UTC.
7. Pasar a Drive como principal: procedimiento de corte del plan de OVH (migrar, modo mantenimiento, segunda pasada, cambiar `MEDIA_STORAGE`) con `--to=google_drive`.

Cambiar `MEDIA_BACKUP_STORAGE` con copias ya hechas en otro destino no las traslada: hay que vaciar `media_backups` para que la siguiente pasada lo copie todo.

Operación diaria: la papelera de una unidad compartida se vacía sola a los 30 días; Hydra nunca la vacía ni restaura. Conservación real de un eliminado con copia en Drive: `MEDIA_BACKUP_RETENTION_DAYS` días más hasta 30 de papelera. Renombrar, mover o enviar a la papelera archivos desde la interfaz de Drive los hace «no existentes» para Hydra.

## Cómo extenderlo

- **Otro almacén remoto**: clase que implemente `MediaStorage` con `#[When(env: 'dev')]`/`'prod'`; añadirla a `$storages` de `MediaStorageRegistry` y del registro de copia `library.media_backup_storage.registry`, sus ubicaciones a `$primaryLocations`/`$backupLocations` de `MediaBackupStorageFactory`, y un doble `InMemoryMediaStorage` en `when@test`. Seguir el mismo contrato de excepciones.
- **Credenciales propias para la copia**: hoy hay una sola cuenta de servicio; haría falta un segundo `GoogleDriveAccessTokenProvider` y su cliente.
- **Subcarpetas por cuenta o caché persistente ruta → id**: fuera de alcance; el adaptador solo mira hijos directos de la carpeta.
- Trampas: sin `supportsAllDrives`/`includeItemsFromAllDrives` las búsquedas en unidades compartidas devuelven vacío sin error; dos escrituras simultáneas de la misma ruta pueden crear duplicados (no hay pérdida: `read()` usa el más antiguo y `delete()` los trashea todos); la carpeta de la copia anidada dentro de la principal no se detecta (sin colisión de datos porque solo se miran hijos directos).

## Tests

- Unitarios: `GoogleDriveAccessTokenProviderTest` (6 tests: petición del token con `assertion` firmada y verificada con la clave pública, caché, credenciales inválidas, 400, red, clave no válida), `GoogleDriveClientTest` (15), `GoogleDriveMediaStorageTest` (21; incluye lectura cortada a mitad e `incompleteSearch`), y los casos nuevos de `MediaBackupStorageFactoryTest` (`:142-172`) y `MediaStorageRegistryTest` (`:19-24`). Todos con `MockHttpClient` y `GoogleDriveMother`.
- Aceptación: dos escenarios en `tests/Acceptance/Library/media-storage-migrate.feature` (`local → google_drive` y vuelta con `--delete-source`) sobre el doble en memoria.
- Ejecutar: `make test-unit options="--filter GoogleDrive"`, `make test-unit options="--filter MediaBackupStorageFactoryTest"` y `make test-acceptance options="tests/Acceptance/Library/media-storage-migrate.feature"`.
- Hueco: nada prueba contra Google real; un parámetro mal escrito pasa los tests. De ahí la validación manual.

## Desviaciones y deuda

- Desviaciones del plan (ver `## Implementación` en `plan.md`): helper `GoogleDriveMother`, test extra de clave privada inválida, `@dataProvider`.
- Tras la review (`review.md`, 0 hallazgos abiertos), el código difiere del plan en dos puntos que el plan aún no refleja: `download()` baja el cuerpo completo a un temporal en lugar de `StreamWrapper` (C1) y `findFileIds()` trata `incompleteSearch` como error (M1).
- Aceptado sin cambio (Q1): `read()` usa el identificador memorizado sin volver a buscar, de modo que un archivo enviado a la papelera desde la interfaz de Drive después de un `exists()`/`write()` de la misma instancia aún puede leerse; la memoria nunca se vacía en procesos de larga vida.
- Sin reintentos ante 429/5xx ni control de cuota (750 GB/día de subida): la pasada de copia aborta a los 20 fallos y es reanudable.
