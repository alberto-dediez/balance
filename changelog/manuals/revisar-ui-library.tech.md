# Interfaz de subida y gestión de archivos de la Biblioteca — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-25 (correcciones de review el 2026-09-26) · **Bounded Contexts:** `Library` (UI), `Shared` (tema de formulario y Stimulus), `Security` (formulario de perfil)
- **Revisión:** `review.md` (revisión 1) y `review-fix.md` (B1, B2 y B6 aplicados; B4 y B5 descartados por el usuario; B3 asumible; Q1 aplicada; Q2 verificación manual pendiente del usuario), misma carpeta

## Qué hace y por qué

La subida y gestión de archivos funcionaba, pero la interfaz era tosca: el selector de archivos
(`MediaPickerType`) mostraba tres botones grandes, miniaturas planas y un modal de resultados sin
paginación; los demás formularios con archivo usaban el `<input type="file">` nativo; el listado y la
edición de la Biblioteca tenían estilos a mano. El cambio es **solo de presentación**: dominio,
aplicación, persistencia, rutas y el JSON del picker no cambian.

Piezas del resultado:

- **Zona de arrastre global para todo `FileType`**: bloque `file_widget` en el tema de formulario
  global, con el controlador Stimulus `file-input`. Los cinco `FileType` del proyecto la obtienen sin
  tocar sus form types ni sus plantillas (decisión *Zona de arrastre reutilizable para todo `FileType`*).
- **Selector de archivos rediseñado**: zona de arrastre que sube por AJAX, botones pequeños
  "Biblioteca" y "Quitar", miniaturas en tarjeta o avatar circular (nueva opción `preview`), modal con
  tarjetas, marca de seleccionado, estado vacío y "Cargar más".
- **Componente `Library:Thumbnail`**: único sitio donde vive el mapa tipo → icono; lo usan listado,
  edición y las `<template>` del picker.
- **Listado y edición** alineados con los componentes compartidos (badges por valor, `Link`
  `primarySubtle` para descargar) y **"Subir archivo"** a dos columnas.

Principio transversal: nada que venga del servidor se pinta con `innerHTML`; el JS clona `<template>`
renderizadas en Twig y rellena con `textContent` / atributos, de modo que el aspecto se define en un
solo sitio y los textos se traducen en Twig (decisión *Markup de las miniaturas en un solo sitio*).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Form type | `src/Library/Infrastructure/Symfony/Form/Type/MediaPickerType.php` | Opción `preview` (`card`/`avatar`) y var de vista `preview` | Modificado |
| Form type | `src/Security/Infrastructure/Symfony/Form/ProfileType.php` | `picture` usa `preview: 'avatar'` | Modificado |
| Tema de formulario | `templates/shared/form/custom_types.html.twig` | Bloque nuevo `file_widget`; reescritura de `media_picker_widget` | Modificado |
| Componente Twig | `templates/components/Library/Thumbnail.html.twig` | Miniatura imagen/icono por tipo; modo `slots` para clonar desde JS | Nuevo |
| Stimulus | `assets/controllers/file_input_controller.js` | Zona de arrastre de `FileType`: clic, arrastre, lista de archivos | Nuevo |
| Stimulus | `assets/controllers/media_picker_controller.js` | Picker: plantillas, arrastre, feedback, paginación, botón "Quitar" | Modificado |
| Plantilla | `templates/library/media/list.html.twig` | `Library:Thumbnail` + badges de tipo, visibilidad y "Compartido" | Modificado |
| Plantilla | `templates/library/media/edit.html.twig` | `Library:Thumbnail` `lg` + `Link` de descarga | Modificado |
| Plantilla | `templates/library/media/upload.html.twig` | Bloque `content` propio a dos columnas | Modificado |
| Plantilla | `templates/security/profile.html.twig` | Columnas responsivas `col-12 col-md-8/4` | Modificado |
| SCSS | `assets/styles/components/_dropzone.scss`, `_media-picker.scss`, `_thumbnail.scss` | Estilos BEM solo con `var(--bs-*)` | Nuevos |
| SCSS | `assets/styles/app.scss:40-42` | Tres `@import` nuevos | Modificado |
| Iconos | `assets/icons/solar/{cloud-upload,gallery-wide,trash-bin-minimalistic,check-circle,videocamera,music-note,file,folder-with-files}-line-duotone.svg` | Iconos nuevos (vía `make icon-import`) | Nuevos |
| Traducciones | `translations/messages+intl-icu.{es,en}.xlf` | 5 claves nuevas | Modificado |
| Tests | `tests/Unit/Shared/Infrastructure/Symfony/Form/FileWidgetTest.php` | Render del `file_widget` | Nuevo |
| Tests | `tests/Unit/Library/Infrastructure/Symfony/Form/Type/MediaPickerTypeTest.php`, `tests/Acceptance/Library/media-picker.feature`, `media-upload.feature` | Ampliados | Modificados |

Sin cambios (explícitamente): `SearchController.php`, `AjaxUploadController.php`,
`MediaJsonResponseTrait.php`, `UploadController.php`, los cinco form types con `FileType` y sus
plantillas, `importmap.php`, `assets/controllers.json`, `config/packages/twig.yaml`.

## Modelo de dominio

Sin cambios. La presentación se apoya en los valores existentes: `MediaType` (`Image`, `Document`,
`Video`, `Audio`, `Other`) y `MediaVisibility` (`Account`, `System`, `Public`;
`isAccessibleOutsideAccount()` es cierto para todo salvo `Account`,
`src/Library/Domain/Model/Media/MediaVisibility.php:15-18`).

## Flujo de una operación

### Zona de arrastre de cualquier `FileType`

1. `form_row` de `bootstrap_5_layout` pinta etiqueta, ayuda y errores y llama al widget; como ni
   `bootstrap_5_layout` ni `form_div_layout` definen `file_widget`, el del tema global
   (`custom_types.html.twig:49-74`) gana para todo `FileType`.
2. El bloque deduce el plural con `attr.multiple is defined` (`:50`; `FileType` no expone la variable
   `multiple`, solo añade `attr.multiple`) y añade `dropzone__input` a la clase (`:51`).
3. El `<input type="file">` real (`:56`) conserva `id`, `name` (con `[]` si es múltiple), `required`,
   `accept`, `multiple`… vía `widget_attributes`; está oculto con `opacity: 0` y cubre toda la zona
   (`_dropzone.scss:40`), así que el clic nativo abre el diálogo aunque el JS no cargue.
4. `file_input_controller.js`:
   - `browse()` (`:6-12`) ignora el clic cuyo `target` es el input (evita abrir el diálogo dos veces).
   - `dragOver()`/`dragLeave()` ponen/quitan `is-dragover`.
   - `drop()` (`:24-42`) construye un `DataTransfer` con todos los archivos o solo el primero si el
     input no es `multiple`, lo asigna a `inputTarget.files` y llama a `update()`.
   - `update()` (`:44-66`) lista cada archivo clonando el icono de `<template data-file-input-target="fileTemplate">`
     más nombre y tamaño (`formatSize()`, base 1024, B/KB/MB/GB con un decimal).
5. El envío es el del formulario normal; la validación de tipo y tamaño la siguen haciendo las
   `constraints` de cada form type en servidor.

### Selector de archivos (`media_picker_widget`)

1. `MediaPickerType::buildView()` (`MediaPickerType.php:112-138`) vuelca los `data-media-picker-*-value`
   (rutas de búsqueda y subida, tipos, `accept`, visibilidad, token CSRF) y expone `multiple`,
   `placeholder`, `visibility`, `selected` y `preview` (`:137`).
2. El bloque (`custom_types.html.twig:76-237`) pinta:
   - dos `<template>`: `itemTemplate` (tarjeta de selección, `:80-90`) y `resultTemplate` (tarjeta del
     modal, `:92-108`) con `Library:Thumbnail slots="true"` y un badge oculto **por tipo**, ya traducido
     con el dominio `mediaType` (`:103-105`, corrección B2);
   - la selección inicial con `Thumbnail` real o el estado vacío (icono de usuario en avatar, de
     galería en card, `:110-128`);
   - los `<input type="hidden">` con el valor (`:130-138`);
   - la zona de arrastre (`dropzone--compact` en avatar) con el aviso "Los archivos de este campo son
     accesibles para: {visibility}" si la visibilidad sale de la cuenta (`:140-160`);
   - los botones "Biblioteca" y "Quitar" (este con `d-none` si no hay selección) y el feedback
     (`:162-179`);
   - el `<input type="file" class="d-none">` (`:181`), que no se envía con el formulario;
   - el modal con buscador, interruptor "Ver archivos compartidos", resultados, estado vacío y
     "Cargar más" (`:183-235`).
3. `media_picker_controller.js`:
   - **Subida**: `browseFile()` abre el diálogo; `drop()` (`:77-89`) toma los archivos soltados (solo
     el primero si no es múltiple). `uploadNext()` (`:293-335`) los sube en secuencia a
     `library_media_ajax_upload` con `types`, `visibility` y `_token`; por cada éxito llama a
     `select(json.element)`; un error (`json.error` o fallo de red) se muestra y la cola continúa. El
     feedback lo gestiona `setFeedback(message, state)` (`:337-350`): `uploading` añade spinner y
     `is-uploading` (zona deshabilitada), `error` pone `text-danger`, `''` lo vacía (solo si no hubo error).
   - **Biblioteca**: `openLibrary()` (`:43-51`) reinicia la página y llama a `search()`, que pide
     `library_media_search` con `query`, `types`, `visibility`, `includeShared`, `page` y `limit=24`
     (`:129-144`). `renderResults()` (`:146-166`) clona `resultTemplate`, marca `is-selected` los ya
     elegidos, muestra el estado vacío si no hay nada y "Cargar más" mientras
     `(page + 1) * limit < totalResults`. `loadMore()` (`:91-97`) incrementa la página y añade.
   - **Relleno**: `fillSlots()` (`:168-200`) pone el nombre con `textContent`, muestra el badge y el
     icono cuyo `data-type` coincide con `element.type` (fallback a `Other`) o la imagen si `isImage`.
   - **Selección**: `select()` (`:202-219`) sustituye en modo simple; en múltiple ignora duplicados.
     `clear()`, `removeItem()` y `select()` acaban en `syncClearButton()`; al quedar vacío se repinta
     el estado vacío guardado en `connect()` (`renderEmptySelection()`, `:105-117`).
4. El valor se persiste al enviar el formulario que contiene el picker (en "Mi perfil",
   `ProfileController` con "Guardar"); la subida AJAX solo crea el archivo en la Biblioteca.

### Pantallas de la Biblioteca

- **Listado** (`templates/library/media/list.html.twig`): mapas `type_colors` y `visibility_colors`
  (`:6-7`); miniatura `sm` con `alt` = texto alternativo o nombre (`:39`, corrección B1); badges de tipo
  (`:49`), visibilidad (`:53`) y "Compartido" (`:55-57`).
- **Edición** (`edit.html.twig`): `Thumbnail` `lg` (`:18`) y botón "Descargar" con `Link`
  `primarySubtle` y `title` para el tooltip (`:24-28`).
- **Subir archivo** (`upload.html.twig:11-36`): `Card` con el archivo en `col-lg-7` y los metadatos en
  `col-lg-5`; `ActionBar` con "Subir".

## Autorización y ámbito

Sin cambios. La búsqueda y la subida AJAX siguen pasando por `ListMedia`/`UploadMedia` y
`MediaAuthorizer`; el token CSRF del picker va ligado a la visibilidad exigida por el campo
(`MediaPickerType::csrfTokenId()`, comprobado en `AjaxUploadController.php:53`). La opción "Compartir
en la biblioteca" de "Subir archivo" solo aparece a quien tiene `Action::Admin` sobre el archivo
(`UploadController.php:48`, `MediaAuthorizer::canAdmin()`: `Admin`/`SuperAdmin` de la cuenta).

## Persistencia y migración

No hay cambios de esquema ni migración.

## Configuración y operación

- Sin variables de entorno ni flags.
- Tras tocar el SCSS: `make develop` (compila `assets/styles/app.scss`).
- Iconos nuevos: `make icon-import p="solar:<nombre>"`.
- Textos nuevos (ES/EN): "Arrastra aquí el archivo o pulsa para elegirlo", "Arrastra aquí los archivos
  o pulsa para elegirlos", "Cargar más", "Seleccionado", "Compartido" (`make translation`).
- Ninguna librería nueva: arrastre nativo del navegador y `Modal` de Bootstrap.

## Cómo extenderlo

- **Nuevo campo de archivo**: basta con un `FileType`; hereda la zona de arrastre. Para limitar tipos
  en el diálogo, `attr: ['accept' => '...']` (llega al input). Si un campo necesitara el input nativo,
  darle un `block_prefix` propio.
- **Nuevo campo que elige de la Biblioteca**: `MediaPickerType` con `types`, `accept`, `visibility`,
  `multiple`, `placeholder` y `preview` (`'card'` por defecto, `'avatar'` para una imagen circular;
  cualquier otro valor lanza `InvalidOptionsException`).
- **Nuevo `MediaType`**: añadir su icono en el mapa de `templates/components/Library/Thumbnail.html.twig:3-9`,
  su color en `type_colors` de `list.html.twig:6`, su badge en la lista de
  `custom_types.html.twig:103` y su traducción en `translations/mediaType+intl-icu.*.xlf`.
- **Trampas**:
  - No usar `display: none` en `.dropzone__input`: el input debe seguir en el DOM y clicable.
  - Conservar los selectores de los que dependen los tests: `data-media-picker-token-value` en la
    raíz, targets `selection`, `inputs`, `modal`, el input real con su `name` y el botón "Subir".
  - Con `strict_variables` en test, `file_widget` solo puede usar variables garantizadas por
    `FileType`/`FormType` (de ahí `attr.multiple is defined`).
  - Todo texto que pinte el JS debe llegar ya traducido desde Twig (variantes ocultas por valor), nunca
    el valor crudo del enum.

## Tests

- **Unitarios**
  - `tests/Unit/Shared/Infrastructure/Symfony/Form/FileWidgetTest.php`: render como zona de arrastre,
    nombre `upload[file][]` y texto en plural con `multiple`, conservación de `accept`.
  - `tests/Unit/Library/Infrastructure/Symfony/Form/Type/MediaPickerTypeTest.php`: `testItRendersTheWidget`
    ampliado (`itemTemplate`, `dropzone`, `media-picker--card`), `testTheResultsShowTheTranslatedTypeOfEachMedia`,
    `testItRendersTheAvatarPreview`, `testItRejectsAnUnknownPreview`,
    `testTheClearButtonIsHiddenWhenNothingIsSelected`.
- **Aceptación**
  - `tests/Acceptance/Library/media-upload.feature:51` — la pantalla de subida muestra la zona de arrastre;
    los seis escenarios previos siguen verdes con el input real.
  - `tests/Acceptance/Library/media-picker.feature:112` — "Mi perfil" ofrece "Biblioteca" y la zona de
    arrastre y ya no "Seleccionar de la biblioteca".
- **Ejecución**: `make test-unit options="--filter 'FileWidgetTest|MediaPickerTypeTest'"`,
  `make test-acceptance options="tests/Acceptance/Library/media-picker.feature"` y
  `make test-acceptance options="tests/Acceptance/Library/media-upload.feature"`.
- **Huecos**: no hay tests de JavaScript. Arrastre, clonado de plantillas, "Cargar más", feedback de
  error y el aspecto en modo claro/oscuro solo se verifican a mano (lista *Verificación manual* del
  plan; pendiente del usuario, `review-fix.md` Q2).

## Desviaciones y deuda

Ver `## Implementación` de `plan.md` y `review-fix.md`:

- Desviaciones declaradas: plural de `file_widget` por `attr.multiple`; textos de la zona del picker
  en un `div` común para el modo compacto; tamaño del icono elegido en Twig; sin `width` inline en el
  listado; propiedades SCSS auxiliares; `id` regenerados de seis `trans-unit` de 2FA.
- Correcciones posteriores al plan (contradicen `plan.md`, pendiente de actualizar vía `feature-planner`):
  `alt` del listado con el texto alternativo (B1, `plan.md:239`) y badge de tipo traducido en el modal
  (B2, `plan.md:182`).
- Deuda abierta:
  - Carrera en `search()`/`loadMore()`: una respuesta obsoleta puede pintarse sobre la nueva y un
    "Cargar más" fallido oculta el botón (B4, descartado por el usuario).
  - `assets/icons/solar/file-text-line-duotone.svg` sin uso (B5, descartado; se conserva).
  - Claves huérfanas "Compartido en la biblioteca" y "Seleccionar de la biblioteca" en los `.xlf` (B3, asumible).
  - `UploadController.php:45` instancia `new Media(...)` fuera de su servicio (fuera de alcance).
  - `.file-input` de `assets/styles/components/_form.scss` sin uso (fuera de alcance).
