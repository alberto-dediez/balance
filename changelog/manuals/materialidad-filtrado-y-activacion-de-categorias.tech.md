# Materialidad · Filtrado y activación de categorías del alcance 3 — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-02 · **Bounded Contexts:** `Materiality`, `CarbonFootprint`, `Shared` (menú)

## Qué hace y por qué

Antes de este plan, completar la evaluación de materialidad (plan `2026-11-02-materialidad-cuestionario-por-sectores`) solo producía una relevancia por categoría sin consecuencias: en el paso Alcance 3 cada categoría se excluía a mano. Ahora:

1. El Administrador fija un **umbral de relevancia** global y una regla de corte duro («magnitud alta fuerza calcular») en `Materialidad → Configuración`.
2. Al completar la evaluación, cada categoría con puntuación recibe un **veredicto** (`calculate` / `omit`) que se guarda con el umbral vigente (congelado; un cambio posterior del umbral no recalcula nada).
3. Un suscriptor de `Materiality` propaga los veredictos a la huella: guarda un **snapshot** de decisiones en la huella y, según su estado, excluye (`not_significant`, con comentario generado) o reactiva las fuentes del alcance 3.
4. El Propietario puede **anular** la recomendación en ambos sentidos, con justificación obligatoria; queda registrado quién, cuándo y por qué.
5. El paso Materialidad pasa a ser el resumen de la decisión y Resultados distingue el **origen** de cada exclusión.

Decisiones clave (tabla «Decisiones tomadas» de `plan.md`): dependencia `Materiality → CarbonFootprint` (nunca al revés; `CarbonFootprint` no importa nada de `Materiality`), cruce categoría↔fuente por número (`Source::ofGhgProtocolCategory`), veredicto guardado en la propia `MaterialityAssessmentTopic`, snapshot en la huella para pintar sin leer `Materiality`, todo síncrono (≤ 15 filas por huella).

## Mapa de piezas

| Capa | Fichero | Rol | |
|---|---|---|---|
| Dominio `Materiality` | `src/Materiality/Domain/Model/MaterialitySettings/MaterialitySettings.php` | Agregado singleton: umbral y regla de magnitud alta; `register()` / `update()` | nuevo |
| | `…/MaterialitySettings/MaterialityDecisionRule.php` | VO con la regla `decide(?float, ?int): ?MaterialityVerdict`; `default()` = 50 % / `true` | nuevo |
| | `…/MaterialitySettings/{MaterialitySettingsRegistered,MaterialitySettingsUpdated,InvalidRelevanceThresholdException,MaterialitySettingsRepository,MaterialitySettingsId}.php` | Eventos, excepción (rango 1–100), puerto e id | nuevo |
| | `src/Materiality/Domain/Model/MaterialityAssessment/MaterialityAssessment.php:283` | `complete(UserId, MaterialityDecisionRule)`: calcula puntuación y veredicto por categoría y los publica | modificado |
| | `…/MaterialityAssessment/{MaterialityAssessmentTopic,MaterialityAssessmentCompleted,MaterialityAssessmentRemoved}.php` | Columnas `threshold_percent`, `forced_by_high_magnitude`, `verdict`; payload ampliado (incluye `scope` por categoría); `Removed` lleva `subjectType` y `carbonFootprintId` | modificado |
| | `src/Materiality/Domain/Event/ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriber.php` | `MaterialityAssessmentCompleted` → servicios Apply de la huella | nuevo |
| | `src/Materiality/Domain/Event/ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriber.php` | `MaterialityAssessmentRemoved` → servicios Clear de la huella | nuevo |
| Aplicación `Materiality` | `src/Materiality/Application/MaterialitySettings/{MaterialitySettingsAuthorizer,Get/*,Update/*,DecisionRule/MaterialityDecisionRuleProvider}.php` | Autorización (`Admin`/`SuperAdmin`), casos de uso y proveedor de la regla vigente | nuevo |
| | `src/Materiality/Application/MaterialityAssessment/MaterialityDecisionInputsMapper.php` | Evento → `MaterialityDecisionInput` (primitivos) | nuevo |
| | `…/MaterialityAssessment/Complete/CompleteMaterialityAssessmentService.php`, `…/GetStep/GetMaterialityStepService.php` (+ Response) | Pasa la regla a `complete()`; construye el resumen del paso | modificado |
| Dominio `CarbonFootprint` | `src/CarbonFootprint/Domain/Model/CarbonFootprint/{MaterialityVerdict,MaterialityDecision,MaterialityDecisionInput,MaterialityOverrideJustificationRequiredException}.php` | Enum, puerto de la decisión, DTO de entrada y excepción | nuevo |
| | `src/CarbonFootprint/Domain/Model/CarbonFootprint/Source.php:57` | `ofGhgProtocolCategory(int): ?self` (inverso de `ghgProtocolCategory()`) | modificado |
| | `…/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintTrait.php:239-460` | Consulta, propagación y pendientes de revisión (compartido por propiedad y empresa) | modificado |
| | `…/{Property,Company}CarbonFootprint/*CarbonFootprint.php` | `applyMaterialityDecisions`, `clearMaterialityDecisions`, `overrideMaterialityDecision`, `revertMaterialityOverride` y sus `apply*` | modificado |
| | `…/{Property,Company}CarbonFootprint/*MaterialityDecision{,Id}.php` y eventos `*MaterialityDecisionsApplied/Cleared`, `*MaterialityOverridden/OverrideReverted` | Entidad hija (snapshot + anulación) y eventos | nuevo |
| | `…/CarbonFootprint/CarbonFootprint.php`, `EventCarbonFootprint.php`, `OperatorContribution/OperatorContribution.php` | La interfaz gana los métodos de materialidad (no-op en las dos últimas) | modificado |
| Aplicación `CarbonFootprint` | `src/CarbonFootprint/Application/CarbonFootprint/Materiality/{MaterialityOmitCommentGenerator,MaterialityOverrideReconciler,MaterialitySummaryBuilder,MaterialitySummaryRow,MaterialitySummaryTopicInput,MaterialityCategoryState}.php` | Comentario generado, validación y reconciliación de anulaciones, resumen y estados | nuevo |
| | `src/CarbonFootprint/Application/{Property,Company}CarbonFootprint/{ApplyMaterialityDecisions,ClearMaterialityDecisions}/*` | Casos de uso invocados por los suscriptores | nuevo |
| | `…/{Property,Company}CarbonFootprint/AnswerScope/Answer*ScopeRequest|Service.php`, `GetScope/Get*ScopeResponse|Service.php` | Justificaciones de anulación en el guardado; decisiones y aviso en la lectura | modificado |
| Infraestructura | `src/Materiality/Infrastructure/UI/Web/Admin/MaterialitySettings/EditController.php` (ruta `admin_materiality_settings`, `/admin/materiality-settings`), `…/Symfony/Form/UpdateMaterialitySettingsType.php`, `templates/materiality/admin/materiality-settings/edit.html.twig` | Pantalla de configuración | nuevo |
| | `src/Materiality/Infrastructure/Persistence/{Doctrine,InMemory}/…MaterialitySettings*` y mappings/tipos DBAL de las hijas de decisión; `config/packages/doctrine.yaml` (3 tipos) | Persistencia | nuevo / modificado |
| | `src/CarbonFootprint/Infrastructure/UI/Web/CarbonFootprint/StageExclusionHttpMapper.php` | `mapOverrideJustifications()` | modificado |
| | `src/CarbonFootprint/Infrastructure/UI/Web/{Property,Company}CarbonFootprint/Step/{Scope3,Results}Controller.php`, `…/OrganizationalCarbonFootprint/View/OrganizationalResultsView.php` | Paso Alcance 3 y Resultados | modificado |
| | `templates/components/CarbonFootprint/SourceSection.html.twig`, `templates/carbon_footprint/organizational_carbon_footprints/_scope_3_sections.html.twig`, `…/{property,company}_carbon_footprints/step/results.html.twig`, `templates/materiality/assessment/_step.html.twig`, `assets/controllers/source_exclusion_controller.js` | UI | modificado |
| | `templates/carbon_footprint/translations/{materialityVerdictType,materialityCategoryStateType}.html.twig`, `translations/materiality{Verdict,CategoryState}Type+intl-icu.*.xlf`, ocho plantillas `templates/security/admin/events/detail/carbon-footprint.*Materiality*.html.twig` y dos `materiality.materialitySettings*` | Traducciones y detalle de auditoría | nuevo |
| | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:441` | Entrada «Configuración» en el grupo Materialidad | modificado |

## Modelo de dominio

**Regla de decisión** (`MaterialityDecisionRule.php`):

```
scorePercent === null → null (sin veredicto)
forced  = highMagnitudeForcesInclusion && highestMagnitudeLevel === MaterialityAnswerScale::Level->max()  // 3
verdict = (scorePercent >= thresholdPercent || forced) ? Calculate : Omit
```

El constructor valida 1–100 (`InvalidRelevanceThresholdException`). Sin fila en `materiality_settings`, `MaterialityDecisionRuleProvider::rule()` devuelve `MaterialityDecisionRule::default()` (50 %, `true`); la fila se crea al primer guardado (`UpdateMaterialitySettingsService`, que llama a `register()` o `update()`; `update()` es no-op si no cambia nada).

**Evaluación** (`MaterialityAssessment::complete()`, línea 283): por categoría calcula `points/minPoints/maxPoints`, `highestMagnitudeLevel`, `scorePercent`, veredicto y `forcedByHighMagnitude`, y los publica en un único `MaterialityAssessmentCompleted` (`scores[]`, con `scope` para distinguir categorías de alcance 1/2 con número 1–15). Reabrir llama a `clearScore()` y deja veredicto, umbral y forzado a `null` (invariante R7 del plan).

**Huella** (`PropertyCarbonFootprint` / `CompanyCarbonFootprint`): colección `materialityDecisions` de hijas con `topicId`, `topicNumber`, `source` (nulo si no hay fuente), `scorePercent`, `thresholdPercent`, `forcedByHighMagnitude`, `verdict`, `recommendedAt` y, si hay anulación, `overriddenByUserId`, `overriddenAt`, `overrideJustification`. Únicos por `(huella, topicId)` y `(huella, source)`.

Eventos de huella: `…MaterialityDecisionsApplied` (lista de entradas), `…MaterialityDecisionsCleared`, `…MaterialityOverridden`, `…MaterialityOverrideReverted`. Eventos de ajustes: `MaterialitySettingsRegistered/Updated`.

**Propagación** (`OrganizationalCarbonFootprintTrait::propagateMaterialityDecisions`, línea 387), llamada desde `applyMaterialityDecisions` tras publicar el evento:

- No hace nada si el alcance 3 está excluido completo; `applyMaterialityDecisions` no hace nada si la huella está completada.
- No toca categorías con anulación ni sin fuente propagable (`materialitySourceIsPropagable`: en propiedad excluye las `companyOnlySources`, en empresa siempre `true`).
- Veredicto `calculate`: `includeSource` solo si la decisión previa de esa fuente era `omit` sin anulación (`recommendedExclusionsBySource`, R4); una exclusión manual nunca se retira sola.
- Veredicto `omit`: si la huella aún no respondió el alcance 3, `excludeSource(Scope3, source, NotSignificant, comentario)`; si ya lo respondió, solo si la fuente está activa y `!hasDataOfSource($source)` (en empresa incluye filas de propiedades no consolidadas); si hay datos, no se toca y la categoría queda **pendiente de revisión**.
- Al reaplicar, las anulaciones se conservan si el veredicto no cambia y se revierten (`OverrideReverted`) si cambia (`overriddenSourcesChangingVerdict`).
- Las filas de decisión se actualizan en sitio por `topicId` en `applyPropertyCarbonFootprintMaterialityDecisionsApplied` (:635) para no violar los únicos con el orden insert/borrado de Doctrine.

**Estados derivados** (`MaterialityCategoryState`, nunca persistidos; los calcula `MaterialitySummaryBuilder::stateOf`): `without_score`, `without_source`, `not_applicable_to_subject`, `stage_excluded`, `excluded_recommended`, `active`, `active_overridden`, `excluded_overridden`, `pending_review`, `not_evaluated`. Una categoría con puntuación pero sin veredicto (evaluación anterior al plan) es `not_evaluated`.

**Pendientes de revisión** (`pendingMaterialityReviewSources()`, línea 268): con alcance 3 respondido y no excluido, toda decisión propagable sin anulación cuyo veredicto diverge de la exclusión actual (`recommendsExclusion() !== isSourceExcluded()`).

## Flujo de una operación

**Completar la evaluación.** `CompleteMaterialityAssessmentService` (autoriza `Edit`, `ensureIsEditable`) → `MaterialityDecisionRuleProvider::rule()` → `complete($userId, $rule)` → `MaterialityAssessmentCompleted`. Dos suscriptores síncronos: `AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber` (avanza el estado) y `ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriber` → `MaterialityDecisionInputsMapper::fromCompleted` (`topicNumber` nulo si `scope` no es `scope_3`) → `ApplyMaterialityDecisionsTo{Property,Company}CarbonFootprintService` (autoriza `Edit`, no-op con huella completada, genera comentarios con `MaterialityOmitCommentGenerator` y llama a `applyMaterialityDecisions`). Cada servicio hace su propio `find`; no cachean la huella.

**Guardar el paso Alcance 3.** `Scope3Controller` → `StageExclusionHttpMapper::mapOverrideJustifications` (campo `materialityOverrides[<source>][justification]`) → `Answer*ScopeService`: `StageAnswersProcessor::validate` → `MaterialityOverrideReconciler::validate` (**antes** de mutar: lanza `MaterialityOverrideJustificationRequiredException`, que el controlador captura como `\InvalidArgumentException` y muestra como flash `danger`) → `applyStageExclusion` → `applyExclusions` → `MaterialityOverrideReconciler::reconcile` (anulación ⇔ veredicto y exclusión divergen; si coinciden, `revertMaterialityOverride`; la justificación existente se conserva si no llega otra) → `sync` → `answerScope`.

**Reiniciar la evaluación.** `MaterialityAssessmentRemoved` (con `subjectType` y `carbonFootprintId`; ignora el evento si faltan, p. ej. payloads antiguos) → `ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriber` → `ClearMaterialityDecisionsFrom…Service` → `clearMaterialityDecisions()`: `includeRecommendedExclusions()` (reincluye las `omit` sin anulación, incluso con alcance 3 respondido) y publica `…DecisionsCleared`, que borra el snapshot. Reabrir la evaluación **no** toca la huella.

**Resumen del paso Materialidad.** `GetMaterialityStepService` construye `summaryRows` con `MaterialitySummaryBuilder::build` si hay evaluación completada o decisiones en la huella; añade al final las fuentes del alcance 3 sin categoría evaluada (`not_evaluated`/`stage_excluded`). `_step.html.twig` pinta la tabla ampliada (Relevancia, Umbral, Veredicto, Estado en la huella, Anulación) y los avisos.

**Resultados.** `OrganizationalResultsView::exclusions()` añade `origin` (`Manual`, `Recomendación de materialidad`, `Anulación de la recomendación`) y `overriddenBy/At`; la plantilla muestra el aviso si `hasPendingMaterialityReview()`.

## Autorización y ámbito

| Acción | Authorizer | Quién |
|---|---|---|
| Ver/editar umbral | `MaterialitySettingsAuthorizer` (`:31`) | `Admin` y `SuperAdmin`, sin scoping por cuenta; el Propietario recibe 403 |
| Completar/reabrir/reiniciar evaluación | `MaterialityAssessmentAuthorizer` `Edit` | Propietario de la cuenta actual |
| Aplicar/limpiar decisiones, anular | `PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer` `Edit` | Propietario de la cuenta actual |
| Ver el resumen | `View` sobre la huella | Propietario |

Los suscriptores usan `LoggedUser`. No hay feature flags. El Administrador no accede al resumen (el paso es solo del Propietario).

## Persistencia y migración

Migración aplicada: `migrations/Version20261102180024.php` (cubre `migration.sql` de la carpeta del plan):

- `materiality_settings` (`relevance_threshold_percent`, `high_magnitude_forces_inclusion`, fechas).
- `materiality_assessment_topics`: `threshold_percent`, `forced_by_high_magnitude`, `verdict` (nullables).
- `property_carbon_footprint_materiality_decisions` y `company_carbon_footprint_materiality_decisions` con FK `ON DELETE CASCADE` a la huella y únicos `(huella, topic_id)` y `(huella, source)`.

Sin backfill: las evaluaciones completadas antes del plan quedan sin veredicto hasta reabrirlas y completarlas (el resumen las muestra con relevancia y estado «Sin evaluar»). El cambio de payload de `MaterialityAssessmentRemoved` es retrocompatible (`rebuildFromPayload` tolera ausencias).

## Configuración y operación

- Sin variables de entorno, colas ni comandos nuevos. Todo es síncrono; no hay `DeferredCommand`.
- `services.yaml` no cambia (autowire del BC). `config/packages/doctrine.yaml` registra tres tipos DBAL de id.
- El comentario de exclusión se genera en el locale por defecto (`LocaleGetter::default()`) y se guarda como texto.
- Los eventos aparecen en el Registro (`RecordEventSubscriber`) con plantilla de detalle propia; las justificaciones se escapan con `| e` en las plantillas.
- Traducciones extraídas con `make translation` (ES, EN y también TR, cuyas entradas nuevas quedan con prefijo `NEW_`).

## Cómo extenderlo

- **Nueva regla de veredicto** (p. ej. umbral por sector): modificar `MaterialityDecisionRule::decide()`; el valor se congela en cada categoría al completar, así que no hay que tocar el snapshot.
- **Nuevo valor de veredicto**: añadir caso a `MaterialityVerdict` (`style()`, `recommendsExclusion()`), traducirlo en `materialityVerdictType` y revisar `propagateMaterialityDecisions` y `MaterialitySummaryBuilder::stateOf`, que asumen dos valores.
- **Nuevo estado derivado**: añadir caso a `MaterialityCategoryState`, su traducción en `materialityCategoryStateType+intl-icu.*.xlf` y su rama en `stateOf`.
- **Nueva fuente del alcance 3 con categoría**: basta con que `Source::ghgProtocolCategory()` / `ofGhgProtocolCategory()` la cubran; la propagación y el resumen la recogen solos. Si es exclusiva de empresa, añadirla a `Scope::companyOnlySources()`.
- **Nuevo tipo de huella con materialidad**: implementar los métodos de materialidad de la interfaz `CarbonFootprint` (`materialitySourceIsPropagable`, decisiones…) o dejarlos no-op como `EventCarbonFootprint`.
- Trampas: `StageAnswersProcessor::applyExclusions` incluye toda fuente no enviada, por lo que la preexclusión sobrevive solo porque `SourceSection` renderiza la casilla marcada; la validación de justificaciones debe ir antes de cualquier mutación; `CarbonFootprint` no puede importar clases de `Materiality`.

## Tests

- **Unitarios:** `tests/Unit/Materiality/Domain/Model/MaterialitySettings/`, `…/Application/MaterialitySettings/`, `…/Domain/Event/{Apply,Clear}MaterialityDecisions…SubscriberTest.php`, `MaterialityAssessmentTest` / `MaterialityAssessmentTopicTest`, `tests/Unit/CarbonFootprint/Application/CarbonFootprint/Materiality/` (reconciliador, resumen, generador de comentario), `…/{Property,Company}CarbonFootprint/{Apply,Clear}MaterialityDecisions/`, `MaterialityVerdictTest` y los de dominio de las dos huellas.
- **Aceptación (Behat):** `tests/Acceptance/Materiality/materiality-settings.feature`, `materiality-assessment-complete.feature`, `tests/Acceptance/CarbonFootprint/{property,company}-carbon-footprint-materiality.feature` (propagación, anulación, reinicio, XSS en el Registro, resumen).
- **Ejecución:** `make test-unit options="--filter Materiality"`, `make test-acceptance options="tests/Acceptance/Materiality/materiality-settings.feature"` o `make test-this test=<ruta>`; completo con `make test-parallel`. Validación del plan: `make cs-fix`, `make phpstan`, 7652 unitarios y 1901 escenarios en verde.
- Hueco conocido: en los `Given` de Behat que llaman a servicios antes de cualquier petición, los suscriptores no están instanciados y no se disparan los efectos cruzados (afecta también a `AdvanceCarbonFootprint…`).

## Desviaciones y deuda

Ver `## Implementación` en [`plan.md`](plan.md) y [`review.md`](review.md). Resumen:

- `MaterialityAssessmentRemoved` lleva ahora `subjectType` y `carbonFootprintId` porque el repositorio excluye las evaluaciones eliminadas; `OperatorContribution` también implementa los métodos no-op; `UpdateMaterialitySettingsRequest::$relevanceThresholdPercent` es `?int`.
- La justificación requerida se traduce dentro del reconciliador porque el `catch` de `Scope3Controller` traduce `getMessage()` sin parámetros (deuda: los mensajes con datos dinámicos y los de `StageExclusionHttpMapper` no son traducibles).
- `find()` de los repositorios de evaluación excluye las eliminadas sin variante que las incluya.
- `review.md`: todos los hallazgos (C1 XSS en el Registro, A1 migración, M1 pendientes de revisión, M2 categorías de otro alcance, B1, B2) están corregidos. Queda **abierta la pregunta Q1**: al excluir el alcance 3 completo y volver a incluirlo, las categorías `omit` quedan activas sin exclusión ni anulación y, con el alcance 3 ya respondido, `pendingMaterialityReviewSources()` las da todas por pendientes de revisión aunque no tengan datos; no se reaplican las exclusiones recomendadas al reincluir. No se documenta como comportamiento decidido.
