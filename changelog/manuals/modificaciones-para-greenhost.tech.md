# Distribuidores (modificaciones para Greenhost) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-04 · **Bounded Contexts:** `Security`, `Property`, `CarbonFootprint`, `Shared`

## Qué hace y por qué

Greenhost y otras organizaciones que agrupan alojamientos necesitan una figura nueva: el **distribuidor**. Es una **cuenta** marcada por un Administrador como «Es distribuidora» (`Account::$distributor`, `src/Security/Domain/Model/Account/Account.php:29,83`). Sus Propietarios ven en su listado «Propiedades» las **propiedades adheridas** (de otras cuentas) mezcladas con las propias, y solo pueden abrir la pantalla **Respuestas** de la calculadora de huéspedes de cada una. No editan, no borran, no compensan, no exportan y no acceden a huellas ni indicadores. Además, las adheridas pasan a ser destinos de su calculadora de viajes de cuenta («Todas mis propiedades» / «Solo estas propiedades»). El distribuidor nunca impersona (no se tocan `ImpersonationPolicy` ni `ConsultantSupervisionPolicy`) y es independiente de la consultora.

La adhesión es un agregado nuevo, `DistributorAssignment`, en `Property` (no puede vivir en `Security`, que no depende de `Property`). Cada fila apunta **o** a una cuenta (todas sus propiedades, presentes y futuras, evaluado en consulta) **o** a una propiedad. La crea solo el Administrador mediante acciones masivas «Asignar distribuidor»; la retira el Administrador o el Propietario de la cuenta adherida (en este último caso se notifica internamente a los Propietarios del distribuidor). Las decisiones están en la tabla «Decisiones tomadas» de `plan.md`.

Regla de visibilidad (`DistributedPropertiesPolicy::distributes()`): el distribuidor `D` ve la propiedad `p` si `D` es distribuidora activa, la cuenta de `p` tiene `AccountFeature::PropertyTripCalculators` y existe asignación `(D, cuenta de p)` o `(D, p)`. Como destino de la calculadora de cuenta no se exige esa funcionalidad.

## Mapa de piezas

### Security

| Fichero | Rol | Estado |
|---|---|---|
| `src/Security/Domain/Model/Account/Account.php` | Campo `distributor`, `isDistributor()`, `register(..., $distributor)`, `changeDistributorStatus()` (`:154`), `apply*` (`:226`, `:253`) | modificado |
| `src/Security/Domain/Model/Account/AccountDistributorStatusChanged.php` | Evento `accountDistributorStatusChanged` (`accountId`, `distributor`) | nuevo |
| `src/Security/Domain/Model/Account/AccountRegistered.php`, `AccountFilter.php` | Campo `distributor` en evento y filtro (`?bool`) | modificado |
| `src/Security/Application/Account/DistributorPolicy.php` | `isActiveDistributor(AccountId)`: existe, marcada, activa y no eliminada | nuevo |
| `src/Security/Application/Account/Register/*`, `Update/*` | `distributor` en request/response; `UpdateAccountService::updateDistributorStatus()` (`:136`) exige `Action::Admin` solo si el valor cambia | modificado |
| `src/Security/Infrastructure/Persistence/Doctrine/Mapping/Account.Account.orm.xml`, `DoctrineAccountRepository.php`, `InMemoryAccountRepository.php` | Columna y filtro | modificado |
| `src/Security/Infrastructure/Symfony/Form/{RegisterAccountType,UpdateAccountType,ListAccountsType}.php` | Campo «Es distribuidora» y filtro «Distribuidoras y no distribuidoras» | modificado |
| `src/Security/Infrastructure/Symfony/Form/Type/DistributorAccountChoiceType.php` | Selector de cuentas distribuidoras (`AccountFilter(distributor: true)`) | nuevo |
| `src/Security/Infrastructure/UI/Web/Admin/Account/Bulk/BulkAccountRequest.php`, `Admin/Account/EditController.php` | `distributorAccountId` en la acción masiva; precarga de la marca | modificado |
| `templates/security/admin/accounts/{edit,list}.html.twig`, `templates/security/admin/events/detail/security.accountDistributorStatusChanged.html.twig` | Campo, columna e histórico | modificado / nuevo |

### Property

| Fichero | Rol | Estado |
|---|---|---|
| `src/Property/Domain/Model/DistributorAssignment/` | Agregado, id, `Registered`/`Removed`, filtro, puerto, excepciones `AccountIsNotDistributorException` y `DistributorCannotDistributeItselfException` | nuevo |
| `src/Property/Domain/Model/Property/PropertyScope.php` | VO con listas de cuentas **o** propiedades (`forAccount()`, `merge()` sin duplicados, `isEmpty()`) | nuevo |
| `src/Property/Domain/Model/Property/PropertyFilter.php` | `?PropertyScope $scope` | modificado |
| `src/Property/Domain/Event/DistributorAssignment/` | Tres suscriptores de cascada (cuenta eliminada, propiedad eliminada, distribuidora desmarcada) | nuevo |
| `src/Property/Application/DistributorAssignment/` | `DistributorAssignmentAuthorizer`, `DistributedPropertiesPolicy`, casos `Register`, `Remove`, `List` | nuevo |
| `src/Property/Application/Property/PropertyAuthorizer.php` | `canView` (`:59`), `canList` (`:64`), `isOwnerOfCurrentAccount()` (`:35`) | modificado |
| `src/Property/Application/Property/List/ListPropertiesService.php` | Expande el alcance para distribuidoras (`:46-61`) | modificado |
| `src/Property/Infrastructure/Persistence/Doctrine/Mapping/DistributorAssignment.DistributorAssignment.orm.xml`, `Type/DistributorAssignmentIdType.php`, `Repository/DoctrineDistributorAssignmentRepository.php`, `InMemory/Repository/InMemoryDistributorAssignmentRepository.php` | Persistencia | nuevo |
| `DoctrinePropertyRepository.php` (`:238`, `applyScope` `:261`), `InMemoryPropertyRepository.php` | Filtro por alcance | modificado |
| `src/Property/Infrastructure/Symfony/Form/Extension/AdminBulkAccountTypeExtension.php` | Añade campo y acción «Asignar distribuidor» a `BulkAccountType` desde `Property` | nuevo |
| `src/Property/Infrastructure/UI/Web/Admin/DistributorAssignment/{ListController,RemoveController}.php`, `Bulk/{AssignDistributorToAccount,RemoveDistributorAssignment}.php`; `Admin/Property/Bulk/AssignDistributor.php`; `Form/AdminBulkPropertyType.php`, `BulkPropertyRequest.php` | Pantalla de Administrador y acciones masivas | nuevo / modificado |
| `src/Property/Infrastructure/UI/Web/DistributorAssignment/{ListController,RemoveController}.php` | Pantalla del Propietario (`/distributors`) | nuevo |
| `src/Property/Infrastructure/UI/Web/Property/ListController.php` (`:76-77`), `templates/property/properties/list.html.twig` | `isDistributor`, `canRegister`, columna «Cuenta», acciones por autorización | modificado |
| `src/Property/Infrastructure/Symfony/Form/{ListDistributorAssignmentsType,ListOwnerDistributorAssignmentsType}.php`, `Symfony/Twig/PropertyExtension.php` (filtro `property_account_name`) | Filtros y utilidades de plantilla | nuevo / modificado |
| `src/Property/Infrastructure/Notification/DistributorAssignmentRemovedNotifier.php` + `templates/property/notification/distributor-assignment-removed.notification.html.twig` | Notificación interna | nuevo |
| `templates/property/admin/distributor_assignments/list.html.twig`, `templates/property/distributors/list.html.twig` | Listados | nuevo |
| `src/Property/Infrastructure/Symfony/DependencyInjection/services.yaml:18` | Prioridad 10 del suscriptor de baja de cuenta | modificado |
| `config/packages/doctrine.yaml:25` | Tipo `distributor_assignment_id` | modificado |

### CarbonFootprint y Shared

| Fichero | Rol | Estado |
|---|---|---|
| `src/CarbonFootprint/Application/Trip/TripDestinationCandidates.php` (`:29-33`) | Candidatas = propias ∪ gestionadas (consultora) ∪ adheridas | modificado |
| `src/CarbonFootprint/Application/TripCalculationLink/TripCalculationLinkAuthorizer.php` (`:56` `canExport`) | La exportación sobre enlaces de propiedad se evalúa con `Action::Export` | modificado |
| `templates/carbon_footprint/property_trip_calculations/results.html.twig` | «Compensar emisiones» bajo `property \| is_authorized('edit')`, «Exportar» bajo `export` | modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | `?DistributorPolicy` (`:36`), `isActiveDistributor()` (`:64`), grupo «Propiedades» para distribuidoras (`:607`), nodos «Distribuidores» (`:221` Admin, `:762` Propietario) | modificado |
| `templates/components/List/FilterBar.html.twig` | `distributorAccountId` se pinta con `account_name` | modificado |

## Modelo de dominio

**`DistributorAssignment`** (`src/Property/Domain/Model/DistributorAssignment/DistributorAssignment.php`): `distributorAccountId` (siempre), `accountId` **o** `propertyId` (excluyentes), `registeredBy`, `removed`, `registeredAt`, `removedAt`. `isForProperty()` (`:59`) distingue el objetivo.

- `registerForAccount()` (`:79`) lanza `DistributorCannotDistributeItselfException` si la cuenta objetivo es la propia distribuidora; `registerForProperty()` (`:96`). Ambos publican `DistributorAssignmentRegistered`.
- `remove(UserId $removedBy, bool $notifyDistributor)` (`:109`) publica `DistributorAssignmentRemoved` (payload con `removedBy` y `notifyDistributor`) y marca la baja lógica.
- Las comprobaciones que dependen de otros agregados viven en `RegisterDistributorAssignmentService`: la cuenta distribuidora debe estar marcada (`AccountIsNotDistributorException`), la cuenta u propiedad objetivo debe existir, y una propiedad cuya cuenta es la distribuidora lanza `DistributorCannotDistributeItselfException`.
- No hay índice único en base de datos (MySQL no distingue `NULL`); la idempotencia la da el servicio, que devuelve la asignación existente (`findForAccount`/`findForProperty`) sin publicar evento.

**`Account`**: `distributor` viaja en `AccountRegistered` (con `(bool) ($payload['distributor'] ?? false)` al reconstruir) y en `AccountDistributorStatusChanged`.

**`PropertyScope`** + `PropertyFilter::$scope`: semántica OR entre cuentas y propiedades; alcance vacío → `1 = 0` (`DoctrinePropertyRepository.php:261-280`). Es independiente de `accountId`/`accountIds`.

**`DistributorAssignmentFilter`**: `distributorAccountId` y `propertyId` son conjunciones; `accountId` y `propertyIds` informados a la vez forman una **disyunción** (`DoctrineDistributorAssignmentRepository.php:149-160`), usada por la pantalla del Propietario, por el filtro «Cuenta adherida» del Administrador y por la baja en cascada de una cuenta. `propertyIds = []` solo, no devuelve nada (`:179`).

## Flujo de una operación

### Asignar (Administrador)

1. En `Administración → Cuentas` (`BulkAccountType` extendido por `AdminBulkAccountTypeExtension`) o en el listado de propiedades de administración (`AdminBulkPropertyType`), la acción con id `assign-distributor` genera un `RegisterDistributorAssignmentRequest` por fila (`AssignDistributorToAccount.php`, `AssignDistributor.php`). Sin distribuidor elegido no genera comandos.
2. `RegisterDistributorAssignmentService::__invoke` autoriza `Register` (solo `canAdmin`), valida exactamente un objetivo (`\InvalidArgumentException('Either accountId or propertyId is required')`), que la distribuidora esté marcada, y delega en `assignmentForAccount()`/`assignmentForProperty()`; añade al repositorio. Los fallos aparecen fila a fila con el mensaje `failure` de la acción.

### Retirar

- Administrador: `admin_distributor_assignments_remove` (`/admin/distributor-assignments/remove/{id}`, `#[CsrfProtected('remove')]`, `ROLE_ADMIN`) o la acción masiva `RemoveDistributorAssignment`.
- Propietario de la cuenta adherida: `distributors_remove` (`/distributors/remove/{id}`).
- Ambos llaman a `RemoveDistributorAssignmentService`: busca, autoriza `Remove`, y `$assignment->remove($user->id(), $request->notifyDistributor && !$this->authorizer->isAdmin($user))`. Es decir, **solo notifica si retira un Propietario** y la petición no lo desactiva.
- `DistributorAssignmentRemovedNotifier` (suscriptor de `DistributorAssignmentRemoved`): si `notifyDistributor` es `true`, crea una `SystemNotification` `Internal` por cada Propietario de la cuenta distribuidora (`UserFilter(role: Owner, ...)`), asunto «Se ha retirado tu acceso como distribuidor».

### Bajas en cascada (`src/Property/Domain/Event/DistributorAssignment/`)

Todos llaman a `RemoveDistributorAssignmentService` con `notifyDistributor: false`:

- `RemoveAssignmentsOnAccountRemovedSubscriber`: elimina las de la cuenta como distribuidora y las de la cuenta como adherida **más las de propiedades de esa cuenta** (consulta en disyunción con `propertyIdsOf()`). Debe ejecutarse antes de `ReleasePropertiesOnAccountRemovedSubscriber`, que desvincula las propiedades de la cuenta: por eso `services.yaml:18` fija `priority: 10`.
- `RemoveAssignmentsOnPropertyRemovedSubscriber`: las de esa propiedad. Por esa razón `DistributorAssignmentAuthorizer::canRemove` deja retirar una asignación por propiedad cuya propiedad ya no existe (`DistributorAssignmentAuthorizer.php:63-86`).
- `RemoveAssignmentsOnDistributorUnmarkedSubscriber`: al llegar `AccountDistributorStatusChanged` con `distributor === false`, elimina todas las de esa distribuidora.
- Cambiar una propiedad de cuenta conserva su asignación por propiedad.

### Listado «Propiedades» del Propietario

`ListController` (`/properties`) mantiene el filtro por defecto `accountId: <cuenta actual>` y pasa `isDistributor` y `canRegister` (`ListController.php:76-77`). `ListPropertiesService::__invoke` clona la petición (`:34`, para no mutar el filtro guardado en sesión, hallazgo A1 de `review.md`) y, si el usuario es Propietario de la cuenta actual y ésta es distribuidora activa (`expandScopeForDistributor`, `:46`), pone `scope = forAccount(cuenta) ∪ scopeFor(cuenta, PropertyTripCalculators)` y anula `accountId`. `scopeFor()` descarta las adheridas cuya cuenta no tenga esa funcionalidad (`DistributedPropertiesPolicy.php`). Un Administrador o una cuenta que no es distribuidora no cambia.

La plantilla `templates/property/properties/list.html.twig`: columna «Cuenta» solo con `isDistributor`; si `element | is_authorized('edit')` se muestran las acciones de siempre; si no, y la **cuenta actual** tiene `property_trip_calculators` y `view` es cierto, solo «Respuestas». `Link:Register` bajo `canRegister`.

### Respuestas de una propiedad adherida

`GetTripCalculationResultsService` (sin cambios) autoriza `View` con `PropertyAuthorizer` (que ahora admite al distribuidor) y además `TripCalculationLinkAuthorizer::canViewForProperty()` (`:68`), que exige la funcionalidad `PropertyTripCalculators` de la **cuenta actual** (la distribuidora) salvo para Administradores. Consecuencia: para abrir Respuestas la distribuidora necesita tener esa funcionalidad ella misma y también el hotel. En `results.html.twig`, «Compensar emisiones» exige `edit` y «Exportar» `export`, de modo que desaparecen.

### Calculadora de viajes de cuenta

`TripDestinationCandidates::candidatesFor()` construye el alcance con `forAccount ∪ supervisedAccountIds ∪ scopeFor($accountId)` (sin funcionalidad requerida) y consulta `PropertyFilter(scope: …, limit: 0)`.

### Menú

`NodeLister::toOwners()` muestra el grupo «Propiedades» si `propiedadesHabilitadas && (algunaFuncionalidad || isActiveDistributor())` (`:607`); «Empresas» solo con funcionalidades. «Administración → Distribuidores» (`:762`) aparece si `propertiesEnabled`. `toAdmins()` añade «Distribuidores» al grupo «Propiedades» (`:221`).

## Autorización y ámbito

| Pieza | Regla |
|---|---|
| `DistributorAssignmentAuthorizer` | `Register`: Administrador. `Edit`: nunca. `List`: Administrador, o Propietario con cuenta actual (el servicio acota). `Remove`/`View`: Administrador; o Propietario de la cuenta adherida (por cuenta: coincide con `accountId`; por propiedad: con la cuenta de la propiedad, o la propiedad ya no existe). Usa `currentAccountId()` y `userHasRole()` (respeta impersonación). |
| `ListDistributorAssignmentsService` | Clona la petición. Administrador: sin acotar; con `accountId` añade las propiedades de esa cuenta. Propietario: fuerza `accountId = cuenta actual` y `propertyIds` = sus propiedades, y anula `distributorAccountId`/`propertyId`. |
| `PropertyAuthorizer` | `canView = canRegister ∨ isDistributedToCurrentAccount` (Propietario y `distributes()`); `canList = canRegister ∨ isActiveDistributorAccount` (Propietario de la cuenta del sujeto, distribuidora activa); `Edit`, `Remove`, `Register`, `Export` solo `canRegister`. |
| `TripCalculationLinkAuthorizer::canExport` | Evalúa `Action::Export` sobre la propiedad, no `View`. |
| Funcionalidades | La cuenta del hotel necesita `PropertyTripCalculators` para el listado y Respuestas. Una distribuidora activa ve el grupo «Propiedades» y puede listar aunque no tenga ninguna funcionalidad de propiedades. |

Roles: SuperAdmin/Admin gestionan todo (rutas `ROLE_ADMIN`); el Propietario de la cuenta adherida ve y retira lo suyo (`/distributors`); el Propietario de la distribuidora lee; el rol `User` no tiene acceso a las pantallas de gestión (el authorizer exige `Role::Owner`).

## Persistencia y migración

- Migración aplicada por el usuario: `migrations/Version20261104151150.php`. Crea `distributor_assignments` (`id`, `distributor_account_id`, `account_id`, `property_id`, `registered_by`, `removed`, `registered_at`, `removed_at`; índices por distribuidor, cuenta y propiedad) y añade `account.distributor TINYINT(1) NOT NULL`. Nota: la tabla de cuentas se llama `account` (`migration.sql` de la carpeta del plan usaba `accounts`; el fichero de `migrations/` es el válido).
- Mapping: `DistributorAssignment.DistributorAssignment.orm.xml`; tipo Doctrine `distributor_assignment_id`.
- Repositorios: `DoctrineDistributorAssignmentRepository` y `InMemoryDistributorAssignmentRepository` (tests). Todas las consultas excluyen las eliminadas.

## Configuración y operación

- Sin variables de entorno ni colas nuevas. `NodeLister` recibe `?DistributorPolicy` por autowiring. El grupo «Propiedades» y «Distribuidores» dependen del parámetro existente `app.properties_enabled`.
- `services.yaml` (Property): único servicio explícito, la prioridad del suscriptor de baja de cuenta.
- La adhesión de los hoteles de Greenhost es manual: marcar la cuenta distribuidora, filtrar el listado de cuentas por consultora (filtro existente) y aplicar «Asignar distribuidor» a la selección.
- Notificación: solo interna, sin correo.

## Cómo extenderlo

- **Dar al distribuidor otra acción de lectura sobre lo adherido:** añadirla a `PropertyAuthorizer::canView` (ya cubierta por `isDistributedToCurrentAccount`) y mostrar el enlace en la rama `elseif` de `templates/property/properties/list.html.twig`. Nunca ampliar `canRegister` ni `canEdit`.
- **Nuevo origen de propiedades visibles** (otra relación como la consultora o el distribuidor): devolverlas desde una política que produzca un `PropertyScope` y unirlas con `merge()`, como hace `TripDestinationCandidates`.
- **Otra acción masiva sobre cuentas definida fuera de `Security`:** copiar el patrón de `AdminBulkAccountTypeExtension` (extensión de formulario y normalizador de `actions`).
- **Notificar en más casos:** hoy `notifyDistributor` lo decide el servicio (`!isAdmin`); añadir la lógica en `RemoveDistributorAssignmentService`, no en el notificador.
- Trampas: la condición de la plantilla de propiedades distingue «adherida» por `edit` falso y `view` verdadero; si `canView` propio se separase de `canEdit`, hay que comparar `element.accountId` con la cuenta actual. Cualquier servicio que reciba el filtro de sesión debe clonarlo antes de modificarlo. `Security` y `Shared` no deben importar nada de `Property` salvo lo ya existente (`NodeLister` solo usa `DistributorPolicy`, de `Security`).

## Tests

Unitarios (`tests/Unit/`): `Security/Domain/Model/Account`, `Security/Application/Account/DistributorPolicyTest.php`, `Property/Domain/Model/DistributorAssignment/`, `Property/Domain/Model/Property/PropertyScopeTest.php` y `PropertyRepositoryTest.php`, `Property/Application/DistributorAssignment/`, `Property/Application/Property/{PropertyAuthorizerTest,List/ListPropertiesServiceTest}.php`, `Property/Domain/Event/DistributorAssignment/`, `Property/Infrastructure/Notification/`, `Property/Infrastructure/Persistence/{Doctrine,InMemory}/Repository/*DistributorAssignmentRepositoryTest.php`, `CarbonFootprint/Application/Trip/TripDestinationCandidatesTest.php`, `Shared/Infrastructure/UI/Web/Navigation/NodeListerTest.php`.

Aceptación (`tests/Acceptance/Property/`): `distributor-assignment-assign-account.feature`, `distributor-assignment-assign-property.feature`, `distributor-assignment-admin-list.feature`, `distributors-owner.feature`, `distributed-properties.feature`; más los escenarios de cuenta en `tests/Acceptance/Security/` y de la calculadora de cuenta en `tests/Acceptance/CarbonFootprint/`. Pasos nuevos en `tests/Acceptance/Property/PropertyContext.php`.

Ejecución (objetivos de `Makefile`): `make test-unit`, `make test-acceptance`, `make test-parallel`, o uno concreto con `make test-this test=tests/Acceptance/Property/distributed-properties.feature`.

## Desviaciones y deuda

Ver `## Implementación` de `plan.md` y `review.md` (0 hallazgos abiertos: 8 corregidos, 2 descartados).

- Respuestas exige además `PropertyTripCalculators` en la cuenta distribuidora (opción B del usuario, por `canViewForProperty`).
- `GET /api/properties` (`src/Property/Infrastructure/UI/Api/PropertyList/PropertyListController.php`) usa `ListPropertiesService`: una distribuidora recibe también las adheridas (con contacto del operador). Hallazgo M1 descartado por el usuario; se acepta.
- `DistributorAssignmentAuthorizer::canRemove` permite a cualquier Propietario retirar una asignación por propiedad cuya propiedad no se encuentra (M3, descartado; necesario para la cascada).
- El registro de huella de una adherida se rechaza con «Property not found in account», no con un 403 literal.
- Traducciones TR con prefijo `NEW_`.
- Sin índice único: dos altas simultáneas podrían duplicar la fila (efecto cosmético).
