# API de propiedades de un distribuidor (Greenhost) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-10 · **Bounded Contexts:** `Property` (reutiliza `Security`)

## Qué hace y por qué

Una cuenta distribuidora necesita saber por API qué propiedades tiene «debajo»: las suyas y las **adheridas** que un Administrador le asignó con «Asignar distribuidor» (por cuenta o por propiedad). Es el conjunto que su Propietario ya ve en la pantalla «Propiedades». El endpoint `GET /api/{_locale}/distributors/{distributorId}/properties` lo devuelve completo, sin paginar, a partir del id de la cuenta distribuidora.

No hay modelo ni persistencia nuevos: se reutiliza la regla de ámbito del listado web (`DistributorPropertiesScope::expand()`), el filtro por `PropertyScope` del repositorio y el DTO `PropertyResponse`. Solo se añade un caso de uso que lista por id de distribuidor y su controlador. Las decisiones (endpoint nuevo en vez de parámetro en `GET /properties`, 404 si la cuenta no es distribuidora activa, sin paginación, formato actual) están en la tabla «Decisiones tomadas» de `plan.md`.

El endpoint `GET /api/{_locale}/properties?account_id=` no sirve para esto: solo amplía a las adheridas si el usuario es Propietario de la cuenta actual (`src/Property/Application/Property/List/ListPropertiesService.php`) y limita a 10 resultados.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/Property/Application/Property/ListDistributorProperties/ListDistributorPropertiesRequest.php` | DTO de entrada: `AccountId $distributorAccountId` | nuevo |
| Aplicación | `src/Property/Application/Property/ListDistributorProperties/ListDistributorPropertiesResponse.php` | DTO de salida: `Property[] $properties`, `int $totalResults` | nuevo |
| Aplicación | `src/Property/Application/Property/ListDistributorProperties/ListDistributorPropertiesService.php` | Caso de uso | nuevo |
| Infraestructura | `src/Property/Infrastructure/UI/Api/DistributorPropertyList/DistributorPropertyListController.php` | Controlador REST y documentación OpenAPI | nuevo |
| Test unitario | `tests/Unit/Property/Application/Property/ListDistributorProperties/ListDistributorPropertiesServiceTest.php` | 15 tests del servicio | nuevo |
| Test unitario | `tests/Unit/Property/Infrastructure/Persistence/Doctrine/Repository/DoctrinePropertyRepositoryTest.php` | `testFindByFilterOrdersByNameByDefault` (orden por nombre en Doctrine, añadido por la review) | modificado |
| Test unitario | `tests/Unit/Security/Domain/Model/Account/AccountMother.php` | `inactiveDistributorAccountFromNameAndId()` | modificado |
| Aceptación | `tests/Acceptance/Property/distributor-properties-api.feature` | 9 escenarios Behat | nuevo |
| Documentación | `.claude/ARCHITECTURE.md` | Párrafo de distribuidores de `Property` | modificado |

Reutilizados sin cambios: `PropertyAuthorizer`, `DistributorPropertiesScope`, `DistributedPropertiesPolicy`, `DistributorPolicy`, `PropertyFilter`, `PropertyScope`, ambos repositorios, `PropertyResponse` y `PropertyResponseGenerator`.

## Modelo de dominio

Sin agregados, eventos ni invariantes nuevos. Conceptos reutilizados:

- «Id de distribuidor» = `AccountId` de una cuenta con `isDistributor()` (no existe otra identidad de distribuidor).
- `AccountIsNotDistributorException` (`src/Property/Domain/Model/DistributorAssignment/`) señala que la cuenta no es una distribuidora activa.
- `PropertyScope` (cuentas **o** propiedades, sin duplicados) acota la consulta; con scope vacío el repositorio Doctrine genera `1 = 0` y devuelve `[]`.

## Flujo de una operación

1. `DistributorPropertyListController` (`#[Route('/distributors/{distributorId}/properties', name: 'api_distributors_properties_list', methods: ['GET'])]`, `#[Areas(['admin'])]`) resuelve el usuario con `LoggedUser` y llama al servicio con `new ListDistributorPropertiesRequest(new AccountId($distributorId))`.
2. `ListDistributorPropertiesService::__invoke` (`ListDistributorPropertiesService.php`):
   - `:29` autoriza `Action::List` con `PropertyAuthorizer` sobre un `new Property(id: PropertyId::generate(), accountId: $distributorAccountId)` usado solo como sujeto (patrón de `ListPropertiesService`; no se persiste).
   - `:34` si `DistributorPolicy::isActiveDistributor()` es falso lanza `AccountIsNotDistributorException`.
   - `:38` construye `PropertyFilter(accountId: …, limit: 0)` (sin paginar; el valor por defecto sería 10).
   - `:40` `DistributorPropertiesScope::expand($filter)` sustituye `accountId` por un `PropertyScope` = cuenta propia + `DistributedPropertiesPolicy::scopeFor($id, AccountFeature::PropertyTripCalculators)`.
   - `:43` `findByFilter` y `totalResultsByFilter`.
3. El controlador captura `AccountIsNotDistributorException` y lanza `createNotFoundException()` (`DistributorPropertyListController.php:46-47`); si no, mapea cada `Property` con `PropertyResponseGenerator::generate()` y devuelve `$this->json($items)`.

El orden de los pasos 2.1 y 2.2 importa: `expand()` no hace nada si la cuenta no es distribuidora activa y el filtro quedaría con `accountId`, devolviendo las propiedades propias de una cuenta cualquiera. La autorización va antes del 404, de modo que un no Administrador no puede enumerar distribuidoras.

Respuesta: array JSON de `PropertyResponse` en snake_case (`property_id`, `name`, `account_id`, `property_type`, `address`, `postal_code`, `destination_id`, `country_id`, `operating_regime`, `operator_email`, `operator_name`, `operator_locale`). Propias y adheridas se distinguen por `account_id`. Sin propiedades: `200 []`.

## Autorización y ámbito

Se usa `PropertyAuthorizer` con `Action::List` sin modificarlo (`canList` = `canRegister` o `isActiveDistributorAccount`):

| Quien llama | Resultado |
|---|---|
| Admin / SuperAdmin | Cualquier distribuidor |
| Propietario de la distribuidora (cuenta actual = id solicitado) | Solo el suyo |
| Propietario de otra distribuidora o de otra cuenta | 403 |
| `ROLE_USER` | 403 |
| Sin token | 403 (firewall `api` con JWT, `config/packages/security.yaml`) |
| Id inexistente, no distribuidora, inactiva o eliminada (con permiso para consultarla) | 404 |

Un Propietario de una cuenta no distribuidora que consulta su propio id pasa la autorización (`canRegister`) y recibe 404; no hay fuga de datos. La cuenta actual y los roles los resuelve el authorizer (`currentAccountId()`, `userHasRole()`), por lo que la impersonación se respeta; el servicio no usa `$user->accountId()`.

Reglas de contenido: las propiedades propias aparecen siempre; las adheridas solo si la cuenta del hotel tiene activa `AccountFeature::PropertyTripCalculators`; las eliminadas no aparecen (`DoctrinePropertyRepository`).

Feature flag: la ruta se carga en `config/routes.yaml` (`property-api`, prefijo `/api/{_locale}`) con la condición `env('bool:PROPERTIES_ENABLED')`; si está desactivada, el endpoint no existe.

## Persistencia y migración

Sin cambios de esquema ni migración. Orden: por defecto nombre ascendente en `DoctrinePropertyRepository` (blindado por `testFindByFilterOrdersByNameByDefault`).

## Configuración y operación

Nada que configurar: el servicio se autoconfigura (`App\Property\` por recurso en `src/Property/Infrastructure/Symfony/DependencyInjection/services.yaml`) y la ruta se descubre por atributos. Sin colas, comandos ni correos. El endpoint aparece en la documentación OpenAPI del área `admin` con el tag `Property`.

## Cómo extenderlo

- **Paginación o filtros** (nombre, país, destino): crear campos en `ListDistributorPropertiesRequest`, volcarlos en el `PropertyFilter` antes de `expand()` y quitar `limit: 0`; es un cambio de contrato, hoy fuera de alcance.
- **Más campos en la respuesta** (nombre de la cuenta, origen de la adhesión): se añaden en `PropertyResponse`/`PropertyResponseGenerator`, que comparten todos los endpoints de propiedades.
- **Trampa conocida:** `GET /properties` sigue limitado a 10 resultados (`PropertyListController`); no es este endpoint.
- **Trampa de tests:** en entorno `test` el contenedor resuelve `PropertyRepository` a `InMemoryPropertyRepository` (`#[When(env: 'test')]`), cuyo `findByFilter` ignora `orderBy`/`orderWay`. Por eso el JSON de los escenarios Behat sale en orden de inserción y no en orden por nombre.

## Tests

- **Unitarios** (`ListDistributorPropertiesServiceTest`): Admin lista propias y adheridas, sin duplicados entre asignación por cuenta y por propiedad, exclusión de no asignadas, de hoteles sin `PropertyTripCalculators` y de eliminadas, propias sin mirar funcionalidades, distribuidora vacía, más de 10 propiedades, Propietario de su distribuidora, denegación de Propietario de otra cuenta, de otra distribuidora y de `ROLE_USER`, y las tres excepciones (no distribuidora, inexistente, inactiva).
- **Aceptación** (`distributor-properties-api.feature`): Admin y Propietario obtienen la misma lista; 403 para Propietario de otra cuenta, de otra distribuidora, `ROLE_USER` y sin autenticar; 404 para cuenta no distribuidora e inexistente; `[]` para distribuidora sin propiedades.
- **Ejecución** (objetivos del `Makefile`): `make test-unit options="tests/Unit/Property/Application/Property/ListDistributorProperties"`; el feature con `make test-acceptance options="tests/Acceptance/Property/distributor-properties-api.feature"`; suite completa con `make test-parallel`.
- **Hueco:** ningún test de aceptación fija el orden por nombre (limitación del InMemory); lo cubre el test de Doctrine.

## Desviaciones y deuda

- Orden del JSON en los escenarios Behat (inserción, no nombre) y `AccountMother::inactiveDistributorAccountFromNameAndId()`: declaradas en `## Implementación` de [plan.md](plan.md).
- Deuda: `InMemoryPropertyRepository::findByFilter` ignora `orderBy`/`orderWay`.
- [review.md](review.md): sin hallazgos abiertos (B1 y B2 corregidos en la revisión 2).
