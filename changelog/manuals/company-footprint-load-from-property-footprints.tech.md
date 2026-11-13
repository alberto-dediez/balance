# Cargar en la huella de empresa los alcances 1 y 2 desde las huellas de propiedad — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-13 (revisado y corregido hasta 2026-11-14) · **Bounded Contexts:** `CarbonFootprint` (más una pieza nueva en `Shared`)

## Qué hace y por qué

El Propietario podía rellenar los alcances 1 y 2 de la huella de empresa solo a mano o con «Cargar consumos de Bioscore». Se añade la acción de cabecera **«Cargar desde huellas de propiedad»** en los pasos Alcance 1 y Alcance 2: por cada propiedad consolidada de la empresa con huella de propiedad **completada, del mismo año y de la misma cuenta, calculada por actividades** y con actividades en el alcance, **copia** esas actividades al bloque de esa propiedad en la huella de empresa.

La copia no reutiliza `ActivityId`: convierte cada actividad de origen en un `ActivityAnswer` sin `existingActivityId` y se lo pasa a `ReplaceCompanyCarbonFootprintScopeAnswersService`, la misma pieza que usa la carga de Bioscore. Esta crea actividades nuevas mediante los manejadores de alta (con el factor de emisión vigente del año de la huella) y retira las anteriores del bloque. Solo se sustituyen los bloques de las propiedades copiadas. Las decisiones (copiar y no enlazar, una acción por paso, aviso solo si las emisiones difieren, no copiar exclusiones) están en la tabla «Decisiones tomadas» de `plan.md`.

Tras las revisiones (`review.md`, 0 hallazgos abiertos) el código incorpora tres cambios respecto al plan original: la regla de «huella de propiedad válida» se extrajo a `CompletedPropertyFootprintFinder`, la sustitución de bloques se ejecuta dentro de una transacción (`TransactionalSession`) y `ReplaceCompanyCarbonFootprintScopeAnswersService` rechaza huellas completadas.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/CarbonFootprint/Application/CompanyCarbonFootprint/LoadPropertyFootprints/LoadPropertyFootprintsIntoCompanyCarbonFootprintService.php` | Caso de uso (`CommandHandler`): valida, selecciona propiedades, delega la sustitución y calcula diferencias | Nuevo |
| Aplicación | `.../LoadPropertyFootprints/LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest.php` / `...Response.php` | DTO de entrada (`companyCarbonFootprintId`, `scope`) y salida (`totalProperties`, `copiedProperties`, `activities`, `skipped`, `differences`, `includedSources`; `isResolved()` = `copiedProperties > 0`) | Nuevo |
| Aplicación | `.../LoadPropertyFootprints/PropertyActivityAnswerBuilder.php` | Convierte las actividades de una huella de propiedad en `SectionAnswer` sin `existingActivityId` y suma sus emisiones | Nuevo |
| Aplicación | `.../LoadPropertyFootprints/PropertyActivityAnswers.php` | DTO `(sectionAnswers, emissions, count)` | Nuevo |
| Aplicación | `.../LoadPropertyFootprints/PropertyFootprintSkipReason.php` | Enum de motivo de omisión: `no_completed_footprint`, `precalculated`, `no_activities` | Nuevo |
| Aplicación | `.../LoadPropertyFootprints/SkippedPropertyFootprint.php`, `PropertyEmissionsDifference.php` | Elementos de los avisos de omitidas y de diferencias | Nuevo |
| Aplicación | `src/CarbonFootprint/Application/CompanyCarbonFootprint/CompletedPropertyFootprintFinder.php` | Regla única de «huella de propiedad válida como origen» | Nuevo |
| Aplicación | `.../NonConsolidatedProperty/NonConsolidatedPropertyEmissionsResolver.php` | Pasa a usar el finder (antes duplicaba la regla) | Modificado |
| Aplicación | `.../ReplaceScopeAnswers/ReplaceCompanyCarbonFootprintScopeAnswersService.php` | Rechaza huellas completadas y ejecuta el reemplazo dentro de `TransactionalSession::run()` | Modificado |
| Shared | `src/Shared/Application/Transaction/TransactionalSession.php` | Puerto `run(callable): mixed` | Nuevo |
| Shared | `src/Shared/Infrastructure/Persistence/Doctrine/Transaction/DoctrineTransactionalSession.php` | Adaptador dev/prod (`#[When]`): `beginTransaction`/`commit`, `rollBack` + `EntityManager::clear()` en error | Nuevo |
| Shared | `src/Shared/Infrastructure/Persistence/InMemory/Transaction/InMemoryTransactionalSession.php` | Adaptador de test (`#[When(env: 'test')]`): ejecuta la operación sin transacción | Nuevo |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/CompanyCarbonFootprint/LoadPropertyFootprintsController.php` | Ruta GET, CSRF, redirección al paso y avisos flash | Nuevo |
| Infraestructura | `templates/components/TopAction/PropertyFootprints.html.twig` | Ítem del desplegable «Acciones», con confirmación | Nuevo |
| Infraestructura | `templates/carbon_footprint/company_carbon_footprints/step/scope-1.html.twig`, `scope-2.html.twig` | Muestran el ítem si `not readOnly and not precalculated` | Modificado |
| Traducciones | `templates/carbon_footprint/translations/propertyFootprintSkipReason.html.twig`, `propertyFootprintLoadMessages.html.twig` | Plantillas de extracción de los motivos de omisión y de los mensajes de excepción | Nuevo |
| Traducciones | `translations/propertyFootprintSkipReason+intl-icu.{es,en,tr}.xlf`; `translations/messages+intl-icu.{es,en,tr}.xlf` | Dominio de motivos y claves nuevas (en TR, prefijo `NEW_` en `messages`) | Nuevo / modificado |
| Tests | `tests/Unit/.../LoadPropertyFootprints/*Test.php`, `.../CompletedPropertyFootprintFinderTest.php`, `tests/Unit/Shared/Application/Transaction/SpyTransactionalSession.php`, `tests/Acceptance/CarbonFootprint/company-carbon-footprint-load-property-footprints.feature` | Ver «Tests» | Nuevo |

No hay cambios de esquema ni de mappings. Tampoco hay entrada en `services.yaml`: servicios y `CommandHandler` se autoconfiguran.

## Modelo de dominio

No se añaden agregados, eventos ni excepciones de dominio. Se reutiliza lo existente:

- `CompanyCarbonFootprint::addActivity()` y `retainActivities()` los invoca `StageAnswersSynchronizer::sync()` (`src/CarbonFootprint/Application/CarbonFootprint/StageAnswersSynchronizer.php`); `addActivity()` empieza por `ensureIsEditable()`, de modo que una huella completada no admite actividades.
- Los bloques por propiedad son `PropertySectionAnswers(?PropertyId, list<SectionAnswer>)` (`.../AnswerScope/PropertySectionAnswers.php`).
- `ActivityPlacement(scope, source, propertyId: ...)` identifica el bloque; en la huella de propiedad las actividades de alcance 1 y 2 se colocan sin `propertyId`.

Invariantes que cumple la copia (probadas en `LoadPropertyFootprintsIntoCompanyCarbonFootprintServiceTest`):

- Por cada propiedad copiada P y fuente F, el número de actividades en la empresa iguala al de la huella de propiedad.
- Ningún `ActivityId` de la empresa coincide con uno de la huella de propiedad, y la huella de propiedad no cambia.
- Hay aviso de diferencia para P si y solo si `round(Σ co2eq origen, 2) !== round(Σ co2eq copia, 2)`.

Conversión por tipo (`PropertyActivityAnswerBuilder::answer()`, `PropertyActivityAnswerBuilder.php:56-81`): `CombustibleActivity` → `CombustibleAnswer`; `TransportActivity` → `TransportAnswer::fromWeightAndDistance` si hay peso y distancia, o `TransportAnswer` con la cantidad; `RefrigerantGasActivity` → `RefrigerantGasAnswer`; `ElectricityActivity` → `ElectricityAnswer` (comercializadora y/o factor GdO). Se conserva `source()` (columna «Fuente»). Cualquier otra clase (p. ej. `ReportedEmissionsActivity`, que solo existe en huellas precalculadas, ya filtradas) lanza `\LogicException`.

## Flujo de una operación

1. `GET /company-carbon-footprints/{companyCarbonFootprintId}/step/{scope}/property-footprints` (ruta `company_carbon_footprints_property_footprints_load`, `scope` restringido a `scope_1|scope_2`; `LoadPropertyFootprintsController.php:26-31`). Las rutas se cargan por atributos desde `config/routes.yaml`.
2. Controlador: `find()` (404 si no existe), `denyAccessUnlessGranted(Edit)`, CSRF `property_footprints_load_<id>` leído de `_token` en la query (`:51`), y resolución de la ruta de retorno `company_carbon_footprints_scope_1|2`.
3. Servicio (`LoadPropertyFootprintsIntoCompanyCarbonFootprintService::__invoke`):
   1. Huella inexistente → `\InvalidArgumentException`; `authorizer->denyUnlessCanDoThat(..., Edit, $user)` (`:49`).
   2. `Scope3` → `\InvalidArgumentException('Solo se pueden cargar los alcances 1 y 2')` (`:51`); huella de empresa precalculada → `\InvalidArgumentException('No se pueden cargar huellas de propiedad en una huella ya calculada')` (`:55`).
   3. `CompanyProperties::consolidated($companyId)` (`:66`): solo propiedades consolidadas; las no consolidadas ni se copian ni se listan como omitidas. Sin propiedades → respuesta con `totalProperties: 0`.
   4. Por propiedad: `CompletedPropertyFootprintFinder::find()` (`:81`); `null` → `NoCompletedFootprint`. Si la huella es precalculada → `Precalculated` (`:89`). `PropertyActivityAnswerBuilder::build()`; `count === 0` → `NoActivities`. Si no, se guarda el bloque y la suma de emisiones de origen.
   5. Sin bloques → respuesta sin cambios (`isResolved()` false), con las omitidas.
   6. Con bloques → `ReplaceCompanyCarbonFootprintScopeAnswersService` (`:113`), que solo recibe los bloques copiados (nunca bloques vacíos, que borrarían filas). Valida cada bloque con `StageAnswersProcessor`, reincluye las fuentes excluidas que reciben filas (`includeSource`), sincroniza con `StageAnswersSynchronizer::sync(..., propertyId: $block->propertyId)` y guarda la huella.
   7. Diferencias (`:120-127`): recarga la huella, suma `emissions()` de las actividades creadas por propiedad y compara redondeando a 2 decimales (`Emissions::add` suma en `float`, y `Emissions::equals` compara cadenas, por eso no se usa).
4. Controlador `flashResult()` (`:81-134`): `totalProperties = 0` → aviso «La empresa no tiene propiedades»; sin resolver → aviso de ninguna propiedad; resuelto → éxito con recuento; además avisos de fuentes reincluidas, propiedades omitidas (motivo traducido con el dominio `propertyFootprintSkipReason`) y diferencias (`nombre: origen → recalculado kg CO₂eq`, formato `number_format(..., 2, ',', '.')`). Excepciones `\InvalidArgumentException|\DomainException` → flash `danger`; `UnauthorizedActionException` → 403.
5. La carga no cambia el estado de la huella; el usuario revisa y guarda el paso con «Siguiente».

### Atomicidad

`StageAnswersSynchronizer` registra cada actividad con `ActivityRepository::save()`, que en Doctrine hace `flush()` inmediato. Para que un fallo a mitad (p. ej. maestro sin factor para el año) no deje bloques a medias, `ReplaceCompanyCarbonFootprintScopeAnswersService` envuelve el bucle de bloques y el `add()` de la huella en `TransactionalSession::run()` (`ReplaceCompanyCarbonFootprintScopeAnswersService.php:75`). `DoctrineTransactionalSession` hace `rollBack()` y `clear()` del `EntityManager` y relanza. Beneficia también a la carga de Bioscore, que usa el mismo servicio. En test se usa el adaptador en memoria (sin transacción real) y `SpyTransactionalSession` en los unitarios.

## Autorización y ámbito

- `CompanyCarbonFootprintAuthorizer::canEdit` (`src/CarbonFootprint/Application/CompanyCarbonFootprint/CompanyCarbonFootprintAuthorizer.php`): rol `Owner` de la cuenta actual (impersonación incluida, vía `userHasRole` y `currentAccountId`), con la funcionalidad de cuenta `OrganizationalCarbonFootprint` y cuenta igual a la de la huella. No usa `$user->accountId()`.
- Se autoriza dos veces: en el controlador (voter) y al inicio del servicio. El servicio de reemplazo vuelve a autorizar.
- La cuenta de las huellas de propiedad se compara con la de la huella de empresa (no con la del usuario) en `CompletedPropertyFootprintFinder.php`.
- Usuarios de otra cuenta o sin rol Owner: 403. Token CSRF inválido: 403. Huella inexistente: 404.

## Persistencia y migración

Sin cambios de esquema ni migración. Solo se crean filas de actividades nuevas con los repositorios existentes.

## Configuración y operación

- Sin variables de entorno, colas ni comandos de consola; la operación es síncrona.
- `TransactionalSession` se resuelve por entorno con `#[When]` (`dev`/`prod` → Doctrine, `test` → en memoria); no hay entrada manual en `services.yaml`.
- Traducciones: las claves de `messages` salen de `make translation`; los mensajes de excepción no los detecta el extractor, por eso existe `propertyFootprintLoadMessages.html.twig`. En TR las claves nuevas de `messages` quedan con prefijo `NEW_`.

## Cómo extenderlo

- **Nuevo tipo de actividad en alcance 1 o 2:** añadir su rama en `PropertyActivityAnswerBuilder::answer()` con el `ActivityAnswer` correspondiente, y un caso en `PropertyActivityAnswerBuilderTest`. Sin ella, la carga lanza `\LogicException`.
- **Nuevo motivo de omisión:** añadir un `case` a `PropertyFootprintSkipReason` y su traducción en los tres `propertyFootprintSkipReason+intl-icu.*.xlf` y en `propertyFootprintSkipReason.html.twig`.
- **Cambiar qué huella de propiedad es válida como origen:** hacerlo solo en `CompletedPropertyFootprintFinder`; lo comparten esta carga y el alcance 3 de no consolidadas.
- **Usar transacciones en otro servicio:** inyectar `TransactionalSession` y envolver la operación en `run()`; el adaptador en memoria no revierte nada, así que los tests de rollback deben apoyarse en `SpyTransactionalSession`.
- Trampas: nunca pasar bloques vacíos para propiedades omitidas (`StageAnswersSynchronizer` borraría sus filas); no usar `Emissions::equals()` para comparar sumas.

## Tests

- **Unitarios:**
  - `tests/Unit/CarbonFootprint/Application/CompanyCarbonFootprint/LoadPropertyFootprints/LoadPropertyFootprintsIntoCompanyCarbonFootprintServiceTest.php`: copia y cambio de ids, transporte con peso y distancia, electricidad, solo bloques copiados sustituidos, omisiones (sin huella, no completada, otra cuenta, precalculada, sin actividades), no consolidada ni copiada ni listada, diferencias de factor con y sin cambio, factor ausente sin modificar la huella, fuente excluida reincluida, huella precalculada o completada rechazada, alcance 3 rechazado, otro Owner denegado, `isHandled`.
  - `.../LoadPropertyFootprints/PropertyActivityAnswerBuilderTest.php` y `.../CompletedPropertyFootprintFinderTest.php`.
  - `.../ReplaceScopeAnswers/ReplaceCompanyCarbonFootprintScopeAnswersServiceTest.php` (usa `SpyTransactionalSession`).
- **Aceptación:** `tests/Acceptance/CarbonFootprint/company-carbon-footprint-load-property-footprints.feature`: copia y respeto de las demás propiedades, no completada, precalculada, aviso de diferencia, acción oculta en huella de empresa precalculada, franquiciada no recibe actividades, alcance 2, otra cuenta (403) y CSRF inválido (403).
- **Ejecución** (el `Makefile` no define `test-this`; `CLAUDE.md` sí lo cita): `make test-unit options=tests/Unit/CarbonFootprint/Application/CompanyCarbonFootprint/LoadPropertyFootprints` y `make test-acceptance options=tests/Acceptance/CarbonFootprint/company-carbon-footprint-load-property-footprints.feature`; `make test-parallel` para todo.

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: plantilla extra `propertyFootprintLoadMessages.html.twig`, prefijo `NEW_` en TR, aviso de omitidas también cuando no se copia nada, test añadido del factor ausente y paso de fondo `combustible ... has an emission factor of "1.5" per "l"` en el Behat.
- Cambios posteriores por review (`review.md`, todos corregidos): `CompletedPropertyFootprintFinder` (M1), rechazo de huella completada en el servicio de reemplazo (B1), escenarios de acceso denegado y alcance 2 (B2, B3) y transacción (A1).
- Deuda conocida: el paso Behat `... has a combustible :combustibleId activity of :co2eq kg` (`tests/Acceptance/CarbonFootprint/CarbonFootprintContext.php`) fija 100 L y no cuadra con un combustible en kg; `CLAUDE.md` cita `make test-this`, inexistente en el `Makefile`.
- Sin hallazgos abiertos en `review.md`.
