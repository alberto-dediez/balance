# Cambio de motivo de una exclusión en la huella de evento — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-22 · **Bounded Contexts:** `CarbonFootprint`

## Qué hace y por qué

En los pasos de la huella de un evento (materiales, montaje, celebración, desmontaje) el usuario puede excluir una fuente de emisión con un motivo y un comentario. Si volvía a un paso y cambiaba el motivo o el comentario de una exclusión ya guardada, la petición fallaba con un 500.

Causa: `applyEventCarbonFootprintSourceExcluded()` quitaba la exclusión existente de la colección y añadía otra con id nuevo para la misma `(huella, fase, fuente)`. En un mismo `flush`, Doctrine ejecuta los `INSERT` antes que los `DELETE` por orphan removal, así que el `INSERT` violaba el índice único `UNIQ_ecf_exclusions_phase_source` (`src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/EventCarbonFootprint.EventCarbonFootprintExclusion.orm.xml:9-11`) mientras la fila antigua seguía existiendo.

Solución: actualizar la exclusión en su sitio (mismo id, misma fila → un `UPDATE`), igual que ya hacían `CompanyCarbonFootprintExclusion::update()` y `PropertyCarbonFootprintExclusion::update()`. Se descartó quitar el índice único para que la base de datos siga garantizando una exclusión por `(huella, fase, fuente)`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Domain/Model/EventCarbonFootprint/EventCarbonFootprintExclusion.php` | Nuevo `update(ExclusionReason, ?string, \DateTimeImmutable)` (`:64-69`): reasigna `reason`, `comment` y `excludedAt` | modificado |
| `src/CarbonFootprint/Domain/Model/EventCarbonFootprint/EventCarbonFootprint.php` | `applyEventCarbonFootprintSourceExcluded()` (`:556-581`) actualiza o crea; `use Doctrine\Common\Collections\Collection` (`:23`) en lugar de FQCN inline | modificado |
| `tests/Unit/CarbonFootprint/Domain/Model/EventCarbonFootprint/EventCarbonFootprintTest.php` | `testChangingTheReasonOfAnExclusionKeepsTheSameRow` | modificado |
| `tests/Acceptance/CarbonFootprint/event-carbon-footprint-exclusions.feature` | Escenario «Changing the reason of an excluded source keeps a single exclusion» | modificado |

No cambian: el evento `EventCarbonFootprintSourceExcluded` (nombre y payload), el mapping, `EventCarbonFootprintExclusions`, `CarbonFootprintContext.php`.

## Modelo de dominio

- `EventCarbonFootprintExclusion` es entidad hija (`id`, `eventCarbonFootprint`, `phase`, `source`, `reason`, `comment`, `excludedAt`). Solo `reason`, `comment` y `excludedAt` son mutables, y solo mediante `update()`. `id`, `phase` y `source` no cambian nunca.
- `EventCarbonFootprint::excludeSource()` (`EventCarbonFootprint.php:488-511`): comprueba que la huella sea editable; si ya existe una exclusión con el mismo motivo y comentario sale sin publicar nada (`:495-498`); si no, publica `EventCarbonFootprintSourceExcluded` y retira las actividades de esa fase y fuente (`:510`).
- `applyEventCarbonFootprintSourceExcluded()`: busca `exclusions()->forPhaseAndSource(phase, source)` (`EventCarbonFootprintExclusions.php`); si existe llama a `update()` con los datos del evento; si no, crea `new EventCarbonFootprintExclusion(...)` con `EventCarbonFootprintExclusionId::generate()` y la añade al array o a la `Collection`. En ambos casos fija `updatedAt`. Ya no llama a `removeExclusionChild()`.
- `applyEventCarbonFootprintSourceIncluded()` sigue usando `removeExclusionChild()` (solo borra, no reinserta).

## Flujo de una operación

Paso del asistente (p. ej. `/events/{eventId}/carbon-footprint/step/assembly`) → controlador del paso (`Infrastructure/UI/Web/EventCarbonFootprint/Step/`) → servicio de guardado → `StageAnswersProcessor::applyExclusions()` (`src/CarbonFootprint/Application/CarbonFootprint/StageAnswersProcessor.php:79-93`), que por cada fuente de la fase llama a `excludeSource()` o `includeSource()` (nunca ambas) → evento aplicado en el agregado → `flush`: la exclusión existente se modifica y Doctrine emite `UPDATE`.

## Autorización y ámbito

Sin cambios: la autorización sigue en los servicios de guardado de los pasos; `ensureIsEditable()` impide excluir en una huella completada.

## Persistencia y migración

Sin cambios de esquema ni migración. El índice único `UNIQ_ecf_exclusions_phase_source (event_carbon_footprint_id, phase, source)` se mantiene.

## Cómo extenderlo

- Cualquier nuevo campo de la exclusión debe añadirse a `update()` además de al constructor, o el cambio se perdería al reeditar.
- Trampa: no combinar `includeSource()` y `excludeSource()` de la misma fuente en un mismo flush; reproduciría el patrón «quitar + añadir» y volvería el error del índice único. Hoy ningún flujo lo hace.
- Las huellas de empresa y propiedad usan el mismo patrón (`CompanyCarbonFootprint.php:495-514`, `PropertyCarbonFootprint.php:325-344`).

## Tests

- Unitario: `testChangingTheReasonOfAnExclusionKeepsTheSameRow` comprueba una sola exclusión, la misma instancia (`assertSame`) y los nuevos motivo y comentario. `testExcludeSourceWithDifferentReasonReplacesTheExclusion` sigue comprobando el evento y el resultado.
- Behat: el escenario nuevo excluye `waste_management` en `assembly` dos veces con motivos distintos, comprueba el motivo guardado y que el paso muestre «Poco relevante» y no «Sin facturas».
- Limitación: en test `EventCarbonFootprintRepository` resuelve a `InMemoryEventCarbonFootprintRepository`, por lo que Behat no reproduce el índice único; la garantía frente a Doctrine descansa en el código y en el unitario. No hay tests de integración con Doctrine.
- Ejecución: `make test-this test=tests/Unit/CarbonFootprint/Domain/Model/EventCarbonFootprint/EventCarbonFootprintTest.php`; `make test-this test=tests/Acceptance/CarbonFootprint/event-carbon-footprint-exclusions.feature`; suite completa con `make test-parallel`.

## Desviaciones y deuda

Sin desviaciones ni deuda en `plan.md`. `review.md`: hallazgo B1 (el Behat no comprobaba el comentario) corregido; 0 hallazgos abiertos.
