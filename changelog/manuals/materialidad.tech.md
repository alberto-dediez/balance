# Materialidad: maestros del GHG Protocol (categorías y preguntas) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-25 · **Correcciones de review:** 2026-09-25 (`review-fix.md`: M1, M2, Q1, Q3) · **Bounded Contexts:** `Materiality` (nuevo), `Shared` (menú y `FilterBar`), configuración de `config/`

## Qué hace y por qué

La plataforma calcula la huella organizacional de propiedades y empresas solo en alcance 1 y 2. Para incorporar el alcance 3 con validez técnica hace falta antes un **análisis de materialidad**: un cuestionario cualitativo que valore, categoría a categoría, los criterios de relevancia del GHG Protocol Scope 3 Standard y permita decidir qué categorías se calculan y cuáles se excluyen con justificación.

Esta entrega construye **solo los maestros** sobre los que se apoyará ese cuestionario, en un Bounded Context nuevo `src/Materiality/` (contexto de eventos `materiality`):

- `MaterialityTopic` — categorías de materialidad, con alcance (`scope_1|2|3`) y, en alcance 3, dirección de la cadena de valor (`upstream|downstream`). Las 15 categorías del alcance 3 del GHG Protocol se siembran como **estándar** (no se pueden eliminar, solo desactivar).
- `MaterialityQuestion` — preguntas del cuestionario, cada una asociada a un criterio normativo (`MaterialityCriterion`), con escala de respuesta, peso y posición; **genéricas** (`topicId = null`, aplican a todas las categorías activas) o **específicas** de una categoría.

El cuestionario del cliente, la puntuación, la decisión por categoría, la segmentación sectorial y el documento de exclusiones quedan para planes posteriores (plan, «Fuera de alcance»). Las decisiones (BC propio, agregado de preguntas independiente, siete criterios, escalas, siembra por servicio y no por migración, baja en cascada) están justificadas en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

Todo `src/Materiality/` es nuevo. Solo se citan los ficheros con lógica; los `Request`/`Response`, ids, eventos y excepciones siguen el andamiaje estándar de `create-entity`.

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Materiality/Domain/DomainEvent.php` | Base de eventos del BC, `$context = 'materiality'` (nombra las plantillas de auditoría) | Nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialityTopic/MaterialityTopic.php` | Agregado categoría, traducible, baja lógica | Nuevo |
| Dominio | `.../MaterialityTopic/{MaterialityScope,MaterialityTopicDirection}.php` | Enums de alcance y dirección | Nuevo |
| Dominio | `.../MaterialityTopic/StandardScope3Category.php` | Enum `int` de las 15 categorías estándar con dirección y textos ES/EN | Nuevo |
| Dominio | `.../MaterialityTopic/MaterialityTopicFilter.php`, `MaterialityTopicRepository.php` | Filtro y puerto (`findByNumber`, `findActive`) | Nuevo |
| Dominio | `.../MaterialityTopic/StandardMaterialityTopicNumberRequiredException.php` | Invariante «estándar ⇒ número» (Q1 de la review) | Nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestion.php` | Agregado pregunta, traducible, baja lógica | Nuevo |
| Dominio | `.../MaterialityQuestion/{MaterialityCriterion,MaterialityAnswerScale}.php` | Enums de criterio (7 casos) y escala (`values()`, `max()`) | Nuevo |
| Dominio | `.../MaterialityQuestion/StandardMaterialityQuestion.php` | Enum de las 5 preguntas iniciales (criterio, escala, peso, posición, textos ES/EN) | Nuevo |
| Dominio | `src/Materiality/Domain/Event/RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriber.php` | Baja en cascada de las preguntas específicas | Nuevo |
| Aplicación | `src/Materiality/Application/MaterialityTopic/MaterialityTopicAuthorizer.php` | Solo `Admin`/`SuperAdmin`; `Remove` denegado a estándar | Nuevo |
| Aplicación | `src/Materiality/Application/MaterialityQuestion/MaterialityQuestionAuthorizer.php` | Solo `Admin`/`SuperAdmin` | Nuevo |
| Aplicación | `src/Materiality/Application/MaterialityTopic/{Register,Update,Translate,Remove,List}/` | Casos de uso de categorías | Nuevo |
| Aplicación | `src/Materiality/Application/MaterialityQuestion/{Register,Update,Translate,Remove,List}/` | Casos de uso de preguntas | Nuevo |
| Aplicación | `src/Materiality/Application/Seed/SeedStandardMaterialityMastersService.php` | Siembra idempotente de maestros estándar | Nuevo |
| Persistencia | `src/Materiality/Infrastructure/Persistence/Doctrine/Mapping/*.orm.xml` | 4 mappings (dos agregados + dos traducciones) | Nuevo |
| Persistencia | `.../Doctrine/Repository/DoctrineMaterialityTopicRepository.php`, `DoctrineMaterialityQuestionRepository.php` | Repos Doctrine (dev/prod) con `DoctrineTranslatableRepositoryTrait` | Nuevo |
| Persistencia | `.../InMemory/Repository/InMemoryMaterialityTopicRepository.php`, `InMemoryMaterialityQuestionRepository.php` | Repos de test (`#[When(env: 'test')]`) | Nuevo |
| Persistencia | `.../Doctrine/Type/{MaterialityTopicIdType,MaterialityQuestionIdType}.php` | Tipos DBAL `materiality_topic_id` / `materiality_question_id` | Nuevo |
| Persistencia | `migrations/Version20260925140445.php` | Cuatro tablas nuevas | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/Symfony/Form/*.php` | Formularios de alta, edición y filtro | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/Symfony/Form/Type/MaterialityTopicChoiceType.php` | Selector de categoría con opción `include_inactive` (M1 de la review) | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/Symfony/Form/Type/Materiality{Scope,TopicDirection,Criterion,AnswerScale}Type.php` | Form types de enums (padre `AutocompleteType`) | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/UI/Web/Admin/MaterialityTopic/*`, `.../MaterialityQuestion/*` | Controladores List/Register/Edit/Remove + `Bulk/RemoveAction` | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/UI/Web/Admin/Seed/SeedController.php` | Botón de siembra (GET + token CSRF de sesión) | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/UI/Web/Twig/MaterialityTopicNameExtension.php` | Filtro Twig `materiality_topic_name` (etiqueta del filtro `topicId`) | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/UI/Console/SeedStandardMaterialityMastersCommand.php` | Comando `materiality:seed <username>` | Nuevo |
| Infraestructura | `src/Materiality/Infrastructure/Symfony/DependencyInjection/services.yaml` | Autowiring del BC + `$localeList` de la siembra | Nuevo |
| Plantillas | `templates/materiality/admin/materiality-topics/{list,register,edit}.html.twig`, `.../materiality-questions/{list,register,edit}.html.twig` | Pantallas de administración | Nuevo |
| Plantillas | `templates/materiality/translations/*.html.twig` | Extracción de los cuatro dominios de enums | Nuevo |
| Plantillas | `templates/components/Link/Seed.html.twig` | Componente `Link:Seed` («Sembrar maestros estándar», `light`) | Nuevo |
| Plantillas | `templates/security/admin/events/detail/materiality.*.html.twig` | Ocho plantillas de auditoría | Nuevo |
| Plantillas | `templates/components/List/FilterBar.html.twig` | Ramas `topicId`, `scope`, `direction`, `criterion` (M2 de la review) | Modificado |
| Shared | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:355-371` | Grupo de menú «Materialidad» en `toAdmins()` | Modificado |
| Traducciones | `translations/materiality{Scope,TopicDirection,Criterion,AnswerScale}Type+intl-icu.{es,en,tr}.xlf`, `messages+intl-icu.*.xlf` | Textos de enums y de pantallas | Nuevo / Modificado |
| Config | `config/services.yaml`, `config/routes.yaml` (`materiality-web`), `config/packages/twig.yaml` (`@materiality`), `config/packages/doctrine.yaml` (tipos + mapping `Materiality`), `behat.yml.dist` (`MaterialityContext`) | Cableado del BC | Modificado |
| Tests | `tests/Unit/Materiality/**`, `tests/Acceptance/Materiality/**`, `tests/Acceptance/Shared/WebContext.php` (paso `I untick the checkbox`) | Cobertura | Nuevo / Modificado |

## Modelo de dominio

### `MaterialityTopic` (`src/Materiality/Domain/Model/MaterialityTopic/MaterialityTopic.php`)

Campos: `id`, `number` (`?int`, número GHG 1-15; nulo en las propias salvo que el Administrador lo teclee), `scope`, `direction` (`?MaterialityTopicDirection`), `name` y `description` (base, traducibles), `position`, `active`, `standard`, `removed`, `registeredAt`, `updatedAt`, `removedAt`, `translations` (`MaterialityTopicTranslation`: `name`, `description`, `locale`).

- `name()` / `description()` devuelven la traducción del locale actual o el campo base (`:60-74`); `defaultName()` / `defaultDescription()`, siempre la base.
- `register(...)` (`:132-162`): lanza `StandardMaterialityTopicNumberRequiredException` si `standard && null === number` (`:142-144`); graba `MaterialityTopicRegistered`; si recibe `locale`, graba además `MaterialityTopicTranslated`.
- `update(...)` (`:164-188`): `number` y `standard` no cambian nunca tras el alta. Si recibe `locale`, el evento `MaterialityTopicUpdated` lleva el `name`/`description` base actuales y el texto nuevo va a una traducción.
- `resolveDirection()` (`:219-230`): alcance ≠ 3 ⇒ dirección `null` (se descarta aunque llegue); alcance 3 sin dirección ⇒ `MaterialityTopicDirectionRequiredException`.
- `remove()` (`:202-217`): estándar ⇒ `StandardMaterialityTopicCannotBeRemovedException`; ya eliminada ⇒ no hace nada (idempotente); si no, `MaterialityTopicRemoved`.

### `MaterialityQuestion` (`src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestion.php`)

Campos: `id`, `criterion`, `topicId` (`?MaterialityTopicId`; `isGeneric()` = `null === topicId`), `text` y `help` (traducibles), `answerScale`, `weight`, `position`, `active`, `removed`, fechas, `translations` (`MaterialityQuestionTranslation`: `text`, `help`, `locale`).

- `register()` / `update()` (`:138`, `:168`) validan `weight ≥ 1` con `guardWeight()` (`:225`, `InvalidMaterialityQuestionWeightException`) y siguen el mismo juego de locale que la categoría.
- `remove()` (`:212`) idempotente.

### Enums

| Enum | Valores | Notas |
|---|---|---|
| `MaterialityScope` | `scope_1`, `scope_2`, `scope_3` | Mismos valores que `CarbonFootprint\...\Scope` (que hoy no tiene alcance 3) |
| `MaterialityTopicDirection` | `upstream`, `downstream` | |
| `StandardScope3Category` (`int`) | 1..15 | `direction()`: 1-8 upstream, 9-15 downstream; `name(Locale)` / `description(Locale)` con ES y EN, `null` para el resto |
| `MaterialityCriterion` | `magnitude`, `influence`, `risk`, `stakeholder_interest`, `outsourcing`, `sector_guidance`, `other` | Tabla 6.1 del Scope 3 Standard; no es maestro editable |
| `MaterialityAnswerScale` | `level` → `values() = [1,2,3]`, `yes_no` → `[1,3]`; `max() = 3` | Base del cálculo previsto: Σ(valor·peso) / Σ(máx·peso) (no implementado aquí) |
| `StandardMaterialityQuestion` | los 5 primeros criterios | `criterion()`, `answerScale()` (`yes_no` solo en `outsourcing`), `weight()` (2 en `magnitude`, 1 el resto), `position()` 1-5, `text(Locale)`, `help(Locale)` |

### Eventos

Ocho eventos, todos extienden `App\Materiality\Domain\DomainEvent`: `materialityTopic{Registered,Updated,Translated,Removed}` y `materialityQuestion{Registered,Updated,Translated,Removed}`. Enums y `Locale` se serializan por `->value`; la dirección nula se reconstruye con `tryFrom`. Quedan en el Registro vía `src/Security/Domain/Event/RecordEventSubscriber.php`, con plantilla propia en `templates/security/admin/events/detail/materiality.<eventName>.html.twig`.

### Invariantes y dónde se validan

| Invariante | Dónde |
|---|---|
| Alcance 3 ⇒ dirección; alcance 1/2 ⇒ dirección nula | `MaterialityTopic::resolveDirection()` |
| Estándar ⇒ número no nulo | `MaterialityTopic::register()` |
| Estándar no se elimina | `MaterialityTopicAuthorizer::canRemove()` (`:34-39`) y `MaterialityTopic::remove()` |
| `number` único entre categorías no eliminadas | `RegisterMaterialityTopicService.php:154-156` (sin índice único en BD; ver «Desviaciones y deuda») |
| `weight ≥ 1` | Formulario (`Range(min: 1)`) y `MaterialityQuestion::guardWeight()` |
| `topicId` referencia una categoría existente y no eliminada | `RegisterMaterialityQuestionService.php:38-40`, `UpdateMaterialityQuestionService.php` (tras autorizar) |

## Flujo de una operación

### Alta y edición de categoría

`GET|POST /{_locale}/admin/materiality-topics/register` → `Admin/MaterialityTopic/RegisterController` (`RegisterTrait`, `denyAccessUnlessGranted(Register)`) → `RegisterMaterialityTopicType` → `RegisterMaterialityTopicService`:

1. `MaterialityTopicId::generate()` y `denyUnlessCanDoThat(..., Register)` (`:150-152`).
2. Número repetido ⇒ `MaterialityTopicNumberAlreadyUsedException` (`:154-156`).
3. Si `LocaleGetter::locale()` ≠ `LocaleGetter::default()`, se pasa el locale al agregado (el texto tecleado se guarda como base **y** como traducción del locale actual).
4. `register()` + `add()`.

El controlador convierte `MaterialityTopicDirectionRequiredException` en error del campo `direction` y `MaterialityTopicNumberAlreadyUsedException` en error del campo `number`; si todo va bien, flash «Categoría de materialidad creada correctamente: {name}» y redirección a `admin_materiality_topics_edit`.

La edición (`/admin/materiality-topics/edit/{materialityTopicId}`, `EditController`) precarga el request con los valores **del locale actual** (`name()`/`description()`), llama a `UpdateMaterialityTopicService` y captura `MaterialityTopicDirectionRequiredException` igual que el alta. Navegando en un locale distinto del por defecto, el servicio pasa el locale y el agregado solo toca la traducción.

### Alta y edición de pregunta

`RegisterController` / `EditController` de `Admin/MaterialityQuestion/` con `RegisterMaterialityQuestionType` / `UpdateMaterialityQuestionType`. El campo `topicId` es `MaterialityTopicChoiceType` (`ChoiceType` con placeholder «Todas las categorías», `required: false`, `CallbackTransformer` string ↔ `MaterialityTopicId`):

- En el **alta** (`include_inactive: false`) ofrece `findActive()` (activas, no eliminadas).
- En la **edición** y en el **filtro del listado** (`include_inactive: true`) ofrece `findByFilter(new MaterialityTopicFilter(limit: 0))`: todas las no eliminadas, activas o no (`MaterialityTopicChoiceType.php:454-467`). Así editar una pregunta de una categoría desactivada conserva su vínculo (M1 de la review).

Los servicios autorizan, comprueban que `topicId` existe (`MaterialityTopicNotFoundException`), aplican el mismo juego de locale y persisten.

### Listados

`ListController` (`FilterTrait` + `BulkTrait`) → `List*Service` (autoriza `List`) → `findByFilter` / `totalResultsByFilter`. `ListMaterialityQuestionsService` devuelve además `topicsById` con las categorías referenciadas por la página (`:353-367`) para pintar la columna «Categoría» sin consultas por fila; si la categoría no está (eliminada), la plantilla muestra `---`.

Orden en Doctrine: categorías `default` = `position, name` y `name|number|registeredAt` (`DoctrineMaterialityTopicRepository.php:155-162`); preguntas `default` = `position, registeredAt` y `criterion|registeredAt` (`DoctrineMaterialityQuestionRepository.php:140-146`). El filtro de nombre/texto busca en la traducción del locale actual o, si no la hay, en la base (`LEFT JOIN` de la traducción, `:100-125`).

La `FilterBar` muestra las etiquetas de los filtros activos traducidas: `topicId` con `materiality_topic_name`, `scope`/`direction`/`criterion` con sus dominios (`templates/components/List/FilterBar.html.twig`).

### Baja y baja en cascada

`RemoveController` (`#[CsrfProtected('remove')]`) → `RemoveMaterialityTopicService` (autoriza `Remove`: `UnauthorizedActionException` en estándar ⇒ 403) → `remove()` → `MaterialityTopicRemoved` → `RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriber::handle()` recorre `findByTopicId()` e invoca `RemoveMaterialityQuestionService` por cada pregunta con el usuario de `LoggedUser`. Las genéricas y las de otras categorías no se tocan. La acción masiva (`Bulk/RemoveAction`) emite un `RemoveMaterialityTopicRequest` por fila; las estándar fallan individualmente sin abortar el resto.

### Siembra de maestros estándar

`SeedStandardMaterialityMastersService` (`src/Materiality/Application/Seed/`):

1. Autoriza `Admin` sobre una `MaterialityTopic` vacía (`canAdmin` heredado de `AbstractAuthorizer`: `Admin` o `SuperAdmin`).
2. `$default = LocaleGetter::default()`; locales de traducción = `Locale::listFrom($localeList)` menos el por defecto (`$localeList` = `%app.locale_list%` = `LANGUAGE_LIST`, inyectado en el `services.yaml` del BC).
3. Por cada `StandardScope3Category` sin `findByNumber()`, invoca `RegisterMaterialityTopicService` (`standard: true`, `position = number`, texto del locale por defecto o EN) y `TranslateMaterialityTopicService` para cada locale con texto en el enum.
4. Si `totalResultsByFilter(new MaterialityQuestionFilter()) === 0`, registra las 5 `StandardMaterialityQuestion` (genéricas) y sus traducciones.
5. Devuelve `registeredTopics`, `skippedTopics`, `registeredQuestions`, `questionsSkipped`.

Nunca instancia agregados con `new` fuera de los servicios de alta. Puntos de entrada:

- Web: `GET /{_locale}/admin/materiality/seed` (`SeedController`), autoriza `Admin`, valida el token de sesión `materiality_seed` (`isCsrfTokenValid`, `:40`), ejecuta síncrono, flash «Se han registrado {topics} categorías y {questions} preguntas estándar» o «Los maestros estándar ya estaban registrados», y redirige al listado de categorías. El enlace es `Link:Seed` en `list_actions` del listado de categorías con confirmación.
- Consola: `make sf c="materiality:seed <username>"`.

## Autorización y ámbito

| Acción | `SuperAdmin` / `Admin` | `Owner` / `User` / `Provider` |
|---|---|---|
| `List`, `Register`, `View`, `Edit` | Sí | No (403) |
| `Remove` categoría | Sí, salvo estándar | No |
| `Remove` pregunta | Sí | No |
| `Admin` (siembra) | Sí | No |
| `Import`, `Export`, `Autocomplete` | No | No |

Los authorizers usan `$this->userHasRole()` (respeta `CurrentRolesProvider`). Son **maestros globales**: sin scoping por cuenta y sin efecto de la impersonación. El menú «Materialidad» solo se añade en `NodeLister::toAdmins()`.

## Persistencia y migración

Migración `migrations/Version20260925140445.php` (misma DDL que `migration.sql` de la carpeta del plan). No toca tablas existentes ni requiere backfill.

| Tabla | Columnas relevantes | Índices / FK |
|---|---|---|
| `materiality_topics` | `id` (`materiality_topic_id`), `number` (null), `scope` (16), `direction` (16, null), `name` (255), `description` (text), `position`, `active`, `standard`, `removed`, `registered_at`, `updated_at`, `removed_at` | `idx_materiality_topics_number (number)`, `idx_materiality_topics_listing (removed, active, position)` |
| `materiality_topic_translation` | PK (`locale`, `materiality_topic_id`), `name`, `description` | FK `ON DELETE CASCADE` a `materiality_topics` |
| `materiality_questions` | `id`, `criterion` (32), `topic_id` (null, **sin FK**: campo plano), `text`, `help`, `answer_scale` (16), `weight`, `position`, `active`, `removed`, fechas | `idx_materiality_questions_topic (topic_id)`, `idx_materiality_questions_listing (removed, active, position)` |
| `materiality_question_translation` | PK (`locale`, `materiality_question_id`), `text`, `help` | FK `ON DELETE CASCADE` |

Tras migrar hay que sembrar: `make sf c="materiality:seed <username-admin>"` o el botón «Sembrar maestros estándar».

## Configuración y operación

- `LANGUAGE_LIST` (`.env`, hoy `es|en`) determina a qué idiomas traduce la siembra. Los enums estándar solo tienen ES y EN: otros locales se omiten y quedan con el texto base.
- Sin colas Messenger, sin tareas programadas, sin correos ni notificaciones.
- Comando de consola: `make sf c="materiality:seed <username>"` — imprime la tabla `Registered topics` / `Skipped topics` / `Registered questions` y la lista de lo registrado. Falla (`FAILURE`) si el usuario no existe; lanza `UnauthorizedActionException` si no es Admin.
- Rutas bajo el bloque `materiality-web` de `config/routes.yaml` (prefijo `/{_locale}`): `admin_materiality_topics[_register|_edit|_remove]`, `admin_materiality_questions[_register|_edit|_remove]`, `admin_materiality_seed`.

## Cómo extenderlo

- **Nueva categoría estándar o nuevo idioma en la siembra:** añadir el caso a `StandardScope3Category` (o el `match` del locale en `name()`/`description()`). La siembra solo registra las que faltan por `number`; las ya existentes **no** reciben traducciones nuevas: se traducen desde la pantalla de edición navegando en ese idioma.
- **Nuevo criterio:** caso nuevo en `MaterialityCriterion` + traducción en `translations/materialityCriterionType+intl-icu.*.xlf` + línea en `templates/materiality/translations/materialityCriterionType.html.twig`. Si debe llevar pregunta sembrada, caso en `StandardMaterialityQuestion` (con todos sus `match`); recuerde que la siembra de preguntas solo actúa si no hay ninguna.
- **Nueva escala:** caso en `MaterialityAnswerScale` con `values()` y `max()`; toda pregunta debe redactarse de modo que el valor alto signifique mayor relevancia.
- **Nuevo filtro enum en un listado:** añadir su rama en `templates/components/List/FilterBar.html.twig` con el dominio `<enum>Type`; la rama genérica `enum_domain` deriva el dominio sin el sufijo `Type` y no traduce.
- **El cuestionario del cliente** debe usar `MaterialityTopicRepository::findActive()` y preguntas activas genéricas + específicas por categoría (`findByFilter` con `topicId` / `onlyGeneric`), y `MaterialityAnswerScale::values()/max()` para puntuar.

Trampas conocidas:

- Desactivar una categoría **no** desactiva ni elimina sus preguntas; solo la baja las elimina en cascada.
- La baja en cascada depende de `LoggedUser`: si en el futuro se elimina una categoría desde consola o desde un comando diferido, el suscriptor fallará por falta de usuario (M3 de la review, asumido).
- La ordenación por `name` usa el nombre base (`t.name`), no la traducción del locale actual.
- Un alta navegando en inglés guarda el texto tecleado como base **y** como traducción EN (patrón de `Material`).

## Tests

- **Unitarios** (`tests/Unit/Materiality/`, 87 métodos de test): agregados (`MaterialityTopicTest`, incluido `testStandardWithoutNumberThrows`; `MaterialityQuestionTest`), repositorios abstractos + InMemory + Doctrine, authorizers, los diez servicios de categorías y preguntas, `SeedStandardMaterialityMastersServiceTest` (8 casos: siembra completa con traducción EN, locale sin texto, solo locale por defecto, segunda ejecución, categorías parciales, preguntas existentes, owner, navegando en inglés) y el suscriptor de cascada.
- **Aceptación** (`tests/Acceptance/Materiality/`, 41 escenarios en 9 `.feature`, contexto `MaterialityContext.php`): acceso 200/403 por rol, alta con errores de dirección y número, edición base y traducción EN, desactivación, baja propia/estándar/cascada, filtros por alcance, dirección (con etiqueta traducida), criterio y categoría (incluida una inactiva), edición de pregunta de categoría inactiva, siembra por servicio y por botón (incluido «sin token → 403»), y visibilidad del grupo de menú para administrador y propietario.
- Ejecución: `make test-this test=tests/Unit/Materiality` para los unitarios; los Behat con `make test-acceptance options="tests/Acceptance/Materiality"`; todo con `make test`.

## Desviaciones y deuda

Ver `plan.md` («## Implementación») y `review-fix.md`.

- **Cambio sobre el plan (M1):** el selector de categoría ofrece también las inactivas en la edición de preguntas y en el filtro del listado (`include_inactive`); el plan prescribía `findActive()` en todos los casos.
- **Añadido sobre el plan (M2, Q1, Q3):** etiquetas traducidas de los filtros `scope`/`direction`/`criterion`; excepción `StandardMaterialityTopicNumberRequiredException`; escenarios de menú.
- **Asumido (M3):** dependencia Domain → `Security\Infrastructure\...\LoggedUser` del suscriptor de cascada, patrón sistémico del repositorio.
- **Asumido (Q2):** unicidad de `number` solo en el servicio, sin índice único (permite reutilizar el número tras una baja lógica; ventana de carrera entre dos altas concurrentes).
- Otras desviaciones menores del plan (paso `I untick the checkbox`, prueba del botón de siembra por clic, error del campo `number`, estado vacío del listado de preguntas, `BooleanType` para el filtro de activación) en `plan.md`.
