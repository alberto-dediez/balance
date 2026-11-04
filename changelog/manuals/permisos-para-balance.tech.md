# Funcionalidades contratadas por cuenta — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-04 · **Bounded Contexts:** `Security`, `Shared`, `EventOperations`, `CarbonFootprint`, `Balance`, `Materiality`, `Property`, `Payments`

## Qué hace y por qué

Cada cuenta contrata solo una parte del catálogo de Balance. Un Administrador puede activar o desactivar, por cuenta, cinco **funcionalidades** (`AccountFeature`): calculadora de viajes de la cuenta, calculadoras de viajes de las propiedades, eventos, compensación y huella de carbono organizacional. Una funcionalidad desactivada desaparece para el Propietario del menú, del buscador, del panel y de las acciones de los listados; el acceso por URL responde 403 y los enlaces públicos (QR de calculadoras y encuestas) responden como «enlace no válido».

El modelo sigue el patrón de los permisos de informe: un value object `AccountFeatures` embebido en `Account`, editable solo por Admin en la edición de la cuenta y consultado desde el resto de contextos a través de una política de `Security` (`AccountFeaturesPolicy`). Todas las funcionalidades están activas por defecto, así que las cuentas existentes no cambian. Las decisiones (nombre del concepto, alcance de «Compensación», pantallas transversales, panel adaptable, enlaces públicos cerrados) están en la tabla «Decisiones tomadas» de `plan.md`.

Solo se restringe al **Propietario** (incluido el Admin que impersona, que pasa a ser `[Owner, User]` en `CurrentRoles`). Admin y SuperAdmin en su panel no se ven afectados porque `currentAccountHasFeature()` solo se consulta en ramas de Propietario.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Security/Domain/Model/Account/AccountFeature.php` | Enum de las cinco funcionalidades (`account_trip_calculator`, `property_trip_calculators`, `events`, `compensation`, `organizational_carbon_footprint`) | nuevo |
| Dominio | `src/Security/Domain/Model/Account/AccountFeatures.php` | Value object con cinco booleanos (todos `true` por defecto), `isEnabled()`, `equals()` | nuevo |
| Dominio | `src/Security/Domain/Model/Account/AccountFeaturesChanged.php` | Evento `accountFeaturesChanged` | nuevo |
| Dominio | `src/Security/Domain/Model/Account/Account.php` | Campo `features` (`:30`), `features()` (`:85`), `changeFeatures()` (`:176`), `applyAccountFeaturesChanged()` (`:243`) | modificado |
| Aplicación | `src/Security/Application/Account/AccountFeaturesPolicy.php` | Punto único de consulta, con caché por cuenta y `reset()` | nuevo |
| Aplicación | `src/Security/Application/AbstractAuthorizer.php` | 5.ª dependencia opcional y `currentAccountHasFeature()` (`:32`) | modificado |
| Aplicación | `src/Security/Application/Account/Update/UpdateAccountRequest.php`, `UpdateAccountService.php` | Cinco campos `feature*` y `updateFeatures()` (`:116`) | modificado |
| Aplicación | `src/CarbonFootprint/Application/Dashboard/OwnerDashboardAuthorizer.php` | Authorizer propio del panel sobre `GetOwnerDashboardRequest` | nuevo |
| Aplicación | `GetOwnerDashboardService.php`, `RegisterTripCalculationLinkService.php`, `GetTripCalculationResultsService.php`, `GetTripCalculationLinkByTokenService.php`, `GetAttendeeSurveyByTokenService.php`, `ListContributionOptionsService.php`, `QuoteContributionService.php` | Cortes por funcionalidad fuera de los authorizers | modificado |
| Aplicación | Authorizers de `EventOperations`, `CarbonFootprint`, `Balance`, `Payments`, `Materiality`, `Property` (ver tabla de «Autorización») | Rama de Propietario condicionada | modificado |
| Persistencia | `src/Security/Infrastructure/Persistence/Doctrine/Mapping/Account.AccountFeatures.orm.xml`, `Account.Account.orm.xml` | Embebido con prefijo `feature_` | nuevo / modificado |
| Persistencia | `migrations/Version20261104124143.php` | Cinco columnas y `UPDATE` a 1 | nuevo |
| Infraestructura | `UpdateAccountType.php`, `EditController.php`, `templates/security/admin/accounts/edit.html.twig` | Sección «Funcionalidades» en la edición de cuenta | modificado |
| Infraestructura | `src/Security/Infrastructure/Symfony/Twig/AccountExtension.php` | Función `account_feature_enabled(string)` (`:53`) | modificado |
| Infraestructura | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | Menú y buscador por funcionalidad | modificado |
| Infraestructura | `RequestController.php` (Balance, `:49`), `MyPayeeController.php` (Payments, `:53`), `OwnerContributionContextResolver.php` (`:41`), `ContributionCheckoutHandler.php` | Cortes en controladores | modificado |
| Infraestructura | `templates/security/admin/events/detail/security.accountFeaturesChanged.html.twig`, `templates/security/translations/accountFeature.html.twig` | Histórico y extracción de etiquetas del enum | nuevo |
| Infraestructura | Plantillas del panel, listados de propiedades y empresas, resultados y wizard | Bloques y acciones condicionados | modificado |
| Configuración | `src/Security/Infrastructure/Symfony/DependencyInjection/services.yaml:51` | Etiqueta `kernel.reset` sobre la política | modificado |

## Modelo de dominio

- `AccountFeatures` es un value object `final`, inmutable, con cinco booleanos promovidos (`AccountFeatures.php`). `isEnabled(AccountFeature)` hace un `match` exhaustivo sobre el enum. `equals()` compara los cinco.
- `Account::changeFeatures()` (`Account.php:176`) hace cortocircuito si `equals()` y, si no, `recordApplyAndPublish(new AccountFeaturesChanged(...))`. `applyAccountFeaturesChanged()` (`:243`) reconstruye el value object. El alta (`register()`) no toca las funcionalidades: nacen con el valor por defecto.
- Evento `AccountFeaturesChanged` (`eventName = 'accountFeaturesChanged'`): payload con `accountId` y los cinco booleanos, con `assert(is_bool(...))` en `rebuildFromPayload()`.
- Invariantes: `AccountFeaturesPolicy::isEnabled(f, null)` y cuenta inexistente devuelven `false`; sin política (authorizers o `NodeLister` construidos sin ella) todo se considera activo.

## Flujo de una operación

**Edición por el Administrador.** `admin_accounts_edit` → `EditController` (carga los cinco valores desde `$account->features()`) → `UpdateAccountType` (cinco `BooleanType`) → `UpdateAccountRequest` → `UpdateAccountService::__invoke` llama a `updateFeatures()` (`UpdateAccountService.php:65,116`): construye `AccountFeatures`, sale si es igual, exige `Action::Admin` sobre la cuenta y llama a `changeFeatures()`. Un Propietario que enviase un cambio recibe `UnauthorizedActionException`; el alta de cuenta (web, API, consola) no pide las funcionalidades.

**Consulta en una pantalla de Propietario.** Ruta → controlador → servicio → `Authorizer::denyUnlessCanDoThat()` → rama de Propietario → `currentAccountHasFeature()` (`AbstractAuthorizer.php:32-38`) → `AccountFeaturesPolicy::isEnabled(feature, currentAccountId($user))`. El `AccountId` es siempre el de la **cuenta actual** (`CurrentAccountIdProvider`, la impersonada si procede), no el de `$user->accountId()`. Si falla: `UnauthorizedActionException` → 403.

**Menú y buscador.** `NodeLister::toOwners()` (`NodeLister.php:~577`) calcula tres booleanos (`Events`, `OrganizationalCarbonFootprint`, `PropertyTripCalculators`) con `featureEnabled()` (`:53`) y construye los grupos. El buscador global (`NavigationSearchProvider`) obtiene sus entradas de `nodes()`, por lo que hereda el filtrado sin cambios.

**Enlaces públicos.** `GetTripCalculationLinkByTokenService` (`:49` enlace de cuenta, `:71` enlace de propiedad) y `GetAttendeeSurveyByTokenService` (`:44`) lanzan `TripCalculationLinkTokenInvalidException` / `AttendeeSurveyTokenInvalidException` si la cuenta dueña no tiene la funcionalidad; los controladores públicos ya las capturan y responden «enlace no válido». Los enlaces de invitación a proveedores, empleados y operadores no se tocan.

**Compensación fuera del authorizer.** `ListContributionOptionsService` (`:29`) devuelve lista vacía sin `Compensation` (de ahí desaparecen los CTA públicos «Compensar mi huella»). `QuoteContributionService` (`:31`) lanza `ProjectNotContributableException` antes de cualquier otra comprobación (cierra el checkout público aunque la cuenta pierda la funcionalidad entre pasos). `ContributionCheckoutHandler` busca el proyecto entre las opciones de la cuenta (sin `co2eqKg` ni `ownerMode`) cuando no hay cotización y devuelve `invalid()` si no está. `OwnerContributionContextResolver` (`:41`) devuelve `null` → 403 en `/compensate/...`. `Balance\...\CreditPurchase\RequestController` y `Payments\...\Payee\MyPayeeController` comprueban la política antes de pintar el formulario (antes solo se denegaba al enviar).

**Panel.** `DashboardController` → `GetOwnerDashboardService` autoriza con `OwnerDashboardAuthorizer` (`List`: rol Propietario y cuenta actual igual a `$request->accountId`); sin `Events` no consulta eventos ni huellas (`:43`) pero calcula siempre las compensaciones. La plantilla `dashboard.html.twig` (`:22-24`) obtiene `eventsEnabled`, `compensationEnabled` y `calculatorsEnabled` con `account_feature_enabled()`: estado vacío «Registrar evento» solo si hay `Events` y cero eventos; aviso informativo si no hay ninguna de las tres; si no, bloques por variable. `_forms.html.twig` usa `eventsEnabled ?? true` y `calculatorsEnabled ?? true` porque también lo incluye el panel de administración.

## Autorización y ámbito

| Funcionalidad | Dónde se comprueba |
|---|---|
| `Events` | `EventAuthorizer`, `EventCarbonFootprintAuthorizer`, `ProviderContributionAuthorizer`, `EmployeeContributionAuthorizer`; `CarbonFootprintReportAuthorizer` (`:73`, cuando el informe es de evento); `GetAttendeeSurveyByTokenService` |
| `AccountTripCalculator` | `TripCalculationLinkAuthorizer::canManageAccountLink` (`:117`); `GetTripCalculationLinkByTokenService` |
| `PropertyTripCalculators` | `TripCalculationLinkAuthorizer` (`canDoThatOnProperty`, `canViewForProperty` `:74`, `canRegisterForProperty` `:84`); `RegisterTripCalculationLinkService`; `GetTripCalculationResultsService`; enlace público de propiedad |
| `Compensation` | `ProjectAuthorizer` (`canList`, `:27`), `AccountProjectAuthorizer`, `CreditPurchaseAuthorizer`, `ProjectContributionAuthorizer`, `ContributionCertificateAuthorizer`, `LedgerEntryAuthorizer`, `PayoutAuthorizer`, `PayeeAuthorizer`; servicios y controladores del apartado anterior |
| `OrganizationalCarbonFootprint` | `PropertyCarbonFootprintAuthorizer`, `CompanyCarbonFootprintAuthorizer`, `CarbonFootprintKpiAuthorizer`, `BaseYearDeclarationAuthorizer`, `MitigationActionAuthorizer`, `PropertyCarbonFootprintCertificateAuthorizer`, `OperatorContributionAuthorizer`, `OrganizationalEmployeeContributionAuthorizer`, `OrganizationalProviderContributionAuthorizer`, `MaterialityAssessmentAuthorizer`; `CarbonFootprintReportAuthorizer` (informes de propiedad y empresa) |
| Disyunciones | `PropertyAuthorizer` y `CompanyAuthorizer` (`Events ∨ OrganizationalCarbonFootprint ∨ PropertyTripCalculators`, `:62` y `:57`); `ProviderAuthorizer` (`Events ∨ OrganizationalCarbonFootprint`, `:69`) |

Por rol: Propietario (y Admin impersonando) queda sujeto a la cuenta actual; Admin/SuperAdmin sin impersonar no cambian (en `TripCalculationLinkAuthorizer` hay un `isAdministrator()` explícito). Los usuarios `User` de la cuenta conservan la consulta de aportaciones y certificados (decisión del usuario registrada en `review.md`, Q1). `ActivityAuthorizer`, `PublicLandingAuthorizer`, `ExportationAuthorizer`, `ImportationAuthorizer` e `ImportationProfileAuthorizer` no cambian: delegan en el authorizer de su entidad o solo se alcanzan desde pantallas ya protegidas.

## Persistencia y migración

- Mapping: `Account.AccountFeatures.orm.xml` (embeddable con cinco `boolean nullable="false"`) y `<embedded name="features" ... column-prefix="feature_">` en `Account.Account.orm.xml`. Columnas de `account`: `feature_account_trip_calculator`, `feature_property_trip_calculators`, `feature_events`, `feature_compensation`, `feature_organizational_carbon_footprint`.
- Migración **escrita por el usuario**: `migrations/Version20261104124143.php`. `up()` añade las cinco columnas `TINYINT(1) NOT NULL` **sin `DEFAULT`** y ejecuta `UPDATE account SET ... = 1` para todas las cuentas; `down()` las elimina. Difiere de `migration.sql` de esta carpeta (que usaba `DEFAULT 1`): manda la migración de `migrations/`. Si esa versión ya se ejecutó en algún entorno antes de añadirse el `UPDATE`, hay que lanzarlo a mano allí. No verificado en este manual si ya está aplicada en algún entorno.

## Configuración y operación

- `services.yaml` de `Security` (`:51`): `AccountFeaturesPolicy` lleva la etiqueta `kernel.reset` (método `reset`) para vaciar su caché entre peticiones en procesos de larga vida (workers de FrankenPHP/Messenger). La caché es un `array` por `accountId`, incluye las cuentas inexistentes.
- Sin variables de entorno, colas ni comandos nuevos. Sin API ni consola: no se pueden editar por esas vías.
- Efecto operativo: desactivar `Events`, `AccountTripCalculator` o `PropertyTripCalculators` invalida los QR ya impresos.

## Cómo extenderlo

Para añadir una sexta funcionalidad:
1. Añadir el caso a `AccountFeature` y su booleano a `AccountFeatures` (constructor, `isEnabled()`, getter, `equals()`), a `AccountFeaturesChanged` (campo, payload, `rebuildFromPayload()`) y a `Account::changeFeatures()`/`applyAccountFeaturesChanged()`.
2. Mapping del embebido, migración manual con `UPDATE` a 1 para las cuentas existentes, `UpdateAccountRequest`, `UpdateAccountService::updateFeatures()`, `UpdateAccountType`, `EditController` y plantilla de edición.
3. Etiqueta en `templates/security/translations/accountFeature.html.twig`, entrada en el mapa de `security.accountFeaturesChanged.html.twig` y `make translation`.
4. Condicionar con `currentAccountHasFeature()` la rama de Propietario de cada authorizer afectado, `NodeLister::toOwners()` y las plantillas con `account_feature_enabled()`. Comprobar con `grep -rn "Role::Owner" src/*/Application --include=*Authorizer.php`.
5. Si hay enlaces públicos o servicios sin authorizer (resolución de token, opciones de compensación, controladores que pintan formularios), cortar en el servicio o controlador.

Trampas: un authorizer nuevo con constructor propio debe recibir `?AccountFeaturesPolicy` y pasarlo a `parent::__construct(..., $accountFeaturesPolicy)` (como `TripCalculationLinkAuthorizer`); sin política devuelve `true` en silencio. `RegisterTrait` (`src/Shared/Infrastructure/UI/Web/CRUD/RegisterTrait.php`) captura `UnauthorizedActionException` y responde 200 «Error inesperado» en lugar de 403 (deuda previa).

## Tests

- Unitarios: `AccountFeaturesTest`, `AccountTest`, `AccountMother::withFeatures`, `AccountFeaturesPolicyTest` y `AccountFeaturesPolicyMother` (el `InMemoryAccountRepository` indexa por id), `UpdateAccountServiceTest`, un caso «sin la funcionalidad» en el test de cada authorizer de la tabla (más los nuevos `BaseYearDeclarationAuthorizerTest`, `PropertyAuthorizerTest`, `EmployeeContributionAuthorizerTest`, `OwnerDashboardAuthorizerTest`), `TripCalculationLinkAuthorizerTest`, `RegisterTripCalculationLinkServiceTest`, `GetTripCalculationResultsServiceTest`, `ListContributionOptionsServiceTest`, `QuoteContributionServiceTest`, `OwnerContributionContextResolverTest`, `GetAttendeeSurveyByTokenServiceTest`, `GetTripCalculationLinkByTokenServiceTest`, `GetOwnerDashboardServiceTest`, `NodeListerTest`.
- Aceptación (Behat): paso `Given account :id has feature :feature disabled` (`tests/Acceptance/Security/SecurityContext.php:488`) y `Then I do not see a link to :href` (`WebContext.php`); `account-edit.feature`, `account-features-menu.feature` (incluye impersonación), `global-search.feature`, `dashboard.feature` y los escenarios de denegación y de enlaces públicos en las features de cada área.
- Ejecución (Makefile): `make test-unit options="--filter AccountFeatures"`, `make test-acceptance options="tests/Acceptance/Security/account-features-menu.feature"`, `make test-parallel` para todo. El Makefile no define `test-this`.

## Desviaciones y deuda

- Declaradas en `## Implementación` de `plan.md`: ampliación de alcance (resultados de calculadora de propiedad, formulario de solicitud de créditos y datos de cobro con comprobación en GET), `AccountFeaturesPolicyMother`, paso Behat nuevo, `?? true` en `_forms.html.twig`.
- Posteriores a la implementación (`review.md`, todas aplicadas): corte en `QuoteContributionService` y en `ContributionCheckoutHandler` (A1, B3), `UPDATE` en la migración en lugar de `DEFAULT 1` (A2), caché y `kernel.reset` en la política (M1), mensaje de histórico para «todas desactivadas» (B1), tres escenarios nuevos (B2).
- Sin hallazgos abiertos en `review.md`. Deuda previa: `RegisterTrait` (arriba). Ver `plan.md` y `review.md` en esta carpeta.
