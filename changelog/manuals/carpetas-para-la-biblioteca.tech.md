# Carpetas para la biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-19 (correcciones de review hasta 2026-11-20) · **Bounded Contexts:** `Library` (principal), `Shared` (menú, barra de filtros, tema del selector)

## Qué hace y por qué

La biblioteca era una lista plana. Ahora los archivos (`Media`) se pueden organizar en **carpetas de un solo nivel**. `Folder` es un agregado nuevo de `Library` (tabla `media_folders`) y `Media` guarda solo una referencia opcional por identificador, `?FolderId $folderId` (mismo patrón que `User::$pictureId`). No hay carpeta padre, ni claves foráneas en base de datos.

Con carpetas, el listado base de «Archivos» enseña solo los archivos sin carpeta; al resto se llega con el filtro «Carpeta» (una carpeta, «Todas las carpetas» o «Sin carpeta»). El selector de archivos de los formularios y la herramienta `list_media` del asistente siguen viendo todo por defecto y pueden acotar por carpeta y buscar por su nombre. Decisiones y su justificación: tabla «Decisiones tomadas» de `plan.md`.

Las carpetas pertenecen a la cuenta (`accountId` nulo = biblioteca «sin cuenta»). Un archivo solo puede estar en una carpeta de su misma cuenta.

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio | `src/Library/Domain/Model/Folder/{Folder,FolderId,FolderFilter,FolderRepository}.php` | Agregado, id, criterios y puerto | nuevo |
| Dominio | `.../Folder/{FolderRegistered,FolderRenamed,FolderRemoved}.php` | Eventos (`folderRegistered`, `folderRenamed`, `folderRemoved`) | nuevo |
| Dominio | `.../Folder/{FolderNotFoundException,FolderAlreadyExistsException}.php` | Excepciones | nuevo |
| Dominio | `src/Library/Domain/Model/Media/Media.php`, `MediaMoved.php` | `folderId`, `moveToFolder()`, evento `mediaMoved` | modificado / nuevo |
| Dominio | `src/Library/Domain/Model/Media/MediaFilter.php` | `folderIds`, `withoutFolder`, `nameFolderIds` | modificado |
| Aplicación | `src/Library/Application/Folder/FolderAuthorizer.php` | Permisos de carpeta | nuevo |
| Aplicación | `src/Library/Application/Folder/CurrentAccountFolders.php` | Lectura de las carpetas de la cuenta actual (sin autorizar, sin caché) | nuevo |
| Aplicación | `src/Library/Application/Folder/{Register,Update,Remove,List}/*` | Casos de uso | nuevo |
| Aplicación | `src/Library/Application/Media/MediaFolderGuard.php` | Garantiza que la carpeta destino es de la cuenta del archivo y visible | nuevo |
| Aplicación | `src/Library/Application/Media/Move/*` | `MoveMediaService` | nuevo |
| Aplicación | `Media/List/ListMediaRequest.php`, `ListMediaService.php`, `Update/UpdateMedia*`, `Upload/UploadMedia*`, `Assistant/ListMediaTool.php` | Carpeta en listado, edición, subida y asistente | modificado |
| Persistencia | `Infrastructure/Persistence/Doctrine/Mapping/Folder.Folder.orm.xml`, `Type/FolderIdType.php`, `Repository/DoctrineFolderRepository.php`, `InMemory/Repository/InMemoryFolderRepository.php` | Mapping, tipo `folder_id` (registrado en `config/packages/doctrine.yaml`), repositorios | nuevo |
| Persistencia | `Media.Media.orm.xml`, `DoctrineMediaRepository.php`, `InMemoryMediaRepository.php` | Columna `folder_id`, criterios de carpeta | modificado |
| Migración | `migrations/Version20261119171746.php` | Tabla `media_folders`, `media.folder_id` e índices | nuevo |
| UI | `Infrastructure/UI/Web/Folder/{List,Register,Edit,Remove}Controller.php`, `GetFolderFromIdTrait.php`, `Bulk/RemoveFolder.php`; `templates/library/folder/{list,register,edit}.html.twig` | Pantallas de carpetas | nuevo |
| UI | `Infrastructure/UI/Web/Media/Bulk/{BulkMediaRequest,MoveMedia}.php`, `Symfony/Form/BulkMediaType.php` | Acción en bloque «Mover a carpeta» | nuevo |
| UI | `Infrastructure/Symfony/Form/Type/{FolderChoiceType,FolderFilterChoiceType}.php`, `Symfony/Twig/FolderExtension.php` | Desplegables y filtro Twig `folder_name` | nuevo |
| UI | `Media/{ListController,EditController,UploadController}.php`, `Picker/{SearchController,MediaJsonResponseTrait}.php`, `Form/{ListMediaType,UpdateMediaType,UploadMediaType}.php`, `Form/Type/MediaPickerType.php` | Filtro, campo «Carpeta», selector | modificado |
| UI | `templates/library/media/{list,edit,upload}.html.twig`, `templates/shared/form/custom_types.html.twig`, `templates/components/List/FilterBar.html.twig`, `assets/controllers/media_picker_controller.js` | Plantillas y JS | modificado |
| UI | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (`libraryNode()`) | Hoja «Carpetas» (icono `solar:folder-line-duotone`) en los tres menús | modificado |
| Auditoría | `templates/security/admin/events/detail/library.*.html.twig` (9) | Frase legible de cada evento de `Library` | nuevo |

## Modelo de dominio

- `Folder(id, accountId?, name, registeredAt)`. Sin borrado lógico: `remove()` registra `FolderRemoved` y el repositorio borra la fila, de modo que el nombre queda libre.
- Eventos: `FolderRegistered` (`folderId`, `name`, `accountId`), `FolderRenamed` (`folderId`, `name`), `FolderRemoved` (`folderId`, `name`), `MediaMoved` (`mediaId`, `folderId?`). El payload de `MediaUploaded` no cambia: la subida a carpeta es `upload()` seguido de `moveToFolder()` (`UploadMediaService.php:49-66`).
- `Media::moveToFolder(?FolderId)` no valida nada; la invariante «misma cuenta» vive en `MediaFolderGuard` porque necesita cargar la carpeta.
- Invariantes: un nivel (no hay referencia carpeta → carpeta); misma cuenta (`MediaFolderGuard.php:24-37`, que lanza `FolderNotFoundException` también para carpetas de otra cuenta, para no revelar su existencia); nombre único por cuenta, **sin distinguir mayúsculas** (corrección B1 de la review: `DoctrineFolderRepository::findOneByAccountAndName` usa `LOWER(f.name) = LOWER(:exactName)`, `InMemoryFolderRepository` usa `mb_strtolower`), validado en el servicio y no con índice único porque `account_id` puede ser nulo; sin referencias colgantes (`RemoveFolderService.php:42-52`).
- `MediaFilter` es neutro por defecto (`folderIds` vacío, `withoutFolder` nulo, `nameFolderIds` vacío = sin restricción), así `DoctrineMediaRepository::find()` y la migración de almacenamiento no cambian.

Semántica del filtro de listado (cuenta actual `A`):

| Filtro «Carpeta» | Con «Ver archivos compartidos» | Resultado |
|---|---|---|
| vacío («Sin carpeta») | no | archivos de `A` sin carpeta |
| vacío | sí | los anteriores más los compartidos por otras cuentas, tengan o no carpeta |
| «Todas las carpetas» | no / sí | ámbito de `A` / ámbito de `A` más compartidos |
| una carpeta | cualquiera | solo los de esa carpeta |

Implementación en `DoctrineMediaRepository.php:147-173` (y su equivalente en memoria en `InMemoryMediaRepository::matches`). Criterio de texto con búsqueda por carpeta: `name LIKE ∨ fileName LIKE ∨ folderId IN nameFolderIds` (`DoctrineMediaRepository.php:68-84`); sin `name`, `nameFolderIds` se ignora. El repositorio de `Media` no hace `JOIN` con `Folder`: `ListMediaService.php:70-72` resuelve antes las carpetas que coinciden (`CurrentAccountFolders::matchingName`).

## Flujo de una operación

**Crear / renombrar carpeta.** `library_folders_register` / `library_folders_edit` (`Folder/RegisterController.php:28`, `EditController`) → autorizan `Action::Register` / `Action::Edit` sobre una carpeta sonda → `RegisterFolderService` / `UpdateFolderService` (`trim`, comprobación de duplicado, evento, `repository->add`). `FolderAlreadyExistsException` se captura dentro del closure de `RegisterTrait`/`EditTrait` (que de otro modo mostrarían «Error inesperado») y se añade como error del campo «Nombre» («Ya existe una carpeta con ese nombre»); en edición, un indicador evita la redirección de «Guardar y salir» cuando falla.

**Eliminar carpeta.** `library_folders_remove` (GET con `#[CsrfProtected('remove')]`, `RemoveController.php:20`) o acción en bloque «Eliminar» → `RemoveFolderService.php:30-58`: carga, `Action::Remove`, saca cada archivo con `moveToFolder(null)` (un `MediaMoved` por archivo, sin pasar por `MediaAuthorizer`: es consecuencia del borrado, un Owner puede eliminar carpetas con archivos de otros), publica `FolderRemoved` y borra. Devuelve `releasedMedia`.

**Mover archivos.** Tres vías, todas con `MediaFolderGuard`:
1. En bloque: `Media/ListController.php` usa `BulkMediaType` con `[MoveMedia, RemoveMedia]`; `MoveMedia::commands()` crea un `MoveMediaRequest` por archivo (`Bulk/MoveMedia.php`). El desplegable de carpeta solo se muestra con la acción `move` (Stimulus `showable`, `BulkMediaType.php`). `MoveMediaService` carga, exige `Action::Edit`, llama al guard y mueve si la carpeta cambia.
2. Edición: campo `folderId` en `UpdateMediaType`. `UpdateMediaService.php:38-70` valida **todo antes de cambiar nada** (carpeta con el guard, `Action::Admin` si cambia `shared`; correcciones M1 y B4 de la review) y después aplica los cambios.
3. Subida: campo `folderId` en `UploadMediaType`; `UploadMediaService.php:45` llama al guard **antes** de `storage->store()` para no dejar un archivo guardado con carpeta inválida.

**Listado de archivos.** `Media/ListController.php` guarda el filtro en sesión (`FilterTrait`); si el id de carpeta guardado ya no existe lo pone a `null` y siempre llama a `applyFolderChoice()` (`ListMediaRequest.php:18-36`: `null` → `withoutFolder`; `all` → sin restricción; otro valor → `folderIds`). La columna «Carpeta» sale del mapa `folderNames` de `CurrentAccountFolders::all()`, por lo que queda vacía para archivos compartidos de otra cuenta. La barra de filtros activos usa la rama `folder` de `FilterBar.html.twig:17-18` (filtro `folder_name`).

**Selector y asistente.** `Picker/SearchController.php` acepta `folder` (vacío = todo, `none` = sin carpeta, id = esa carpeta; un id ajeno se ignora), activa `searchInFolderNames` y devuelve `folder` (nombre) en cada elemento. `media_picker_controller.js` envía el parámetro y rellena `data-slot="folder"`; el desplegable solo se pinta si la cuenta tiene carpetas (`MediaPickerType` expone `folders`). `ListMediaTool` añade el parámetro `folder` (coincidencia parcial; sin carpetas coincidentes responde «No hay ninguna carpeta con ese nombre» sin consultar archivos) y el campo `folder` por fichero. Los nombres de carpeta se resuelven siempre con `CurrentAccountFolders`, nunca se revelan los de otras cuentas.

## Autorización y ámbito

`FolderAuthorizer` (`FolderAuthorizer.php`): ver y listar (`canView`/`canList`) = la carpeta es de la cuenta actual (`currentAccountId()`, con impersonación; carpeta sin cuenta solo para quien no tiene cuenta efectiva); registrar, editar y eliminar además exigen rol efectivo `SuperAdmin`, `Admin` u `Owner` (`userHasRole`). Un `User` ve el listado pero no los botones, y las URL directas dan 403. Mover un archivo exige `Action::Edit` de `MediaAuthorizer` (quien lo subió, Owner, Admin) y poder ver la carpeta destino. Los controladores traducen `UnauthorizedActionException` a 403. No hay feature flags ni API REST de carpetas.

## Persistencia y migración

- `media_folders`: `id` (`folder_id`), `name` varchar 255, `account_id` nulo (`account_id`), `registered_at`; índice `idx_media_folder_account`.
- `media.folder_id` nulo (`folder_id`), índice `idx_media_folder`. Sin FK. Sin backfill: todo lo existente queda sin carpeta.
- Migración **aplicable**: `migrations/Version20261119171746.php` (revisada en la review, hallazgo A1). `getDescription()` devuelve `''`.
- Tests: el esquema SQLite se regenera desde el mapping, así que no detectan una migración ausente.

## Configuración y operación

Sin variables de entorno, colas, comandos ni tareas programadas nuevas. El autowire de `App\Library\` y la importación de rutas `library-web` (`config/routes.yaml`, con `%app.admin_prefix%/{_locale}`) cubren las clases nuevas. Rutas: `library_folders`, `library_folders_register`, `library_folders_edit`, `library_folders_remove` (sin prefijo `user`/`admin`).

## Cómo extenderlo

- **Nuevo criterio de listado por carpeta:** añadir la propiedad a `MediaFilter` con valor neutro, y reflejarla en **los dos** repositorios (`DoctrineMediaRepository`, `InMemoryMediaRepository`); los casos se prueban una vez en `MediaRepositoryTest` (abstracto) que ejecutan ambos.
- **Nuevo punto que asigne carpeta a un archivo:** pasar siempre por `MediaFolderGuard::assertAssignable` antes de efectos secundarios (guardar archivo, publicar eventos) y por `Media::moveToFolder`.
- **Nuevo desplegable de carpetas:** usar `FolderChoiceType` (valor `?FolderId`) o `FolderFilterChoiceType` (valor `?string`); ambos cargan las carpetas por formulario (opción `folder_names` perezosa) para no servir datos obsoletos con el `OptionsResolver` cacheado.
- **Mostrar el nombre de una carpeta:** resolverlo con `CurrentAccountFolders` o el filtro `folder_name`, nunca con el repositorio directo, para no filtrar carpetas de otras cuentas.
- Trampas: `CurrentAccountFolders` no memoiza a propósito (el kernel de Behat reutiliza servicios entre peticiones); `RegisterTrait`/`EditTrait` tragan cualquier `\Exception`; los argumentos nuevos de `Media`/`MediaFilter` van al final por las llamadas posicionales; `CurrentAccountFolders::all()` carga todas las carpetas sin paginar.

## Tests

- Unitarios (`tests/Unit/Library/`): `Domain/Model/Folder/*` (mother, eventos, repositorio abstracto ejecutado por `InMemoryFolderRepositoryTest` y `DoctrineFolderRepositoryTest`), `Domain/Model/Media/{MediaTest,MediaFilterTest,MediaRepositoryTest}`, `Application/Folder/*` (authorizer, `CurrentAccountFolders`, los cuatro servicios), `Application/Media/{MediaFolderGuardTest,Move,List,Update,Upload,Assistant}`, `MediaPickerTypeTest`, y la lista de hojas de `NavigationSearchProviderTest`. `UpdateMediaServiceTest` cubre `testAnInvalidFolderLeavesTheMediaUntouched` y `testAnUnauthorizedSharingLeavesTheMediaUntouched`.
- Aceptación (`tests/Acceptance/Library/`): `folder-list`, `folder-register`, `folder-edit`, `folder-remove`, `media-folder` (incluye barra de filtros, compartidos, suplantación, formulario manipulado y auditoría) y `media-picker` ampliado; pasos en `LibraryContext.php`. Los de `Library` usan el repositorio en memoria.
- Ejecución: `make test-unit options="--filter Folder"`, `make test-acceptance options="tests/Acceptance/Library/media-folder.feature"` (o `make test-parallel` para todo). Última validación registrada en `review.md`: `make phpstan`, `make cs-fix` y `make test-parallel` en verde (2191 unitarios, 560 escenarios).

## Desviaciones y deuda

- Sin hallazgos abiertos en `review.md` (A1, M1, B1-B4 corregidos); decisión Q1 (búsqueda por carpeta) confirmada.
- Desviaciones del plan declaradas en `## Implementación` (capturas de excepción dentro del closure, opción perezosa `folder_names`, pasos extra de `LibraryContext`) y correcciones que contradicen el plan: orden de validación en `UpdateMediaService` (M1/B4) y nombre único sin distinguir mayúsculas (B1). Ver [`plan.md`](plan.md) y [`review.md`](review.md).
- Pendiente de verificar a mano (declarado en el plan): que el desplegable de carpeta de la acción en bloque se oculta con «Eliminar» y se muestra con «Mover a carpeta».
