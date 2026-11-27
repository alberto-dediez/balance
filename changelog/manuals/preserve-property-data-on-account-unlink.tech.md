# Desvincular la cuenta de una propiedad sin perder sus datos — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-24 · **Bounded Contexts:** `Property`

## Qué hace y por qué

`DELETE /api/{_locale}/properties/{propertyId}/account` reutilizaba `UpdatePropertyService` con un `UpdatePropertyRequest` incompleto. Los campos no pasados valen `null` por defecto y `Property::applyPropertyUpdated` los asigna tal cual, de modo que al desvincular se perdían `numberOfEmployees` y `dailyClients` (datos introducidos a mano y no recuperables). El mismo patrón se repetía en otros tres puntos del contexto.

La solución es un caso de uso dedicado, `ReleasePropertyAccountService`, que delega en la operación que el agregado ya tenía, `Property::releaseAccount()` (evento `PropertyAccountReleased`). Esa operación pasa a imponer el invariante «sin cuenta no hay empresa». Además se completan los tres puntos que perdían datos: bulk «Asignar cuenta», `PropertyMatchController` y `UnlinkCompanyOnCompanyRemovedSubscriber`, más `Property::applyPropertyMatched`. Las decisiones (caso de uso dedicado, empresa siempre suelta, sin migración de datos, sin acción nueva en el authorizer) están en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Property/Domain/Model/Property/Property.php` | `releaseAccount()` (l. 143), `applyPropertyMatched` (l. 180), `applyPropertyAccountReleased` (l. 214) | modificado |
| Dominio | `src/Property/Domain/Event/Company/UnlinkCompanyOnCompanyRemovedSubscriber.php` | Conserva empleados y clientes diarios al borrar una empresa | modificado |
| Aplicación | `src/Property/Application/Property/ReleaseAccount/ReleasePropertyAccountRequest.php` | Comando (`propertyId`) | nuevo |
| Aplicación | `src/Property/Application/Property/ReleaseAccount/ReleasePropertyAccountResponse.php` | Respuesta (`propertyId`) | nuevo |
| Aplicación | `src/Property/Application/Property/ReleaseAccount/ReleasePropertyAccountService.php` | `CommandHandler` del caso de uso | nuevo |
| Infraestructura | `src/Property/Infrastructure/UI/Api/PropertyUnlinkAccount/PropertyUnlinkAccountController.php` | Usa el servicio nuevo en lugar de `UpdatePropertyService` | modificado |
| Infraestructura | `src/Property/Infrastructure/UI/Web/Admin/Property/Bulk/AssignAccount.php` | Request de actualización completo (l. 45-54) | modificado |
| Infraestructura | `src/Property/Infrastructure/UI/Api/PropertyMatch/PropertyMatchController.php` | Request completo al asignar `account_id` (l. 89-103) | modificado |
| Tests | `tests/Unit/Property/Domain/Model/Property/PropertyTest.php`, `.../Application/Property/ReleaseAccount/ReleasePropertyAccountServiceTest.php`, `.../Domain/Event/Company/UnlinkCompanyOnCompanyRemovedSubscriberTest.php`, `.../Infrastructure/UI/Web/Admin/Property/Bulk/AssignAccountTest.php` | Unitarios nuevos | nuevos |
| Tests | `PropertyMother.php`, `ReleasePropertiesOnAccountRemovedSubscriberTest.php`, `UpdatePropertyServiceTest.php`, `tests/Acceptance/Property/PropertyContext.php`, `property-unlink-account.feature`, `property-match.feature` | Ampliados | modificados |

No hay cambios en `services.yaml` (el contexto se carga como recurso, `src/Property/Infrastructure/Symfony/DependencyInjection/services.yaml`), plantillas ni traducciones.

## Modelo de dominio

`Property` conserva `accountId`, `companyId`, `numberOfEmployees` y `dailyClients` como estado reconstruido por eventos.

- `releaseAccount()` (`Property.php:143`): no hace nada (ni publica) si no hay cuenta **ni** empresa; si queda alguna de las dos, publica `PropertyAccountReleased(propertyId)`. El payload no cambia.
- `applyPropertyAccountReleased` (`Property.php:214`): pone `accountId` y `companyId` a `null`. Es el invariante «sin cuenta no hay empresa»; el resto de campos no se toca.
- `applyPropertyMatched` (`Property.php:180`): el `PropertyUpdated` anidado ahora incluye `companyId`, `numberOfEmployees` y `dailyClients` actuales. Antes un match que cambiaba info o códigos los borraba. Sigue publicando el evento desde dentro de un `apply*` (deuda ya señalada en `plan.md`).
- Dos usos de `releaseAccount()`: el nuevo caso de uso y `ReleasePropertiesOnAccountRemovedSubscriber.php:41`, que al borrar una cuenta libera sus propiedades. Efecto colateral aceptado: ahora también sueltan la empresa.

## Flujo de una operación

Desvincular por API:

1. `DELETE /api/{_locale}/properties/{propertyId}/account` (`api_properties_unlink_account`, `#[Areas(['admin'])]`) llega a `PropertyUnlinkAccountController`.
2. El controlador busca la propiedad en `PropertyRepository`; si no existe, `createNotFoundException()` (404).
3. Invoca `ReleasePropertyAccountService` con `ReleasePropertyAccountRequest(propertyId)`.
4. El servicio: `find` (`InvalidArgumentException('Property not found')` si no existe) → `PropertyAuthorizer::denyUnlessCanDoThat($property, Action::Edit, $user)` → `$property->releaseAccount()` → `repository->add()` → `ReleasePropertyAccountResponse`.
5. El controlador relee la propiedad y responde 200 con `PropertyResponseGenerator`. La respuesta no expone `company_id`, `number_of_employees` ni `daily_clients`.

Idempotencia: sobre una propiedad ya sin cuenta ni empresa, un admin recibe 200 sin evento nuevo; un owner recibe 403 (el authorizer no le reconoce la propiedad).

Bulk «Asignar cuenta» (`AssignAccount::commands()`): sigue generando `UpdatePropertyRequest` (lo atiende `UpdatePropertyService`, no el caso de uso nuevo), ahora con `name`, `info`, `externalId`, `numberOfEmployees` y `dailyClients` actuales. `companyId` es `$property->companyId()` si hay cuenta destino y `null` si la cuenta destino es vacía. `UpdatePropertyService` anula la empresa si la cuenta cambia (`UpdatePropertyService.php:36-43`). Consecuencia: la vía bulk deja `propertyUpdated` en el historial y la vía API deja `propertyAccountReleased`; el resultado sobre la propiedad es el mismo.

Match con `account_id` (`PropertyMatchController`): si la propiedad encontrada no tiene cuenta, el request de actualización lleva ahora `companyId`, `numberOfEmployees` y `dailyClients` actuales.

Borrado de empresa: `UnlinkCompanyOnCompanyRemovedSubscriber::handle` llama a `Property::update` con `companyId: null` y pasa empleados y clientes diarios actuales.

## Autorización y ámbito

`PropertyAuthorizer` con `Action::Edit` (la misma que aplicaba el endpoint vía `UpdatePropertyService`), sin acción nueva: `Admin`/`SuperAdmin` sobre cualquier propiedad; `Owner` solo si `currentAccountId($user)` coincide con la cuenta de la propiedad (respeta impersonación); `User` no puede. Contexto activo solo con `PROPERTIES_ENABLED`. El bulk del admin requiere el rol de administrador de la web de administración.

## Persistencia y migración

Sin cambio de esquema ni migración (columnas `account_id`, `company_id`, `number_of_employees`, `daily_clients` de `properties`). No se limpian datos heredados: las filas con `account_id IS NULL AND company_id IS NOT NULL` anteriores se corrigen al volver a desvincularse (`releaseAccount()` ya no es no-op con empresa huérfana) o al pasar por el bulk con cuenta vacía. Los empleados y clientes diarios ya perdidos no se recuperan.

## Configuración y operación

Nada nuevo: sin variables, colas, comandos ni tareas programadas. El `CommandHandler` se autoconfigura por `Kernel` y los traits `RegisterHandlerTrait` y `CommandInvokeHandlerTrait`.

## Cómo extenderlo

- Para que un flujo nuevo suelte la cuenta de una propiedad, despachar `ReleasePropertyAccountRequest` (o llamar a `releaseAccount()` desde un suscriptor), nunca reconstruir un `UpdatePropertyRequest` parcial.
- Trampa: `UpdatePropertyRequest` y `Property::applyPropertyUpdated` asignan `companyId`, `numberOfEmployees` y `dailyClients` sin caer al valor actual; todo llamador de `update()` debe pasar los valores actuales de los campos que no cambia. Si `Property` gana un campo, revisar `AssignAccount`, `PropertyMatchController`, `UnlinkCompanyOnCompanyRemovedSubscriber` y `applyPropertyMatched`.
- Plataformas derivadas: si ya cambiaron `PropertyUnlinkAccountController` (redirección de ids fusionados), conservar la redirección y sustituir solo la llamada a `UpdatePropertyService` por `ReleasePropertyAccountService`.

## Tests

- Unitarios: `PropertyTest` (liberar cuenta y empresa, empresa huérfana, sin evento sin cuenta ni empresa, match conserva datos), `ReleasePropertyAccountServiceTest` (admin, owner propio y ajeno, user, inexistente, idempotencia, `isHandled`), `UnlinkCompanyOnCompanyRemovedSubscriberTest`, `AssignAccountTest` (cuenta destino y cuenta vacía), ampliaciones de `ReleasePropertiesOnAccountRemovedSubscriberTest` y `UpdatePropertyServiceTest` (misma cuenta conserva empresa; propiedad sin cuenta y con empresa).
- Aceptación: `property-unlink-account.feature` (3 escenarios nuevos sobre los 6 existentes) y `property-match.feature` (escenario de match con `account_id`); pasos nuevos en `PropertyContext.php` (l. 158-209).
- Ejecución: `make test-unit options="--filter Property"`, `make test-acceptance options="tests/Acceptance/Property/property-unlink-account.feature"` y `make test-acceptance options="tests/Acceptance/Property/property-match.feature"`.
- La respuesta API no expone empresa, empleados ni clientes diarios, por lo que Behat los comprueba en el repositorio.

## Desviaciones y deuda

Ver `## Implementación` en `plan.md` y `review.md` (único hallazgo, B1, ya corregido; sin abiertos).

- `AssignAccount.php:40-46`: elementos del bulk `mixed` usados como `Property` sin `instanceof`.
- `Property.php:180-199`: evento publicado desde un `apply*`.
- `RemovePropertyService`, `UpdatePropertyService` y `ReleasePropertyAccountService` comparan con `null == $property` (laxo).
- Dos formas de desvincular dejan historiales distintos (`propertyAccountReleased` frente a `propertyUpdated`); no se unifican.
