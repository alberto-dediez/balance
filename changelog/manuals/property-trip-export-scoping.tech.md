# Autorización de exportaciones según el filtro — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-22 (revisado 2026-10-23) · **Bounded Contexts:** `DataExchange`, `CarbonFootprint`, `Property`, `Security`

## Qué hace y por qué

El pipeline de exportación decidía el acceso solo por **tipo** (`ExportationType::isAuthorized`), nunca por los datos pedidos. Las únicas barreras reales eran las opciones de los `ChoiceType` de los formularios, es decir, la UI. `RegisterExportationService` y el job `ExecuteExportationService` aceptaban cualquier filtro, de modo que `property_trip_calculations`, `event_attendee_surveys` y `users` podían exportar datos de otras cuentas.

La solución añade un punto de autorización opcional sobre el **filtro ya construido** (`FilterAuthorizedExportationType::isAuthorizedForFilter`), evaluado al registrar (petición web) y de nuevo al ejecutar (worker). Cada tipo delega en el `Authorizer` del recurso con `Action::Export`. Además se alinean los formularios: un Admin puede elegir cualquier propiedad y un Owner deja de ver el selector de cuentas en «Usuarios». Decisiones y alternativas descartadas: tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/DataExchange/Domain/Model/Exportation/FilterAuthorizedExportationType.php` | Interfaz opcional `isAuthorizedForFilter(object $filter, User $user): bool` | Nuevo |
| `src/DataExchange/Application/Exportation/Register/RegisterExportationService.php:46-49` | Rechaza antes de crear la `Exportation` | Modificado |
| `src/DataExchange/Application/Exportation/Execute/ExecuteExportationService.php:91-94` | Rechaza tras `hydrateFilter`, antes de abrir el writer; `generate()` recibe `$user` | Modificado |
| `src/CarbonFootprint/Infrastructure/DataExchange/PropertyTripCalculationsExportationType.php:187` | Autoriza por `PropertyAuthorizer` | Modificado |
| `src/CarbonFootprint/Infrastructure/DataExchange/EventAttendeeSurveysExportationType.php:202` | Autoriza por `EventAuthorizer` | Modificado |
| `src/Security/Infrastructure/DataExchange/UsersExportationType.php:67-90,158` | `createFilter()` fija la cuenta actual; `canExportEveryAccount()`; `isAuthorizedForFilter()` | Modificado |
| `src/CarbonFootprint/Infrastructure/Symfony/Form/AdminPropertyTripCalculationsExportType.php:22-30` | Activa `all_accounts` si el usuario puede exportar propiedades de cualquier cuenta | Modificado |
| `src/Property/Infrastructure/Symfony/Form/Type/PropertyIdType.php:22-39` | Nueva opción `all_accounts` (bool, `false`); `choices` pasa a ser lazy | Modificado |
| `src/Security/Infrastructure/Symfony/Form/AdminUsersExportType.php:20-41` | Campo `account` solo si `canExportEveryAccount()`; roles según perfil | Modificado |

## Modelo de dominio

No hay agregados ni eventos nuevos. El contrato es la interfaz `FilterAuthorizedExportationType extends ExportationType` (hereda `#[AutoconfigureTag('exchange.exportation.type')]`); los tipos que no la implementan se comportan como antes (los 18 catálogos globales). El rechazo es siempre `\RuntimeException('Export filter not allowed')`.

## Flujo de una operación

1. `ExportController` (`/{_locale}/{code}/export`) construye el filtro con `createFilter()` y `handleRequest` lo sobrescribe con los campos presentes en el formulario. Los campos que no están en el formulario conservan el valor de `createFilter()`; así «Usuarios» fija la cuenta del Owner.
2. `RegisterExportationService`: `isAuthorized($user)` y después `isAuthorizedForFilter($request->filter, $user)`. Si falla, no se persiste nada ni se despacha el comando. El controlador convierte la excepción en el flash «No se ha podido iniciar la exportación.».
3. El comando diferido (`Priority::LOW`) restaura usuario e impersonación en el worker. `ExecuteExportationService::generate()` rehidrata el filtro guardado y repite la comprobación; la excepción la recoge el `catch` existente y la exportación pasa a fallida con el mensaje `Export filter not allowed`, sin fichero. Cubre exportaciones encoladas antes del despliegue y cambios de rol o impersonación entre registro y ejecución.

Reglas por tipo:

- `property_trip_calculations`: filtro de otra clase, `propertyId` nulo o propiedad inexistente ⇒ `false`. Si existe, `PropertyAuthorizer::canDoThat($property, Action::Export, $user)` (Owner con cuenta actual = cuenta de la propiedad, o Admin/SuperAdmin).
- `event_attendee_surveys`: igual con `EventRepository::find` y `EventAuthorizer` (solo Owner de la cuenta actual; Admin queda excluido por el authorizer).
- `users`: sujeto stub `new User(id: UserId::generate(), accountId: $filter->account?->id())` con `UserAuthorizer`. Admin/SuperAdmin: cualquier cuenta o «Todas las cuentas»; Owner (o consultora / admin impersonando): solo la cuenta actual; sin cuenta ⇒ rechazado.

## Autorización y ámbito

- El worker no tiene `CurrentAccountIdProvider` fiable sin impersonación; los authorizers funcionan porque `AbstractAuthorizer::currentAccountId($user)` cae a `$user->accountId()`.
- Los formularios usan `AuthorizationCheckerInterface::isGranted(Action::Export->value, <stub sin cuenta>)`, resuelto por `AuthorizerVoter`, que respeta los roles efectivos de la impersonación. Un sujeto sin cuenta solo pasa para Admin/SuperAdmin.
- Roles en el formulario de usuarios: con acceso global, `Role::casesForAdmins()`; sin él, `Role::casesForOwners()` más `Provider` (`AdminUsersExportType.php:37-39`).

## Persistencia y migración

Sin cambios de esquema ni migración. El formato de `filterCriteria` no cambia, por lo que las exportaciones existentes siguen siendo válidas.

## Configuración y operación

Sin variables, colas ni comandos nuevos. Los tipos se autoconfiguran por la etiqueta heredada; las dependencias nuevas (`PropertyRepository`, `EventRepository`, `AuthorizationCheckerInterface`) se autowirean. En `test` los transportes async son `in-memory`, por lo que Behat no ejecuta el worker.

## Cómo extenderlo

Para proteger otro tipo que filtre por un recurso concreto:

1. Cambia `implements ExportationType` por `implements FilterAuthorizedExportationType`.
2. Inyecta el repositorio del recurso y su `Authorizer`.
3. Implementa `isAuthorizedForFilter()`: comprueba la clase del filtro, que el identificador no sea nulo, que el recurso exista y delega en `canDoThat($recurso, Action::Export, $user)`.
4. Si el formulario ofrece un selector, limítalo con la misma regla (patrón `isGranted` + stub sin cuenta).

Trampas: no validar en `createFilter()` (se ejecuta antes de que `handleRequest` sobrescriba el filtro) ni en `rows()` (no recibe usuario). Los stubs de agregado como sujeto de autorización son deuda conocida.

## Tests

- Unitarios: `tests/Unit/CarbonFootprint/Infrastructure/DataExchange/PropertyTripCalculationsExportationTypeTest.php`, `EventAttendeeSurveysExportationTypeTest.php`, `tests/Unit/Security/Infrastructure/DataExchange/UsersExportationTypeTest.php`, `tests/Unit/DataExchange/Application/Exportation/Register/RegisterExportationServiceTest.php`, `Execute/ExecuteExportationServiceTest.php`. Apoyo: `FakeFilterAuthorizedExportationType.php`, `StubAuthorizationChecker.php`. Cubren cuenta propia, ajena, impersonada, Admin, filtro sin recurso, recurso inexistente, filtro de otra clase, no persistencia al rechazar y fallo en ejecución.
- Behat: `tests/Acceptance/DataExchange/export.feature` (selector de cuenta y roles para Admin y Owner; exportación del Owner) y `tests/Acceptance/CarbonFootprint/property-trip-calculations-export.feature` (Owner, Admin con propiedad ajena, usuario normal).
- Ejecución: `make test-unit`, `make test-acceptance`, `make test-this test=<ruta>`.

## Desviaciones y deuda

- `review.md`: sin hallazgos abiertos (B1 y B2 corregidos en la revisión 2).
- Deuda declarada: uso de stubs de agregado como sujeto de autorización.
- Los formularios de filtro de usuarios y de propiedades dependen de `UsersExportationType::canExportEveryAccount()` y del voter; no hay tests unitarios de los form types, se cubren con Behat.
