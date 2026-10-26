# Creación de cuentas (importación de la plataforma Balance) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-26 · **Bounded Contexts:** `Balance`, `Security`, `CarbonFootprint` (`Property` solo lectura)

## Qué hace y por qué

Las antiguas plataformas Balance se apagan. El comando de consola interactivo `balance:legacy:import` lee seis JSON exportados de MongoDB y, paso a paso (cada uno con una tabla previa y un `confirm`), crea las cuentas, los usuarios, los cálculos de la calculadora de viajes (para estadísticas) y las aportaciones ya cobradas (para estadísticas), y termina escribiendo `redirects.txt` con reglas `mod_rewrite` 301 para que los QR y enlaces `…/emissions/?organization=<slug>` sigan funcionando desde el servidor Apache del dominio antiguo. Es idempotente: se puede relanzar sin duplicar nada.

No se crea un agregado ni un BC nuevos: el comando (`src/Balance/Infrastructure/UI/Console/LegacyImport/ImportLegacyBalanceCommand.php`) orquesta servicios de aplicación de `Security`, `CarbonFootprint` y `Balance`, como ya hace `src/Property/Infrastructure/UI/Console/Property/ImportCommand.php`. Para ello se cerraron cuatro huecos de dominio con parámetros opcionales cuyo valor por defecto es el comportamiento anterior, de modo que los flujos web/API no cambian (decisiones en `plan.md`, tabla «Decisiones tomadas»):

1. Fecha de registro/pago distinta de «ahora» (`TripCalculation`, `ProjectContribution`).
2. Alta de usuario sin correo de bienvenida (`welcome`).
3. Enlace de la calculadora de viajes de una cuenta indicando la cuenta explícitamente (en consola no hay cuenta «actual»).
4. Aportación pagada sin los efectos de un cobro real (certificado, correo, tesorería, créditos) mediante un evento propio y un modo de financiación `Imported`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/TripCalculation/TripCalculation.php:187,204` | `register(..., ?registeredAt)` pasa `occurredOn` al evento | modificado |
| Dominio | `src/Security/Domain/Model/User/User.php:172` y `UserRegistered.php:22,49,69,88` | `welcome` (payload tolerante: `?? true`) | modificado |
| Dominio | `src/Balance/Domain/Model/ProjectContribution/ProjectContribution.php:364,405,450,605` | `register(..., ?registeredAt, funding)`, `importAsPaid()` y su `apply` | modificado |
| Dominio | `…/ProjectContribution/ProjectContributionImportedAsPaid.php` | evento sin suscriptores | nuevo |
| Dominio | `…/ProjectContribution/DuplicatedProjectContributionException.php` | id ya existente | nuevo |
| Dominio | `…/ProjectContribution/ProjectContributionFunding.php` | caso `Imported = 'imported'` e `isImported()` | modificado |
| Aplicación | `src/Security/Application/User/Register/RegisterUserRequest.php:12`, `RegisterUserService.php:43` | `welcome` | modificado |
| Aplicación | `src/CarbonFootprint/Application/TripCalculationLink/RegisterForAccount/RegisterAccountTripCalculationLinkService.php:32` | `$request->accountId ?? currentAccountId()` | modificado |
| Aplicación | `src/CarbonFootprint/Application/TripCalculation/Import/` (`Request`, `Response`, `Service`) | importa un cálculo ya calculado | nuevo |
| Aplicación | `src/Balance/Application/ProjectContribution/ImportPaid/` (`Request`, `Response`, `Service`) | importa una aportación pagada | nuevo |
| Aplicación | `src/Balance/Application/LedgerEntry/Check/CheckLedgerService.php:144`, `…/Reallocate/ReallocateUnderallocatedContributionsService.php:139` | excluyen las `Imported` | modificado |
| Infraestructura | `src/Security/Infrastructure/Notification/WelcomeUserNotifier.php:38` | no notifica si `!welcome()` | modificado |
| Infraestructura | `src/Balance/Infrastructure/Persistence/{Doctrine,InMemory}/Repository/…ProjectContributionRepository.php` | excluyen `imported` de toneladas comprometidas e infra-asignadas | modificado |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/` | lector de JSON, DTOs `Legacy*`, `ImportOptions`, `ImportDecisions`, `OrganizationDecision`, `OrganizationTarget`, `ImportDecisionsResolver`, `LegacyCalculationMapper`, `RedirectRule`, `RedirectRulesWriter` | nuevo |
| Infraestructura | `src/Balance/Infrastructure/UI/Console/LegacyImport/ImportLegacyBalanceCommand.php` | comando | nuevo |
| Configuración | `src/Balance/Infrastructure/Symfony/DependencyInjection/services.yaml:5-29` | `LegacyImport` excluido del recurso general; servicios solo para lector, resolver, mapeador y escritor; `$projectDir` | modificado |
| Tests | `tests/Acceptance/Shared/SystemContext.php:86,95`, `tests/Acceptance/Balance/BalanceContext.php:95,104`, `tests/Acceptance/Security/SecurityContext.php:327` | pasos Behat nuevos | modificado |

## Modelo de dominio

- **Sin agregados nuevos.** `Account` no gana slug ni tipo: `organizationType` solo se usa durante la ejecución (`ota` → calculadora de la cuenta; el resto → calculadora de una propiedad).
- **`UserRegistered`** añade `welcome` (por defecto `true`); al reconstruir eventos antiguos sin la clave vale `true` (`UserRegistered.php:88`).
- **`TripCalculation::register`** acepta `?\DateTimeImmutable $registeredAt` y lo pasa como `occurredOn`; `applyTripCalculationRegistered` ya tomaba `registeredAt` del evento (`TripCalculation.php:236`).
- **`ProjectContribution`**:
  - `register(..., ?registeredAt, funding)` (`:364,405`).
  - `importAsPaid(paidAt)` (`:450`): si `status->isResolved()` lanza `\LogicException('Contribution already resolved')`; si no publica `ProjectContributionImportedAsPaid` con `occurredOn = paidAt`. Su `apply` (`:605`) deja `status = Paid` y `paidAt = resolvedAt = occurredOn`.
  - `ProjectContributionImportedAsPaid` **no tiene suscriptores**, y es lo que evita el certificado, el correo al donante, los apuntes de tesorería y la asignación de créditos que dispara `ProjectContributionPaid`.
  - `ProjectContributionFunding::Imported` (`funding = 'imported'`, columna `varchar(20)` con `enum-type`, sin migración) distingue la aportación importada de una cobrada aquí. Sin él, el chequeo del libro mayor, la reasignación nocturna y las consultas de toneladas comprometidas la tratarían como un cobro propio al que le faltan asientos (hallazgo C1 de `review.md`).

## Flujo de una operación

`make sf c="balance:legacy:import var/balance --legacy-url=https://balance.bioscore.info"` → `ImportLegacyBalanceCommand::execute` (`:138`):

1. **Lectura** (`:153-163`). `LegacyBalanceDatasetReader::read()` exige los seis ficheros (`Organization`, `User`, `OrganizationMember`, `CalculatedEmissions`, `Donation`, `EcoProject`), hace `json_decode` con `JSON_THROW_ON_ERROR` y mapea a DTOs (`_id.$oid`, `created_at.$date`, `request.referral.sluglified_name` vacío → `null`, `roundtrip` ausente → `false`, rol corrupto `'["organization-admin"]'` normalizado). El directorio relativo se resuelve contra `projectDir` (`:200`).
2. **`--legacy-url`** (`:220`). Obligatoria: si falta, en interactivo se pregunta (validada con `FILTER_VALIDATE_URL`); sin interacción falla antes de escribir.
3. **Decisiones iniciales** (`:171-178`). `ImportDecisionsResolver::resolve()` calcula las preselecciones a partir de las opciones y los repositorios: cuenta (`--skip`, `--account`, `findByName`, si no «Crear»), propiedad (`--property` debe pertenecer a la cuenta; si no, la única de la cuenta), medio de transporte (`--mobility-<transport>`; sin interacción es obligatoria para cada transporte importable) y proyecto (`--project` o nombre igual tras `mb_strtolower`, quitar marcas diacríticas y colapsar espacios; el apóstrofo se compara tal cual). Un `InvalidArgumentException` aborta con `FAILURE` sin escribir. `--account`, `--property`, `--project` y `--mobility-*` deben existir.
4. **Pasos** (`:182-188`), cada uno con tabla previa + `confirm` (rechazar salta el paso con aviso). Se ejecutan con `UserMother::systemUser()` (Admin + SuperAdmin, sin cuenta; `:141`):
   - `organizationsStep` (`:316`): `choice` de destino (Omitir / Crear / cuenta existente) y `confirm` de «personal». Confirmar crea con `RegisterAccountService` (`name = displayName`, `active`). Si se rechaza, las «Crear» pasan a «Omitir» para el resto de pasos.
   - `propertiesStep` (`:422`): solo se pregunta si la cuenta tiene propiedades; sin ellas, «sin propiedad».
   - `usersStep` (`:472`, `userPlan` `:539`): usuario `GENERAL_GLOBAL_ADMIN` o de una organización marcada personal → «personal»; se asigna la cuenta de su **primera** pertenencia por `createdAt` entre las no omitidas (las demás se listan como «no aplicadas»); `findByUsername(email)` existente → «ya existía» (no se toca). Alta con `RegisterUserService(new RegisterUserRequest(email, bin2hex(random_bytes(16)), accountId, welcome: false))` y `GrantRoleService` (`organization-admin` → `Owner`; `external` → `User`).
   - `calculationsStep` (`:587`): `choice` de maestro `Mobility` por transporte presente; por organización se resuelve el enlace destino (`existingLink`), se descartan duplicados y se importa con `ImportTripCalculationService` mediante `LegacyCalculationMapper::request()`. El enlace se crea o reutiliza con `RegisterAccountTripCalculationLinkService` (`ota`) o `RegisterTripCalculationLinkService` (propiedad). Se omiten cálculos sin `referral`, de organizaciones omitidas y de organizaciones no `ota` sin propiedad.
   - `contributionsStep` (`:715`): `choice` de proyecto por cada proyecto antiguo referenciado; `ImportPaidProjectContributionService` por donación.
   - `redirectsStep` (`:831`): escribe `<directorio>/redirects.txt` con `RedirectRulesWriter`; las URLs salen de `UrlGeneratorInterface::ABSOLUTE_URL` con `PublicTripRoutes::for($link)` (`:949`) y `BASE_URL` (`router.default_uri`).
5. **Resumen** (`:984`): tabla creados / reutilizados / omitidos / errores. Un registro fallido se anota como error y el paso continúa; una excepción no controlada en un paso devuelve `FAILURE` (`:189-193`).

### Reglas de mapeo de cálculos (`LegacyCalculationMapper.php`)

- `emissions_total` (gramos) `/ 1000` → `co2eq` en kg (`:22`). Con origen todo va a `mobilityEmissions`; sin origen (formulario «solo pernoctación») a `overnightEmissions` (`:35-36`). `co2`, `ch4`, `n2o` quedan a cero.
- `distanceKm = distance_in_meters / 1000` solo con origen; `vehicleOccupants = persons` solo para `car` (`:33-34`).
- `ImportTripCalculationService` (`:43-56`) crea un único `ResolvedLeg` `intercity` (posición 0, `DistanceSource::Manual`) si hay origen, destino y medio; si no, `legs = []`. `trips = 1`, `calculationYear` = año de `registeredAt`, sin consentimiento ni grupo de coeficientes. Autoriza `Action::Edit` sobre el enlace (`:41`).
- Idempotencia (`isDuplicated`, `:42`): mismo `registeredAt` a segundos (`Y-m-d H:i:s`, hora de reloj UTC del JSON) y `co2eq` con tolerancia 0,001 kg frente a los cálculos ya existentes del enlace.

### Reglas de aportaciones (`ImportPaidProjectContributionService.php`)

- Autoriza `Action::Edit` al inicio (`:40`; Admin/SuperAdmin). `find(id)` existente → `DuplicatedProjectContributionException` (el comando lo muestra como «ya existía»). El id es el `transaction_uuid` del JSON.
- Proyecto inexistente o sin `pricePerTonne > 0` → `InvalidArgumentException`. `tonnes = round(amount / pricePerTonne, 6)`; `co2eqKg = tonnes * 1000`.
- Se registra con `funding = Imported`, origen `TripCalculation` sin `originId` (voluntaria: cuenta en el panel), `freeAmount = true`, importe = total, sin gestión ni IVA, `currency = 'EUR'`, `contributorCountry = 'ES'`, `locale = Spanish`, `registeredAt = paidAt` (`:92-93`), y se marca pagada en `paidAt` con `importAsPaid`.

### Formato de `redirects.txt` (`RedirectRulesWriter.php`)

Cabecera de dos líneas de comentario (sin fecha: el fichero sale idéntico al relanzar) y, por organización, `RewriteCond %{HTTP_HOST} ^<host escapado>$ [NC]`, `RewriteCond %{QUERY_STRING} (?:^|&)organization=<slug>(?:&|$) [NC]` y `RewriteRule ^emissions/?$ <url>? [R=301,L]` (el `?` final descarta la query). Omitidas: solo comentario. Sin propiedad: marcador `{{PROPERTY_TRIP_URL:<slug>}}`; en `--dry-run`, `{{DRY_RUN}}` donde el token aún no existe (`ImportLegacyBalanceCommand.php:909`).

## Autorización y ámbito

Todo se ejecuta como `UserMother::systemUser()`; pasan `AccountAuthorizer::canRegister`, `UserAuthorizer::canRegister`/`canChangeRole` (SuperAdmin), `TripCalculationLinkAuthorizer` (Admin) y `ProjectContributionAuthorizer::canEdit` (Admin). No hay impersonación ni `CurrentAccountIdProvider` en consola: por eso la cuenta se pasa explícita en `RegisterAccountTripCalculationLinkRequest`. No hay pantallas ni rutas nuevas.

## Persistencia y migración

Sin cambios de esquema ni de mapping. `funding` es `varchar(20)` con `enum-type` (acepta `imported`); `payment_id` es nullable. No hace falta migración.

## Configuración y operación

- **Interactivo (habitual):** `make sf c="balance:legacy:import var/balance --legacy-url=https://balance.bioscore.info"`.
- **Sin interacción** (`--no-interaction`): opciones `--skip`, `--staff`, `--account=slug=id`, `--property=slug=id`, `--project=idAntiguo=id`, `--mobility-plane|car|train|bus=id`, `--dry-run`. Las opciones repetibles aceptan varias veces la misma clave.
- `BASE_URL` debe ser la URL pública de producción al generar las redirecciones.
- `HUMAN_RESOURCES_ENABLED=true` (`.env`): `LinkEmployeeOnUserRegisteredSubscriber` crea un empleado por cada usuario importado con cuenta; para evitarlo, ejecutar con `HUMAN_RESOURCES_ENABLED=false`.
- Los usuarios importados reciben una contraseña aleatoria que no se guarda: entran con «Generar nueva contraseña». No se envía correo de bienvenida ni ninguna notificación.
- El comando importa `App\Tests\Unit\Security\Domain\Model\User\UserMother` desde `src/` (igual que `RegisterCommand` y `ImportCommand` de Property); depende de que `tests/` esté en el autoload.
- Los JSON de entrada no se modifican; solo se añade `redirects.txt`.

## Cómo extenderlo

- **Segunda plataforma Balance:** relanzar con otro directorio y otra `--legacy-url`; el `RewriteCond` por host permite que las reglas de ambas convivan en un `.htaccess`.
- **Nuevo transporte:** añadirlo a `TRANSPORTS` (`ImportLegacyBalanceCommand.php:68`) y declarar su opción `--mobility-<x>` en `configure()`.
- **Importar otro tipo de dato:** añadir su DTO `Legacy*` y su lectura en `LegacyBalanceDatasetReader`, concentrar las reglas en una clase unitaria (como `ImportDecisionsResolver` o `LegacyCalculationMapper`) y crear un servicio de aplicación con su `Authorizer`; el comando solo pregunta y orquesta. Nunca instanciar agregados directamente desde el comando.
- **Trampa:** cualquier consulta nueva sobre aportaciones pagadas que no deba incluir las importadas debe excluir `funding = imported`, como hacen `committedTonnesByAccount`, `underallocatedContributions` y `committedTonnesForProject` (`DoctrineProjectContributionRepository.php:203,311,395`; espejo en `InMemoryProjectContributionRepository.php:150,220,282`).

## Tests

- **Unitarios:** `ImportTripCalculationServiceTest`, `ImportPaidProjectContributionServiceTest`, `LegacyBalanceDatasetReaderTest` (con `fixtures/`), `ImportDecisionsResolverTest`, `LegacyCalculationMapperTest`, `RedirectRulesWriterTest` (todos bajo `tests/Unit/Balance/Infrastructure/LegacyImport/` salvo los dos primeros), más ampliaciones de `ProjectContributionTest`, `ProjectContributionRepositoryTest`, `CheckLedgerServiceTest`, `ReallocateUnderallocatedContributionsServiceTest`, `TripCalculationTest`, `UserTest`, `WelcomeUserNotifierTest`, `RegisterUserServiceTest` y `RegisterAccountTripCalculationLinkServiceTest`.
- **Aceptación:** `tests/Acceptance/Balance/legacy-import.feature` (etiqueta `@legacy-import`, fixtures en `tests/Acceptance/Balance/fixtures/legacy-balance/`): sin interacción, interactivo, rechazo del paso de usuarios, idempotencia, validaciones, `--dry-run` y redirecciones. `SystemContext` ofrece `Given the command answers are:` y `When command :name is executed with parameters:`.
- **Ejecución** (el `Makefile` no tiene `test-this`): `make test-unit options=…`, `make test-acceptance options=…`, `make test-parallel`, `make phpstan`, `make cs`.
- Sin cubrir: la ejecución real contra los JSON de `var/balance` (solo fixtures reducidos).

## Desviaciones y deuda

- Declaradas en la sección «Implementación» de `plan.md`: `ImportOptions` no inventariado; `payload()`/`rebuildFromPayload()` en lugar de `toPrimitives()`; autorización al inicio del servicio de aportaciones; propiedades solo preguntadas si la cuenta tiene alguna; `services.yaml` con exclusiones adicionales.
- Cambios posteriores a la implementación (ver `review.md`): `registeredAt` de la aportación ya es la fecha original (M1); `funding = Imported` y exclusiones en tesorería/créditos (C1, **contradice el plan**: la nota del comando sobre toneladas comprometidas se retiró); cabecera de `redirects.txt` sin fecha (B3, contradice el formato del plan); `LegacyCalculationMapper` extraído (B1).
- `review.md`: 0 hallazgos abiertos; B2 (moneda siempre EUR) descartado porque todas las donaciones son EUR. Deuda no corregida: dependencia de `UserMother` desde `src/`.
- Ejemplo sin interacción del plan: pasar `--property` de una cuenta que aún no existe falla en la validación; solo sirve para relanzar cuando la cuenta ya existe.
