# Importación de la plataforma Greenhost (consultora, grupos y propiedades) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-28 · **Bounded Contexts:** `Balance` (todo el código nuevo); `Security` y `Property` solo se usan a través de sus servicios de aplicación

## Qué hace y por qué

Greenhost es la segunda plataforma antigua (misma aplicación y mismo formato de exportación que Balance) que hay que traspasar. Sus 113 organizaciones son hoteles; una de ellas, HOSBEC, debe quedar como **consultora**, y cada hotel como **propiedad** dentro de una cuenta que tenga a HOSBEC como consultora. Muchos hoteles son del mismo grupo y un mismo usuario gestiona varios, y aquí un usuario pertenece a una sola cuenta, así que se crea **una cuenta por grupo** y una propiedad por hotel.

El comando `balance:legacy:import` (`src/Balance/Infrastructure/UI/Console/LegacyImport/ImportLegacyBalanceCommand.php`, ver el manual de `2026-10-26-creacion-de-cuentas`) ya leía este formato, pero creaba una cuenta por organización, no creaba propiedades y no trataba consultoras. Se resuelve en el propio comando, sin tocar dominio ni esquema, con tres opciones nuevas, todas opcionales (decisiones en `plan.md`, tabla «Decisiones tomadas»):

- `--groups=<csv>`: fichero con el grupo de cada organización y los datos del hotel en Bioscore. El comando **no** se conecta a Bioscore; el CSV se genera una vez y se revisa a mano.
- `--consultant=<slug>`: organización que será la consultora.
- `--create-properties`: crea una propiedad por hotel cuando su cuenta no tiene una equivalente.

Sin ninguna de las tres el comportamiento es el de la importación de Balance (los seis escenarios de `tests/Acceptance/Balance/legacy-import.feature` no se modificaron). Reutiliza `RegisterAccountService` (con `consultant: true`), `ChangeConsultantStatusService`, `AssignConsultantService` y `RegisterPropertyService`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/LegacyGroupAssignment.php` | DTO de una fila del CSV (`organizationId`, `group`, `bioscoreHotelId`, `latitude`, `longitude`, `address`, `postalCode`) | nuevo |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/LegacyGroups.php` | Mapa `organizationId → LegacyGroupAssignment`; `assignment()`, `groupOf()`, `empty()` | nuevo |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/LegacyGroupsReader.php` | Lee y valida el CSV (`read()`, `:13`) | nuevo |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/ImportOptions.php` | `consultant` y `createProperties` | modificado |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/OrganizationDecision.php` | `accountKey`, `group`, `consultant`, `createProperty`, `hasProperty()`, `usesAccountCalculator()` | modificado |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/ImportDecisions.php` | decisiones indexadas por **id** de organización; `groups`, `consultantOrganizationId`, `consultantDecision()`, `decisionsOfAccountKey()` | modificado |
| Infraestructura | `src/Balance/Infrastructure/LegacyImport/ImportDecisionsResolver.php` | grupos, consultora, preselección de propiedad, cuentas parecidas | modificado |
| Infraestructura | `src/Balance/Infrastructure/UI/Console/LegacyImport/ImportLegacyBalanceCommand.php` | opciones, pasos nuevos y por grupo, alta de propiedades, asignación de consultora, duplicados, resumen | modificado |
| Configuración | `src/Balance/Infrastructure/Symfony/DependencyInjection/services.yaml:19` | `LegacyGroupAssignment` y `LegacyGroups` excluidos del autowiring (son DTOs); `LegacyGroupsReader` es servicio | modificado |
| Tests | `tests/Unit/Balance/Infrastructure/LegacyImport/LegacyGroupsReaderTest.php` | lector del CSV (11 casos) | nuevo |
| Tests | `tests/Unit/Balance/Infrastructure/LegacyImport/ImportDecisionsResolverTest.php` | resolver ampliado (grupos, consultora, propiedades, cuentas parecidas) | modificado |
| Tests | `tests/Acceptance/Balance/legacy-import-greenhost.feature` + `fixtures/legacy-greenhost/` | 7 escenarios Behat y fixture (seis JSON, `groups.csv`, `groups-invalid.csv`) | nuevo |
| Tests | `tests/Acceptance/Balance/BalanceContext.php` | pasos `Then` de consultora y propiedades; el hook `@legacy-import` borra también `fixtures/legacy-greenhost/redirects.txt` | modificado |

No hay cambios en `LegacyBalanceDatasetReader`, `LegacyOrganization`, mappings ORM ni en los servicios de `Security` y `Property`.

## Modelo de dominio

No hay agregados, eventos ni excepciones de dominio nuevos: el trabajo está en el modelo de **decisiones** de la importación (infraestructura).

- **`OrganizationDecision`** (`OrganizationDecision.php`): una por organización del dump. `accountKey` es el nombre del grupo si lo hay y, si no, el id de la organización (por defecto `''`, siempre lo rellena el resolver). `group` es el nombre del grupo; `consultant` marca la decisión consultora; `createProperty` indica que su propiedad se creará.
- **Invariantes** (del plan, sección «Modelo conceptual e invariantes»):
  - una cuenta por cada `accountKey` distinta;
  - una propiedad por cada par (cuenta, `bioscore_hotel_id` o, sin él, nombre normalizado);
  - la consultora no tiene consultora y no comparte cuenta (`ImportLegacyBalanceCommand.php:400-402`).
- **Calculadora**: `usesAccountCalculator()` es verdadero para una `ota` o para la consultora; esas decisiones usan la calculadora de viajes de la **cuenta** y no reciben propiedad. El resto usa la de su propiedad (`hasProperty()` = `propertyId` o `createProperty`).
- **Organizaciones duplicadas**: `hotel-les-dunes-comodoro` aparece dos veces en el dump con ids distintos. Por eso `ImportDecisions::$organizations` se indexa por id (`ImportDecisionsResolver.php:50`). Los cálculos y las reglas de redirección, que se referencian por slug, solo se atribuyen a la **primera** organización con ese slug (`organizationBySlug`, `LegacyBalanceDataset.php:26`; `isDuplicated()` en el comando, `:1393`).

### Formato del CSV de grupos

Separador `;` y cabecera, como `src/Property/Infrastructure/UI/Console/Property/ImportCommand.php`. Las columnas se leen por nombre; el resto (`slug`, `display_name`, `match`, `users`…) se ignora.

| Columna | Obligatoria | Uso |
|---|---|---|
| `organization_id` | sí | `_id.$oid` de la organización; las filas sin valor se saltan (`LegacyGroupsReader.php:51-53`) |
| `group` | no | nombre de la cuenta; vacío = cuenta propia |
| `bioscore_hotel_id` | no | `ExternalId::bioscore` de la propiedad |
| `latitude`, `longitude` | no | coordenadas; deben venir las dos o ninguna, y ser numéricas |
| `address`, `postal_code` | no | datos de la propiedad |

`LegacyGroupsReader` lanza `\InvalidArgumentException` (el comando la captura y devuelve `FAILURE` antes de escribir nada, `ImportLegacyBalanceCommand.php:188-198`) cuando: falta `organization_id` (`:45`); el id no existe en el dump; el id se repite; solo hay una coordenada o no son numéricas; o el mismo `bioscore_hotel_id` aparece en dos cuentas distintas (`denyBioscoreHotelsInSeveralGroups`, `:108-123`). Esta última compara por `group ?? organizationId`, de modo que dos organizaciones sin grupo con el mismo hotel de Bioscore también fallan (hallazgo M2 de `review.md`, corregido). Un fichero inexistente o ilegible lanza `\RuntimeException` (`:17-27`).

## Flujo de una operación

`make sf c="balance:legacy:import <directorio> --legacy-url=… [--groups=… --consultant=… --create-properties]"`.

1. **Lectura** (`ImportLegacyBalanceCommand.php:176-200`): `LegacyBalanceDatasetReader::read()` y, si hay `--groups`, `LegacyGroupsReader::read()`; sin la opción, `LegacyGroups::empty()`. La ruta de `--groups` se resuelve respecto al proyecto si no empieza por `/` (`directory()`, `:241`). `showTotals()` añade la fila «Grupos (CSV)».
2. **Resolución** (`ImportDecisionsResolver::resolve()`, `:40-68`): valida `--consultant` (debe existir y no estar en `--skip`, `:342-359`) y construye una decisión por organización:
   - `organization()` (`:70-110`): el nombre de cuenta es el grupo o el `display_name`. Orden: `--skip`; `--account=<clave>=<id>`, con el nombre del grupo como clave para organizaciones agrupadas; `AccountRepository::findByName(<nombre>)`; si no, `Create`. `--account=<slug>` y `--staff=<slug>` sobre una organización agrupada (con grupo distinto del slug) fallan con `\InvalidArgumentException`; `--staff=<grupo>` marca a todo el grupo (hallazgo M1, corregido).
   - `property()` (`:112-168`), en este orden: nada si usa calculadora de cuenta o se omite; `--property=<slug>=<id>` (debe ser de la cuenta); cuenta aún inexistente → `createProperty = --create-properties`; propiedad **de la cuenta** con el mismo `ExternalId::bioscore`; propiedad de la cuenta con el mismo nombre normalizado; la única propiedad de la cuenta, solo si la organización no está agrupada; si no, `createProperty = --create-properties`. La búsqueda por id de Bioscore recorre las propiedades de la cuenta en vez de usar `findBySourceAndExternalId`, que no filtra por cuenta y devuelve una sola fila (hallazgo A1, corregido).
3. **Pasos** (`:219-229`), en este orden: `consultantChoiceStep` → `organizationsStep` → `propertiesStep` → `consultantAssignmentStep` → `usersStep` → `calculationsStep` → `contributionsStep` → `redirectsStep`, y `showSummary()`. Cada paso interactivo muestra una tabla y pide confirmación antes de escribir; `--dry-run` hace las mismas preguntas y no escribe.
   - **`consultantChoiceStep`** (`:365-403`): solo pregunta si se usó `--consultant`, `--groups` o `--create-properties` (`$askConsultant`, `:219`); si preguntara siempre desplazaría las respuestas fijas de los escenarios interactivos de `legacy-import.feature`. `choice` entre `Ninguna` y las organizaciones no omitidas, con la de `--consultant` preseleccionada.
   - **`organizationsStep`** (`:405-546`): primero `missingGroupAccounts()` (`:548-585`), que lista en «Grupos sin cuenta en Balance» los grupos con destino `Create` y sin cuenta, con hasta tres cuentas parecidas (`similarAccounts()`, `ImportDecisionsResolver.php:270-288`, basada en `nameKey()`: sin puntuación ni formas jurídicas finales `sl, slu, sa, sau, sll, scoop, cb`; parecida si las claves son iguales o una contiene a la otra como palabras completas). Es solo un aviso, no cambia destino ni código de salida. Después, un `choice` y un `confirm` de personal **por cuenta destino** (`accountGroups()`, `:588`), copiados a todas las organizaciones del grupo. La cuenta nueva se registra una vez por `accountKey` con `RegisterAccountRequest(name, active: alguna activa, consultant: es la consultora)` (`:524-530`). Si la consultora se reutiliza y no lo es, `markConsultantAccount()` (`:616-646`) llama a `ChangeConsultantStatusService`; si falla, desmarca la consultora y registra el error en «Consultora».
   - **`propertiesStep`** (`:648-786`): pregunta siempre, también para cuentas nuevas, con las propiedades de la cuenta, `Crear propiedad nueva "<display_name>"` y `Sin propiedad`; la preselección se recalcula con `resolver->property()` sobre la cuenta elegida en el paso anterior. Si hay propiedades por crear, un `confirm`. Clave de fusión `accountKey|bioscoreHotelId` (o nombre normalizado): la segunda organización con la misma clave reutiliza el `PropertyId` («misma propiedad que <slug>», `:733-741`). El alta usa `RegisterPropertyRequest` con `PropertyInfo(propertyType: Hotel, address, postalCode, longitude, latitude)` y `ExternalId::fromSourceAndId(Bioscore, <id>)`; no se pasa `placeReference`, así que no se llama a Google (`PropertyPlaceLocationSetter` conserva las coordenadas). Con `--dry-run` o cuenta aún sin crear solo escribe «se crearía».
   - **`consultantAssignmentStep`** (`:788-880`): no hace nada sin consultora. Una fila por cuenta (no omitida, no personal, no consultora) con la acción de `consultantAction()`: `asignar` / `se asignaría` (simulación o cuenta nueva), `ya asignada`, `tiene otra consultora, se mantiene` (nunca se sobrescribe) y `es consultora, no se asigna`. Tras un `confirm`, `AssignConsultantService(AssignConsultantRequest)`.
   - **`usersStep` / `userPlan`** (`:882-994`): sin cambios en la regla (primera pertenencia válida); «Pertenencias no aplicadas» solo incluye las que van a una cuenta con **otra** `accountKey`.
   - **Cálculos y redirecciones** (`:1008-1126`, `:1252-1376`): la condición de «sin propiedad» pasa a `!usesAccountCalculator() && !hasProperty()`; la consultora se comporta como una `ota` (enlace de cuenta, comentario «calculadora de viajes de la cuenta …»); la segunda organización duplicada se salta (en la tabla de redirecciones aparece como «duplicada de <id>»).
4. **Resumen** (`:1418-1436`): filas Cuentas, Grupos sin cuenta, Propiedades, Consultora, Usuarios, Cálculos, Aportaciones y Redirecciones.

## Autorización y ámbito

El comando ejecuta todos los servicios como `UserMother::systemUser()` (`ImportLegacyBalanceCommand.php:166`, usuario con roles `Admin` y `SuperAdmin`); `AssignConsultantService` y `ChangeConsultantStatusService` exigen `Action::Admin`, y `RegisterPropertyService` autoriza `Register`. No hay superficie HTTP ni impersonación. `AssignConsultantService` rechaza (excepción) asignar una cuenta que no sea consultora, y `Account` impide que una cuenta consultora tenga consultora; ambos errores se muestran como aviso en «Consultora» y cuentan en la columna `errores`.

## Persistencia y migración

Sin cambios de esquema ni migración. Se escribe a través de los servicios de aplicación existentes (cuentas, propiedades, consultoras, usuarios, enlaces de calculadora, cálculos, aportaciones) y `redirects.txt` en el directorio de datos (`RedirectRulesWriter`).

## Configuración y operación

- Registro de servicios: `services.yaml:14-19` registra `LegacyImport/` salvo la lista de DTOs excluidos, a la que hubo que añadir `LegacyGroupAssignment` y `LegacyGroups`; `LegacyGroupsReader`, `RegisterPropertyService`, `ChangeConsultantStatusService` y `AssignConsultantService` se autoinyectan.
- Ejecución recomendada con Greenhost (según `plan.md`): copiar `groups.csv` de la carpeta del plan a `var/greenhost/groups.csv`; `--skip` y `--staff` para `bioscore` y `--skip` para las tres organizaciones de prueba; primero con `--dry-run` y revisar «Grupos sin cuenta en Balance»; después la ejecución real. Los usuarios de HOSBEC sin pertenencia válida (`aida.marin@hosbec.com`, `aida.marin.hosbec@gmail.com`) se crean a mano.
- Idempotencia: al repetir, las cuentas se reutilizan por nombre, las propiedades por id de Bioscore o nombre normalizado, y la consultora ya asignada aparece como «ya asignada».
- Es una importación única: no usa colas ni tareas programadas ni envía correos.

## Cómo extenderlo

- **Nueva columna del CSV** (p. ej. otro dato de la propiedad): 1) campo en `LegacyGroupAssignment`; 2) lectura y validación en `LegacyGroupsReader::groups()`; 3) uso en la creación de `RegisterPropertyRequest` (`ImportLegacyBalanceCommand.php:755-769`); 4) caso en `LegacyGroupsReaderTest`.
- **Otro criterio de preselección de propiedad**: añadirlo en `ImportDecisionsResolver::property()` respetando el orden y la restricción a la cuenta de la decisión; el paso de propiedades lo recalcula, no hay que duplicar la regla.
- **Trampas**: las decisiones se indexan por **id**, no por slug (los dos son `string`, PHPStan no avisa; revisar cada `organization(`); `OrganizationDecision::$accountKey` tiene valor por defecto `''` para no romper construcciones posicionales; `nameKey()` quita los puntos antes de normalizar para reconocer «S.L.»; el nombre del grupo debe coincidir exactamente con el de la cuenta de Balance para reutilizarla (`findByName`), de ahí el aviso de cuentas parecidas.

## Tests

- Unitarios: `LegacyGroupsReaderTest` (cabeceras en otro orden, valores vacíos, fichero y columna ausentes, organización inexistente o repetida, hotel de Bioscore en dos grupos o en dos organizaciones sin grupo, coordenadas) y `ImportDecisionsResolverTest` (grupo y `--account`, rechazo de slug agrupado, `--staff` por grupo, consultora, preselección por id de Bioscore y por nombre, `--create-properties`, decisiones por id, cuentas parecidas).
- Acceptance: `tests/Acceptance/Balance/legacy-import-greenhost.feature`: importación agrupada con consultora; repetición sin duplicar; CSV con organización inexistente (código 1); simulación con aviso de grupos sin cuenta; grupo cuyo nombre coincide con una cuenta; consultora existente marcada; cuenta reutilizada que conserva otra consultora.
- Ejecución: `make test-unit options="tests/Unit/Balance/Infrastructure/LegacyImport"` y `make test-acceptance options="--tags=@legacy-import"` (objetivos del `Makefile`; `test-this` no existe aunque `CLAUDE.md` lo documenta).
- Huecos: la interacción real con `choice` en grupos no está cubierta por Behat (los escenarios son sin interacción); el flujo de `consultantChoiceStep` se prueba solo con opciones.

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`) y `review.md`. Resumen:

- Desviaciones declaradas: `consultantChoiceStep` solo pregunta con las opciones nuevas; `accountKey` con valor por defecto; `nameKey()` quita puntos; la regla del lector del CSV usa la clave de cuenta (corregida tras la review); `legacy-import-greenhost.feature` comprueba `creada:` en lugar del texto con comillas.
- Review: los cuatro hallazgos (A1, M1, M2, B1) están corregidos y no queda ninguno abierto. Sigue pendiente que `plan.md` refleje que la búsqueda por id de Bioscore recorre las propiedades de la cuenta.
- Deuda no corregida: el código de producción depende de `App\Tests\Unit\Security\Domain\Model\User\UserMother` (`ImportLegacyBalanceCommand.php:66`, patrón anterior y transversal; descartada por el usuario); `calculationsStep()` falla con `Undefined array key` si no hay medios de transporte dados de alta (descartada); `Makefile` sin `test-this`.
- Un fichero de bloqueo de LibreOffice (`.~lock.groups.csv#`) permanece en la carpeta del plan.
