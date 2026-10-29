# Alcance 3 · Recogida de datos de empleados — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-28 · **Bounded Contexts:** `CarbonFootprint` (todo el cambio), `HumanResources` (lectura de `Employee`), `Property` (lectura de propiedades consolidadas), `Shared` (parcial de consentimiento)

## Qué hace y por qué

Las categorías 6 «Viajes de negocio» y 7 «Desplazamientos al trabajo» del alcance 3 de una huella de propiedad o de empresa solo admitían actividades tecleadas por el Propietario (`src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/Scope.php`, secciones `businessTravel`, `businessTravelStays`, `employeeCommuting`; `Scope::employeeSections()` y `Scope::employeeSources()`). Ahora existe un paso nuevo, **«Empleados»** (paso 5 de 7), en el que el Propietario elige empleados de la cuenta, indica el total de plantilla y el nivel de confianza, y la plataforma les envía por correo una encuesta pública por token. Las respuestas se agregan, se extrapolan al total de plantilla y se escriben como actividades `Mobility` y `OvernightStay` marcadas `fromEmployees` en el alcance 3, **sumándose** a las que el Propietario siga teniendo a mano.

Decisiones clave (tabla «Decisiones tomadas» del plan):

- Agregados propios, independientes de los de eventos: `OrganizationalEmployeeParticipation` (una por huella) y `OrganizationalEmployeeContribution` (una por empleado), identificando la huella con `CarbonFootprintReportSubjectType` + id en texto, como `OrganizationalProviderContribution`.
- Encuesta de dos pantallas con filas hijas (tramos y viajes), con tablas propias y sin JSON.
- Sincronización «sustituye todo»: cada vez se reescriben las actividades `fromEmployees` de las tres secciones en todos los bloques.
- En la huella de empresa, el empleado puede indicar su centro de trabajo; las actividades se atribuyen a ese bloque o a «Sin asignar a propiedad».

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/OrganizationalEmployeeParticipation/OrganizationalEmployeeParticipation.php` (+ `…Registered`, `…Updated`, `…Aggregated`, `…Id`, `…Repository`) | Total de plantilla, nivel de confianza, margen de error, nº de contestadas y `aggregatedAt` de una huella | Nuevo |
| Dominio | `…/OrganizationalEmployeeParticipation/OrganizationalEmployeeContributionsAggregator.php`, `…ContributionsAggregation.php` | Agregación y extrapolación por bloque | Nuevo |
| Dominio | `…/OrganizationalEmployeeContribution/OrganizationalEmployeeContribution.php` (+ eventos `Registered`, `CommutingAnswered`, `BusinessTravelAnswered`, `Answered`, `Ignored`, `Unignored`, `NotificationResent`, `ConsentGranted`; hijos `…Commute`, `…Trip`; DTO `CommuteAnswer`, `TripAnswer`; `…StepStatus`; excepciones `…ClosedException`, `…IgnoredException`, `…TokenInvalidException`) | Aportación de un empleado | Nuevo |
| Dominio | `…/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatus.php` | Estado `EmployeesAnswered` (`employees_answered`) entre `ProvidersAnswered` y `Completed`; `stepNumber()` 1-7 | Modificado |
| Dominio | `…/PropertyCarbonFootprint/PropertyCarbonFootprint.php`, `…/CompanyCarbonFootprint/CompanyCarbonFootprint.php` (+ `…ActivityAdded`, `…Activity`) | `answerEmployees()`, `ensureCanBeCompleted()`, `from_employees` en las actividades | Modificado |
| Dominio | `…/Mobility/MobilityEmissionFactors.php`, `…/OvernightStay/OvernightStayEmissionFactors.php`, `…/Activity/Answer/{MobilityAnswer,OvernightStayAnswer}.php` | `nearestYearForGroup()` y `emissionFactorYear` opcional en las respuestas | Modificado |
| Aplicación | `src/CarbonFootprint/Application/OrganizationalEmployeeContribution/AssignEmployees/AssignOrganizationalEmployeesService.php` | Guardado del paso «Empleados» | Nuevo |
| Aplicación | `…/Sync/SyncOrganizationalEmployeeActivitiesService.php`, `EmployeeEmissionFactorYears.php`, `…Response.php` | Agregación y escritura de actividades | Nuevo |
| Aplicación | `…/Register`, `Resend`, `Ignore` (incluye `Unignore*`), `List`, `Show`, `GetByToken`, `AnswerCommuting`, `AnswerBusinessTravel` | Casos de uso | Nuevo |
| Aplicación | `…/OrganizationalEmployeeContributionAuthorizer.php`, `…Authenticator.php`, `…Authentication.php`, `…ContributionsOverview.php`, `EmployeeContributionEmissionsCalculator.php` | Autorización, token, vista agregada y emisiones por fuente/bloque | Nuevo |
| Aplicación | `Application/{Property,Company}CarbonFootprint/Complete/Complete*Service.php`, `…/GetScope/*`, `Application/Activity/Answer/{Mobility,OvernightStay}AnswerHandler.php`, `…/CarbonFootprintReport/Data/OrganizationalCarbonFootprintReportLinesBuilder.php` | Completar sincroniza antes de calcular; el alcance 3 devuelve `employeeContributions`; el informe incluye las líneas | Modificado |
| Infraestructura | `Infrastructure/UI/Web/{Property,Company}CarbonFootprint/Step/EmployeesController.php` | Paso «Empleados» | Nuevo |
| Infraestructura | `Infrastructure/UI/Web/OrganizationalEmployeeContribution/{Show,Resend,Ignore,Unignore}Controller.php`, `…Routes.php`, `GetOrganizationalEmployeeContributionFromIdTrait.php` | Acciones sobre aportaciones (una clase, dos rutas) | Nuevo |
| Infraestructura | `Infrastructure/UI/Web/Public/OrganizationalEmployeeContribution/{Entry,Commuting,BusinessTravel,Answered}Controller.php` + trait | Encuesta pública | Nuevo |
| Infraestructura | `Infrastructure/Notification/OrganizationalEmployeeContributionNotifier.php` | Correo y notificación interna | Nuevo |
| Infraestructura | `Infrastructure/Persistence/Doctrine/Mapping/OrganizationalEmployee*.orm.xml`, `Repository/DoctrineOrganizationalEmployee*Repository.php`, `Type/…IdType.php`, `Persistence/InMemory/Repository/InMemoryOrganizationalEmployee*Repository.php`, `config/packages/doctrine.yaml` | Persistencia | Nuevo / modificado |
| Plantillas | `templates/carbon_footprint/{property,company}_carbon_footprints/step/employees.html.twig`, `…/wizard-layout.html.twig`, `…/mitigation-plan.html.twig`, `…/results.html.twig` | Paso y asistente de 7 pasos | Nuevo / modificado |
| Plantillas | `templates/carbon_footprint/organizational_carbon_footprints/employee_contributions/show.html.twig`, `_scope_3_employee_contributions.html.twig`, `_scope_3_sections.html.twig` | Detalle de aportación y tarjeta «Datos de empleados» | Nuevo / modificado |
| Plantillas | `templates/carbon_footprint/public/organizational_employee_contribution/*`, `emails/organizational_employee_contribution_requested.html.twig`, `templates/shared/form/_consent_field.html.twig` | Encuesta, correo y consentimiento con `consent_purpose` | Nuevo / modificado |
| Plantillas | `templates/carbon_footprint/translations/{organizationalEmployeeContributionMessages,employeeContributionMessages,providerContributionMessages,employeeContributionStatus}.html.twig` | Plantillas solo de extracción de traducciones | Nuevo |
| Config | `config/packages/security.yaml:76` | `PUBLIC_ACCESS` para el prefijo público | Modificado |
| Migración | `migrations/Version20261028153405.php` (origen: `migration.sql` del plan) | Esquema | Aplicada |

## Modelo de dominio

**`OrganizationalCarbonFootprintStatus`**: `started → scope_1_answered → scope_2_answered → scope_3_answered → providers_answered → employees_answered → completed` (`stepNumber()` 1..7). `advancedTo()` nunca retrocede. Con el alcance 3 excluido por completo, `answerScope(Scope3)` deja la huella directamente en `employees_answered` (igual que con proveedores) y el asistente oculta «Proveedores» y «Empleados» (`providersStepShown = not scope3Excluded` en `wizard-layout.html.twig`, `wizard_active` descuenta 2 pasos). Reabrir vuelve a `employees_answered`. `PropertyCarbonFootprint::ensureCanBeCompleted()` (`PropertyCarbonFootprint.php:286`) exige huella editable y estado `EmployeesAnswered`; `complete()` lo reutiliza. Mismo método en `CompanyCarbonFootprint`.

**`OrganizationalEmployeeParticipation`**: una por huella (índice único `subject_type, carbon_footprint_id`). Campos: `totalEmployees`, `confidenceLevel` (`ConfidenceLevel`), `marginOfError`, `answeredCount`, `aggregatedAt`. Eventos `Registered`, `Updated` (`update(total, confidence)`), `Aggregated` (`recordAggregation(answeredCount, marginOfError)`, `OrganizationalEmployeeParticipation.php:124`).

**`OrganizationalEmployeeContribution`** (`OrganizationalEmployeeContribution.php`): una por (huella, empleado). Campos: `subjectType`, `carbonFootprintId`, `accountId`, `employeeId`, `employeeName` (copia), `token` (64 hex, `generateToken()`), `status` (`EmployeeContributionStatus`: `Pending`/`Answered`/`Ignored`, reutilizado de eventos), `stepStatus` (`Started`/`CommutingAnswered`/`Completed`), `workplacePropertyId`, `commutingWeeksPerYear`, `ConsentProof` embebido, y las colecciones hijas `commutes` y `trips`.

Invariantes que valida el agregado (todas lanzan `\InvalidArgumentException`; los mensajes están en español porque se traducen en el controlador):

- `answerCommuting` (`:181`): semanas al año 1–53 si hay tramos; km por trayecto > 0; días por semana 1–7; ocupantes 1–`MAX_VEHICLE_OCCUPANTS` (100). Sin tramos, las semanas se guardan a `null`.
- `answerBusinessTravel` (`:212`): km > 0; nº de viajes 1–`MAX_TRIPS` (1000); ocupantes 1–100; noches de hotel 0–`MAX_HOTEL_NIGHTS` (366). Tras guardar llama a `answer()` y la aportación pasa a `Answered`.
- `ignore()` / `unignore()` idempotentes; `unignore` devuelve a `Answered` si hay `answeredAt`, si no a `Pending` (`:367`).
- Los eventos `…CommutingAnswered` y `…BusinessTravelAnswered` reemplazan por completo los hijos (`clear()` + alta).

Excepciones: `…TokenInvalidException` y `…IgnoredException` (404 en la web) y `…ClosedException` (409, huella completada).

**Agregación** (`OrganizationalEmployeeContributionsAggregator::aggregate`, `:17`): solo recibe las aportaciones `Answered`. Para cada una, bloque = `workplacePropertyId` si está en la lista de bloques admitidos, si no `''` (sin asignar).

- Desplazamientos: `FactorBasis::kmCoveringOneOccupant(distanciaKm × 2 × díasPorSemana × semanasAlAño, ocupantes)` por movilidad y bloque (`:24`). Las movilidades desconocidas se tratan como `PerPassenger`.
- Viajes: `kmCoveringOneOccupant(km × nºViajes, ocupantes)`; noches = `hotelNights × nºViajes` (`:30-33`).
- Extrapolación: factor `totalEmployees / contestadas` (`:54`) aplicado a todos los bloques y magnitudes.
- Sin contestadas devuelve agregación vacía.

## Flujo de una operación

### Guardar el paso «Empleados»

`GET|POST /property-carbon-footprints/{id}/step/employees` (`property_carbon_footprints_employees`) y `/company-carbon-footprints/{id}/step/employees` → `Step/EmployeesController` →

1. `denyAccessUnlessGranted(Edit)`; si el estado es anterior a `providers_answered`, redirige al paso que toque; si `scope3Excluded()`, a medidas (si ya `employees_answered`) o a alcance 3.
2. En POST: huella completada → flash `warning` y redirige a resultados; CSRF `employees_<id>`; `totalEmployees < 1` → flash `danger`; ids de empleado de `employees[employeeIds][]`; `confidenceLevel` por `ConfidenceLevel::tryFrom` (95 por defecto).
3. `AssignOrganizationalEmployeesService::__invoke` (`AssignOrganizationalEmployeesService.php`): autoriza vía `OrganizationalCarbonFootprintLocator` (`Edit`) y `ensureIsEditable()`; exige `totalEmployees >= max(1, seleccionados)` (`:57`); cada empleado debe existir, no estar eliminado y ser de la cuenta; descarta las aportaciones de los desmarcados, reactiva las de los remarcados, registra las nuevas con `RegisterOrganizationalEmployeeContributionService` (genera el token y publica `…Registered`); crea o actualiza la participación; invoca `SyncOrganizationalEmployeeActivitiesService`; llama a `answerEmployees()` (`:120`) y guarda.
4. `OrganizationalEmployeeContributionNotifier` (suscriptor de `…Registered` y `…NotificationResent`) envía el correo con `EmailSender` en el locale del usuario vinculado, si lo hay, y una notificación interna si el empleado está vinculado a un usuario. Asunto «Aporta tus desplazamientos y viajes de {year} para {subject}».
5. El controlador traduce la respuesta en flashes: `overnightStayCatalogMissing` (`warning`), `skippedMobilities` (`warning`) y `fallbackYears` (`info`); redirige a `…_mitigation_plan`.

### Sincronización de actividades

`SyncOrganizationalEmployeeActivitiesService` (`Sync/…Service.php`):

1. `Edit` sobre la huella (`:64`). Sin participación devuelve respuesta vacía; con ella exige `ensureIsEditable()`.
2. Toma las aportaciones `Answered`, calcula `N = max(totalEmployees ?? n, n)` y el margen de error con `MarginOfError::calculate(n, N, confianza ?? 95)` (nulo si n = 0); registra la agregación (`:87`).
3. Fuentes excluidas (`:90`): todas si el alcance 3 está excluido; si no, las de `Scope::Scope3->employeeSources()` marcadas como excluidas. Las secciones de fuentes excluidas no se escriben (`employeeSections` filtradas). Si no queda ninguna sección, devuelve sin tocar actividades.
4. Bloques admitidos: en empresa, las propiedades consolidadas (`CompanyProperties::consolidated`); en propiedad, ninguno. `blocksToSync()` (`:150`) añade `''` y los bloques que ya tienen actividades `fromEmployees` de una propiedad que dejó de ser consolidada, para que la sustitución las alcance.
5. Por bloque, `StageAnswersSynchronizer::sync(..., fromEmployees: true, propertyId: bloque)` con `MobilityAnswer` por movilidad con km > 0 y un `OvernightStayAnswer` con el primer `OvernightStay` de `CategoryOvernight::OrganizingTeam` (`defaultOvernightStay`, `:246`). Sin pernoctación de ese tipo no se generan noches y la respuesta lleva `overnightStayCatalogMissing`.
6. **Año del factor** (`EmployeeEmissionFactorYears`): para cada movilidad (y para la pernoctación por defecto) busca el factor del grupo de la huella con `nearestYearForGroup()` (año exacto, si no el más cercano; a igual distancia, el anterior). Si el año difiere del de la huella, se pasa `emissionFactorYear` en la respuesta y se informa en `fallbackYears`; si no hay ningún factor del grupo, la movilidad se omite y entra en `skippedMobilities`.

Efecto: se reemplazan por completo las actividades `fromEmployees` de `employeeCommuting`, `businessTravel` y `businessTravelStays`; las del Propietario y las de proveedores no se tocan (`ActivityPlacement::equals()` distingue `fromEmployees` y `propertyId`).

### Completar la huella

`Complete{Property,Company}CarbonFootprintService`: autoriza, llama a `ensureCanBeCompleted()` (`CompletePropertyCarbonFootprintService`, tras el `Edit`), **después** sincroniza empleados (para incluir respuestas posteriores al último «Siguiente»), recarga la huella, calcula el desglose y completa. Si no está en `employees_answered`, el guard lanza antes de que haya efectos.

### Acciones sobre aportaciones

`ShowController`, `ResendController`, `IgnoreController`, `UnignoreController` (`Infrastructure/UI/Web/OrganizationalEmployeeContribution/`): dos `#[Route]` por clase con `defaults: subjectType` (`property`/`company`), nombres `*_carbon_footprints_employees_{show,resend,ignore,unignore}`. Las tres acciones con efectos llevan `#[CsrfProtected('resend'|'ignore'|'unignore')]` (el id que generan los componentes `List:Action:*`). Con la huella completada, flash `warning` y no se ejecutan. Descartar o reactivar **no** resincroniza las actividades: el efecto llega en el siguiente «Siguiente» o al completar (decisión aceptada, `review.md` Q1).

### Encuesta pública

Prefijo `/{locale}/carbon-footprint/organizational-employee-contribution/{token}`:

| Ruta | Controlador | Comportamiento |
|---|---|---|
| `public_organizational_employee_contribution` | `EntryController` | Autentica el token y redirige a `stepStatus->activeStepRouteName()` |
| `…_commuting` | `CommutingController` | GET/POST. CSRF `employee_contribution_commuting_<token>`. Exige consentimiento (`ConsentRequestReader::accepted()`); filas sin medio o con km ≤ 0 se ignoran; `noCommuting` envía lista vacía |
| `…_business_travel` | `BusinessTravelController` | Exige `stepStatus` ≥ `CommutingAnswered`, si no redirige a desplazamientos. CSRF `employee_contribution_business_travel_<token>`; `noTrips` envía lista vacía |
| `…_answered` | `AnsweredController` | Agradecimiento |

Todos aplican los limitadores `employeeContributionTokenLimiter` / `employeeContributionInvalidTokenLimiter` (`config/packages/rate_limiter.yaml`, reutilizados de eventos) mediante `OrganizationalEmployeeContributionPublicControllerTrait`: 429, token inválido o descartada → 404 (`invalid.html.twig`), huella completada en un POST → 409 (`closed.html.twig`). Los servicios `AnswerOrganizationalEmployeeContributionCommutingService` / `…BusinessTravelService` autentican por token (sin `User`), comprueban `isCompleted()`, validan que el centro de trabajo sea propiedad consolidada de la empresa (en huella de propiedad lo rechazan) y guardan el consentimiento (`ConsentProof` con versión de política, locale, IP, user agent) solo la primera vez. La encuesta se puede volver a abrir y modificar mientras la huella siga abierta.

## Autorización y ámbito

- Pantallas del Propietario: el `Edit` de la huella (`PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer`, rol `Owner` de la cuenta actual) más `OrganizationalEmployeeContributionAuthorizer` (`Owner` de la cuenta actual vía `userHasRole` y `currentAccountId`, es decir, respeta la impersonación). Los controladores del paso llaman `denyAccessUnlessGranted(Edit)`; Admin y User no acceden.
- El enlace «Registrar empleado» solo se muestra si no es solo lectura y `isGranted(Register, new Employee(...))` (`canRegisterEmployee`).
- Flujo público: solo token; sin sesión. `config/packages/security.yaml:76` abre el prefijo como `PUBLIC_ACCESS`. El branding de la cuenta se aplica con `BrandedPublicControllerTrait::brandFor()`.
- No hay feature flag.

## Persistencia y migración

Migración **aplicada**: `migrations/Version20261028153405.php` (el `migration.sql` del plan es su origen). Contenido:

| Tabla | Notas |
|---|---|
| `organizational_employee_participations` | índice único `(subject_type, carbon_footprint_id)` |
| `organizational_employee_contributions` | `token` único; índice `(subject_type, carbon_footprint_id)`; columnas `consent_*` del embebido `ConsentProof` |
| `organizational_employee_contribution_commutes` | FK a la aportación, `ON DELETE CASCADE` |
| `organizational_employee_contribution_trips` | ídem |
| `property_carbon_footprint_activities.from_employees`, `company_carbon_footprint_activities.from_employees` | `TINYINT(1) NOT NULL DEFAULT 0` |

No hay backfill de estado: las huellas que estaban en `providers_answered` deben pasar por «Empleados» antes de completarse.

Tipos Doctrine de los ids en `config/packages/doctrine.yaml:106-109`. Repositorios: `DoctrineOrganizationalEmployee{Contribution,Participation}Repository` e `InMemory…` para tests.

## Configuración y operación

- Sin variables de entorno, colas ni comandos de consola nuevos. El correo se envía directamente con `EmailSender` desde el suscriptor (no hay `DeferredCommand`).
- Limitadores: `employee_contribution_token` (1000/min) y `employee_contribution_invalid_token` (10/min), compartidos con la encuesta de empleados de eventos.
- Versión de política de consentimiento: argumento `consentPolicyVersion` del servicio `AnswerOrganizationalEmployeeContributionCommutingService`.
- `_consent_field.html.twig` acepta `consent_purpose` (por defecto «del evento»); estas encuestas pasan «de la organización».
- Traducciones: se rellenaron ES, EN y TR. Los mensajes de excepción de dominio se traducen con `new TranslatableMessage($e->getMessage())` y las plantillas `templates/carbon_footprint/translations/*Messages.html.twig` existen solo para que `make translation` extraiga esas cadenas (los mensajes con `sprintf` no se extraen). Tras añadir un mensaje nuevo en el dominio hay que añadirlo a esas plantillas.

## Cómo extenderlo

- **Añadir una pregunta a la encuesta** (p. ej. otro dato del tramo): 1) campo en `CommuteAnswer` / `TripAnswer` y en la entidad hija con su mapping y migración; 2) validación en `answerCommuting` / `answerBusinessTravel`; 3) el evento de respuesta lo transporta y el `apply…` lo copia; 4) campo en la macro de fila de `commuting.html.twig` / `business_travel.html.twig` y lectura en el controlador público; 5) uso en el agregador; 6) detalle en `show.html.twig`; 7) mensajes en `organizationalEmployeeContributionMessages.html.twig`.
- **Otra sección alimentada por empleados**: añadirla a `Scope::employeeSections()` / `employeeSources()` y a `sectionAnswers()` del servicio de sincronización; la tarjeta «Datos de empleados» se activa en `_scope_3_employee_contributions.html.twig` por fuente.
- **Trampas**: la sincronización sustituye todo; una actividad `fromEmployees` editada a mano se pierde. El sincronizador no mira exclusiones, por eso el servicio filtra las secciones. Las aportaciones se identifican por huella con id en texto, sin FK. Una propiedad que deja de ser consolidada produce un bloque «huérfano» que `blocksToSync()` limpia, pero las aportaciones que la elegían pasan a «Sin asignar» en la siguiente sincronización.

## Tests

- **Unitarios** (`tests/Unit/CarbonFootprint/…`): `Domain/Model/OrganizationalEmployeeContribution/` (invariantes, límites superiores, repositorio), `Domain/Model/OrganizationalEmployeeParticipation/` (agregador con el ejemplo del plan: 33.120 / 22.080 / 3.600 km y 6 noches; `PerVehicle` dividido por ocupantes), `Application/OrganizationalEmployeeContribution/{AssignEmployees,Sync,List,Show,AnswerCommuting,AnswerBusinessTravel,Register,Resend,Ignore,GetByToken}/`, `…Authorizer`/`…Authenticator`, y `Application/{Property,Company}CarbonFootprint/Complete/`. Año de factor más cercano: `MobilityTest`, `OvernightStayTest`.
- **Behat** (`tests/Acceptance/CarbonFootprint/`): `property-carbon-footprint-employees-step.feature`, `company-carbon-footprint-employees-step.feature`, `event-carbon-footprint-employees-step.feature` (mensaje traducido de «total menor»), `organizational-employee-contribution-public.feature`, `organizational-employee-contribution-sync.feature`. Pasos auxiliares en `CarbonFootprintContext.php`, `MailContext.php` y `HumanResourcesContext.php`.
- **Ejecución:** `make test-unit options="--filter=OrganizationalEmployee"` y `make test-acceptance options="tests/Acceptance/CarbonFootprint/property-carbon-footprint-employees-step.feature"`. Hay también `make test-parallel`. El `Makefile` no define `test-this`, aunque `CLAUDE.md` lo menciona.
- Sin huecos abiertos conocidos.

## Desviaciones y deuda

Del apartado `## Implementación` de [`plan.md`](plan.md) y de [`review.md`](review.md):

- Los ids de CSRF de las acciones son `resend` / `ignore` / `unignore` (los de los componentes de lista), no `organizational_employee_contribution_<acción>`.
- Las filas de tramos y viajes replican el markup mínimo del embed `Collection/Table` con el controller Stimulus `collection`, mediante macros, porque el embed falla fuera de un Twig Component.
- `ListOrganizationalEmployeeContributionsService` devuelve directamente `OrganizationalEmployeeContributionsOverview` (sin `Response` propio).
- Contradicciones plan/código resueltas en la review (el código manda): completar solo sincroniza tras `ensureCanBeCompleted()` (B4, B13); el badge de estado usa el dominio de traducción `employeeContributionStatus` (B8); se eliminaron los servicios `AnswerXxxCarbonFootprintEmployees` (B10).
- La review no deja hallazgos abiertos: A1–B13 corregidos o descartados (B11 descartado: no alcanzable con los datos actuales). Q1 aceptada por el usuario.
