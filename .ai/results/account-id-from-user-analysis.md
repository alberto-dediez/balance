# Análisis: Uso de `$user->accountId()` vs `LoggedAccount`

**Fecha:** 2026-04-15  
**Alcance:** Todos los Bounded Contexts (`FoodLoss`, `Waste`, `Property`, `DataExchange`, `Security`)  
**Motivación:** Inventariar todos los puntos donde el `AccountId` se obtiene directamente del usuario logueado (`$user->accountId()`) en vez de usar el servicio `LoggedAccount`, como paso previo a implementar una funcionalidad que afecta a la lógica de autorización por cuenta.

---

## Contexto: diferencia entre `$user->accountId()` y `LoggedAccount`

| | `$user->accountId()` | `LoggedAccount` |
|---|---|---|
| **Qué devuelve** | `AccountId\|null` (UUID almacenado en la entidad User) | `Account\|null` (entidad completa cargada desde la BD) |
| **Validación** | Ninguna: devuelve el UUID aunque la cuenta no exista | Consulta el repositorio: devuelve `null` si la cuenta fue eliminada/desactivada |
| **Capa** | Domain / Application | Infrastructure (Symfony) |
| **Uso correcto** | Comparar IDs para autorización dentro del dominio | Obtener el aggregate `Account` completo (por ejemplo, para leer sus datos o para establecer el `accountId` al crear entidades) |

El problema surge cuando se usa `$user->accountId()` para **establecer** el `AccountId` de nuevas entidades o para construir filtros, en lugar de obtenerlo de `LoggedAccount` que garantiza que la cuenta existe y está activa.

---

## Categoría 1 — Servicios que ESTABLECEN `AccountId` desde `$user->accountId()`

Estos son los casos más críticos: el `AccountId` se asigna a entidades nuevas o se usa para construir filtros a partir del campo almacenado en el usuario, sin pasar por `LoggedAccount`.

### FoodLoss

| Archivo | Línea | Descripción |
|---|---|---|
| `src/FoodLoss/Application/CriticalPoint/Register/RegisterCriticalPointService.php` | 32 | `$accountId = $user->accountId()` → se asigna al nuevo `CriticalPoint` y valida que la `Property` pertenezca a esa cuenta |
| `src/FoodLoss/Application/CriticalPoint/Update/UpdateCriticalPointService.php` | 49 | `$userAccountId = $user->accountId()` → se usa para verificar que la `Property` elegida pertenece al mismo account |
| `src/FoodLoss/Application/RecipientOrganization/Register/RegisterRecipientOrganizationService.php` | 31 | `$accountId = $user->accountId()` → se asigna a la nueva `RecipientOrganization` |

**Patrón observado:** El controlador ya usa `$loggedAccount()?->id()` para el pre-check del voter de Symfony, pero luego pasa `$loggedUser()` al servicio, que extrae el `accountId` nuevamente desde el usuario sin pasar por `LoggedAccount`.

### Waste

| Archivo | Línea | Descripción |
|---|---|---|
| `src/Waste/Application/Measurement/Import/ImportMeasurementService.php` | 123 | `accountId: $user->accountId()` → se asigna al nuevo `Container` creado on-the-fly durante la importación |
| `src/Waste/Application/Measurement/MeasurementImportationType.php` | 228 | `accountId: $user->accountId()` → se usa para construir una `Measurement` temporal en `isAuthorized()` para el check de autorización `Action::Import` |

### Waste — Infrastructure

Estos casos están en la capa de Infrastructure, pero igualmente se salta `LoggedAccount`:

| Archivo | Línea | Descripción |
|---|---|---|
| `src/Waste/Infrastructure/UI/Web/Container/ListController.php` | 34 | `$accountId = $user->accountId()` → se pasa directamente a `ListContainersRequest`; el controlador no inyecta `LoggedAccount` |
| `src/Waste/Infrastructure/UI/Api/MeasurementHistory/MeasurementHistoryController.php` | 39 | `accountId: $user->accountId()` → se pasa a `ListMeasurementsRequest`; se usa `SecurityUser` (no el `User` de dominio) obtenido de `$this->getUser()` |

---

## Categoría 2 — Authorizers que usan `$user->accountId()` para comparación

Los authorizers reciben un `User $user` por contrato de `AbstractAuthorizer` y usan `$user->accountId()` para comparar el account del usuario contra el account del agregado. Este patrón es **funcionalmente correcto** hoy (compara UUIDs), pero si en el futuro se necesita un check más rico (por ejemplo, comprobar que la cuenta está activa, tiene un plan activo, etc.) habrá que revisarlos todos.

### FoodLoss

| Archivo | Métodos afectados |
|---|---|
| `src/FoodLoss/Application/CriticalPoint/CriticalPointAuthorizer.php` | `canList` (L31), `canEdit` (L36, L46) |
| `src/FoodLoss/Application/DeliveryLog/DeliveryLogAuthorizer.php` | `canList` (L31, L37) |
| `src/FoodLoss/Application/DoggyBagException/DoggyBagExceptionAuthorizer.php` | `canList` (L29, L35), `canEdit` (L40, L50) |
| `src/FoodLoss/Application/DoggyBagSign/DoggyBagSignAuthorizer.php` | `canList` (L33, L39), `canEdit` (L46, L52) |
| `src/FoodLoss/Application/DonationAgreement/DonationAgreementAuthorizer.php` | `canRegister`/`canEdit` (L34, L38) |
| `src/FoodLoss/Application/Incidence/IncidenceAuthorizer.php` | `canRegister` (L27), `canList` (L32), `canEdit` (L42) |
| `src/FoodLoss/Application/Packaging/PackagingAuthorizer.php` | `canRegister` (L32–34), `canEdit` (L39, L49) |
| `src/FoodLoss/Application/PreventionPlan/PreventionPlanAuthorizer.php` | `canList` (L34, L38) |
| `src/FoodLoss/Application/Protocol/ProtocolAuthorizer.php` | `canList` (L29, L40), `canEdit` (L47, L53) |
| `src/FoodLoss/Application/RecipientOrganization/RecipientOrganizationAuthorizer.php` | `canList` (L31, L46), `canEdit` (L36) |
| `src/FoodLoss/Application/TrainingSession/TrainingSessionAuthorizer.php` | `canList` (L29, L40), `canEdit` (L47, L53) |

### Waste

| Archivo | Métodos afectados |
|---|---|
| `src/Waste/Application/Measurement/MeasurementAuthorizer.php` | `canRegister` (L16, L20), `canView` (L25, L30), `canList` (L40), `canEdit` (L45, L50), `canRemove` (L60, L65), `canImport` (L73, L78) |
| `src/Waste/Application/Container/ContainerAuthorizer.php` | `canList` (L16, L21), `canEdit` (L27, L32), `canRemove` (L38) |
| `src/Waste/Application/DailyWaste/DailyWasteAuthorizer.php` | `canRegister` (L16, L20), `canView` (L25, L30), `canList` (L37), `canEdit` (L37) |
| `src/Waste/Application/WasteManager/WasteManagerAuthorizer.php` | `canRegister` (L18, L20) |

### Property

| Archivo | Métodos afectados |
|---|---|
| `src/Property/Application/Property/PropertyAuthorizer.php` | `canRegister` (L17–19) — y todos los que delegan en él: `canView`, `canList`, `canEdit`, `canRemove` |

### DataExchange

| Archivo | Métodos afectados |
|---|---|
| `src/DataExchange/Application/Importation/ImportationAuthorizer.php` | `canRegister` (L63) |

### Security

| Archivo | Métodos afectados |
|---|---|
| `src/Security/Application/Account/AccountAuthorizer.php` | `canRegister` (L43) |
| `src/Security/Application/User/UserAuthorizer.php` | `canRegister` (L26, L41), `canEdit` (L70, L72) |

---

## Categoría 3 — Usos en Infrastructure sin impacto en lógica de negocio

Estos usos son correctos o irrelevantes para la funcionalidad que se quiere implementar:

| Archivo | Línea | Motivo |
|---|---|---|
| `src/Security/Infrastructure/Symfony/Security/LoggedAccount.php` | 22, 29 | Es la propia implementación de `LoggedAccount`; llama a `$user->accountId()` internamente para cargar el `Account` |
| `src/Security/Infrastructure/Persistence/Doctrine/Repository/DoctrineUserRepository.php` | 37–44 | Carga el `Account` asociado al `User` en el repositorio Doctrine |
| `src/Security/Infrastructure/Persistence/InMemory/Repository/InMemoryUserRepository.php` | 130 | Filtrado por account en el repositorio InMemory |
| `src/Security/Infrastructure/UI/Web/User/OnlyUsersFromAccountTrait.php` | 15 | Guard para restringir vistas al account del usuario logueado |
| `src/Security/Infrastructure/UI/Api/UserFind/FindController.php` | 45 | Serialización del `accountId` en la respuesta JSON |
| `src/Security/Infrastructure/UI/Api/UsersMeController.php` | 39 | Mostrar el nombre de la cuenta en `/me` |
| `src/Security/Application/User/Register/RegisterUserService.php` | 47 | Construcción del DTO de respuesta |

---

## Resumen de impacto

| Categoría | Nº de ficheros | Bounded Contexts afectados |
|---|---|---|
| Servicios que establecen `AccountId` sin `LoggedAccount` | 7 | `FoodLoss` (3), `Waste` (4) |
| Authorizers que comparan con `$user->accountId()` | 20+ ficheros, ~50 líneas | `FoodLoss`, `Waste`, `Property`, `DataExchange`, `Security` |
| Infrastructure sin impacto | 7 | `Security` |

---

## Recomendación para la funcionalidad a implementar

Si la nueva funcionalidad requiere **enriquecer la lógica de autorización** con datos del `Account` (estado, plan, tipo de cuenta, etc.):

1. **Authorizers:** El contrato de `AbstractAuthorizer::canXxx(mixed $subject, User $user)` no recibe el `Account`. Habrá que decidir entre:
   - Inyectar `LoggedAccount` como dependencia en los authorizers que lo necesiten.
   - Añadir el `Account` como parámetro adicional (breaking change en la interfaz).
   - Crear un nuevo método en `AbstractAuthorizer` que reciba el `Account`.

2. **Servicios (Categoría 1):** El `AccountId` debería venir del controlador a través del `Request` DTO, obteniéndolo de `$loggedAccount()?->id()`. Así el servicio no depende de la fuente del ID y queda desacoplado de la infraestructura de seguridad.

3. **Waste/Infrastructure (Categoría 1):** `Container/ListController` debería inyectar `LoggedAccount` en lugar de usar `$loggedUser()->accountId()`.
