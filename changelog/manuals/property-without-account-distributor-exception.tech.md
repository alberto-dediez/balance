# Mensaje claro al asignar un distribuidor a una propiedad sin cuenta — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-28 · **Bounded Contexts:** `Property`

## Qué hace y por qué

Adherir a un distribuidor una propiedad sin cuenta fallaba con `\InvalidArgumentException('Property not found')`, aunque la propiedad existe. Ahora ese caso lanza una excepción de dominio propia, `PropertyWithoutAccountException`, cuyo mensaje nombra la propiedad y dice cómo corregirlo.

La regla de negocio no cambia: una propiedad sin cuenta no se puede adherir. Es un invariante de la asignación de distribuidores (plan `2026-11-04-modificaciones-para-greenhost`), porque la visibilidad depende de la cuenta del hotel (`src/Property/Application/DistributorAssignment/DistributedPropertiesPolicy.php`, `distributes()`). El resto de decisiones está en la tabla «Decisiones tomadas» de `plan.md`.

No hay cambios en dominio persistido, rutas, formularios, plantillas, traducciones, configuración ni esquema.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Property/Domain/Model/DistributorAssignment/PropertyWithoutAccountException.php` | Excepción del caso «sin cuenta» | nuevo |
| Aplicación | `src/Property/Application/DistributorAssignment/Register/RegisterDistributorAssignmentService.php` | `assignmentForProperty()` separa «no existe o eliminada» de «sin cuenta» | modificado |
| Test unitario | `tests/Unit/Property/Application/DistributorAssignment/Register/RegisterDistributorAssignmentServiceTest.php` | `testFailsWithPropertyWithoutAccount`, `testFailsWithUnknownProperty` | modificado |
| Test de aceptación | `tests/Acceptance/Property/distributor-assignment-assign-property.feature` | Escenario de la acción masiva con una propiedad sin cuenta | modificado |
| Test (fuera del alcance funcional) | `tests/Unit/Materiality/Domain/Model/MaterialityAssessment/MaterialityAssessmentRepositoryTest.php` | Aísla el `DomainEventPublisher` | modificado |

Piezas que intervienen sin cambios:

| Fichero | Papel en el flujo |
|---|---|
| `src/Property/Infrastructure/Symfony/Form/AdminBulkPropertyType.php` | Declara el selector `distributorAccountId` y las acciones masivas del listado de administración |
| `src/Property/Infrastructure/UI/Web/Admin/Property/Bulk/AssignDistributor.php` | Genera un comando por propiedad y los mensajes de éxito y fallo |
| `src/Property/Infrastructure/UI/Web/Admin/Property/ListController.php` | Procesa la acción masiva |
| `src/Shared/Infrastructure/UI/Web/CRUD/BulkTrait.php` | Convierte la excepción en avisos |
| `templates/components/Messages.html.twig` | Pinta los avisos |
| `src/Property/Infrastructure/UI/Console/DistributorAssignment/AssignDistributorFromFileCommand.php` | Alta por fichero; filtra las propiedades sin cuenta antes de llamar al servicio |

## Modelo de dominio

`PropertyWithoutAccountException` (`PropertyWithoutAccountException.php:9-22`):

- Extiende `\RuntimeException`, código `409`.
- Constructor `__construct(Property $property)`.
- Mensaje: `La propiedad %s (%s) no tiene cuenta asignada: asígnale una cuenta antes de adherirla a un distribuidor.`, con `name()` e `id()` de la propiedad.
- Sin traducción, como las otras excepciones del agregado (`AccountIsNotDistributorException`, `DistributorCannotDistributeItselfException`).

No hay eventos, campos ni estados nuevos.

Comprobaciones de `assignmentForProperty()` (`RegisterDistributorAssignmentService.php:85-113`), en orden:

| Condición | Resultado | Línea |
|---|---|---|
| Propiedad inexistente o eliminada | `\InvalidArgumentException('Property not found')` | `:89-91` |
| Propiedad sin cuenta | `PropertyWithoutAccountException` | `:93-97` |
| La cuenta de la propiedad es la del distribuidor | `DistributorCannotDistributeItselfException` | `:99-101` |
| Ya existe la asignación por propiedad | Devuelve la existente, sin evento | `:103-107` |
| Resto | `registerForProperty()` | `:109-112` |

La comprobación de cuenta es anterior a la idempotencia: si una propiedad con asignación se queda sin cuenta, volver a asignarla lanza la excepción en lugar de devolver la asignación existente.

El tipo de excepción del caso «sin cuenta» pasa de la rama `\LogicException` (`\InvalidArgumentException`) a `\RuntimeException`. Un `catch` que solo capture `\InvalidArgumentException` ya no la recoge.

## Flujo de una operación

Acción masiva «Asignar distribuidor» sobre una propiedad sin cuenta:

1. `GET`/`POST /{_locale}/admin/properties` (`admin_properties`). `ListController.php:32-33` exige `Action::List` y `Action::Admin`.
2. `AdminBulkPropertyType` (`:37-51`, `:59-63`) construye el formulario; `BulkTrait::bulkActions()` lo procesa.
3. `AssignDistributor::commands()` (`:26-54`) crea un `RegisterDistributorAssignmentRequest(distributorAccountId, propertyId)` por propiedad. Sin distribuidor elegido devuelve `[]` (`:36-38`) y `BulkTrait.php:27-31` muestra «Debes seleccionar algún elemento sobre el que poder realizar la acción».
4. `CommandBusDispatcher` ejecuta cada comando. El servicio autoriza (`RegisterDistributorAssignmentService.php:41`), valida el objetivo (`:43-45`) y la distribuidora (`:47-51`) y entra en `assignmentForProperty()`.
5. Se lanza `PropertyWithoutAccountException`. `BulkTrait.php:40-42` la captura (`\Exception`) y añade dos avisos:
   - `warning`: `$e->getMessage() . $e->getTraceAsString()`.
   - `danger`: `AssignDistributor::failure()` (`:63-68`), «No se ha podido asignar el distribuidor seleccionado a la propiedad {id}».
6. El bucle sigue con las demás propiedades: un fallo no revierte las que ya se asignaron.
7. `ListController.php:47-51` redirige a la misma URL y `Messages.html.twig` pinta los avisos.

Presentación de los avisos, a tener en cuenta al depurar o al tocar la interfaz:

- `Messages.html.twig:2-8` abre un `.toast-container` por etiqueta y todos comparten la posición fija de `assets/styles/layouts/_layouts.scss:32-41`, así que los avisos de distinto tipo se superponen.
- `assets/controllers/toast_controller.js` usa las opciones por defecto de Bootstrap (se ocultan solos a los 5 s).
- El aviso `warning` lleva la traza pegada al mensaje.

La acción masiva «Asignar distribuidor» del listado de cuentas (`Admin/DistributorAssignment/Bulk/AssignDistributorToAccount.php`) usa la rama `accountId` del servicio y no pasa por esta comprobación.

## Autorización y ámbito

Sin cambios. `DistributorAssignmentAuthorizer::canRegister()` delega en `canAdmin` (`DistributorAssignmentAuthorizer.php:39-42`): solo un Administrador da de alta asignaciones. La autorización sigue siendo lo primero del servicio, antes de cualquier comprobación sobre la propiedad.

## Persistencia y migración

Sin migración ni cambios de mapping.

Una propiedad puede no tener cuenta porque se registró sin ella (`RegisterPropertyType.php:63-67`, campo opcional «Sin cuenta»), porque se le quitó desde la edición o la acción masiva «Asignar cuenta», o porque su cuenta se dio de baja (`src/Property/Domain/Event/Property/ReleasePropertiesOnAccountRemovedSubscriber.php`).

## Configuración y operación

Sin variables de entorno, servicios, colas ni tareas programadas nuevas.

El comando `property:distributor:assign` no cambia. `status()` (`AssignDistributorFromFileCommand.php:344-361`) marca las filas como `sin cuenta` y solo las filas `asignar` llegan al servicio, así que en el uso normal no recibe la excepción; su `catch (\RuntimeException|\InvalidArgumentException)` (`:169`) la cubriría. `status()` comprueba antes «ya asignada», de modo que una propiedad sin cuenta que conserve una asignación por propiedad aparece con ese estado.

```bash
make sf c="property:distributor:assign <fichero.csv> --distributor=<AccountId> --dry-run"
```

## Cómo extenderlo

Para añadir otro motivo de rechazo con mensaje propio:

1. Crea la excepción en `src/Property/Domain/Model/DistributorAssignment/`, extendiendo `\RuntimeException` con código `409` y un mensaje que identifique el objeto y diga qué hacer.
2. Lánzala desde `assignmentForProperty()` o `assignmentForAccount()`, después de la autorización y antes de la búsqueda de la asignación existente.
3. Si el motivo se puede detectar sin escribir, añade el estado correspondiente en `AssignDistributorFromFileCommand::status()` para que `--dry-run` lo muestre.
4. Cubre el tipo y el mensaje en `RegisterDistributorAssignmentServiceTest` y el aviso en `distributor-assignment-assign-property.feature`.

Trampas:

- Permitir adherir propiedades sin cuenta no es quitar el `if`: la visibilidad para el distribuidor, la retirada por el Propietario del hotel y el crédito compartido dependen de la cuenta (ver «Fuera de alcance» en `plan.md`).
- Los mensajes de estas excepciones llegan literalmente a la pantalla del Administrador a través de `BulkTrait`: redáctalos para ese lector.

## Tests

- Unitarios (`RegisterDistributorAssignmentServiceTest`): `testFailsWithPropertyWithoutAccount` (`:150`, tipo y mensaje con nombre e id), `testFailsWithUnknownProperty` (`:161`) y `testFailsWithRemovedProperty` (`:141`).
- Behat (`distributor-assignment-assign-property.feature`, escenario de la línea 34): comprueba los dos avisos y que no se crea ninguna asignación. Verifica la presencia del texto en la página, no su visibilidad.

```bash
make test-unit options=tests/Unit/Property/Application/DistributorAssignment/Register/RegisterDistributorAssignmentServiceTest.php
make test-acceptance options=tests/Acceptance/Property/distributor-assignment-assign-property.feature
make test-parallel
```

Última verificación registrada en `review.md`: `make phpstan`, `make cs` y `make test-parallel` correctos (10003 tests unitarios, 2444 escenarios).

## Desviaciones y deuda

Declarado en `## Implementación` de [`plan.md`](plan.md):

- El `Makefile` no define `make test-this`; se usan `make test-unit options=…` y `make test-acceptance options=…`.
- Fuera del inventario del plan: `MaterialityAssessmentRepositoryTest` reinicia el `DomainEventPublisher` en `setUp()`/`tearDown()`. El nuevo reparto de `bin/phpunit-parallel` dejaba un suscriptor vivo de otro test en el mismo proceso y rompía `testFindByFilter` y `testChildrenAreRecoveredWithTheirValues`.
- Deuda: `BulkTrait.php:41` concatena la traza al mensaje del aviso (afecta a todas las acciones masivas; previsto un plan aparte en `../hydra`).
- Deuda: `LedgerEntryRepositoryTest` y otros tests de repositorio sobre `KernelTestCase` sobrescriben `tearDown()` sin llamar a `parent::tearDown()`.

[`review.md`](review.md) no tiene hallazgos abiertos.
