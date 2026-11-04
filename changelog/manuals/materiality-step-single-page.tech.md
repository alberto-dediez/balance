# Materialidad · Cuestionario en una sola página — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-03 (correcciones de review aplicadas el 2026-11-04) · **Bounded Contexts:** `Materiality` (más un ajuste menor en `Shared`)

## Qué hace y por qué

El paso «Materialidad» del asistente de huella (propiedad y empresa) obligaba a recorrer una página por categoría (hasta 15 recargas). Ahora el cuestionario completo se pinta dentro de la página del paso, con dos vistas conmutables (acordeón por alcance y dirección, y matriz con una columna por pregunta genérica), autoguardado por categoría vía `fetch` y contadores que se actualizan sin recargar. El modelo de dominio no cambia: se sigue guardando por categoría con `MaterialityAssessment::answerTopic()` y se publica un `MaterialityAssessmentTopicAnswered` por guardado. Sin migración. Las decisiones están en la tabla «Decisiones tomadas» de `plan.md`.

Hay una diferencia deliberada entre el plan y el código final (ver «Desviaciones»): el cuestionario solo se pinta mientras la evaluación está en borrador; completada, la página muestra únicamente la tabla resumen.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Materiality/Application/MaterialityAssessment/GetStep/MaterialityQuestionnaireBoardBuilder.php` | Agrupa categorías por alcance/dirección, detecta columnas genéricas y calcula contadores por fila y grupo | nuevo |
| `src/Materiality/Application/MaterialityAssessment/GetStep/MaterialityQuestionnaireBoard.php`, `…Group.php`, `…Row.php`, `…Column.php` | DTOs de solo lectura que consume Twig | nuevos |
| `src/Materiality/Application/MaterialityAssessment/GetStep/GetMaterialityStepService.php` | Inyecta el builder y añade `board` a la respuesta; `inheritedFromYear()` privado (`:93`); `summaryRows()` solo con evaluación completada (`:73`) | modificado |
| `src/Materiality/Application/MaterialityAssessment/GetStep/GetMaterialityStepResponse.php` | Nuevo campo `board` | modificado |
| `src/Materiality/Application/MaterialityAssessment/AnswerTopic/AnswerMaterialityAssessmentTopicService.php` / `…Response.php` | Devuelve `topicAnswered`, `topicTotal`, `answered`, `total`, `fullyAnswered` además de `nextTopicId` | modificado |
| `src/Materiality/Domain/Model/MaterialityAssessment/MaterialityAssessmentAnswer.php` | `isInherited()` (`:100`); `answer()` no toca valor ni fecha si el valor no cambia (`:112-118`) | modificado |
| `src/Materiality/Infrastructure/UI/Web/MaterialityAssessment/TopicController.php` | `GET` redirige al paso con ancla; `POST` responde JSON si es XHR, o flash + redirección si no | modificado |
| `src/Materiality/Infrastructure/UI/Web/MaterialityAssessment/StartController.php` | Tras iniciar, redirige directamente al paso con ancla de la primera categoría (`:97`) | modificado |
| `templates/materiality/assessment/_step.html.twig` | Monta el controlador Stimulus, incluye el cuestionario, convierte «Progreso» en targets, botón «Completar» con `disabled` según `board.fullyAnswered` | modificado |
| `templates/materiality/assessment/_questionnaire.html.twig` | Conmutador de vistas, acordeón y matriz | nuevo |
| `templates/materiality/assessment/_answer.html.twig` | Macro `answer()` con el `<fieldset>` de una pregunta (único por pregunta) | nuevo |
| `templates/materiality/assessment/topic.html.twig` | Página por categoría | eliminado |
| `assets/controllers/materiality_questionnaire_controller.js` | Stimulus: vistas, autoguardado, contadores, atajo, apertura por ancla | nuevo |
| `assets/styles/components/_materiality.scss`, `assets/styles/app.scss:44` | Estilos de la matriz y del modo compacto | nuevo / modificado |
| `assets/icons/solar/info-circle-line-duotone.svg` | Icono de ayuda de las columnas de la matriz | nuevo |
| `translations/messages+intl-icu.{es,en,tr}.xlf` | Textos nuevos (ver abajo) | modificados |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | Cambio no relacionado con la funcionalidad: «Proveedores» pasa a nodo con un hijo (`:615-625`) | modificado (ajeno) |

## Modelo de dominio

- Sin eventos nuevos. `answerTopic()` (`MaterialityAssessment.php:249`) valida que la evaluación sea borrador, que cada `answerId` pertenezca a la categoría (`InvalidMaterialityAnswerValueException::foreignAnswer`) y que el valor esté en la escala (`accepts()`), y publica `MaterialityAssessmentTopicAnswered`.
- **Respuesta heredada** (`MaterialityAssessmentAnswer::isInherited()`): `answeredAt` no nulo e igual (`==`) a `MaterialityAssessment::registeredAt()`. Es derivada: las respuestas copiadas al iniciar llevan `occurredOn()` del evento de inicio, el mismo instante que `registeredAt`.
- **Corrección posterior (review A2):** `MaterialityAssessmentAnswer::answer()` hace `return` temprano si `$value === $this->value`. Consecuencia: reenviar los valores de toda la categoría (que es lo que hace el autoguardado) solo pierde la marca de heredada en las respuestas cuyo valor cambia. `answeredAt` es, pues, la fecha del último cambio de valor, no del último guardado. Esto contradice la redacción del plan («Respuestas heredadas») y está cubierto en `MaterialityAssessmentTest`.
- Un valor vacío (`''` en el POST) se traduce a `null` y desmarca la respuesta (`TopicController.php:118-124`).

## Flujo de una operación

**Pintar el paso** (`GET property_carbon_footprints_materiality` / `company_carbon_footprints_materiality`):
`Step/{Property,Company}MaterialityStepController` → `GetMaterialityStepService` (`:51-66`) → si hay evaluación, `MaterialityQuestionnaireBoardBuilder::build($assessment, $inheritedFromYear)`; `inheritedFromYear` es el año de la evaluación anterior solo si alguna respuesta es heredada (`:93-102`) → `_step.html.twig` (`showQuestionnaire = step.board is not null and not assessment.isCompleted`) → `_questionnaire.html.twig`.

Reglas del builder (`MaterialityQuestionnaireBoardBuilder.php`):
1. Columnas genéricas = preguntas (`questionId`) presentes en **todas** las categorías con al menos una respuesta, ordenadas por `position` (`columns()`); la instantánea no guarda si una pregunta era genérica, se deduce.
2. Grupos por `(scope, direction)`, ordenados por `scope` y luego Upstream, Downstream, sin dirección (`directionOrder()`); dentro, el orden de `MaterialityAssessment::topics()`.
3. Cada fila separa `genericAnswers` (indexadas por `questionId`) y `specificAnswers`, con `answered`/`total`.

**Guardar una categoría** (`POST materiality_assessments_topic`, `TopicController.php`):
1. Carga con `GetMaterialityAssessmentTopicService` (autoriza `View`; `UnauthorizedActionException` → 403, no encontrado → 404).
2. `GET`: `302` al paso con `_fragment` `topic-{id}` (`:58-60`). Mantiene vivos enlaces antiguos.
3. `POST`: `answer()` comprueba huella completada → `MaterialityAssessmentNotEditableException`, evaluación completada → `MaterialityAssessmentAlreadyCompletedException`, CSRF (`materiality_topic_{topicId}`, `AccessDenied` 403), parsea `answers[]` (`ctype_digit`) y llama a `AnswerMaterialityAssessmentTopicService` (autoriza `Edit`, `ensureIsEditable`, `answerTopic`, `add`).
4. Errores: valor inválido → 422, huella o evaluación completada → 409. Con XHR (`isXmlHttpRequest()`) devuelve `{"message": …}` traducido; sin XHR, `addFlash` (`danger`/`warning`) y redirección al paso con ancla.
5. Éxito con XHR: JSON `{topicAnswered, topicTotal, answered, total, fullyAnswered}`; sin XHR, redirección al paso con ancla.

**Cliente** (`materiality_questionnaire_controller.js`):
- Un único `<fieldset>` por pregunta; al conmutar vista `#show()` lo mueve (`appendChild`) entre su hueco del acordeón (`data-origin-answer-id`) y su celda de matriz (`data-slot-answer-id`) y `#applyViewClasses()` cambia las clases (solo hay modo compacto si el hueco lleva `data-slot-compact`: las específicas, que van en la fila desplegable, conservan texto, ayuda y radios normales). Los radios se asocian a su formulario con el atributo `form=` para seguir siendo del `<form>` de la categoría aunque se muevan.
- Vista persistida en `localStorage` con la clave `materiality.questionnaireView` (`_step.html.twig:6`).
- Autoguardado: `changed()` → debounce de 400 ms por categoría → `#save()` encadena las peticiones de una categoría en una promesa; `#send()` hace `fetch` con `X-Requested-With` y `Accept: application/json`. Se considera error cualquier respuesta no OK, redirigida, no JSON o ilegible (corrección review M1): toast `danger` y badge «Sin guardar». Un 409 deshabilita todos los radios y el botón «Completar».
- `#applyCounts()` actualiza badges de categoría, de grupo (suma de los `data-answered`/`data-total` de las filas del grupo), global, «Progreso: X de Y» y habilita o no «Completar evaluación». Si una categoría pasa a completa en vista acordeón, abre la siguiente pendiente y pliega la actual conservando la posición de scroll (`#openNextPendingTopic()`).
- «Marcar toda la categoría como Bajo/No» (`markMinimum`): marca el primer valor de la escala (`data-scale-min` = `values()[0]`) de cada pregunta de la categoría y dispara `change`, es decir, el autoguardado normal; no hay endpoint nuevo.
- Al cargar, todos los paneles del acordeón se pliegan salvo el del ancla `#topic-{id}`; `hashchange` reabre desde el ancla (`openFromHash`). El botón «Guardar» (`no-js-only`) se oculta con JS; sin JS cada categoría se guarda con su `<form>` normal.
- Cambiar un radio retira la marca de heredada (`#removeInheritedMark`).

## Autorización y ámbito

Sin cambios: `MaterialityAssessmentAuthorizer` (`View`/`Edit` solo Propietario de la cuenta actual, resuelta con `currentAccountId($user)`). Un Propietario de otra cuenta recibe 403 en el paso y en el guardado (escenarios Behat). Con impersonación de un Administrador se accede al paso como Propietario de la cuenta impersonada (`materiality-assessment-step.feature`). Sin feature flags.

## Persistencia y migración

Ninguna. Las evaluaciones existentes se pintan tal cual; la marca de heredada se deriva de `answeredAt` y `registeredAt`.

## Configuración y operación

- Sin variables de entorno, colas ni comandos. Sin dependencias nuevas en `importmap.php` (no hay Turbo).
- Estilos en `assets/styles/components/_materiality.scss`; tras tocarlos, `make develop`. El icono nuevo se importó con `make icon-import`.
- Traducciones nuevas (clave = texto ES): «Sin guardar», «Heredada de la evaluación de {year}», «Vista», «Acordeón», «Matriz», «Marcar toda la categoría como Bajo/No», «Específicas», «{count} preguntas específicas», «Cuestionario». Se reutilizan «No se han podido guardar las respuestas.», «La huella está completada. Reábrela para modificarla.» y «La evaluación está completada. Reábrela para modificarla.».

## Cómo extenderlo

- **Añadir una vista nueva:** pintar su contenedor en `_questionnaire.html.twig` con huecos `data-slot-answer-id`, registrar el target en `static targets` y ampliar `#show()`/`VIEW_CLASSES`. Nunca duplicar los radios: dos `name="answers[id]"` en la misma página rompen el envío.
- **Nuevo dato por categoría/grupo:** añadirlo al DTO correspondiente en `GetStep/` y calcularlo en el builder; Twig solo itera.
- **Nuevo campo en la respuesta JSON:** `AnswerMaterialityAssessmentTopicResponse` → `TopicController` → `#applyCounts()`.
- **Trampas:** los badges de categoría existen dos veces (acordeón y matriz), por eso se distinguen con `data-group-key` (solo el del acordeón) en `#accordionBadge()`; el `<form>` de cada categoría solo existe en el acordeón y es el que serializa `FormData`, así que la matriz depende de que los radios mantengan el atributo `form=`. `GetMaterialityAssessmentTopicService` sigue vivo solo para autorizar y resolver el `TopicController`.

## Tests

- Unitarios: `tests/Unit/Materiality/Application/MaterialityAssessment/GetStep/MaterialityQuestionnaireBoardBuilderTest.php` (agrupación, columnas, contadores), `GetStep/GetMaterialityStepServiceTest.php`, `AnswerTopic/AnswerMaterialityAssessmentTopicServiceTest.php` (contadores), `tests/Unit/Materiality/Domain/Model/MaterialityAssessment/MaterialityAssessmentTest.php` (heredada, conserva marca si el valor no cambia).
- Behat: `tests/Acceptance/Materiality/materiality-assessment-answer.feature` (redirección del `GET`, guardado con y sin ajax, valores fuera de escala o no numéricos, evaluación y huella completadas, otra cuenta, heredadas, matriz), `materiality-assessment-step.feature` («grouped by direction»), `materiality-assessment-start.feature`; pasos en `tests/Acceptance/Materiality/MaterialityContext.php`.
- Ejecutar: `make test-unit options="--filter Materiality"`, `make test-this test=tests/Acceptance/Materiality/materiality-assessment-answer.feature`, `make test-acceptance options="tests/Acceptance/Materiality"`.
- Hueco: el controlador Stimulus (conmutador, movimiento de nodos, apertura automática, atajo) no tiene cobertura automatizada; se verificó a mano (ver `review.md`).

## Desviaciones y deuda

- Respecto al plan, el cuestionario no se muestra con la evaluación completada (`_step.html.twig:2`); la tabla resumen sigue mostrándose. Por eso el plan «cuestionario plegado en solo lectura» solo aplica a la huella completada con evaluación en borrador.
- La «barra fija de Completar» que el plan y la review B1 describen no está en el código actual: `_questionnaire.html.twig` no contiene ninguna barra y el único botón «Completar evaluación» está al final de la tarjeta del paso (`_step.html.twig:161`). Pendiente de confirmar con quien aplicó la corrección.
- La corrección A2 cambia el significado de `answeredAt` (ver arriba) respecto al plan.
- El fichero `NodeLister.php` aparece modificado en el árbol de trabajo por un cambio ajeno a esta funcionalidad.
- Hallazgos de la review: todos (A1, A2, M1, B1–B4) figuran como corregidos; no queda ninguno abierto en [`review.md`](review.md). Plan: [`plan.md`](plan.md).
