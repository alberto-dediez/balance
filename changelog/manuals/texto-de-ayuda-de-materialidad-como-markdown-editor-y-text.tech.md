# Ayuda de las preguntas y descripción de las categorías de materialidad con formato (Markdown) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-29 · **Revisado:** 2026-09-29 (`review.md`, 0 hallazgos) · **Bounded Contexts:** `Materiality` (reutiliza el editor de `Shared`)

## Qué hace y por qué

La **ayuda** de una pregunta de materialidad (`MaterialityQuestion::help`) y la **descripción** de una categoría de materialidad (`MaterialityTopic::description`) se escribían en un `TextareaType` de texto plano. Ahora los cuatro formularios de alta y edición usan el editor Markdown compartido (`MarkdownEditorType`, Toast UI en modo WYSIWYG) con botón de enlace, de modo que el Administrador puede usar negritas, listas, párrafos y enlaces sin escribir sintaxis Markdown.

El valor se guarda como **Markdown en crudo**: dominio y aplicación siguen tratando ambos campos como `string` opaco, sin validarlo ni transformarlo. No hay cambio de esquema porque las columnas ya eran `LONGTEXT` (tabla *Decisiones tomadas* de `plan.md`, fila «Tipo de columna»). Además, las cinco ayudas de las preguntas estándar que se siembran (`StandardMaterialityQuestion::help()`) pasan a estar redactadas en Markdown en español e inglés; solo afecta a siembras futuras.

Hoy ni la ayuda ni la descripción se muestran fuera de sus formularios de edición: el listado de preguntas no las incluye y el cuestionario del cliente aún no existe. Renderizarlas como HTML es trabajo del plan que construya ese cuestionario (ver *Cómo extenderlo*).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/StandardMaterialityQuestion.php:68-88` | `help(Locale)`: ayudas sembradas en Markdown (ES/EN) | modificado |
| UI | `src/Materiality/Infrastructure/Symfony/Form/RegisterMaterialityQuestionType.php:49-55` | Campo `help` con `MarkdownEditorType` | modificado |
| UI | `src/Materiality/Infrastructure/Symfony/Form/UpdateMaterialityQuestionType.php:51-57` | Campo `help` con `MarkdownEditorType` | modificado |
| UI | `src/Materiality/Infrastructure/Symfony/Form/RegisterMaterialityTopicType.php:49-55` | Campo `description` con `MarkdownEditorType` | modificado |
| UI | `src/Materiality/Infrastructure/Symfony/Form/UpdateMaterialityTopicType.php:45-51` | Campo `description` con `MarkdownEditorType` | modificado |
| Shared (reutilizado) | `src/Shared/Infrastructure/Symfony/Form/Type/MarkdownEditorType.php` | Form type, padre `TextareaType`, prefijo `markdown_editor` | sin cambios |
| Shared (reutilizado) | `templates/shared/form/custom_types.html.twig:12-29` | Bloque `markdown_editor_widget` (tema global) | sin cambios |
| Shared (reutilizado) | `assets/controllers/markdown_editor_controller.js` | Controlador Stimulus que monta Toast UI | sin cambios |
| Tests | `tests/Unit/Materiality/Infrastructure/Symfony/Form/MarkdownFieldsTest.php` | Render de los cuatro widgets | nuevo |
| Tests | `tests/Unit/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestionTest.php` | Ayudas estándar con formato | modificado |
| Tests | `tests/Unit/Materiality/Application/Seed/SeedStandardMaterialityMastersServiceTest.php` | Ayuda sembrada base y traducida | modificado |
| Tests | `tests/Unit/Materiality/Application/MaterialityQuestion/{Register/RegisterMaterialityQuestionServiceTest,Update/UpdateMaterialityQuestionServiceTest}.php` | Guardado literal y traducción de la ayuda | modificado |
| Tests | `tests/Unit/Materiality/Application/MaterialityTopic/{Register/RegisterMaterialityTopicServiceTest,Update/UpdateMaterialityTopicServiceTest}.php` | Guardado literal y traducción de la descripción | modificado |
| Tests | `tests/Acceptance/Materiality/MaterialityContext.php` | Cuatro pasos `Then` y dos helpers | modificado |
| Tests | `tests/Acceptance/Materiality/materiality-{question,topic}-{register,edit}.feature` | Escenarios de guardado | modificado |

Sin cambios: mappings `*.orm.xml`, `migrations/`, `src/Materiality/Application/`, agregados, plantillas `templates/materiality/admin/**`, `translations/`, `StandardScope3Category::description()`.

## Modelo de dominio

- `MaterialityQuestion::help()` / `defaultHelp()` (`src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestion.php:91`, `:104`) y `MaterialityTopic::description()` / `defaultDescription()` (`src/Materiality/Domain/Model/MaterialityTopic/MaterialityTopic.php:68`, `:81`) no cambian: devuelven la traducción del locale actual o el texto base. Ningún invariante nuevo; la cadena vacía sigue siendo válida.
- `StandardMaterialityQuestion::help(Locale $locale): ?string` (`StandardMaterialityQuestion.php:68`). Los cuatro casos de escala de niveles (`Magnitude`, `Influence`, `Risk`, `StakeholderInterest`) devuelven una lista de dos elementos con el nivel en negrita, en comillas dobles con `\n` real, p. ej. `"- **Alto**: previsiblemente una de las mayores fuentes.\n- **Bajo**: marginal frente al resto."` (`:72`). `Outsourcing` devuelve una sola línea `'**Sí**: …'`. Inglés en `:79-83`; `default => null` para el resto de locales.
- `StandardScope3Category::description()` sigue en texto plano (texto plano es Markdown válido).

## Flujo de una operación

**Alta / edición de una pregunta** (idéntico para categorías con `description`):

1. `GET /{_locale}/admin/materiality-questions/edit/{id}` → `src/Materiality/Infrastructure/UI/Web/Admin/MaterialityQuestion/EditController.php:43` precarga `help` con `$materialityQuestion->help()` (traducción del locale actual o texto base). Categorías: `.../MaterialityTopic/EditController.php:47`.
2. El form type declara `help` como `MarkdownEditorType` con `height: '220px'`, `hide_link: false`, `required: false`, `empty_data: ''` (`UpdateMaterialityQuestionType.php:51-57`). `MarkdownEditorType::buildView()` pasa `height`, `initial_edit_type` (`wysiwyg`), `hide_image` (`true`), `hide_link`, `hide_mode_switch` (`true`) a la vista (`MarkdownEditorType.php:20-27`, defaults en `:31-37`).
3. El tema global renderiza `markdown_editor_widget`: un `div data-controller="markdown-editor"` con los `data-markdown-editor-*-value` y un `<textarea>` oculto con `name="{{ full_name }}"` y el valor autoescapado (`custom_types.html.twig:12-29`). Las plantillas `templates/materiality/admin/materiality-questions/edit.html.twig:32` y `.../materiality-topics/edit.html.twig:35` hacen `form_row` sin cambios; las altas usan el layout `@shared/layout/register.html.twig`, que recorre los campos.
4. En el navegador, `markdown_editor_controller.js:16-22` monta la barra (`link` solo si `hideLinkValue` es falso; `image` oculto), `:28` inicializa el editor con el valor del `<textarea>` y `:34-36` vuelca `getMarkdown()` al `<textarea>` **solo al producirse un cambio**. Si el campo no se toca, se envía el valor original byte a byte.
5. El POST llega al `Update*Request` como `string` y el servicio de aplicación actualiza el agregado sin transformar el valor. En el locale por defecto se modifica el texto base; en otro locale se graba la traducción y el texto base se conserva (patrón ya existente de `Translatable`, cubierto por los tests nuevos).
6. Mensajes: «Pregunta de materialidad creada correctamente: {text}» (`.../MaterialityQuestion/RegisterController.php:37`, redirige al listado), «Pregunta de materialidad actualizada con éxito» (`.../MaterialityQuestion/EditController.php:57`), «Categoría de materialidad creada correctamente: {name}» (`.../MaterialityTopic/RegisterController.php:54`), «Categoría de materialidad actualizada con éxito» (`.../MaterialityTopic/EditController.php:68`).

**Siembra:** `SeedStandardMaterialityMastersService::seedQuestion()` (`src/Materiality/Application/Seed/SeedStandardMaterialityMastersService.php:135-170`) pasa `$standardQuestion->help($default)` al registro y `help($locale)` a cada traducción. Solo siembra preguntas si no existe ninguna, así que las preguntas ya sembradas conservan su ayuda en texto plano hasta que el Administrador la edite.

## Autorización y ámbito

Sin cambios. Los formularios pertenecen a maestros globales: `MaterialityQuestionAuthorizer` (`src/Materiality/Application/MaterialityQuestion/MaterialityQuestionAuthorizer.php:24-31`) y su equivalente de categorías solo autorizan `Admin` y `SuperAdmin`; Owner y User reciben 403 (escenarios existentes en `materiality-topic-edit.feature`). No hay ámbito de cuenta ni efecto de la impersonación.

## Persistencia y migración

Sin migración. `help` y `description` están mapeados como `type="text"` en `MaterialityQuestion.MaterialityQuestion.orm.xml:21`, `MaterialityQuestion.MaterialityQuestionTranslation.orm.xml:11`, `MaterialityTopic.MaterialityTopic.orm.xml:22` y `MaterialityTopic.MaterialityTopicTranslation.orm.xml:11` (todos en `src/Materiality/Infrastructure/Persistence/Doctrine/Mapping/`), creados como `LONGTEXT NOT NULL` en `migrations/Version20260925140445.php`. Los textos existentes, en texto plano, son Markdown válido: sin backfill.

## Configuración y operación

- Sin variables de entorno, colas ni tareas programadas nuevas. `@toast-ui/editor` ya estaba en `importmap.php`.
- Sin textos nuevos: las etiquetas «Ayuda» y «Descripción» ya existían en `translations/`.
- La siembra sigue disponible desde el botón «Sembrar maestros estándar» del listado de categorías o por consola: `make sf c="materiality:seed <username>"`.

## Cómo extenderlo

- **Añadir otro campo con formato a un maestro:** declararlo con `MarkdownEditorType::class` y las mismas opciones (`required: false`, `empty_data: ''`, `height`, `hide_link`) en los form types de alta y edición; ninguna plantilla necesita cambios si hace `form_row`. Añadir el caso a `MarkdownFieldsTest`.
- **Renderizar la ayuda o la descripción (cuestionario del cliente):** hay dos filtros Markdown. `md_to_html` (`src/Shared/Infrastructure/Symfony/Twig/MarkdownExtension.php`) convierte sin escapar HTML embebido y se declara `is_safe`; `changelog_markdown` (`src/Changelog/Infrastructure/UI/Web/Twig/MarkdownExtension.php`) escapa HTML y bloquea enlaces inseguros. Estos textos los escribe un Administrador, admiten enlaces y los leerá un cliente: conviene la variante que escapa.
- **Cambiar las ayudas sembradas:** editar `StandardMaterialityQuestion::help()`. Los literales de varias líneas deben ir en comillas dobles; con comillas simples se guardaría la secuencia `\n` literal (lo detecta el `assertSame` de `MaterialityQuestionTest`).
- **Trampas:**
  - El editor WYSIWYG reescribe el Markdown completo en cuanto se modifica el campo: caracteres especiales de un texto plano previo (`*`, `_`, `#`, `<`) pueden salir escapados con `\` y los saltos de línea simples se renderizarán como espacios.
  - Behat usa `KernelBrowser` sin JavaScript: envía el `<textarea>` oculto directamente. Que el widget sea el editor lo verifica `MarkdownFieldsTest`, no los `.feature`.
  - `WebContext::submitForm()` parte por `|` y `:`; los valores de un escenario no pueden contenerlos.

## Tests

- **Unitarios**
  - `MarkdownFieldsTest` (`KernelTestCase`, `form.factory->create()` con el nombre por defecto del tipo): los cuatro formularios renderizan `data-controller="markdown-editor"`, altura `220px`, `hide-link` `false`, el `name` real del campo y, en edición, el valor precargado.
  - `MaterialityQuestionTest::testStandardMaterialityQuestions`: todas las ayudas ES/EN contienen `**`, y `assertSame` literal de `Magnitude` en español.
  - `SeedStandardMaterialityMastersServiceTest::testSeededQuestionsKeepMarkdownHelp`: ayuda base y traducción inglesa de la pregunta sembrada.
  - `testRegisterKeepsMarkdownHelpVerbatim` / `testRegisterKeepsMarkdownDescriptionVerbatim`: texto de varios párrafos, con lista y enlace, de más de 255 caracteres, guardado idéntico.
  - `testUpdateInNonDefaultLocaleKeepsBaseMarkdownHelp` / `...Description`: en inglés se graba la traducción y el texto base no cambia.
- **Aceptación** (`tests/Acceptance/Materiality/`): alta con formato y alta sin ayuda (cadena vacía), edición del campo, edición sin tocar el campo (conserva valor) y edición en `/en/` (traducción inglesa, texto base intacto), para preguntas y categorías. Pasos nuevos en `MaterialityContext.php`: `materiality question :id should have help :help in locale :locale`, `materiality question with text :text should have help :help`, `materiality topic :id should have description :description in locale :locale`, `materiality topic with name :name should have description :description`.
- **Ejecutar:**
  - `make test-this test=tests/Unit/Materiality/Infrastructure/Symfony/Form/MarkdownFieldsTest.php`
  - `make test-this test=tests/Unit/Materiality`
  - `make test-acceptance options="tests/Acceptance/Materiality"`

## Desviaciones y deuda

Según `## Implementación` de `plan.md`:

- Las altas de pregunta redirigen al listado (`admin_materiality_questions`), no a la edición, como ya hacía `RegisterController`; los escenarios lo reflejan.
- Los helpers `request()` de los tests de actualización ganan un parámetro opcional `$help` / `$description`.
- `MarkdownFieldsTest` usa `form.factory->create()` para que el `name` del campo coincida con el de las pantallas.

`review.md` (revisión 1): 0 hallazgos abiertos. Deuda conocida y fuera de alcance: elegir el filtro de renderizado seguro cuando se muestren estos textos al cliente.
