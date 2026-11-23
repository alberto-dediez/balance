# Subcarpetas de la biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-20 (correcciones de review aplicadas el 2026-11-23) · **Bounded Contexts:** `Library`, `Shared` (plantillas de listados y tema de formulario)

## Qué hace y por qué

Las carpetas de la biblioteca (`Folder`) pasan de un nivel a dos: una carpeta puede tener subcarpetas y una subcarpeta no puede tener hijas. No hay agregado nuevo: una subcarpeta es un `Folder` con `parentId` (`src/Library/Domain/Model/Folder/Folder.php:23`). `Media` no cambia: ya referenciaba su carpeta por id, así que un archivo puede estar en una subcarpeta tal cual.

Cambia además el significado de eliminar: eliminar una carpeta o subcarpeta elimina sus archivos (y, en una carpeta, sus subcarpetas con sus archivos). Si algún archivo no se puede eliminar, la carpeta no se elimina (`RemoveFolderService.php:36-83`). Las decisiones de diseño están en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Library/Domain/Model/Folder/Folder.php` | `parentId`, `isSubfolder()`, `register(..., ?FolderId $parentId)` | modificado |
| Dominio | `.../Folder/FolderRegistered.php` | Evento con `parentId` en constructor, `payload()` y `rebuildFromPayload()` (tolera payloads antiguos sin la clave, l. 69) | modificado |
| Dominio | `.../Folder/FolderFilter.php` | Criterio `parentId` | modificado |
| Dominio | `.../Folder/FolderRepository.php` | `findOneByAccountAndName(?AccountId, string, ?FolderId $parentId = null)` | modificado |
| Dominio | `.../Folder/FolderNestingNotAllowedException.php` | El padre ya es subcarpeta | nuevo |
| Dominio | `.../Folder/FolderNotRemovedException.php` | Hay archivos que no se pudieron eliminar (`failedMedia()`) | nuevo |
| Aplicación | `src/Library/Application/Folder/CurrentAccountFolders.php` | Árbol: `roots()`, `subfoldersOf()`, `tree()`, `paths()`, `idsWithSubfolders()`, `matchingName()` | modificado |
| Aplicación | `.../Folder/Register/RegisterFolderService.php` (+ Request/Response) | Valida el padre y la unicidad por padre | modificado |
| Aplicación | `.../Folder/Update/UpdateFolderService.php` | Unicidad dentro del mismo padre | modificado |
| Aplicación | `.../Folder/Remove/RemoveFolderService.php` (+ Response) | Borrado en cascada de archivos y subcarpetas | modificado |
| Aplicación | `src/Library/Application/Media/Remove/RemoveMediaService.php:41` | Borra del almacén antes de marcar el archivo como eliminado | modificado |
| Aplicación | `src/Library/Application/Media/List/ListMediaRequest.php` | Valores `all:<id>`, `chosenFolderId()`, `includesSubfolders()`, `applyFolderChoice(list<FolderId>)` | modificado |
| Aplicación | `src/Library/Application/Media/Assistant/ListMediaTool.php` | Nombre de carpeta con ruta (`paths()`) y búsqueda que incluye subcarpetas | modificado |
| Persistencia | `.../Persistence/Doctrine/Mapping/Folder.Folder.orm.xml`, `DoctrineFolderRepository.php`, `InMemoryFolderRepository.php` | Columna `parent_id`, índice, filtros | modificado |
| Persistencia | `migrations/Version20261121081813.php` | Migración creada por el usuario | nuevo |
| Infraestructura | `.../UI/Web/Folder/{Register,Edit,Remove}Controller.php` | `?parent=`, mensajes y avisos | modificado |
| Infraestructura | `.../UI/Web/Media/ListController.php` | Calcula carpeta activa, segunda tira y modo «todas las subcarpetas» | modificado |
| Infraestructura | `.../UI/Web/Media/Picker/SearchController.php` | Parámetros `folder` y `subfolder` | modificado |
| Infraestructura | `.../Symfony/Form/Type/{FolderChoiceType,FolderFilterChoiceType,MediaPickerType}.php`, `.../Symfony/Twig/FolderExtension.php` | Rutas «Contratos / 2024» y opciones `all:<id>` | modificado |
| Plantillas | `templates/library/media/list.html.twig`, `templates/library/folder/{register,edit}.html.twig`, `templates/security/admin/events/detail/library.folderRegistered.html.twig` | Segunda tira, formularios, detalle del registro de auditoría | modificado |
| Shared | `templates/components/List/Action/Delete.html.twig` (prop `confirm`), `templates/components/List/FilterBar.html.twig` (chip `all:<id>`), `templates/shared/form/custom_types.html.twig` (selector de subcarpeta) | | modificado |
| JS | `assets/controllers/media_picker_controller.js` | `changeFolder()`, objetivo `subfolder`, parámetro `subfolder` en la búsqueda | modificado |

## Modelo de dominio

- `Folder` guarda `parentId` solo a través de `FolderRegistered` (`applyFolderRegistered`, `Folder.php:97-103`); `rename` y `remove` no lo tocan. Una carpeta no cambia de padre.
- Invariantes, validados en `RegisterFolderService`:
  - El padre existe y es de la misma cuenta (ambas nulas cuentan como iguales) o se lanza `FolderNotFoundException` (`:59-66`, `:72-79`).
  - El padre no es subcarpeta o se lanza `FolderNestingNotAllowedException` (`:67-69`).
  - El nombre es único por cuenta **y padre**, sin distinguir mayúsculas (`:44`). Dos subcarpetas con el mismo nombre en padres distintos, o una subcarpeta con el nombre de una carpeta, son válidas.
- El evento `FolderRegistered` lleva `parentId` (nulo en carpetas). El detalle de auditoría muestra «Creada la subcarpeta {name}» si lo tiene.

## Flujo de una operación

**Crear subcarpeta.** `library_folders_register` con `?parent=<id>` → `RegisterController` resuelve el padre con `CurrentAccountFolders::find()` (acota a la cuenta actual) y responde 404 si no existe o es subcarpeta (`RegisterController.php:57`) → `RegisterFolderService` (autoriza `Action::Register`, valida padre y nombre, registra, persiste) → flash «Subcarpeta creada con éxito» y redirección a `library_media`. `FolderAlreadyExistsException` se muestra como error del campo `name`; `FolderNotFoundException`/`FolderNestingNotAllowedException` como 404.

**Editar.** `UpdateFolderService` comprueba unicidad con `findOneByAccountAndName(accountId, name, folder.parentId())` (`UpdateFolderService.php:42`). El controlador muestra el padre en el título (`edit.html.twig`).

**Eliminar.** `library_folders_remove` (`#[CsrfProtected('remove')]`) → `RemoveFolderService`:
1. Autoriza `Action::Remove` sobre la carpeta.
2. Por cada subcarpeta (`FolderFilter(parentId: ...)`): invoca `RemoveMediaService` archivo a archivo; si ninguno falla, elimina la subcarpeta (`:48-59`).
3. Hace lo mismo con los archivos directos de la carpeta; si en total hay fallos lanza `FolderNotRemovedException` y la carpeta se conserva (`:61-71`). Una subcarpeta sin fallos ya eliminada no se restaura.
4. `RemoveMediaService` borra primero del almacén (`:41`) y después marca el archivo como eliminado, para que un fallo del almacén no deje un archivo «eliminado» con fichero huérfano. Cualquier `\Exception` del archivo cuenta como fallo.
5. El controlador traduce `FolderNotRemovedException` en un flash `danger` específico y cualquier otra excepción en uno genérico (`RemoveController.php:41-45`); siempre redirige a `library_media`.

**Listado.** `ListController` (`:48-96`): el filtro `folder` de `ListMediaRequest` vale `null`, `'all'`, `<id>` o `'all:<id>'`. Si la carpeta elegida ya no existe o es de otra cuenta se vuelve a `null`; si se pide `all:<id>` sobre una subcarpeta o una carpeta sin subcarpetas se degrada a `<id>`. `activeFolder` es la carpeta de primer nivel (la propia o la madre de la subcarpeta elegida) y de ella salen las fichas de la segunda tira. Con `all:<id>`, `applyFolderChoice()` añade al filtro los ids de las subcarpetas.

**Selector de archivos.** `library_media_search` acepta `folder` (`none`, vacío o id de carpeta de primer nivel) y `subfolder` (vacío, `all` o id de subcarpeta cuyo padre sea esa carpeta; si no coincide se ignora) (`SearchController.php:63,93-109`). `MediaPickerType` pasa a la vista el árbol (`tree()`) y `custom_types.html.twig` pinta un `<select>` de subcarpetas por carpeta que las tenga; `media_picker_controller.js` los muestra u oculta (`changeFolder`) sin lógica de negocio.

## Autorización y ámbito

- `FolderAuthorizer` no cambia: ver/listar la cuenta actual; crear/editar/eliminar `Owner`, `Admin`, `SuperAdmin`. Una subcarpeta se autoriza igual que su carpeta.
- Todo acceso de lectura pasa por `CurrentAccountFolders`, que usa `CurrentAccountIdProvider` (cuenta impersonada incluida) y nunca revela carpetas de otra cuenta. `RemoveController` y `EditController` obtienen la carpeta con el repositorio y confían en el authorizer.
- El rol `User` ve las dos tiras pero no las acciones: `canManageFolders` (`ListController.php:102`) y `is_authorized` en la plantilla.

## Persistencia y migración

- `media_folders.parent_id` `VARCHAR(36)` nula, sin FK, tipo `folder_id`, con índice `idx_media_folder_parent` (`Folder.Folder.orm.xml:11,20`). Las carpetas existentes quedan con `parent_id` nulo: no hace falta backfill.
- Migración aplicada: `migrations/Version20261121081813.php` (coincide con `migration.sql`; `getDescription()` vacío). Al no haber FK, borrar una carpeta no cascada en base de datos: la cascada es de aplicación.
- El árbol se monta en PHP desde una sola lectura de `all()` (`CurrentAccountFolders::tree()`, `paths()`), sin memoización.

## Configuración y operación

Sin variables de entorno, colas, comandos ni tareas programadas nuevas. Traducciones nuevas en `translations/messages+intl-icu.{es,en}.xlf` (ES: «Subcarpetas de {folder}», «Nueva subcarpeta», «Sin subcarpeta», «Todas las subcarpetas», etc.).

## Cómo extenderlo

- Más niveles: habría que cambiar `assertValidParent` y sustituir `paths()`/`tree()` (que asumen dos niveles) por una navegación recursiva; la ruta se construye en `CurrentAccountFolders::paths()`.
- Mover una carpeta de padre exige un evento nuevo (`parentId` solo se fija en `FolderRegistered`).
- Para pintar un nombre de carpeta en cualquier pantalla usa `paths()` o el filtro Twig `folder_name`, no `Folder::name()`, para que las subcarpetas salgan como «Contratos / 2024».
- Trampa: `ListMediaRequest::$folder` ya no es solo un id; usa `chosenFolderId()` en lugar de construir un `FolderId` a mano.
- `choice_translation_domain` está a `false` en los tipos de carpeta: las etiquetas «Todas las carpetas» y «{folder} / Todas las subcarpetas» se traducen en PHP con `TranslatorInterface`.

## Tests

- Unitarios: `tests/Unit/Library/Domain/Model/Folder/{FolderTest,FolderRepositoryTest,FolderMother}.php`, `tests/Unit/Library/Application/Folder/{CurrentAccountFoldersTest,Register/RegisterFolderServiceTest,Update/UpdateFolderServiceTest,Remove/RemoveFolderServiceTest}.php`, `tests/Unit/Library/Application/Media/{List/ListMediaRequestTest,List/ListMediaServiceTest,Remove/RemoveMediaServiceTest,MediaFolderGuardTest,Assistant/ListMediaToolTest}.php`, `tests/Unit/Library/Infrastructure/Symfony/Form/Type/MediaPickerTypeTest.php`.
- Behat (`tests/Acceptance/Library/`): `subfolder-register.feature`, `subfolder-browse.feature`, `folder-edit.feature`, `folder-remove.feature`, `media-folder.feature`, `media-picker.feature`; pasos en `LibraryContext.php`.
- Ejecución: `make test-unit options="--filter Folder"`, `make test-acceptance options="tests/Acceptance/Library/subfolder-browse.feature"` (o `make test-parallel` para todo).
- No cubierto por Behat: el JavaScript del selector (`media_picker_controller.js`) y la confirmación del navegador; solo se comprueba su atributo en el HTML. El borrado físico del almacén se cubre solo en unitarios (hallazgo B2 de `review.md`, descartado).

## Desviaciones y deuda

- Desviaciones y deuda declaradas en `## Implementación` de `plan.md`; `review.md` no tiene hallazgos abiertos (A1, A2 y B1 corregidos, B2 descartado).
- `Media/ListController.php` y el filtro `folder_name` siguen leyendo todas las carpetas varias veces por petición (número constante, no crece con las carpetas), anotado en `review.md`.
- Al eliminar la subcarpeta en la que se está, el listado vuelve a «Sin carpeta», no a la carpeta madre (comportamiento aceptado en el plan).
