# Rama biohydra — base común para waste, food-loss y events — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-14 · **Bounded Contexts:** `Property` (nuevo), `HumanResources` (nuevo), `Security`, `Shared`, `DataExchange`, `Notifications`, `Library`
- **Adjuntos del plan:** `manifest.tsv` (origen y acción por fichero), `fragments.md` (fragmentos de los ficheros de registro), `migration.sql`

## Qué hace y por qué

waste, food-loss y events (los tres proyectos Bioscore sobre hydra) repetían mucho código que hydra no tiene: consultores, SSO de Bioscore, el BC `Property`, empresas, empleados, progreso de trabajos y exportación PDF, entre otros. La rama `biohydra` es una capa intermedia (`master → biohydra → {waste, food-loss, events}`) que reúne ese código común. Así una mejora común se hace una vez y llega a los tres proyectos con un merge.

No se diseñó código nuevo: se trasplantó código existente. El 99,9 % de los ficheros son copia **byte a byte** del blob de `origin/events` (E) o de `origin/food-loss` (F), para que el primer merge en cada proyecto no tenga conflictos en los ficheros que ya coinciden (invariante 3 del plan). En la revisión, 787 de los 788 ficheros `copy` resultaron idénticos a su origen. La excepción pactada es `tests/Acceptance/Shared/WebContext.php` (`review.md`, revisión 1).

Los ficheros de registro centralizados (`NodeLister`, `config/*.yaml`, `behat.yml.dist`, traducciones, `.env*`, `Makefile`, `composer.json`, `phpstan.dist.neon`) se modificaron solo con los fragmentos de `fragments.md`. Las decisiones de alcance están justificadas en la tabla *Decisiones tomadas* de `plan.md`: versión de `Property` de E, plantillas de admin de F, HR de E más el suscriptor de F, exclusión de la marca blanca y de los extras de `Account` de events, y una migración idempotente.

> **Regla de mantenimiento clave.** Un fichero `copy` no se edita en biohydra. Se corrige en la rama de origen (events, salvo que `manifest.tsv` diga F) y se vuelve a copiar con `git show origin/<rama>:<ruta> > <ruta>`. Así se conserva la identidad de blob. `make cs-fix` y `make rector` no se pasan sobre ficheros `copy` (`plan.md`, *Restricciones*).

## Mapa de piezas

Resumen por áreas. El inventario completo, fichero a fichero, está en `manifest.tsv`: 826 filas, de las que 788 son `copy`, 35 `fragment`, 2 `strip` y 1 `delete`.

| Área | Ficheros principales | Rol | Estado |
|---|---|---|---|
| BC Property | `src/Property/**` (298), `templates/property/**`, `translations/propertyType+intl-icu.{es,en}.xlf` | Propiedades, empresas, categorías, marcas, países y destinos. Web de admin y de owner, API, consola e importación por fuente externa | Nuevo (E; F en las plantillas de listado y en `_form` de admin) |
| BC HumanResources | `src/HumanResources/**`, `templates/human_resources/**` | Empleados por cuenta y vinculación automática con usuarios | Nuevo (E + `LinkEmployeeOnUserRegisteredSubscriber` de F) |
| Consultores | `src/Security/Application/Account/{ConsultantPolicy,ImpersonationPolicy}.php`, `AssignConsultant/`, `ChangeConsultantStatus/`, `src/Security/Domain/Model/Account/Account.php`, `ConsultantAccountChoiceType`, `Infrastructure/UI/Web/Admin/Account/Bulk/AssignConsultant.php`, `Infrastructure/UI/Api/Account*` | Cuentas consultoras, consultora asignada y suplantación limitada a las cuentas asignadas | Nuevo o modificado (E; F en la familia `Account`) |
| Impersonación | `src/Shared/Application/CurrentImpersonation/CurrentImpersonationProvider.php`, `src/Security/Infrastructure/Symfony/Security/CurrentImpersonation.php`, `src/Security/Application/AbstractAuthorizer.php` | Saber si el usuario está suplantando una cuenta (`isImpersonating`) | Nuevo o modificado |
| SSO Bioscore | `src/Security/Infrastructure/Sso/Bioscore/*`, `src/Security/Infrastructure/UI/Api/LoginSso/*` | Proveedor `bioscore` del `SsoProvider` existente y login SSO por API | Nuevo |
| Exportación PDF | `src/DataExchange/Infrastructure/Persistence/Writer/{TcpdfExportWriter,DelegatingExportWriter,BrandedPdfBuilderTrait}.php`, `Domain/Model/Exportation/ExportationFormat.php` | Formato `pdf` en las exportaciones | Nuevo o modificado |
| JobProgress | `src/Shared/Domain/Model/JobProgress/*`, `src/Shared/Application/JobProgress/TrackedCommandHandler.php`, `src/Shared/Application/CommandBus/TrackedCommand.php`, `src/Shared/Infrastructure/UI/Web/JobProgress/JobProgressController.php`, `assets/controllers/job_progress_controller.js`, `templates/components/JobProgress.html.twig` | Progreso de trabajos largos, consultado por *polling* | Nuevo (sin emisores en biohydra) |
| Geo / Places | `src/Shared/Domain/Model/Geo/PlaceFinder.php`, `src/Shared/Infrastructure/Geo/*` (Google, Cached, Null), `src/Shared/Infrastructure/UI/Web/Place/SuggestController.php`, `PlaceReferenceType` | Sugerencia y resolución de lugares (campo «Ubicación exacta») | Nuevo |
| Gemini | `src/Shared/Infrastructure/AI/Gemini/*` | Cliente de Google Gemini | Nuevo (sin consumidores en biohydra) |
| Utilidades Shared | `Address`, `MarkdownExtension` (`md_to_html`), `CurrentUserContext`, `Email/EmailAttachment`, `Locale::listFrom()`, filtro Twig `enum_domain`, `FilterTrait::filter(..., $formOptions)`, layout `wizard.html.twig` y componente `StepList`, nuevas acciones de lista | Piezas genéricas que necesitan los proyectos | Nuevo o modificado |
| Menú | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php`, `assets/icons/solar/*` (7 SVG) | Grupo «Propiedades» y renombrado de «Seguridad» a «Administración» con «Empleados» | Modificado |
| Registro | `config/{services,services_test,routes}.yaml`, `config/packages/{doctrine,twig,rate_limiter,security,routing,framework,nelmio_api_doc}.yaml`, `behat.yml.dist`, `phpstan.dist.neon`, `Makefile`, `composer.json`, `.env*` | Alta de los BCs nuevos y configuración | Modificado (fragmentos) |
| Marca | `assets/images/*`, `assets/styles/variables/*`, `assets/styles/email.css`, `COMPANY_NAME` | Marca Bioscore | Modificado |
| Baja | `src/DataFixtures/AppFixtures.php` | Placeholder eliminado (la carpeta queda vacía; las fixtures viven en `fixtures/UserFixtures.php`, namespace `App\Fixtures`, `composer.json:100`) | Borrado |
| Migraciones | `migrations/Version20260909120214.php` (consultor), `migrations/Version20261014081026.php` (idempotente) | Esquema nuevo | Nuevo (creadas por el usuario) |

## Modelo de dominio

### Property (`src/Property/Domain/Model/`)

- **`Property`**: `name`, `accountId` (opcional), `companyId`, `numberOfEmployees`, `dailyClients`, el embebible `PropertyInfo` y el embebible `ExternalId`. El borrado es lógico (`removed`).
  - `PropertyInfo` lleva tipo (enum `PropertyType`, por defecto `Hotel`; `PropertyInfo.php:15`), categoría, marca, dirección, código postal, latitud y longitud, habitaciones, zona, web, destino, país, `placeReference` y `placeLabel`.
  - `ExternalId` lleva los identificadores `hotelbeds`, `restel` y `bioscore`.
  - Eventos: `PropertyRegistered`, `PropertyUpdated`, `PropertyMatched`, `PropertyUnmatched`, `PropertyAccountReleased` y `PropertyRemoved`.
  - `releaseAccount()` pone `accountId = null` (`Property.php:143-154, 211-214`).
  - `match()` combina la información y los identificadores externos, conservando los valores existentes no vacíos (`Property.php:131-141, 180-196`; `PropertyInfo::combine`).
- **`Company`**: nombre, CIF (`vatNumber`), cuenta y `Address` embebida (`src/Shared/Domain/Model/Address.php`).
- **`Category`, `Brand`, `Country` y `Destination`**: nombre e identificadores externos.
  - `Country` añade `iso`, `vatRate` y `europeanUnion`. Hoy solo se editan y se listan: nada en `src/` los consume.
  - `Destination` pertenece a un país.
- **Invariantes de empresa**, validadas en `RegisterPropertyService.php:39-48` y `UpdatePropertyService.php:45-54`:
  - Una propiedad sin cuenta no puede tener empresa.
  - La empresa debe pertenecer a la cuenta de la propiedad.
  - Si cambia la cuenta de la propiedad, `companyId` se pone a `null` (`UpdatePropertyService.php:36-43`).
- **Ubicación**: `PropertyPlaceLocationSetter::resolve()` (`src/Property/Application/Property/PropertyPlaceLocationSetter.php:15-43`) contempla cuatro casos:
  1. Referencia vacía: se limpia el lugar y se respetan las coordenadas del formulario.
  2. Misma referencia que la guardada: no llama a Google.
  3. Referencia nueva: se resuelve con `PlaceFinder` y se sobrescriben `placeLabel`, latitud y longitud.
  4. Si no se puede resolver, lanza `InvalidArgumentException`.
- No hay restricciones de unicidad ni comprobaciones de «en uso» al eliminar categorías, marcas, países o destinos. Las propiedades conservan el id huérfano.

### HumanResources (`src/HumanResources/Domain/Model/Employee/`)

- **`Employee`**: nombre, email, teléfono, `Address`, `accountId` (obligatorio) y `userId` (opcional).
- Eventos: `EmployeeRegistered`, `EmployeeUpdated`, `EmployeeAndUserLinked`, `EmployeeAndUserUnlinked` y `EmployeeRemoved` (`Employee.php:88-198`). El borrado es lógico.
- Registrar sin cuenta lanza `EmployeeWithoutAccountException` (`RegisterEmployeeService.php:98-100`).

### Consultores (`src/Security/Domain/Model/Account/Account.php`)

- `Account` gana `consultant` (bool) y `consultantAccountId` (nullable).
- Eventos: `AccountConsultantStatusChanged` y `AccountConsultantChanged`. `AccountRegistered` lleva ahora `consultant`.
- Invariantes y excepciones (todas con código 409):
  - Una consultora no puede tener consultora: `ConsultantCannotHaveConsultantException`.
  - Solo se asigna una cuenta marcada como consultora: `AccountIsNotConsultantException` (`ConsultantPolicy::denyUnlessAssignable`, `ConsultantPolicy.php:18-29`).
  - No se desmarca una consultora con cuentas asignadas: `ConsultantHasAssignedAccountsException` (`ConsultantPolicy.php:31-40`).
- `ImpersonationPolicy` (`src/Security/Application/Account/ImpersonationPolicy.php`) decide qué cuentas puede suplantar cada usuario:
  - El administrador puede suplantar todas las cuentas activas.
  - El owner de una cuenta consultora activa puede suplantar las cuentas activas que tiene asignadas.
  - Para resolver la consultora usa el rol y la cuenta **propios** del `User` (líneas 63-80), no los efectivos. Es intencionado: así el consultor puede cambiar entre cuentas asignadas mientras suplanta (`review-fix.md`, Q1).

### JobProgress (`src/Shared/Domain/Model/JobProgress/`)

- Estados: `InProgress`, `Completed` y `Failed`. Eventos: `JobProgressAdvanced`, `JobProgressCompleted` y `JobProgressFailed`.
- `TrackedCommand(command, progressKey, progressTotal)` envuelve un comando. `TrackedCommandHandler` (`src/Shared/Application/JobProgress/TrackedCommandHandler.php:30-62`) inicia o reinicia el progreso, despacha el comando interno y marca el trabajo como completado o fallido.

## Flujo de una operación

### Alta de una propiedad (owner)

1. `GET|POST /{_locale}/properties/register` → `src/Property/Infrastructure/UI/Web/Property/RegisterController.php`.
   - Sin cuenta actual responde 403 «Debes pertenecer a una cuenta para poder acceder» (líneas 37-39).
   - Fuerza `accountId` a la cuenta actual, que puede ser la suplantada (línea 51).
2. El formulario `RegisterPropertyType` (`src/Property/Infrastructure/Symfony/Form/RegisterPropertyType.php:27-83`) incluye `PropertyInfoType` y, solo para el owner, `companyId`. `CompanyIdType` lista únicamente las empresas de la cuenta (`CompanyIdType.php:27-31`).
3. `RegisterPropertyService` sigue estos pasos:
   1. Llama a `PropertyAuthorizer` con `Action::Register`.
   2. Comprueba la invariante de empresa.
   3. Resuelve la ubicación con `PropertyPlaceLocationSetter` (`RegisterPropertyService.php:50`).
   4. Ejecuta `Property::register()` y persiste.
4. Se muestra el flash «Propiedad creada correctamente: {name}». Cualquier excepción muestra «Error inesperado» (`RegisterTrait.php:24`).

### Sugerencia de lugar

`PlaceReferenceType` es un autocompletado remoto contra `places_suggest`: `GET /{_locale}/places/suggest?query=&kind=` (`src/Shared/Infrastructure/UI/Web/Place/SuggestController.php:18-44`).

- Requiere `IS_AUTHENTICATED_FULLY`.
- Tiene el limitador `place_suggest` (120 por minuto, 500 en test; `config/packages/rate_limiter.yaml:11-14`). Al superarlo responde 429.
- `PlaceFinder` se resuelve como `CachedPlaceFinder` (solo cachea `resolve`, 30 días) sobre `GooglePlacesApiPlaceFinder`.
- Google Places solo está operativo con `GOOGLE_MAPS_ENABLED=true` y `GOOGLE_MAPS_API_KEY` informada (`.env:146-148`). Por defecto está desactivado.

### Borrados en cascada lógica (suscriptores)

- `src/Property/Domain/Event/Company/UnlinkCompanyOnCompanyRemovedSubscriber.php:27-44`: al recibir `CompanyRemoved`, pone `companyId = null` en las propiedades de esa empresa.
- `src/Property/Domain/Event/Property/ReleasePropertiesOnAccountRemovedSubscriber.php:34-44`: al recibir `AccountRemoved`, ejecuta `releaseAccount()` en todas las propiedades de la cuenta. La propiedad sigue en el catálogo, sin cuenta.
- `src/Security/Domain/Event/Account/ReleaseConsultantOnAccountRemovedSubscriber.php:34-44`: al eliminar una consultora, quita la consultora a las cuentas que la tenían asignada.
- Empleados:
  - `LinkEmployeeOnUserRegisteredSubscriber` (`src/HumanResources/Domain/Event/Employee/…:43-89`): al recibir `UserRegistered` con cuenta, busca un empleado de la cuenta con ese email. Si no existe, lo crea con nombre = email y lo vincula. Si existe sin usuario, lo vincula.
  - `LinkUserOnEmployeeEventSubscriber`: al registrar o editar un empleado sin usuario, lo vincula al usuario con ese email si es de la misma cuenta.
  - `UnlinkEmployeeOnUserRemovedSubscriber`: al eliminar un usuario, desvincula su empleado, que se conserva.

### Asignar consultora en bloque

`/{_locale}/admin/accounts` incluye la acción masiva `assign-consultant`, con la etiqueta «Asignar consultora» (`src/Security/Infrastructure/Symfony/Form/BulkAccountType.php`, `Bulk/AssignConsultant.php`). Por cada cuenta seleccionada despacha `AssignConsultantRequest` con `BulkTrait` (`src/Shared/Infrastructure/UI/Web/CRUD/BulkTrait.php`). Elegir «Sin consultora» quita la consultora.

### Edición de cuenta con errores de consultora

`src/Security/Infrastructure/UI/Web/Admin/Account/EditController.php:89-108` traduce las tres excepciones a errores de campo con texto en castellano fijo y añade el flash «El formulario contiene errores…».

### Exportación PDF

1. `ExportOptionsType` ofrece los formatos XLSX, CSV y PDF. La etiqueta es `strtoupper($format->value)` (`src/DataExchange/Infrastructure/UI/Web/Form/ExportOptionsType.php:53`).
2. `ExecuteExportationService` abre el writer con el nombre traducido del tipo como título (`ExportWriter::open(..., ?string $title)`).
3. El alias `ExportWriter` apunta a `DelegatingExportWriter` (`src/DataExchange/Infrastructure/Symfony/DependencyInjection/services.yaml`), que reparte así (`DelegatingExportWriter.php:63-73`):
   - XLSX y CSV van a `SpreadsheetWriter`.
   - PDF va a `TcpdfExportWriter`.
4. `TcpdfExportWriter` acumula filas y genera en `close()` un A4 apaisado con la marca de `BrandedPdfBuilderTrait`: logo, colores, fuentes Rubik y pie con título y paginación.

### SSO Bioscore (web)

1. El botón «Continuar con {provider}» de `templates/security/login.html.twig` (etiqueta `Bioscore`, `BioscoreSsoProvider.php:31-33`) lleva a `/login/sso/bioscore`.
2. `HttpBioscoreClient` redirige a `{SSO_URL}/{locale}/login-sso/{SSO_PLATFORM}`.
3. El *callback* `/login/sso/bioscore/callback?token=` lo atiende `SsoAuthenticator`, que llama a `BioscoreSsoProvider::authenticate()`. Este hace lo siguiente:
   1. Valida el token con `POST {SSO_API_URL}/sso-token/check`.
   2. Verifica la firma RS256 con la clave `SSO_JWT_PUBLIC_KEY` (`RsaBioscoreJwtDecoder.php`).
   3. Toma el *claim* `email`.
4. Empareja por email con un usuario existente, sin alta automática. Los mensajes de error salen de `translations/security.es.xlf:85-90`.

### SSO Bioscore (API)

`POST /api/login/sso` con `{username, token}` (`src/Security/Infrastructure/UI/Api/LoginSso/LoginSsoController.php`). Es público. Responde:

- Con éxito: `{"token": <JWT>}`.
- `401 Invalid SSO token.`: token no válido.
- `401 User not found.`: usuario inexistente.

## Autorización y ámbito

| Authorizer | Admin | SuperAdmin | Owner | User |
|---|---|---|---|---|
| `PropertyAuthorizer` (`src/Property/Application/Property/PropertyAuthorizer.php:14-26`) | Todo. `canExport = canList` (48-51) | Solo si además tiene `ROLE_ADMIN` | Sus propiedades (cuenta actual) | Nada |
| `CompanyAuthorizer` (`…/Company/CompanyAuthorizer.php:14-26`) | Todo | Todo | Sus empresas | Nada |
| `BrandAuthorizer`, `CategoryAuthorizer`, `CountryAuthorizer`, `DestinationAuthorizer` | Todo | Solo si además tiene `ROLE_ADMIN` | Nada (salvo autocompletado) | Nada (salvo autocompletado) |
| `EmployeeAuthorizer` (`src/HumanResources/Application/Employee/EmployeeAuthorizer.php`) | Todo | Todo | Sus empleados | Nada |
| `UserAuthorizer` (consultor) | — | — | Al suplantar como consultor: ve usuarios, pero no registra, no edita a otros, no elimina y no cambia roles (`UserAuthorizer.php:14-79, 111-117`) | — |

- `AbstractAuthorizer::userHasRole()` compara sin jerarquía. Por eso un SuperAdmin que no tenga `ROLE_ADMIN` explícito recibe 403 en Property y en los catálogos, aunque `NodeLister` sí le muestre el menú de admin (usa `isGranted`). Lo habitual es que los SuperAdmin tengan ambos roles.
- Al suplantar una cuenta, `CurrentRoles` devuelve `[Owner, User]` (`src/Security/Infrastructure/Symfony/Security/CurrentRoles.php:18-25`). El suplantador ve el menú y las pantallas de owner de esa cuenta, y las pantallas de admin le devuelven 403.
- Suplantar requiere `ACCOUNT_IMPERSONATION_ENABLED=true`. Su valor es `false` en `.env:84` y `true` en `TEST_CONT`.
- Las rutas de admin de empresas no comprueban `Action::Admin` en el controlador; dependen del authorizer. Un owner que conozca la URL `/admin/companies/edit/{id}` de una empresa propia puede editarla, igual que desde su pantalla.
- Los authorizers `RecordedEvent`, `SignatureRequest` y `Notification` pasan a usar `userHasRole`, así que un admin que suplanta pierde allí los permisos de admin.
- API: todas las rutas de `/api/{_locale}/properties*` usan JWT. `POST /properties/match` exige `Action::Admin`. `DELETE /properties/{id}/account` lo pueden usar el admin y el owner de la propiedad. La API de consultor (`/api/accounts/*`) exige `ROLE_ADMIN`.

## Persistencia y migración

- **Tablas nuevas:** `properties`, `companies`, `categories`, `brands`, `countries`, `destinations`, `employees` y `job_progresses`. Columnas nuevas en `account`: `consultant` y `consultant_account_id`. No hay índices ni FKs.
  - Mappings: `src/Property/Infrastructure/Persistence/Doctrine/Mapping/*.orm.xml`, `src/HumanResources/.../Mapping/Employee.Employee.orm.xml` y `src/Shared/.../Mapping/{JobProgress.JobProgress,Address}.orm.xml`.
- **Tipos Doctrine** (`config/packages/doctrine.yaml`): `country_id`, `destination_id`, `property_id`, `category_id`, `brand_id`, `company_id`, `job_progress_id`, `job_progress_key` y `employee_id`. Se añaden los mappings `Property` y `HumanResources`.
- **Migraciones:**
  - `migrations/Version20260909120214.php`: añade las columnas de consultor. Es byte-idéntica a la de los proyectos, donde ya está registrada.
  - `migrations/Version20261014081026.php`: idempotente.
    - Crea las tablas con `CREATE TABLE IF NOT EXISTS` / `hasTable()` y añade columnas con `hasColumn()`.
    - Rellena el IVA y el indicador UE de `countries` solo si acaba de añadir `european_union`.
    - Su `down()` lanza `IrreversibleMigration`.
  - Las dos están aplicadas en la base dev (`review.md`, revisión 1).
- Antes de aplicarlas en producción de cada proyecto, revisar `SHOW COLUMNS FROM properties` y `SHOW COLUMNS FROM countries` (`plan.md`, *Migración*).

## Configuración y operación

| Variable (`.env`) | Valor por defecto | Uso |
|---|---|---|
| `SSO_BIOSCORE_ENABLED` | `true` (:95) | Muestra el botón «Continuar con Bioscore» y habilita las rutas |
| `SSO_URL`, `SSO_API_URL` | `https://sostenibilidad.bioscore.info[/api]` | Redirección y validación del token |
| `SSO_PLATFORM` | `biohydra` (:128) | Segmento de la URL de login en Bioscore. Cada proyecto pone el suyo |
| `SSO_JWT_PUBLIC_KEY` | `%kernel.project_dir%/config/jwt/bioscore.pem` | Clave pública RS256. **No está versionada** (`.gitignore:38`): hay que provisionarla por entorno; sin ella todo login Bioscore falla |
| `GOOGLE_MAPS_ENABLED`, `GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_REGION` | `false`, vacío, `es` | Sugerencia de lugares |
| `GEMINI_API_KEY`, `GEMINI_MODEL_{LOW,MEDIUM,HIGH}`, `GEMINI_LOG_ENABLED` | placeholder, `gemini-2.5-flash`, `false` | Cliente Gemini (logs en `var/gemini-logs`) |
| `COMPANY_NAME` | `'Bioscore Sustainability'` (`.env.dev` conserva `'Dediez Software'`) | Marca |

- **Consola:**
  - `make sf c="property:import <source> <file> [--skip-*] [--create-*] [--offset-properties=N]"`: CSV separado por `;`. Fuentes `bioscore` y `restel`; `hotelbeds` no tiene mapeo. Sin `--create-*`, las filas no emparejadas se escriben en `var/*-unmatched.csv`.
  - `make sf c="property:property:load <source> <file>"`: alta directa, sin emparejado.
- **Rutas:** `config/routes.yaml` define `property-api` (`/api/{_locale}`), `property-web` y `human-resources-web` (`/{_locale}`).
- **Twig:** espacios de nombres `@property` y `@human_resources`.
- **OpenAPI:** área `mobile` en `config/packages/nelmio_api_doc.yaml`. `^/openapi/mobile/documentation` exige `ROLE_ADMIN`.
- **Entorno de desarrollo:**
  - `framework.yaml` desactiva `verify_peer`/`verify_host` (`when@dev`).
  - `compose.override.yaml` añade el contenedor `bioscore-proxy`. Solo puede funcionar en un checkout a la vez, porque comparte el puerto 8088 (`review-fix.md`, Q2).
- **Makefile:** las pruebas usan `memory_limit=512M`. Se añade el objetivo `phpstan-json`.
- **Sin cambios:** `CommandSchedule` sigue como en master. No hay colas ni tareas programadas nuevas.

## Cómo extenderlo

- **Corregir o mejorar algo común:** hacerlo en la rama de origen del fichero (columna `source` de `manifest.tsv`) y volver a copiarlo en biohydra. Solo los ficheros `fragment` y `strip` se editan directamente.
- **Añadir un catálogo nuevo a Property** (p. ej. «Cadena»): seguir el patrón de `Brand` en los siguientes puntos.
  1. Agregado, id, repositorio, filtro y eventos en `src/Property/Domain/Model/<X>/`.
  2. Tipo Doctrine registrado en `config/packages/doctrine.yaml`.
  3. Mapping XML.
  4. Authorizer que extienda `AbstractAuthorizer`.
  5. Casos de uso `Register`, `Update`, `Remove` y `List` (más `Import` si llega por consola o API).
  6. Controladores en `Infrastructure/UI/Web/Admin/<X>/` con `#[CsrfProtected('remove')]` en el borrado.
  7. Plantillas en `templates/property/admin/<x>/`.
  8. Nodo en el grupo «Propiedades» de `NodeLister::toAdmins()`.
  9. Icono vendido con `make icon-import p="solar:…"`. `make icon-lock` no detecta nombres que llegan desde PHP (`review-fix.md`, B1).
  10. Traducciones con `make translation`.
- **Exportar propiedades:** `PropertyAuthorizer::canExport()` ya existe. Falta un `ExportationType` (ver la skill `create-exportation`).
- **Otro formato de exportación:** añadir el caso a `ExportationFormat` y su writer en `DelegatingExportWriter`.
- **Progreso de un trabajo largo:** envolver el comando en `TrackedCommand` con una clave no enumerable (UUID) y pintar `<twig:JobProgress key="…">`.
- **Trampas conocidas:**
  - `Property::update()` asigna `numberOfEmployees`, `dailyClients` y `companyId` aunque no se pasen. Cualquier llamada nueva debe pasar los valores actuales (ver *Desviaciones y deuda*).
  - Los grupos del menú se buscan por etiqueta en los tests (`'Administración'`).
  - Los ficheros de registro centralizados generarán conflictos en cada merge con los proyectos (decisión aceptada en el plan).

## Tests

- **Unitarios:**
  - `tests/Unit/Property/**`: servicios de los seis agregados, importaciones, `MatchPropertyService`, `CompanyAuthorizerTest`, `PropertyPlaceLocationSetterTest`, `ReleasePropertiesOnAccountRemovedSubscriberTest` y repositorios Doctrine e InMemory.
  - `tests/Unit/HumanResources/**`, incluido el test de `LinkEmployeeOnUserRegisteredSubscriber`.
  - `tests/Unit/Security/**`: consultor, `ImpersonationPolicyTest`, `CurrentRoles`, `AccountTest` y `UpdateAccountServiceTest`.
  - `tests/Unit/Shared/**`: Geo, JobProgress, Gemini, `Email/EmailSenderTest` (adjuntos), `Locale/LocaleTest`, `NodeListerTest` y `NavigationSearchProviderTest`.
  - `tests/Unit/DataExchange/**`: writers.
- **Aceptación:**
  - `tests/Acceptance/Property/*.feature`: CRUD por rol de los seis agregados, autocompletado de destinos y API de listado, alta, match y desvinculación.
  - `tests/Acceptance/HumanResources/*.feature`.
  - Features de consultor, `account-consultant-api.feature`, `login-sso.feature`, `places-suggest.feature`, `api-doc.feature` y `health-check.feature`.
  - `tests/Acceptance/Shared/global-search.feature` (grupo «Administración»).
  - Contextos `PropertyContext` y `HumanResourcesContext` en `behat.yml.dist`.
- **Ejecución:**
  - `make test-unit options="--filter Property"`
  - `make test-acceptance options="tests/Acceptance/Property"`
  - `make test-acceptance options="tests/Acceptance/HumanResources"`
  - Todo: `make test-parallel` (1283 unitarios y 459 escenarios en verde, según `review.md`).
- **Huecos de cobertura:** no hay test de `UnlinkCompanyOnCompanyRemovedSubscriber`. Solo `Company` tiene test de authorizer en Property. Los tests de Places usan `AcceptancePlaceFinder` (`config/services_test.yaml`), no Google.

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`), `review.md` y `review-fix.md`. `review.md` no tiene hallazgos abiertos: M1 y B2 están descartados como deuda heredada, B1 está corregido y Q1/Q2 están resueltos.

- **Desviaciones declaradas:**
  - `ignoreErrors` de `variable.undefined` acotado a `ImportCommand.php`.
  - `AppFixtures` borrado con `rm`.
  - `_form.scss` copiado sin fila en el manifiesto.
  - Búsqueda en inglés «Administration».
  - Id `sYsTeM1` restaurado.
  - Iconos vendidos con `make icon-import` en lugar de `make icon-lock`.
- **Deuda heredada, que se corrige en origen:**
  - `src/Property/Infrastructure/UI/Console/Property/ImportCommand.php:32` usa `App\Tests\…\UserMother::systemUser()` en producción. Funciona porque `bin/deploy` no instala con `--no-dev`.
  - `JobProgressController` no tiene authorizer ni filtro por cuenta (B2).
- **Observado al documentar** (no figura en `review.md`; son ficheros `copy`, así que se corrigen en origen):
  - `src/Shared/Infrastructure/UI/Web/CRUD/BulkTrait.php:40`: ante un fallo en una acción masiva añade un flash `warning` con el mensaje de la excepción y la traza completa. Es idéntico en `origin/events`.
  - `Property::update()` sin `numberOfEmployees`/`dailyClients` borra esos valores. Pasa en `UnlinkCompanyOnCompanyRemovedSubscriber.php:34-40`, en `Bulk/AssignAccount.php:45-51`, en `PropertyMatchController.php:92-101` y en `PropertyUnlinkAccountController.php:47-56`. `applyPropertyMatched` además pierde `companyId` (`Property.php:186-194`).
  - Textos sin traducir al castellano:
    - Los flashes de empleados («Employee created successfully: {name}», etc.; `translations/messages+intl-icu.es.xlf:2401-2421`).
    - Los títulos «Edit» y «Register» de las pantallas de empleados (`:2625-2630`).
    - La etiqueta «Zone» del formulario de propiedad (`:2389`).
    - Las descripciones de alta, edición y baja de empleado en el registro de eventos.
  - En las pantallas de admin de empleados, el campo «Cuenta» no se pinta en la plantilla y `form_end` lo coloca fuera de la tarjeta, preseleccionando la primera cuenta.
  - El administrador no puede elegir «Empresa» al registrar una propiedad, solo al editarla si ya tiene cuenta.
  - `LinkEmployeeOnUserRegisteredSubscriber` busca el email con `LIKE %email%` (`DoctrineEmployeeRepository.php:82-88`), así que puede vincular un empleado con un email que solo coincida en parte.
  - API de consultor: `consultant-status` no captura `ConsultantCannotHaveConsultantException`, y un `accountId` desconocido en `AssignConsultantService.php:32-34` acaba en 500.
  - `ImportCommand.php:246` informa «Importadas %s marcas» en el paso de categorías.
