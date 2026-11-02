# Materialidad · Cuestionario por sectores — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-02 · **Bounded Contexts:** `Materiality` (agregado nuevo, UI del paso), `CarbonFootprint` (estado nuevo del asistente)

## Qué hace y por qué

Los maestros de materialidad (categorías, preguntas, sectores) ya existían. Esta funcionalidad añade el uso real: un paso nuevo **«Materialidad»** en el asistente de la huella de una propiedad y de una empresa, entre «Alcance 2» y «Alcance 3», donde el Propietario elige el sector, responde un cuestionario por categoría y obtiene una **puntuación de relevancia por categoría** (porcentaje normalizado de 0 a 100).

Decisiones clave (tabla *Decisiones tomadas* de `plan.md`):

- El agregado `MaterialityAssessment` vive en `Materiality` y se liga a la huella por referencia plana (`subjectType` + `carbonFootprintId`, sin FK), como `MitigationAction`. La dependencia es unidireccional `Materiality → CarbonFootprint`; `CarbonFootprint` solo gana un estado, un evento y un servicio por tipo de huella.
- El sector se elige en la propia evaluación (no se añade a `Property`, `Company` ni `Account`). Sin sector solo aplican las preguntas sin sectores enlazados.
- Al iniciar se **congela el cuestionario** (instantánea en dos tablas hijas), de modo que editar los maestros después no altera la evaluación.
- La puntuación se normaliza: `round(100 × (points − minPoints) / (maxPoints − minPoints), 2)`. Se guardan `points`, `min_points`, `max_points` y `highest_magnitude_level` (este último para el plan posterior de activación de categorías).
- El paso es **opcional** («Continuar sin evaluar») y no tiene consecuencias aguas abajo todavía (activación automática y documento de exclusiones son planes posteriores en `planning`).

## Mapa de piezas

### Materiality — dominio (`src/Materiality/Domain/`)

| Fichero | Rol | Estado |
|---|---|---|
| `Model/MaterialityAssessment/MaterialityAssessment.php` | Agregado: `start`, `answerTopic`, `complete`, `reopen`, `remove` | nuevo |
| `Model/MaterialityAssessment/MaterialityAssessmentTopic.php` | Hija: categoría congelada + puntuación (`scorePercent()` `:94`) | nuevo |
| `Model/MaterialityAssessment/MaterialityAssessmentAnswer.php` | Hija: pregunta congelada + valor (`accepts`, `answer`, `points`, `minPoints`, `maxPoints`) | nuevo |
| `Model/MaterialityAssessment/MaterialityTopicSnapshot.php`, `MaterialityQuestionSnapshot.php` | VOs de instantánea (con `assessmentTopicId` / `assessmentAnswerId` opcionales, ver *Modelo*) | nuevo |
| `Model/MaterialityAssessment/MaterialityAssessment{Started,TopicAnswered,Completed,Reopened,Removed}.php` | Eventos de dominio | nuevo |
| `Model/MaterialityAssessment/MaterialityAssessmentStatus.php`, `…SubjectType.php`, `…Filter.php`, `…Repository.php`, `…Id.php`, `…TopicId.php`, `…AnswerId.php` | Estado, tipo de sujeto, puerto y VOs | nuevo |
| `Model/MaterialityAssessment/*Exception.php` | `NotFound`, `AlreadyExists`, `AlreadyCompleted`, `NotCompleted`, `Incomplete`, `TopicNotFound`, `InvalidMaterialityAnswerValue` | nuevo |
| `Model/MaterialityQuestion/MaterialityQuestionRepository.php` | `findApplicableToSector(?MaterialitySectorId)` | modificado |
| `Model/MaterialityQuestion/MaterialityAnswerScale.php` | `labelKey(int)` `:27` (`level_2`, `yes_no_3`); `values()` con PHPDoc `non-empty-list<int>` | modificado |
| `Event/AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber.php` | Avanza la huella al completar | nuevo |
| `Event/RemoveMaterialityAssessmentOn{Property,Company}CarbonFootprintRemovedSubscriber.php` | Baja en cascada al eliminar la huella | nuevo |

### Materiality — aplicación (`src/Materiality/Application/MaterialityAssessment/`)

| Fichero | Rol | Estado |
|---|---|---|
| `MaterialityAssessmentAuthorizer.php` | Owner de la cuenta actual; `Remove` también Admin/SuperAdmin | nuevo |
| `MaterialityAssessmentFootprints.php` | Localiza la huella, autoriza contra el authorizer de huella, `ensureIsEditable`, `ensureScope2Answered`, `previous` | nuevo |
| `MaterialityQuestionnaireBuilder.php`, `MaterialityQuestionnaire.php` | Construye la instantánea del cuestionario | nuevo |
| `Start/`, `AnswerTopic/`, `Complete/`, `Reopen/`, `Remove/`, `RemoveOfCarbonFootprint/` | Casos de uso de escritura (`CommandHandler`) | nuevo |
| `GetStep/`, `GetTopic/` | Consultas para las pantallas (no son `CommandHandler`) | nuevo |
| `MaterialityAssessmentNotEditableException.php`, `MaterialityAssessmentScope2NotAnsweredException.php` | Excepciones de aplicación | nuevo |

### Materiality — infraestructura

| Fichero | Rol | Estado |
|---|---|---|
| `Infrastructure/Persistence/Doctrine/Mapping/MaterialityAssessment.*.orm.xml` (×3) | Mappings de las tres tablas | nuevo |
| `Infrastructure/Persistence/Doctrine/Type/MaterialityAssessment{,Topic,Answer}IdType.php` | Tipos Doctrine, registrados en `config/packages/doctrine.yaml` | nuevo |
| `Infrastructure/Persistence/Doctrine/Repository/DoctrineMaterialityAssessmentRepository.php`, `…/InMemory/Repository/InMemoryMaterialityAssessmentRepository.php` | Repositorios | nuevo |
| `Infrastructure/Persistence/Doctrine/Repository/DoctrineMaterialityQuestionRepository.php:93` y `InMemory…QuestionRepository.php` | `findApplicableToSector` (DQL con `NOT EXISTS` / `EXISTS` sobre `MaterialityQuestionSectorLink`) | modificado |
| `Infrastructure/Symfony/Form/StartMaterialityAssessmentType.php` | Formulario de inicio: `sectorId`, `copyPreviousAnswers`, `start` | nuevo |
| `Infrastructure/UI/Web/MaterialityAssessment/Step/{Property,Company}MaterialityStepController.php` | Paso del asistente (GET pantalla, POST «omitir») | nuevo |
| `Infrastructure/UI/Web/MaterialityAssessment/{Start,Topic,Complete,Reopen,Remove}Controller.php`, `MaterialityStepRoute.php`, `GetMaterialityAssessmentFromIdTrait.php` | Resto de rutas | nuevo |
| `Infrastructure/UI/Web/Twig/MaterialityQuestionTextExtension.php` | Filtro `materiality_question_text` (id → pregunta actual o `null`) | nuevo |
| `templates/materiality/assessment/_step.html.twig`, `step/{property,company}.html.twig`, `topic.html.twig` | Pantallas | nuevo |
| `templates/materiality/translations/materialityAssessmentStatusType.html.twig`, `materialityAnswerValueType.html.twig` | Plantillas de extracción de dominios de traducción | nuevo |
| `templates/security/admin/events/detail/materiality.materialityAssessment{Started,TopicAnswered,Completed,Reopened,Removed}.html.twig` y `carbon-footprint.{property,company}CarbonFootprintMaterialityAnswered.html.twig` | Detalle del registro de auditoría | nuevo |

### CarbonFootprint

| Fichero | Rol | Estado |
|---|---|---|
| `Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatus.php` | Caso `MaterialityAnswered = 'materiality_answered'`, `isMaterialityAnswered()`, `stepNumber()` 1..8, `activeStepKey()` `materiality` | modificado |
| `Domain/Model/{Property,Company}CarbonFootprint/…CarbonFootprint.php` | `answerMateriality()` (`PropertyCarbonFootprint.php:114`), `applyPropertyCarbonFootprintMaterialityAnswered` `:422` | modificado |
| `Domain/Model/{Property,Company}CarbonFootprint/…CarbonFootprintMaterialityAnswered.php` | Evento | nuevo |
| `Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintScope2NotAnsweredException.php` | Se lanza si se responde materialidad sin alcance 2 | nuevo |
| `Application/{Property,Company}CarbonFootprint/AnswerMateriality/*` | `Request` + `Service` (sin `Response`, como `AnswerProviders`) | nuevo |
| `Infrastructure/UI/Web/{Property,Company}CarbonFootprint/EditController.php`, `Step/{Scope2,Scope3,Providers,Employees,MitigationPlan,Results}Controller.php` | Redirecciones al paso nuevo (`Scope2Controller.php:81`, guarda en `Scope3Controller.php:51`, rama `materiality` en `EditController.php:30`) | modificado |
| `templates/carbon_footprint/{property,company}_carbon_footprints/wizard-layout.html.twig` y plantillas `step/*.html.twig` | Paso «Materialidad» en el listado, `wizard_active`, `wizard_selected` | modificado |
| `templates/carbon_footprint/translations/organizationalCarbonFootprintStatusType.html.twig` | Etiqueta del estado `materiality_answered` | modificado |

## Modelo de dominio

### Estados de la huella

| Estado | `stepNumber()` | `activeStepKey()` |
|---|---|---|
| `started` | 1 | `scope-1` |
| `scope_1_answered` | 2 | `scope-2` |
| `scope_2_answered` | 3 | `materiality` |
| `materiality_answered` | 4 | `scope-3` |
| `scope_3_answered` | 5 | `providers` |
| `providers_answered` | 6 | `employees` |
| `employees_answered` | 7 | `mitigation-plan` |
| `completed` | 8 | `results` |

`isMaterialityAnswered()` es verdadero desde `materiality_answered` en adelante; `isScope2Answered()` incluye el caso nuevo. El avance usa `advancedTo` (monótono): una huella que ya tenía el alcance 3 respondido no retrocede.

`answerMateriality()` (`PropertyCarbonFootprint.php:114`): exige huella editable, es **idempotente** (si ya está respondida retorna sin publicar) y lanza `OrganizationalCarbonFootprintScope2NotAnsweredException` si el alcance 2 no está respondido.

### Agregado `MaterialityAssessment`

Campos: `subjectType` (`property`|`company`), `carbonFootprintId` (string, sin FK), `accountId` y `year` (copiados de la huella al iniciar), `sectorId` + `sectorName` (instantánea), `status` (`draft`|`completed`), `completedBy`/`completedAt`, `removed`. Hijas `topics` y `answers` (`MaterialityAssessment.php:17-21`).

| Operación | Reglas | Evento |
|---|---|---|
| `start(...)` `:207` | Asigna ids a las hijas **antes** de publicar (`withAssessmentTopicId` / `withAssessmentAnswerId`) para que el payload sea reproducible; `apply…Started` crea las hijas y aplica los `initialValues` válidos para su escala | `MaterialityAssessmentStarted` (instantánea completa en el payload) |
| `answerTopic(...)` `:241` | No eliminada, en borrador; la categoría existe; cada id de respuesta pertenece a ella (`InvalidMaterialityAnswerValueException::foreignAnswer`); valor en la escala o `null` (`::outOfScale`) | `MaterialityAssessmentTopicAnswered` (un evento por categoría guardada) |
| `complete(UserId)` `:275` | En borrador y **todas** las respuestas informadas (`MaterialityAssessmentIncompleteException`); calcula por categoría `points`, `minPoints`, `maxPoints`, `highestMagnitudeLevel` (máximo valor de respuestas de criterio `magnitude`, `null` si no hay) | `MaterialityAssessmentCompleted` (puntuaciones en el payload) |
| `reopen()` `:320` | Exige `completed` (`…NotCompletedException`); vuelve a `draft`, limpia `completedBy/At` y `clearScore()` en las hijas | `MaterialityAssessmentReopened` |
| `remove()` `:335` | Idempotente; baja lógica | `MaterialityAssessmentRemoved` |

`topics()` ordena por `[scope, position, name]` (`:106-113`; corrección posterior a la review, el plan fijaba solo `position, name`).

### Puntuación

- `MaterialityAssessmentAnswer`: `points() = (value ?? 0) × weight`, `minPoints() = min(scale.values()) × weight`, `maxPoints() = scale.max() × weight`. Ambas escalas (`level` 1..3, `yes_no` 1|3) tienen mínimo 1 y máximo 3.
- `MaterialityAssessmentTopic::scorePercent()` (`:94`): `null` si falta alguna puntuación o `maxPoints <= minPoints`; si no, `round(100 × (points − minPoints) / (maxPoints − minPoints), 2)`.
- Ejemplo (Magnitud Alto peso 2; Influencia Medio, Riesgo Bajo, Interés Alto, Externalización Sí, peso 1): `points = 15`, `minPoints = 6`, `maxPoints = 18` → 75,00 %. Todo «Bajo/No» → 0,00 %; todo «Alto/Sí» → 100,00 %.
- En pantalla se formatea con `number_format(2, ',', '.')`.

### Excepciones

`MaterialityAssessmentNotFoundException` (también al operar sobre una evaluación eliminada), `…AlreadyExistsException` (ya hay una activa para la huella), `…AlreadyCompletedException` (responder una completada), `…NotCompletedException` (reabrir un borrador), `…IncompleteException` (completar con huecos), `…TopicNotFoundException`, `InvalidMaterialityAnswerValueException`. De aplicación: `MaterialityAssessmentNotEditableException` (huella completada) y `MaterialityAssessmentScope2NotAnsweredException`.

### Consulta de preguntas aplicables

`MaterialityQuestionRepository::findApplicableToSector(?MaterialitySectorId)`: preguntas activas, no eliminadas, y sin sectores enlazados **o** enlazadas al sector dado; con `null`, solo las que no tienen sectores. Orden `position, registeredAt, id`. El filtro `sectorId` de `findByFilter` conserva su semántica estricta (solo enlazadas).

### Construcción del cuestionario

`MaterialityQuestionnaireBuilder::build()` (`Application/MaterialityAssessment/MaterialityQuestionnaireBuilder.php`): categorías = `MaterialityTopicRepository::findActive()` (de cualquier alcance) ordenadas por `[scope, position, defaultName]`; para cada categoría T, preguntas = genéricas aplicables (`topicId === null`) + específicas aplicables con `topicId == T`, ordenadas por `[position, registeredAt, id]`. Una pregunta genérica se repite en todas las categorías. Los snapshots usan siempre `defaultName()` / `defaultText()` / `defaultHelp()` (locale por defecto) y `questionUpdatedAt = updatedAt() ?? registeredAt()`.

## Flujo de una operación

### Entrar al paso (GET)

`GET /{_locale}/property-carbon-footprints/{id}/step/materiality` → `PropertyMaterialityStepController` (`company_` es espejo):
1. Carga la huella (404 si no existe) y `denyAccessUnlessGranted(Edit)`.
2. Si `!status.isScope2Answered()` redirige a `*_scope_2`/`*_scope_1`.
3. `GetMaterialityStepService` (autoriza `View` con el authorizer de la huella): devuelve la evaluación actual, `MaterialitySectorRepository::findAll()`, y, si la huella anterior del sujeto (`MaterialityAssessmentFootprints::previous`: la no eliminada de mayor año inferior) tiene evaluación **completada**, su año y su sector.
4. Renderiza `@materiality/assessment/step/{property,company}.html.twig` (`_step.html.twig`): sin evaluación → formulario de inicio (o aviso si la huella está completada); borrador/completada → tabla de categorías y botones.

### Omitir (POST en la misma ruta)

Guarda de huella completada (flash `warning`, vuelve al paso); CSRF `materiality_skip_{footprintId}`; `AnswerPropertyCarbonFootprintMaterialityService` (autoriza `Edit`, `answerMateriality()`) y redirige a `*_scope_3`.

### Iniciar

`POST /materiality-assessments/{subjectType}/{carbonFootprintId}/start` (`materiality_assessments_start`) → `StartController` → `StartMaterialityAssessmentService` (`Start/StartMaterialityAssessmentService.php`):
`footprints->find` → `denyUnlessCanDoThat(Edit)` → `ensureIsEditable` → `ensureScope2Answered` → si ya hay evaluación actual, `AlreadyExistsException` → resuelve el sector (`MaterialitySectorNotFoundException`) → `questionnaireBuilder->build` → si `copyPreviousAnswers`, `previousValues()` (solo desde una evaluación **completada** de la huella anterior; copia los pares categoría×pregunta que existan en el cuestionario nuevo con la **misma escala**) → `new MaterialityAssessment(...)->start(...)` → `authorizer Register` → `add`. Redirige a la primera categoría (`materiality_assessments_topic`) o al paso si no hay categorías. La unicidad por huella se comprueba en el servicio, sin restricción en BD (decisión del plan por la baja lógica; riesgo de doble envío aceptado en `review.md` Q1).

El formulario `StartMaterialityAssessmentType` usa el CSRF propio de Symfony Form. Si no es válido o el sector no existe: flash `danger` «No se ha podido iniciar la evaluación de materialidad.».

### Responder una categoría

`GET|POST /materiality-assessments/{id}/topics/{assessmentTopicId}` (`materiality_assessments_topic`) → `TopicController`. GET: `GetMaterialityAssessmentTopicService` (autoriza `View`) → `topic.html.twig`. POST: guarda de huella completada / evaluación completada (flash `warning`), CSRF `materiality_topic_{assessmentTopicId}`, lee `answers[{answerId}]` (vacío → `null`; solo se aceptan cadenas `ctype_digit`, si no flash `danger` «No se han podido guardar las respuestas.»), `AnswerMaterialityAssessmentTopicService` (autoriza `Edit`, huella editable, `answerTopic`). Redirige a la siguiente categoría o, tras la última, al paso. Se admiten huecos en borrador.

### Completar, reabrir, reiniciar

`POST /materiality-assessments/{id}/{complete|reopen|remove}` con CSRF `materiality_assessment_{acción}_{id}`; ante `UnauthorizedActionException` → 403. `Complete` → `MaterialityAssessmentCompleted` → `AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber` invoca `Answer{Property,Company}CarbonFootprintMaterialityService` con `LoggedUser`. Incompleta → flash `danger` «Faltan preguntas por responder.». `AlreadyCompleted` / `NotCompleted` (doble envío) se ignoran sin aviso. `Remove` ("Reiniciar cuestionario") autoriza `Remove` sobre la evaluación **y además `Edit` sobre la huella** (`RemoveMaterialityAssessmentService.php`, corrección B5 de la review: un Admin sin impersonar recibe 403) y exige huella editable. Reabrir la huella (`reopen()`) no toca la evaluación.

### Baja de la huella

`PropertyCarbonFootprintRemoved` / `CompanyCarbonFootprintRemoved` → `RemoveMaterialityAssessmentOn…RemovedSubscriber` → `RemoveMaterialityAssessmentOfCarbonFootprintService`: da de baja la evaluación actual (autoriza `Remove`, **sin** exigir huella editable). No-op si no hay evaluación. Si hubiera varias activas (doble envío), solo se elimina la más reciente.

## Autorización y ámbito

- `MaterialityAssessmentAuthorizer`: `canEdit` = rol `Owner` (vía `userHasRole`) ∧ cuenta actual (`currentAccountId`, respeta impersonación) igual a `accountId` de la evaluación; `View`/`List`/`Register` delegan en `canEdit`; `Remove` = `canEdit` ∨ `Admin` ∨ `SuperAdmin`; `Import`/`Export`/`Autocomplete` = `false`.
- Los casos de uso que reciben la huella (`Start`, `GetStep`, `Remove`, «omitir») autorizan además contra el authorizer de la huella (`MaterialityAssessmentFootprints::denyUnlessCanDoThat`), que hoy es solo `Owner` de la cuenta actual. El rol `User`, `Admin` sin impersonar y Propietarios de otra cuenta reciben 403.
- Bloqueo por huella completada: toda escritura pasa por `MaterialityAssessmentFootprints::ensureIsEditable()`; los controladores repiten la guarda con el flash «La huella está completada. Reábrela para modificarla.» y la plantilla oculta los botones (`readOnly`).
- No hay feature flag propio.

## Persistencia y migración

| Tabla | Contenido | Claves |
|---|---|---|
| `materiality_assessments` | evaluación (`subject_type`, `carbon_footprint_id`, `account_id`, `year`, `sector_id`, `sector_name`, `status`, `completed_by/at`, `removed`, fechas) | índices `idx_materiality_assessments_subject (subject_type, carbon_footprint_id)` y `idx_materiality_assessments_account` |
| `materiality_assessment_topics` | categoría congelada + `points`, `min_points`, `max_points`, `highest_magnitude_level` | única `uq_materiality_assessment_topic (assessment, topic_id)`; FK `ON DELETE CASCADE` |
| `materiality_assessment_answers` | pregunta congelada + `value`, `answered_at` | única `uq_materiality_assessment_answer (assessment, topic_id, question_id)`; índice `(assessment, topic_id)`; FK `ON DELETE CASCADE` |

`topic_id` y `question_id` son referencias planas sin FK a los maestros (la instantánea sobrevive a su baja). Las hijas son `one-to-many` con `orphan-removal`, gestionadas solo desde el agregado. Sin columnas JSON.

Migración: `migrations/Version20261102135131.php` (creada por el usuario a partir de `migration.sql`, ver `review.md` A1). No se toca el esquema de las tablas de huellas: `materiality_answered` es un valor más de `status`. Sin backfill: las huellas en `scope_2_answered` aterrizan en el paso nuevo; las más avanzadas lo tienen disponible pero no obligatorio.

## Configuración y operación

- Sin variables de entorno, colas, comandos de consola ni tareas programadas nuevas. `services.yaml` no cambia: autowire por recurso de `App\Materiality\`; los suscriptores se registran por `autoconfigure`.
- Rutas bajo el prefijo `/{_locale}`: `property_carbon_footprints_materiality`, `company_carbon_footprints_materiality`, `materiality_assessments_{start,topic,complete,reopen,remove}`.
- Traducciones: dominios nuevos `materialityAssessmentStatusType` (`draft`, `completed`) y `materialityAnswerValueType` (`level_1..3`, `yes_no_1`, `yes_no_3`); claves nuevas en `messages+intl-icu.*.xlf` (ES y EN traducidas; TR con prefijo `NEW_`). Se regeneran con `make translation`.
- Registro de auditoría: cada evento tiene plantilla de detalle en `templates/security/admin/events/detail/`.
- El texto de ayuda y la descripción de categoría se pintan con `md_to_html` (primera pantalla que los muestra como HTML). Mientras la pregunta/categoría existe en el maestro se muestra su texto actual traducido (`materiality_question_text`, `materiality_topic_name`); si se eliminó, se cae a la instantánea. **Ojo:** lo congelado es el texto base; la pantalla puede mostrar el texto actual del maestro, no el instantaneado.
- Panel de listado de huellas: la etiqueta de estado `materiality_answered` ya existe en `organizationalCarbonFootprintStatusType`.

## Cómo extenderlo

- **Consumir la puntuación (plan de activación de categorías, plan de exclusiones):** leer `MaterialityAssessmentRepository::findCurrentByCarbonFootprint(subjectType, carbonFootprintId)` y, por cada `MaterialityAssessmentTopic`, `scorePercent()`, `points/minPoints/maxPoints` y `highestMagnitudeLevel()`. Los umbrales o veredictos deben añadirse como columnas a `materiality_assessment_topics`; no existen hoy. La instantánea de cada respuesta lleva `questionUpdatedAt` para auditoría.
- **Nueva escala de respuesta:** añadir el caso en `MaterialityAnswerScale` con `values()` y `max()`, y las etiquetas `{escala}_{valor}` en `materialityAnswerValueType` (+ plantilla de extracción). `minPoints()` asume que `values()` no está vacío.
- **Nuevo tipo de sujeto de huella:** añadir caso a `MaterialityAssessmentSubjectType` (+ `toReportSubjectType()`), ramas en `MaterialityAssessmentFootprints` y `MaterialityStepRoute`, controlador de paso y suscriptores de baja.
- **Nuevo paso o cambio de orden en el asistente:** la numeración vive en `OrganizationalCarbonFootprintStatus::stepNumber()` y en `wizard_active`/`wizard_selected` de las plantillas de cada paso (el `wizard_active` resta 2 cuando se oculta Proveedores/Empleados por alcance 3 excluido, desde el paso 5). Las cadenas de redirección por estado están repetidas en los controladores de paso (propiedad y empresa).
- Trampas: la instantánea no incluye traducciones; cambiar de sector exige reiniciar; el id de las hijas debe asignarse antes de publicar `Started` (si se añade otra hija, seguir el mismo patrón para que la reconstrucción desde eventos sea determinista).

## Tests

- **Unitarios** (`tests/Unit/Materiality/…`): `MaterialityAssessmentTest` (incluye reconstrucción desde payloads y orden por alcance), `MaterialityAssessmentTopicTest` (porcentajes), repositorio (abstracto + InMemory + Doctrine), `MaterialityQuestionRepositoryTest` (`findApplicableToSector`), `MaterialityAnswerScaleTest`, `Application/MaterialityAssessment/` (authorizer, builder, casos de uso con `MaterialityAssessmentContextTrait`) y suscriptores. En `CarbonFootprint`: `OrganizationalCarbonFootprintStatusTest`, `PropertyCarbonFootprintTest`/`CompanyCarbonFootprintTest` (avance, idempotencia, alcance 2 requerido) y servicios `AnswerMateriality`.
- **Aceptación** (`tests/Acceptance/Materiality/`): `materiality-assessment-step.feature`, `-start.feature`, `-answer.feature`, `-complete.feature` (permisos, sectores, copia del año anterior, valores no válidos, huella completada, 403 en los POST, cascada). Pasos nuevos en `MaterialityContext.php` y `CarbonFootprintContext.php`; el helper de token CSRF en sesión está en `tests/Acceptance/Shared/CsrfTokenInSessionTrait.php`. Las 14+ features existentes del asistente se adaptaron al paso nuevo.
- Ejecución (objetivos del `Makefile`): `make test-unit options="tests/Unit/Materiality"` / `make test-acceptance options="tests/Acceptance/Materiality"`, o `make test-unit`, `make test-acceptance`, `make test-parallel`; además `make phpstan` y `make cs`.
- Huecos: ninguno abierto en `review.md`.

## Desviaciones y deuda

Ver `plan.md` (sección *Implementación*) y `review.md`. Resumen del estado actual del código:

- Corregidos tras la review (todos aplicados, `review.md` sin hallazgos abiertos): migración (A1), alcance 2 obligatorio al iniciar/completar (M1), orden por alcance (B1), `answerMateriality()` idempotente (B2), ids de hijas asignados antes de publicar `Started` (B3), valor no numérico rechazado (B4), reinicio denegado a Admin sin impersonar (B5), 403 en POST cubiertos (B6), helper CSRF compartido (B7). Los puntos B1 y B3 contradicen la letra del plan (orden de `topics()`, payload de `Started`).
- Riesgo aceptado (Q1): un doble envío de «Comenzar cuestionario» puede crear dos evaluaciones activas; solo la más reciente se considera y se da de baja en cascada.
- Los planes posteriores `…filtrado-y-activacion-de-categorias` y `…justificacion-de-exclusiones-para-auditorias` (en `planning`) presuponían contratos distintos entre sí; deben revisarse contra este modelo.
