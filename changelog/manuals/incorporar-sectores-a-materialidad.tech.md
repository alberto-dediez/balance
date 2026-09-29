# Sectores de materialidad — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-28 · **Revisado:** 2026-09-29 (`review.md`) · **Bounded Contexts:** `Materiality` (principal), `Shared` (menú y componentes de listado), `Security` (plantillas de auditoría)

## Qué hace y por qué

El cuestionario de materialidad no distinguía sectores: una pregunta solo sabía a qué categoría aplicaba (`topicId`, o a todas si era nulo). Esta funcionalidad añade un **maestro de sectores** (`MaterialitySector`), editable por el Administrador en **Materialidad → Sectores**, y una **relación N:M entre preguntas y sectores**. Una pregunta sin sectores enlazados aplica a todos los sectores, en paralelo a `topicId = null` ⇒ todas las categorías.

El sector es un agregado propio y no el enum `EventSector` de `EventOperations`, porque aquel clasifica eventos y no es editable (tabla *Decisiones tomadas* de `plan.md`). La relación sigue el patrón de la skill `create-relationship`: entidad de enlace con id propio, colección envoltorio, eventos `Linked`/`Unlinked` y sincronización desde `register()`/`update()` de la pregunta.

Al dar de baja un sector, un suscriptor desenlaza el sector de las preguntas que tienen otros sectores y **elimina** (baja lógica) las preguntas cuyo único sector era ese, para que no pasen a aplicar a todos los sectores. El cuestionario del cliente, que usará esta segmentación, queda para un plan posterior.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Materiality/Domain/Model/MaterialitySector/MaterialitySector.php` | Agregado traducible con baja lógica | nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialitySector/{MaterialitySectorId,MaterialitySectorTranslation,MaterialitySectorFilter,MaterialitySectorRepository,MaterialitySectorNotFoundException}.php` | Id, traducción, filtro, puerto y excepción | nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialitySector/MaterialitySector{Registered,Updated,Translated,Removed}.php` | Eventos del sector | nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestionSectorLink.php` + `MaterialityQuestionSectorLinkId.php` | Entidad de enlace pregunta–sector | nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestionSectors.php` | Colección envoltorio (`ids`, `has`, `add`, `removeById`) | nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestionSector{Linked,Unlinked}.php` | Eventos de la relación | nuevo |
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/MaterialityQuestion.php` | Colección `sectors`, `link/unlink/syncSectors`, `appliesToAllSectors()` | modificado |
| Dominio | `src/Materiality/Domain/Model/MaterialityQuestion/{MaterialityQuestionFilter,MaterialityQuestionRepository}.php` | Filtro `sectorId`, `findBySectorId()` | modificado |
| Dominio | `src/Materiality/Domain/Event/UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber.php` | Cascada de la baja de sector | nuevo |
| Aplicación | `src/Materiality/Application/MaterialitySector/MaterialitySectorAuthorizer.php` | Autorización Admin only | nuevo |
| Aplicación | `src/Materiality/Application/MaterialitySector/{Register,Update,Remove,List}/*` | Casos de uso del maestro | nuevo |
| Aplicación | `src/Materiality/Application/MaterialityQuestion/{Register,Update}/*{Request,Service}.php` | `sectorIds` y validación de existencia | modificado |
| Aplicación | `src/Materiality/Application/MaterialityQuestion/List/ListMaterialityQuestions{Service,Response}.php` | `sectorsById` | modificado |
| Persistencia | `src/Materiality/Infrastructure/Persistence/Doctrine/Mapping/MaterialitySector.MaterialitySector.orm.xml`, `MaterialitySector.MaterialitySectorTranslation.orm.xml`, `MaterialityQuestion.MaterialityQuestionSectorLink.orm.xml` | Mappings nuevos | nuevo |
| Persistencia | `src/Materiality/Infrastructure/Persistence/Doctrine/Mapping/MaterialityQuestion.MaterialityQuestion.orm.xml` | `one-to-many sectors` | modificado |
| Persistencia | `src/Materiality/Infrastructure/Persistence/Doctrine/Type/{MaterialitySectorIdType,MaterialityQuestionSectorLinkIdType}.php` | Tipos DBAL | nuevo |
| Persistencia | `src/Materiality/Infrastructure/Persistence/{Doctrine,InMemory}/Repository/*MaterialitySectorRepository.php` | Adaptadores del sector | nuevo |
| Persistencia | `src/Materiality/Infrastructure/Persistence/{Doctrine,InMemory}/Repository/*MaterialityQuestionRepository.php` | Filtro `sectorId`, `findBySectorId()` | modificado |
| Persistencia | `migrations/Version20260929024409.php` | Migración de las tres tablas | nuevo |
| Config | `config/packages/doctrine.yaml:101-102` | Registro de `materiality_sector_id` y `materiality_question_sector_link_id` | modificado |
| UI | `src/Materiality/Infrastructure/Symfony/Form/{Register,Update}MaterialitySectorType.php`, `ListMaterialitySectorsType.php` | Formularios del sector | nuevo |
| UI | `src/Materiality/Infrastructure/Symfony/Form/Type/MaterialitySectorChoiceType.php` | Selector simple o múltiple de sectores | nuevo |
| UI | `src/Materiality/Infrastructure/Symfony/Form/{Register,Update,List}MaterialityQuestion(s)Type.php` | Campo `sectorIds` / filtro `sectorId` | modificado |
| UI | `src/Materiality/Infrastructure/UI/Web/Admin/MaterialitySector/{List,Register,Edit,Remove}Controller.php`, `GetMaterialitySectorFromIdTrait.php`, `Bulk/RemoveAction.php` | Pantallas del maestro | nuevo |
| UI | `src/Materiality/Infrastructure/UI/Web/Admin/MaterialityQuestion/{Register,Edit,List}Controller.php` | DTO explícito, `sectorIds`, `sectorsById` | modificado |
| UI | `src/Materiality/Infrastructure/UI/Web/Twig/MaterialitySectorNameExtension.php` | Filtro Twig `materiality_sector_name` | nuevo |
| UI | `templates/materiality/admin/materiality-sectors/{list,register,edit}.html.twig` | Pantallas del sector | nuevo |
| UI | `templates/materiality/admin/materiality-questions/{list,edit}.html.twig` | Columna «Sectores» y campo en edición | modificado |
| UI | `templates/components/List/Action/Delete.html.twig` | Prop opcional `confirmText` | modificado |
| UI | `templates/components/List/FilterBar.html.twig:35` | Rama `sectorId` | modificado |
| UI | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:370-374` | Entrada de menú «Sectores» | modificado |
| Auditoría | `templates/security/admin/events/detail/materiality.{materialitySectorRegistered,materialitySectorUpdated,materialitySectorTranslated,materialitySectorRemoved,materialityQuestionSectorLinked,materialityQuestionSectorUnlinked}.html.twig` | Texto del registro de auditoría | nuevo |
| i18n | `translations/messages+intl-icu.{es,en,tr}.xlf` | 17 claves nuevas | modificado |

## Modelo de dominio

### `MaterialitySector`

`MaterialitySector implements Translatable, \Stringable` con el trait `AggregateRoot` (`MaterialitySector.php:12-31`). Campos: `id`, `name` (base), `removed`, `registeredAt`, `updatedAt`, `removedAt` y la colección `translations`. Es una copia simplificada de `MaterialityTopic`: sin descripción, posición, estado activo ni estándar.

- `name()` devuelve la traducción del locale actual o el nombre base (`:38-44`); `defaultName()` siempre el base.
- `register(name, ?locale)` graba `MaterialitySectorRegistered` y, si hay locale, `translate()` (`:82-94`).
- `update(name, ?locale)`: con locale, el evento `Updated` conserva el nombre base (`$locale instanceof Locale ? $this->name : $name`) y el texto nuevo va a la traducción (`:96-108`).
- `remove()` es idempotente: no graba evento si ya estaba eliminado (`:121-132`).

| Evento (`eventName`) | Payload |
|---|---|
| `materialitySectorRegistered` | `materialitySectorId`, `name` |
| `materialitySectorUpdated` | `materialitySectorId`, `name` |
| `materialitySectorTranslated` | `materialitySectorId`, `name`, `locale` |
| `materialitySectorRemoved` | `materialitySectorId` |

`MaterialitySectorFilter` (trait `Filter`): `name`, `includeRemoved`, `limit`. El repositorio (`MaterialitySectorRepository.php`) expone `add`, `remove`, `find` (nulo si eliminado), `findByFilter`, `totalResultsByFilter` y `findAll()` (no eliminados, ordenados por nombre; lo usa el selector del formulario).

### Relación pregunta–sector

- `MaterialityQuestion::$sectors` es un `iterable` inicializado a `[]` en el cuerpo del constructor (`MaterialityQuestion.php:22-23`, `:43`), porque Doctrine lo sustituye por una `PersistentCollection`. El `@var` incluye `Collection<int, …>` para satisfacer la regla `doctrine.associationType` de PHPStan.
- `sectors()` envuelve la colección en `MaterialityQuestionSectors` (`:67-70`), que recibe el iterable **por referencia** y lo normaliza a `ArrayCollection` si hace falta (`MaterialityQuestionSectors.php:18-25`).
- `sectorIds(): list<MaterialitySectorId>` (`:73-76`) y `appliesToAllSectors()` ⇔ `count($this->sectors()) === 0` (`:78-81`).
- `register(..., array $sectorIds = [], ?Locale $locale = null)` y `update(...)` llaman a `syncSectors()` tras el evento principal (`:161-187`, `:195-223`). `sectorIds` va antes de `$locale`: todas las llamadas usan argumentos con nombre.
- `linkSector()` no hace nada si el sector ya está enlazado; si no, graba `MaterialityQuestionSectorLinked` con un `MaterialityQuestionSectorLinkId` nuevo (`:255-268`).
- `unlinkSector()` no hace nada si el sector no está enlazado; si no, graba `MaterialityQuestionSectorUnlinked` (`:270-282`).
- `syncSectors()` desenlaza los actuales que no vienen en la lista y enlaza los nuevos, sin tocar los que se mantienen (`:285-298`).
- `applyMaterialityQuestionSectorLinked()` añade el enlace con `MaterialityQuestionSectors::add()` y `applyMaterialityQuestionSectorUnlinked()` delega en `removeById()` (`:344-358`).

| Evento (`eventName`) | Payload |
|---|---|
| `materialityQuestionSectorLinked` | `materialityQuestionId`, `linkId`, `sectorId` (`rebuildFromPayload` genera un `linkId` si falta) |
| `materialityQuestionSectorUnlinked` | `materialityQuestionId`, `sectorId` |

`MaterialityQuestionRegistered`/`Updated` no cambian: los sectores viajan solo en `Linked`/`Unlinked`.

### Invariantes

- Un par `(pregunta, sector)` existe una sola vez: guarda de `has()` en `linkSector()` y restricción única `uq_materiality_question_sector_link` en BD.
- Desenlazar un sector no enlazado no graba evento.
- Una pregunta sin enlaces aplica a todos los sectores. No hay backfill: las preguntas anteriores y las sembradas quedan así.
- Tras `MaterialitySectorRemoved`, ninguna pregunta no eliminada conserva el sector, y ninguna pasa de «con sectores» a «sin sectores» por causa de la baja.

### Excepciones

- `MaterialitySectorNotFoundException`: la lanzan `Update`/`RemoveMaterialitySectorService` si el id no existe (o está eliminado) y `Register`/`UpdateMaterialityQuestionService` si alguno de los `sectorIds` no existe (`RegisterMaterialityQuestionService.php:45-49`, `UpdateMaterialityQuestionService.php:48-52`). La validación va antes de tocar el agregado, así que la pregunta no se crea ni se modifica.

## Flujo de una operación

### Alta o edición de una pregunta con sectores

1. `GET/POST /admin/materiality-questions/register` → `RegisterController` crea el formulario con `new RegisterMaterialityQuestionRequest()` (`MaterialityQuestion/RegisterController.php:31`). El DTO explícito es obligatorio: sin él, el campo `multiple` recibe `null` y el transformador falla.
2. `MaterialitySectorChoiceType` (múltiple, `expanded`) ofrece los sectores de `findAll()` y transforma `list<string>` ↔ `list<MaterialitySectorId>` (`MaterialitySectorChoiceType.php:24-36`).
3. `RegisterMaterialityQuestionService`: autoriza `Register` → valida `topicId` → valida cada `sectorId` → `register(..., sectorIds:, locale:)` → `add()` (`RegisterMaterialityQuestionService.php:35-67`). Se publica un `MaterialityQuestionSectorLinked` por sector.
4. En la edición, `EditController` rellena `sectorIds: $materialityQuestion->sectorIds()` (`MaterialityQuestion/EditController.php:48`), y `UpdateMaterialityQuestionService` sigue la misma secuencia con `Edit`. Desmarcar todos los sectores desenlaza todos y la pregunta pasa a aplicar a todos los sectores.

### Listado y filtro de preguntas

- `ListMaterialityQuestionsService` construye `sectorsById` (`array<string, MaterialitySector>`) recorriendo `sectorIds()` de las preguntas de la página, sin repetir consultas (`ListMaterialityQuestionsService.php:51-71`), igual que `topicsById`. `ListController` lo pasa a la vista (`:57`).
- La celda «Sectores» muestra `Todos los sectores` si `appliesToAllSectors`, o los nombres separados por coma (`'---'` si un id no se resuelve) (`templates/materiality/admin/materiality-questions/list.html.twig:53-63`).
- El filtro `sectorId` es estricto: solo preguntas **enlazadas** a ese sector, sin las que aplican a todos (misma semántica que `topicId`). En Doctrine es un `innerJoin('q.sectors')` (`DoctrineMaterialityQuestionRepository.php:134-139`); el total usa `COUNT(DISTINCT q)` (`:73`), y el par único evita duplicados. En memoria, `InMemoryMaterialityQuestionRepository.php:105`.
- La `FilterBar` traduce el id al nombre con el filtro Twig `materiality_sector_name` (`FilterBar.html.twig:35`, `MaterialitySectorNameExtension.php`, con caché por id).

### Baja de un sector

1. `GET /admin/materiality-sectors/remove/{id}` con `#[CsrfProtected('remove')]` (`MaterialitySector/RemoveController.php:18-19`). La acción de fila muestra la confirmación específica vía `confirmText` (`materiality-sectors/list.html.twig`).
2. `RemoveMaterialitySectorService`: `find()` o excepción → autoriza `Remove` → `remove()` → `add()` (`RemoveMaterialitySectorService.php`).
3. `MaterialitySectorRemoved` dispara `UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber` (`:33-56`), que recorre `findBySectorId()` (no incluye preguntas ya eliminadas):
   - si la pregunta tiene un solo sector, llama a `RemoveMaterialityQuestionService` (`:42-48`);
   - si tiene más, llama a `UpdateMaterialityQuestionService` con un request que copia todos los campos actuales y `sectorIds` sin el eliminado (`:63-80`).
   Las dos llamadas pasan por los servicios con `LoggedUser`, así que quedan autorizadas y auditadas como una edición manual.
4. El borrado masivo (`Bulk/RemoveAction.php`) genera un `RemoveMaterialitySectorRequest` por fila y produce la misma cascada, pero sin la confirmación específica.

## Autorización y ámbito

`MaterialitySectorAuthorizer` (`src/Materiality/Application/MaterialitySector/MaterialitySectorAuthorizer.php`): `List`, `Register`, `View`, `Edit`, `Remove` solo para `SuperAdmin` y `Admin` (vía `userHasRole`, que respeta la impersonación); `Import`, `Export`, `Autocomplete` siempre `false`. Es un maestro global: no depende de la cuenta ni interviene `CurrentAccountIdProvider`. `Owner` y `User` reciben 403 en las ocho rutas nuevas (cubierto en los `.feature`). La edición de sectores de una pregunta usa el `MaterialityQuestionAuthorizer` existente, también Admin only.

Rutas (`src/Materiality/Infrastructure/UI/Web/Admin/MaterialitySector/`): `admin_materiality_sectors` (`/admin/materiality-sectors`), `admin_materiality_sectors_register`, `admin_materiality_sectors_edit` (`/edit/{materialitySectorId}`), `admin_materiality_sectors_remove` (`/remove/{materialitySectorId}`, CSRF). El alta redirige a la edición del sector creado; la edición guarda y vuelve al listado con «Guardar y salir».

## Persistencia y migración

| Tabla | Columnas | Índices y claves |
|---|---|---|
| `materiality_sectors` | `id`, `name` (255), `removed`, `registered_at`, `updated_at`, `removed_at` | `idx_materiality_sectors_removed (removed)` |
| `materiality_sector_translation` | `locale`, `materiality_sector_id`, `name` | PK `(locale, materiality_sector_id)`; FK `ON DELETE CASCADE` |
| `materiality_question_sector_links` | `id`, `materiality_question_id`, `sector_id` | `uq_materiality_question_sector_link (materiality_question_id, sector_id)`, `idx_materiality_question_sector_links_sector (sector_id)`; FK a `materiality_questions` `ON DELETE CASCADE` |

- `sector_id` no tiene FK hacia `materiality_sectors` (mismo criterio que `topic_id`): la coherencia la mantiene el suscriptor de baja.
- La relación en `MaterialityQuestion.MaterialityQuestion.orm.xml:41` es `one-to-many` con `orphan-removal` y `fetch="EXTRA_LAZY"`; la entidad de enlace tiene id simple, nunca clave compuesta, para que `removeElement()` funcione con `orphan-removal`.
- `DoctrineMaterialitySectorRepository` ordena por nombre con `COALESCE(trans.name, s.name) AS HIDDEN sortName` (`:117`), porque DQL no admite `COALESCE` en `ORDER BY`, y `totalResultsByFilter()` resetea el `orderBy` (`:72`). El filtro por nombre busca en base y traducción del locale actual (`:101-106`).
- Migración: `migrations/Version20260929024409.php`, creada a partir de `migration.sql` de esta carpeta, con los nombres generados por Doctrine (`IDX_/FK_5CD5DE213D699DDD`, `IDX_/FK_E5A93C1C91B76C`). Sin columnas nuevas en `materiality_questions` y sin backfill.

## Configuración y operación

- Tipos DBAL registrados en `config/packages/doctrine.yaml:101-102`. Los `orm.xml` se cargan por directorio y los servicios por autowire (`src/Materiality/Infrastructure/Symfony/DependencyInjection/services.yaml`), y las rutas por atributos (`config/routes.yaml`): no hubo que declarar nada más.
- Menú: tercer hijo del nodo `Materialidad`, «Sectores», icono `solar:buildings-2-line-duotone` (`NodeLister.php:370-374`).
- Sin comandos de consola, colas, tareas programadas, variables de entorno ni correos. No se siembran sectores: `SeedStandardMaterialityMastersService` no cambia y las preguntas sembradas aplican a todos los sectores.
- Auditoría: seis plantillas en `templates/security/admin/events/detail/`. Las de `materialitySectorRemoved`, `materialityQuestionSectorLinked` y `materialityQuestionSectorUnlinked` muestran ids (sector y pregunta), no nombres; las de alta, edición y traducción muestran el nombre del payload.
- Traducciones: 17 claves nuevas; ES y EN completas, TR con prefijo `NEW_` pendiente.

## Cómo extenderlo

- **Cuestionario por sector** (plan futuro): las preguntas aplicables a un sector X son las enlazadas a X **más** las que tienen `appliesToAllSectors()`. El filtro `sectorId` actual no sirve para eso (es estricto): añade un criterio nuevo al `MaterialityQuestionFilter` (p. ej. `applicableToSectorId`) con un `leftJoin` + `OR` sin enlaces en Doctrine y su equivalente en `InMemoryMaterialityQuestionRepository::matches()`, y cúbrelo en `MaterialityQuestionRepositoryTest` (abstracto) para que lo verifiquen los dos adaptadores.
- **Nuevo campo del sector** (posición, activo…): añádelo al constructor, a los eventos `Registered`/`Updated` y sus `apply*`, al mapping y a los formularios, y revisa `findAll()` si el selector debe excluir inactivos.
- **Segmentar otro agregado por sector** (p. ej. categorías): copia el patrón `MaterialityQuestionSectorLink` + `MaterialityQuestionSectors` + `link/unlink/sync` y amplía el suscriptor de baja para que también recorra ese agregado.
- **Confirmaciones destructivas**: `List:Action:Delete` admite `confirmText` para sustituir el texto por defecto cuando la baja tiene efectos en cascada.
- **Trampas conocidas:** un campo `multiple` exige pasar el DTO a `createForm()`; la propiedad de colección debe inicializarse en el cuerpo del constructor, no promovida; mantén el paso por los servicios de aplicación en el suscriptor para conservar autorización y auditoría.

## Tests

- Unitarios (`tests/Unit/Materiality/`):
  - `Domain/Model/MaterialitySector/`: `MaterialitySectorTest` (traducción, `update` con locale, `remove` idempotente, `__toString`), Mothers y la tríada de repositorio (`MaterialitySectorRepositoryTest` abstracto + `InMemory…Test` + `Infrastructure/Persistence/Doctrine/Repository/DoctrineMaterialitySectorRepositoryTest.php` sobre SQLite).
  - `Application/MaterialitySector/`: authorizer y los cuatro servicios, con casos de permiso denegado e id inexistente.
  - `Domain/Model/MaterialityQuestion/MaterialityQuestionSectorsTest.php`: `appliesToAllSectors`, idempotencia de `linkSector`, `unlinkSector` sin evento, `sectorIds` como VOs, sincronización en `update`, payload y `rebuildFromPayload`.
  - `Domain/Event/UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriberTest.php`: cuatro preguntas (dos sectores, uno, ninguno, otro sector) y el caso con locale EN (`testKeepsTextsWhenLoggedUserBrowsesInAnotherLocale`).
  - Ampliados: `MaterialityQuestionRepositoryTest` (filtro `sectorId`, `findBySectorId`, total sin duplicados), servicios de alta/edición/listado de preguntas y `SeedStandardMaterialityMastersServiceTest` (preguntas sembradas aplican a todos).
- Aceptación (`tests/Acceptance/Materiality/`): `materiality-sector-{list,register,edit,remove}.feature` (incluida la cascada de baja) y escenarios ampliados en `materiality-question-{register,edit,list}.feature`. Pasos nuevos en `MaterialityContext.php` (`Given materiality sector :name with id :id exists`, `Then materiality question :id should have sectors :sectorIds`, `…should apply to all sectors`, etc.).
- Ejecución:
  - `make test-this test=tests/Unit/Materiality` (PHPUnit sobre una ruta).
  - `make test-acceptance options="tests/Acceptance/Materiality"` (Behat sobre el contexto).
  - `make test` para la batería completa.
- Detalle de aceptación: DomCrawler asigna los valores de las casillas por posición, por eso el alta envía `sectorIds:[222,111]` (orden alfabético del maestro) y la edición desmarca con `I untick the checkbox`.

## Desviaciones y deuda

Detalle en [`plan.md`](plan.md) (`## Implementación`) y [`review.md`](review.md).

- Las skills `create-entity`/`create-relationship` no se ejecutaron como generadores; el código se escribió a mano con sus convenciones.
- `MaterialityQuestionSectors::add()` añadido; `@var` de `$sectors` con `Collection`; `COALESCE … AS HIDDEN sortName`; `empty_data: ''` en el `name` de los formularios del sector.
- **B1 (abierto, bajo):** si quien da de baja el sector navega en un locale no por defecto y una pregunta afectada no tenía traducción en ese locale, el suscriptor crea una traducción idéntica al texto base y un `MaterialityQuestionTranslated` en auditoría (`UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber.php:69-70`).
- **B2 (abierto, bajo, fuera de alcance):** los formularios de **categorías** no tienen `empty_data: ''` en `name` (`RegisterMaterialityTopicType.php:41`, `UpdateMaterialityTopicType.php:37`).
- **Q1 (abierta):** el borrado masivo de sectores produce la cascada sin confirmación específica; su aviso muestra el id del sector, no el nombre.
- Traducciones TR de las 17 claves nuevas pendientes.
