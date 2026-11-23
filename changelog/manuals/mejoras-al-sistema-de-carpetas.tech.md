# Carpetas de la biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-20 · **Bounded Contexts:** `Library` (principal), `Shared` (menú, layout de listados, controlador Stimulus del filtro, barra de filtros, tema del selector)

Este manual describe la funcionalidad de carpetas **entera y en su estado final**: el agregado y sus reglas (introducidos por el plan `2026-11-19-carpetas-para-la-biblioteca`) y la interfaz unificada de este plan. Sustituye al `tech-manual.md` del plan anterior.

## Qué hace y por qué

Los archivos de la biblioteca (`Media`) se agrupan en **carpetas de un solo nivel**. `Folder` es un agregado de `Library` (tabla `media_folders`) y `Media` guarda solo una referencia opcional por identificador, `?FolderId $folderId` (mismo patrón que `User::$pictureId`): no hay carpeta padre ni claves foráneas.

Con carpetas, el listado base de «Archivos» enseña solo los archivos sin carpeta; al resto se llega con el filtro «Carpeta». **Toda la gestión vive en una única pantalla, `library_media`** (Biblioteca → Archivos): una tarjeta «Carpetas» entre la barra de filtros y la tabla muestra una ficha por carpeta más dos fichas fijas («Sin carpeta» y «Todas las carpetas»); las fichas no son enlaces, fijan el valor del filtro «Carpeta» y reenvían el formulario de filtro. Las pantallas de alta y edición de carpeta se conservan, pero ya no existe el listado de carpetas (`/library/folders` da 404) ni su entrada de menú. Decisiones y alternativas descartadas: tabla «Decisiones tomadas» de `plan.md`.

Las carpetas pertenecen a la cuenta (`accountId` nulo = biblioteca «sin cuenta»). Un archivo solo puede estar en una carpeta de su misma cuenta.

## Mapa de piezas

| Capa | Fichero | Rol |
|---|---|---|
| Dominio | `src/Library/Domain/Model/Folder/{Folder,FolderId,FolderFilter,FolderRepository}.php` | Agregado, id, criterios y puerto |
| Dominio | `.../Folder/{FolderRegistered,FolderRenamed,FolderRemoved}.php` | Eventos |
| Dominio | `.../Folder/{FolderNotFoundException,FolderAlreadyExistsException}.php` | Excepciones |
| Dominio | `src/Library/Domain/Model/Media/{Media,MediaMoved,MediaFilter}.php` | `folderId`, `moveToFolder()`, evento `MediaMoved`, criterios `folderIds`/`withoutFolder`/`nameFolderIds` |
| Aplicación | `src/Library/Application/Folder/FolderAuthorizer.php` | Permisos de carpeta |
| Aplicación | `src/Library/Application/Folder/CurrentAccountFolders.php` | Lectura de las carpetas de la cuenta actual (`all`, `find`, `matchingName`) |
| Aplicación | `src/Library/Application/Folder/{Register,Update,Remove}/*` | Casos de uso de escritura |
| Aplicación | `src/Library/Application/Media/MediaFolderGuard.php`, `Media/Move/*` | Guarda de «misma cuenta» y `MoveMediaService` |
| Aplicación | `Media/List/ListMediaRequest.php` (`ALL_FOLDERS`, `applyFolderChoice()`), `Update/*`, `Upload/*`, `Assistant/ListMediaTool.php` | Carpeta en listado, edición, subida y asistente |
| Persistencia | `Infrastructure/Persistence/Doctrine/Mapping/Folder.Folder.orm.xml`, `Type/FolderIdType.php`, `Repository/DoctrineFolderRepository.php`, `InMemory/Repository/InMemoryFolderRepository.php` | Mapping, tipo `folder_id`, repositorios |
| Migración | `migrations/Version20261119171746.php` | `media_folders`, `media.folder_id`, índices |
| UI (carpetas) | `Infrastructure/UI/Web/Folder/{Register,Edit,Remove}Controller.php`, `GetFolderFromIdTrait.php`; `templates/library/folder/{register,edit}.html.twig`; `Symfony/Form/RegisterFolderType.php` | Alta, edición y borrado; los tres redirigen a `library_media` |
| UI (listado) | `Infrastructure/UI/Web/Media/ListController.php`, `templates/library/media/list.html.twig` | Tarjeta «Carpetas», título, enlace de subida |
| UI (listado) | `templates/components/Library/FolderTile.html.twig`, `assets/styles/components/_folder-tile.scss` (importado en `assets/styles/app.scss`) | Ficha de carpeta |
| UI (listado) | `templates/shared/layout/list.html.twig` (bloque `list_top`), `assets/controllers/filter_controller.js` (`setField`) | Soporte compartido |
| UI (movimiento) | `Media/Bulk/{BulkMediaRequest,MoveMedia}.php`, `Symfony/Form/BulkMediaType.php` | Acción en bloque «Mover a carpeta» |
| UI (formularios) | `Symfony/Form/Type/{FolderChoiceType,FolderFilterChoiceType}.php`, `Symfony/Twig/FolderExtension.php` (filtro `folder_name`), `Form/{ListMediaType,UpdateMediaType,UploadMediaType}.php` | Desplegables |
| UI (subida) | `Media/UploadController.php` | Preselección por `?folder=` |
| UI (selector) | `Media/Picker/SearchController.php`, `Form/Type/MediaPickerType.php`, `templates/shared/form/custom_types.html.twig`, `assets/controllers/media_picker_controller.js` | Selector con filtro y búsqueda por carpeta |
| Menú | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` (`libraryNode()`) | Solo «Archivos» y «Subir archivo» |
| Auditoría | `templates/security/admin/events/detail/library.{folderRegistered,folderRenamed,folderRemoved,mediaMoved}.html.twig` | Frase legible de cada evento |

Eliminado en este plan: `Folder/ListController`, `Folder/Bulk/RemoveFolder`, `Symfony/Form/ListFoldersType`, `Application/Folder/List/*`, `templates/library/folder/list.html.twig`, `ListFoldersServiceTest` y `folder-list.feature`. `FolderFilter`, `FolderRepository::findByFilter`/`totalResultsByFilter` y `FolderAuthorizer::canList` se mantienen: los usa `CurrentAccountFolders`.

## Modelo de dominio

- `Folder(id, accountId?, name, registeredAt)`. Sin borrado lógico: `remove()` registra `FolderRemoved` y el repositorio borra la fila, así que el nombre queda libre.
- Eventos: `FolderRegistered` (`folderId`, `name`, `accountId`), `FolderRenamed` (`folderId`, `name`), `FolderRemoved` (`folderId`, `name`), `MediaMoved` (`mediaId`, `folderId?`). La subida a carpeta es `upload()` seguido de `moveToFolder()`; el payload de `MediaUploaded` no cambia.
- `Media::moveToFolder(?FolderId)` no valida nada: la invariante «misma cuenta» vive en `MediaFolderGuard::assertAssignable` (`MediaFolderGuard.php:24-37`), que lanza `FolderNotFoundException` también para carpetas de otra cuenta (no revela su existencia) y exige `Action::View` sobre la carpeta.
- Nombre único por cuenta, sin distinguir mayúsculas, comprobado en el servicio (`RegisterFolderService.php:37`, `findOneByAccountAndName`) y no con índice único porque `account_id` puede ser nulo. Se hace `trim` del nombre.
- Sin referencias colgantes: `RemoveFolderService.php:42-50` saca cada archivo de la carpeta (`moveToFolder(null)`, un `MediaMoved` por archivo, sin pasar por `MediaAuthorizer`: es consecuencia del borrado) antes de publicar `FolderRemoved`.
- Un nivel: no existe referencia carpeta → carpeta.
- `MediaFilter` es neutro por defecto (`folderIds` vacío, `withoutFolder` nulo, `nameFolderIds` vacío), por lo que el resto de consumidores no cambia.

Semántica del filtro «Carpeta» del listado (cuenta actual `A`), traducida por `ListMediaRequest::applyFolderChoice()`:

| Valor de `ListMediaRequest::$folder` | Filtro resultante | Resultado (sin «Ver archivos compartidos») |
|---|---|---|
| `null` («Sin carpeta») | `withoutFolder = true` | archivos de `A` sin carpeta |
| `'all'` | sin restricción | todos los de `A` |
| id de carpeta | `folderIds = [id]` | solo los de esa carpeta |

Con «Ver archivos compartidos», los compartidos por otras cuentas se suman en la rama «sin carpeta» y «todas» (`DoctrineMediaRepository`, y `InMemoryMediaRepository::matches`). El criterio de texto con búsqueda por carpeta es `name LIKE ∨ fileName LIKE ∨ folderId IN nameFolderIds`; el repositorio de `Media` no hace `JOIN` con `Folder`, `ListMediaService` resuelve antes las carpetas que coinciden con `CurrentAccountFolders::matchingName`.

## Flujo de una operación

**Ver y entrar en una carpeta** (`GET /library/media`, ruta `library_media`).
1. `Media/ListController.php:48-50` crea o recupera de sesión el filtro (`FilterTrait`). Si la carpeta guardada ya no existe en la cuenta actual (`CurrentAccountFolders::find` devuelve `null`) la pone a `null` (`:52-58`).
2. `:60` carga `$accountFolders = $folders->all()` una sola vez (ordenadas por nombre, cuenta efectiva con impersonación); `:62-68` calcula `$currentFolder`; `:70` conserva `$folderChoice = $filter->folder` **antes** de `applyFolderChoice()` (`:72`), porque la plantilla necesita el valor original (`null`, `'all'` o id).
3. `:100` calcula `$canManageFolders` autorizando `Action::Register` sobre una carpeta sonda de la cuenta actual (`FolderAuthorizer`). La plantilla recibe `folders`, `currentFolder`, `folderChoice`, `canManageFolders` y `folderNames` (id → nombre, para la columna «Carpeta»).
4. `templates/library/media/list.html.twig`: el título añade `> {carpeta}` o `> Todas las carpetas`; el bloque `list_top` (definido en `templates/shared/layout/list.html.twig:16`, entre la tarjeta de filtros y la de la tabla) pinta la tarjeta solo si hay carpetas o `canManageFolders`. «Nueva carpeta» aparece si `canManageFolders`. Con carpetas: `FolderTile` «Sin carpeta» (activa si `folderChoice is null`), una por carpeta (activa si es `currentFolder`; con `Editar`/`Eliminar` según `folder | is_authorized('edit'|'remove')`) y «Todas las carpetas» (activa si `folderChoice == 'all'`). Sin carpetas (y por tanto gestor): texto «Todavía no hay carpetas».
5. Al pulsar una ficha, `filter_controller.js` `setField`: escribe `value` en el campo del filtro (`field` = `#{{ form.folder.vars.id }}`, el desplegable «Carpeta» del panel de filtros, que siempre está en el DOM), lanza `change` (el controlador del panel pone la página a 0) y envía el formulario. La carpeta actual sigue en sesión con el resto del filtro; no hay URL por carpeta.
6. El enlace «Subir archivo» incluye `?folder=<id>` solo cuando hay `currentFolder`.

**Crear / renombrar carpeta.** `library_folders_register` (`Folder/RegisterController.php:28`) y `library_folders_edit` (`EditController.php:26`) autorizan `Action::Register` / `Action::Edit` → `RegisterFolderService` / `UpdateFolderService`. `FolderAlreadyExistsException` se captura dentro del closure de `RegisterTrait`/`EditTrait` y se añade como error del campo «Nombre» («Ya existe una carpeta con ese nombre»). Al terminar redirigen a `library_media` con el flash «Carpeta creada con éxito» / «Carpeta actualizada con éxito». «Volver» (`TopAction:Return`) también apunta a `library_media`; títulos «Biblioteca > Nueva carpeta» y «Biblioteca > Editar carpeta > {nombre}».

**Eliminar carpeta.** `library_folders_remove` (`RemoveController.php:19-21`): GET con `#[CsrfProtected('remove')]`; el enlace de la ficha lo genera `List:Action:Delete`, que añade `_token`. Ejecuta `RemoveFolderService` y redirige a `library_media` (`:44`) con «Se ha eliminado la carpeta {folder}» o, si falla, «No se ha podido eliminar la carpeta {folder}». Si se estaba dentro de esa carpeta, el filtro guardado deja de apuntar a una carpeta existente y el listado vuelve al base (paso 1 de arriba), donde aparecen sus archivos.

**Subir con carpeta preseleccionada.** `Media/UploadController.php:51-54`: lee `?folder=`; si `CurrentAccountFolders::find` la devuelve (carpeta de la cuenta actual) el formulario nace con `new UploadMediaRequest(folderId: …)`; si es de otra cuenta, inexistente o vacío, con el `UploadMediaRequest` por defecto. La validez real al enviar la sigue garantizando `MediaFolderGuard` en `UploadMediaService`, que lo invoca **antes** de `storage->store()` para no dejar un archivo guardado con carpeta inválida.

**Mover archivos.** Tres vías, todas con `MediaFolderGuard`:
1. En bloque (`ListController` con `BulkMediaType` y `[MoveMedia, RemoveMedia]`): `MoveMedia::commands()` crea un `MoveMediaRequest` por archivo. El desplegable `folderId` solo se muestra con la acción `move` (Stimulus `showable`, `BulkMediaType.php:18-24`). `MoveMediaService` carga, exige `Action::Edit` de `MediaAuthorizer`, llama al guard y mueve si la carpeta cambia. «Sin carpeta» saca el archivo de su carpeta.
2. Edición: campo `folderId` en `UpdateMediaType`. `UpdateMediaService` valida **todo antes de cambiar nada** (carpeta con el guard, `Action::Admin` si cambia `shared`).
3. Subida: campo `folderId` en `UploadMediaType`.

**Selector y asistente.** `Picker/SearchController.php` acepta `folder` (vacío = todo, `none` = sin carpeta, id = esa carpeta; un id ajeno se ignora), activa la búsqueda por nombre de carpeta y devuelve el nombre de la carpeta en cada elemento. El desplegable del selector (`custom_types.html.twig:218`) solo se pinta si la cuenta tiene carpetas. `ListMediaTool` añade el parámetro `folder` (coincidencia parcial; sin carpetas coincidentes responde «No hay ninguna carpeta con ese nombre» sin consultar archivos) y el campo `folder` por fichero. Los nombres de carpeta se resuelven siempre con `CurrentAccountFolders`.

## Autorización y ámbito

`FolderAuthorizer` (`FolderAuthorizer.php`): `canView`/`canList` = la carpeta es de la cuenta actual (`currentAccountId()`, con impersonación; carpeta sin cuenta solo para quien no tiene cuenta efectiva); `canRegister`/`canEdit`/`canRemove` además exigen rol efectivo `SuperAdmin`, `Admin` u `Owner` (`userHasRole`). Un `User` ve las fichas y entra en las carpetas, pero no ve «Nueva carpeta», «Editar» ni «Eliminar», y las URL directas dan 403 (los controladores traducen `UnauthorizedActionException`). Si la cuenta no tiene carpetas, el `User` no ve la tarjeta. Mover un archivo exige `Action::Edit` de `MediaAuthorizer` (quien lo subió, Owner, Admin) y poder ver la carpeta destino. Suplantando una cuenta se ven y gestionan las carpetas de la cuenta suplantada. No hay feature flags ni API REST de carpetas.

## Persistencia y migración

- `media_folders`: `id` (`folder_id`), `name` varchar 255, `account_id` nulo, `registered_at`; índice `idx_media_folder_account`.
- `media.folder_id` nulo (`folder_id`), índice `idx_media_folder`. Sin FK ni backfill: todo lo existente queda sin carpeta.
- Migración `migrations/Version20261119171746.php` (aplicable). Este plan no añade migración.
- Los tests regeneran el esquema SQLite desde el mapping, así que no detectan una migración ausente.

## Configuración y operación

Sin variables de entorno, colas, comandos ni tareas programadas. Rutas por atributo (importación `library-web` de `config/routes.yaml`, con `%app.admin_prefix%/{_locale}`): `library_media`, `library_media_upload`, `library_folders_register`, `library_folders_edit`, `library_folders_remove`. `library_folders` ya no existe.

Traducciones nuevas de este plan en `translations/messages+intl-icu.{es,en}.xlf`: «Editar carpeta» y «Todavía no hay carpetas». El SCSS se compila con `make develop`.

## Cómo extenderlo

- **Acción nueva en la ficha:** añadirla en el bloque de `Library:FolderTile` dentro del `{% for folder in folders %}` de `list.html.twig`, condicionada con `folder | is_authorized('…')`; añadir la acción a `FolderAuthorizer` si procede.
- **Otro listado con fichas que filtren:** definir el bloque `list_top` y usar `FolderTile` (o un componente análogo) con `data-action="filter#setField"`, `data-filter-field-param` (selector del campo del filtro) y `data-filter-value-param`; el valor vacío debe llegar como cadena vacía.
- **Nuevo criterio de listado por carpeta:** propiedad nueva en `MediaFilter` con valor neutro y reflejarla en **los dos** repositorios (`DoctrineMediaRepository`, `InMemoryMediaRepository`); se prueba una vez en `MediaRepositoryTest`.
- **Nuevo punto que asigne carpeta a un archivo:** pasar siempre por `MediaFolderGuard::assertAssignable` antes de efectos secundarios y por `Media::moveToFolder`.
- **Nuevo desplegable de carpetas:** `FolderChoiceType` (valor `?FolderId`) o `FolderFilterChoiceType` (valor `?string`).
- **Mostrar el nombre de una carpeta:** con `CurrentAccountFolders` o el filtro Twig `folder_name`, nunca con el repositorio directo (filtraría carpetas de otras cuentas).
- Trampas: `CurrentAccountFolders` no memoiza a propósito (el kernel de Behat reutiliza servicios entre peticiones); `RegisterTrait`/`EditTrait` tragan cualquier `\Exception`, por eso las capturas de dominio van dentro del closure; con muchas carpetas la tira hace `flex-wrap` sin paginar y `CurrentAccountFolders::all()` carga todas.

## Tests

- Unitarios (`tests/Unit/Library/`): `Domain/Model/Folder/*` (repositorio abstracto ejecutado en memoria y Doctrine), `Domain/Model/Media/{MediaTest,MediaFilterTest,MediaRepositoryTest}`, `Application/Folder/*` (authorizer, `CurrentAccountFolders`, servicios de alta, edición y borrado), `Application/Media/{MediaFolderGuardTest,Move,List,Update,Upload,Assistant}`, `MediaPickerTypeTest`; y la lista de hojas del menú en `tests/Unit/Shared/Infrastructure/UI/Web/Navigation/NavigationSearchProviderTest.php`.
- Aceptación (`tests/Acceptance/Library/`): `folder-browse.feature` (fichas por rol y cuenta, tarjeta vacía, carpeta vacía, entrar en una carpeta, suplantación, 404 de `/es/library/folders`, preselección en la subida), `folder-register`, `folder-edit`, `folder-remove` (comprueban con `the response redirects to "/es/library/media"`), `media-folder` y `media-picker`. Pasos de las fichas en `LibraryContext.php` (`I see the folder tiles`, `the folder tile … is active`, comprobando el campo y el valor que cada ficha envía) y de la subida (`the upload form has the folder … selected`).
- Behat no ejecuta JavaScript: el funcionamiento real de `setField` (cambio de carpeta, vuelta a la página 1, conservación del resto de filtros, resaltado) solo se comprueba a mano en el navegador.
- Ejecución: `make test-unit options="--filter Folder"`, `make test-acceptance options="tests/Acceptance/Library/folder-browse.feature"`, o `make test-parallel` para todo. Última validación del plan: `make cs-fix`, `make phpstan` y `make test-parallel` en verde (2189 unitarios, 567 escenarios).

## Desviaciones y deuda

- Sin hallazgos abiertos en [`review.md`](review.md) (B1 y B2 corregidos).
- Desviación declarada en `## Implementación` de [`plan.md`](plan.md): los `.feature` de alta/edición/borrado usan `the response redirects to` en lugar de `I am redirected to` (este último ya sigue la redirección).
- Fuera de alcance (no implementado): URL propia por carpeta, arrastrar y soltar, contador de archivos por ficha, alta/edición en modal, carpetas anidadas.
- Pendiente para el usuario: comprobar a mano las fichas en el navegador y borrar la entrada y manuales publicados del plan anterior (`changelog/entries/2026-11-20-carpetas-para-la-biblioteca.md`, `changelog/manuals/carpetas-para-la-biblioteca.{pdf,tech.md}`).
