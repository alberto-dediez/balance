# Integrar reporting en biohydra: flags de Propiedades y Empleados y mejoras comunes de reporting — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `Property`, `HumanResources`, `Shared` (navegación, Gemini, Markdown), `Security` (`Action`, `AbstractAuthorizer`, supervisión por consultor), configuración (`config/routes.yaml`, `.env`, `composer.json`), Behat compartido

## Qué hace y por qué

La plataforma reporting se construyó sobre una base de la que se habían borrado los BCs `Property` y `HumanResources`. Para que pueda mezclar `biohydra` como waste, food-loss y events sin exponer esas pantallas, biohydra añade dos interruptores por entorno, `PROPERTIES_ENABLED` y `HUMAN_RESOURCES_ENABLED`, activos por defecto. Con un flag en `false`, su BC desaparece del menú lateral (y por tanto del buscador de acciones), todas sus rutas web y API dejan de casar en el router (404) y, en el caso de RR. HH., deja de crearse o vincularse un empleado al registrar un usuario. Ni el dominio ni el esquema cambian: los mappings Doctrine se cargan siempre y las tablas existen aunque no se usen (decisiones «Mecanismo para las rutas: 404», «Mappings Doctrine y tablas» y «Suscriptor de alta automática de empleados» en `plan.md`).

Además, el plan trae de reporting, byte a byte, sus mejoras genéricas del núcleo para que dejen de ser conflicto en cada merge: proveedor de nodos de menú por cuenta (`AccountNodeProvider`), supervisión de cuentas por consultor (`Action::Supervise` + `ConsultantSupervisionPolicy`), cliente Gemini con nivel de razonamiento por prompt y errores controlados, enlaces externos en pestaña nueva en el filtro Markdown, un controlador Stimulus de colecciones más capaz y siete pasos de Behat. En biohydra ninguna de estas piezas tiene todavía consumidor: quedan disponibles para los proyectos (reporting las usa en `Assessment` y `Audit`).

Los ficheros copiados de reporting no deben editarse en biohydra ni pasarse por `make cs-fix`/`make rector`: su identidad byte a byte con `reporting:<ruta>` es lo que mantiene limpio el merge (sección *Restricciones* de `plan.md`).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Configuración | `.env:107-113` | Bloques `###> property ###` (`PROPERTIES_ENABLED=true`) y `###> human resources ###` (`HUMAN_RESOURCES_ENABLED=true`) | Modificado |
| Configuración | `config/routes.yaml:63,75,98` | `condition: "env('bool:…')"` en las importaciones `property-api`, `property-web` y `human-resources-web` | Modificado |
| Configuración | `composer.json:33` (+ `composer.lock`, `config/reference.php`) | `symfony/expression-language` `7.4.*`, necesario para evaluar `condition` | Modificado (desviación declarada) |
| Configuración | `src/Property/Infrastructure/Symfony/DependencyInjection/services.yaml:1-2` | Parámetro `app.properties_enabled: '%env(bool:PROPERTIES_ENABLED)%'` | Modificado |
| Configuración | `src/HumanResources/Infrastructure/Symfony/DependencyInjection/services.yaml:1-2` | Parámetro `app.human_resources_enabled: '%env(bool:HUMAN_RESOURCES_ENABLED)%'` | Modificado |
| Dominio | `src/HumanResources/Domain/Event/Employee/LinkEmployeeOnUserRegisteredSubscriber.php:31-32,49-51` | Recibe el flag y no hace nada si está apagado | Modificado |
| Aplicación | `src/Security/Application/Action.php:31` | `case Supervise = 'supervise'` | Copia de reporting |
| Aplicación | `src/Security/Application/AbstractAuthorizer.php:21,63,98-134` | 4.º parámetro `?ConsultantSupervisionPolicy`, `canSupervise()`, `supervisesAccount()`, `supervisedAccountIds()` | Copia de reporting |
| Aplicación | `src/Security/Application/Account/ConsultantSupervisionPolicy.php` | Reglas de supervisión de una cuenta consultora sobre sus cuentas asignadas | Nuevo (copia) |
| Infraestructura | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | Flags de menú, proveedor de nodos por cuenta, conservación de `selected` | Modificado a mano |
| Infraestructura | `src/Shared/Infrastructure/UI/Web/Navigation/AccountNodeProvider.php` | Interfaz `nodes(): list<Node>` | Nuevo (copia) |
| Infraestructura | `src/Shared/Infrastructure/AI/Gemini/GeminiHttpClient.php` | `thinkingConfig` por modelo, cabecera `x-goog-api-key`, timeout 300 s, excepciones | Copia de reporting |
| Infraestructura | `src/Shared/Infrastructure/AI/Gemini/GeminiPrompt.php:18` | Método obligatorio `thinkingLevel(): GeminiThinkingLevel` | Copia de reporting |
| Infraestructura | `src/Shared/Infrastructure/AI/Gemini/GeminiThinkingLevel.php` | Enum `MINIMAL`, `LOW`, `MEDIUM`, `HIGH` | Nuevo (copia) |
| Infraestructura | `src/Shared/Infrastructure/AI/Gemini/GeminiResponseException.php` | `incomplete()` e `invalidJson()` | Nuevo (copia) |
| Infraestructura | `src/Shared/Infrastructure/Symfony/Twig/MarkdownExtension.php` | Filtro `md_to_html` con `ExternalLinkExtension` | Copia de reporting |
| Frontend | `assets/controllers/collection_controller.js` | Placeholder configurable, renumeración, `moveUp`/`moveDown`, foco al añadir | Copia de reporting |
| Tests | `tests/Unit/Shared/Infrastructure/UI/Web/Navigation/NodeListerTest.php` | Flags y proveedor | Modificado a mano |
| Tests | `tests/Unit/HumanResources/Domain/Event/Employee/LinkEmployeeOnUserRegisteredSubscriberTest.php` | Suscriptor con el flag apagado | Modificado a mano |
| Tests | `tests/Unit/Security/Application/Account/ConsultantSupervisionPolicy{Test,Mother}.php` | Política de supervisión | Nuevos (copia) |
| Tests | `tests/Unit/Shared/Infrastructure/AI/Gemini/GeminiHttpClientTest.php` | Cliente Gemini con `MockHttpClient` | Nuevo (copia) |
| Tests | `tests/Unit/Shared/Infrastructure/Symfony/Twig/MarkdownExtensionTest.php` | Enlaces externos y escape | Copia de reporting |
| Tests | `tests/Acceptance/Shared/WebContext.php` | Siete pasos nuevos | Modificado a mano |

## Modelo de dominio

Sin cambios de agregados, eventos ni invariantes. La única pieza de dominio tocada es el suscriptor `LinkEmployeeOnUserRegisteredSubscriber`: sigue suscrito a `UserRegistered` (`isSubscribedTo()` no cambia) pero `handleUserRegistered()` sale en la primera línea si `$humanResourcesEnabled` es `false` (`LinkEmployeeOnUserRegisteredSubscriber.php:49-51`), antes de buscar empleados, despachar `RegisterEmployeeRequest` o llamar a `LinkUserService`.

Los otros suscriptores de los dos BCs (`LinkUserOnEmployeeEventSubscriber`, `UnlinkEmployeeOnUserRemovedSubscriber`, `UnlinkCompanyOnCompanyRemovedSubscriber`, `ReleasePropertiesOnAccountRemovedSubscriber`) no se gatean: solo reaccionan a eventos de sus propios agregados o a `AccountRemoved`/`UserRemoved` sobre tablas sin filas cuando el BC está apagado.

## Flujo de una operación

### Petición a una URL de un BC apagado

1. `config/routes.yaml:63/75/98` aplica `condition: "env('bool:PROPERTIES_ENABLED')"` (o `HUMAN_RESOURCES_ENABLED`) a toda la colección importada. La función `env` del lenguaje de expresiones de routing la registra FrameworkBundle (`container.getenv`, ver *Hechos verificados* en `plan.md`) y devuelve el valor ya procesado por `bool:`.
2. Con el flag en `false`, el matcher descarta todas las rutas de la importación y Symfony responde 404, que renderiza `templates/bundles/TwigBundle/Exception/error404.html.twig`.
3. `RouterInterface::generate()` no evalúa condiciones: una plantilla que haga `path('admin_properties')` sigue generando la URL (que luego da 404). En biohydra solo enlazan a esas rutas el menú y las propias plantillas de Property/HR.

### Construcción del menú lateral

`NodeLister::nodes()` elige por rol efectivo (`Admin` → `toAdmins()`, `Owner` → `toOwners()`, resto → `toUsers()`) y aplica `markSelected()`:

- `toAdmins()` (`NodeLister.php:157`): el grupo «Propiedades» solo se añade si `$propertiesEnabled` (`:161`); «Administración» se construye en `$administrationItems` empezando por «Cuentas», añade «Empleados» solo si `$humanResourcesEnabled` (`:208`) y sigue con «Usuarios», «Firma de documentos» y «Registro». Los administradores **no** reciben nodos del proveedor.
- `toOwners()` (`:292`): arranca con `$this->accountNodeProvider?->nodes() ?? []` (`:294`), añade «Propiedades» si procede (`:296`) y una «Administración» con «Empleados» condicional (`:317`) seguido de «Usuarios».
- `toUsers()` (`:363`): arranca también con los nodos del proveedor (`:365`).
- `markSelected()` (`:64`): un ítem que llega con `selected: true` (p. ej. desde el proveedor) se conserva seleccionado aunque la URL actual no coincida: `$item->selected = $item->selected || (…)`.

`NavigationSearchProvider` (`src/Shared/Infrastructure/UI/Web/Navigation/NavigationSearchProvider.php:69`) recorre `NodeLister::nodes()`, así que el grupo «Acciones» del buscador global hereda los flags sin código adicional.

### Alta de usuario con RR. HH. apagado

`UserRegistered` → `LinkEmployeeOnUserRegisteredSubscriber::handleUserRegistered()` → `return` inmediato. No se crea ni se vincula ningún `Employee`.

### Petición a Gemini

`GeminiHttpClient::send()` (`src/Shared/Infrastructure/AI/Gemini/GeminiHttpClient.php`):

1. Resuelve el modelo por `GeminiModelTier` (`model()`).
2. Construye `generationConfig` con `responseMimeType`, `responseSchema` y, mediante `thinkingConfig($model, $prompt->thinkingLevel())`, la configuración de razonamiento:
   - Modelos `gemini-3*` o superiores: `thinkingLevel` = `minimal`/`low`/`medium`/`high`, degradado solo cuando el modelo no soporta el nivel pedido: `MINIMAL` → `low` en modelos `-pro` y en los no-pro 3.7 o superiores (3.7, 3.8, 4+); `MEDIUM` → `high` solo en `gemini-3-pro` (3.0).
   - Modelos `gemini-2.5*`: `thinkingBudget` = 0/1024/8192/24576 (`MINIMAL` en `-pro` → 128).
   - Cualquier otro modelo: sin `thinkingConfig`.
3. Envía `POST` con cabecera `x-goog-api-key` (antes la clave iba en la query `?key=`) y `timeout` de 300 s.
4. Si `finishReason` (o `promptFeedback.blockReason`, o `NO_CANDIDATES`) no es `STOP`, lanza `GeminiResponseException::incomplete()` con modelo, motivo y tokens de salida y de razonamiento. Si el texto no decodifica a array, lanza `GeminiResponseException::invalidJson()`. Antes, una respuesta inválida devolvía `[]` en silencio.
5. Con `GEMINI_LOG_ENABLED`, el log JSON incluye ahora `thinkingLevel`.

El constructor no cambia, así que el wiring de `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml:11-15` sigue igual.

### Markdown

`MarkdownExtension` (`src/Shared/Infrastructure/Symfony/Twig/MarkdownExtension.php`) mantiene `html_input: escape` y `allow_unsafe_links: false` y añade `ExternalLinkExtension` con `internal_hosts: []`, `open_in_new_window: true` y `noopener`/`noreferrer` en enlaces externos. Los enlaces relativos se quedan en la misma pestaña. Ninguna plantilla de biohydra usa todavía `md_to_html` (el filtro `changelog_markdown` es de otra extensión, `src/Changelog/Infrastructure/UI/Web/Twig/MarkdownExtension.php`).

### Colecciones en formularios

`assets/controllers/collection_controller.js`:

- Valor `placeholder` (por defecto `__name__`) sustituido en el prototipo con `split/join` (`:16`).
- Solo cuenta y renumera los hijos directos (`:scope > .collection-element`, `:8,73,89`), de modo que colecciones anidadas no se interfieren.
- `renumber()` escribe la posición 1..N en `input[data-collection-position]` y `renumberIndexes()` el índice visible en `[data-collection-index]`, siempre del propio controlador (`closest('[data-controller~="collection"]') === this.element`).
- Acciones nuevas `moveUp`/`moveDown`; al añadir, el elemento hace scroll y el primer campo visible recibe el foco.

Ninguna plantilla de biohydra usa hoy `data-controller="collection"`; la copia alinea el fichero para los merges.

## Autorización y ámbito

- **Flags**: no pasan por authorizers. El corte está en el router (404) y en el menú; los authorizers de Property/HR no cambian.
- **`Action::Supervise`**: `AbstractAuthorizer::canDoThat()` lo delega en `canSupervise()` (`AbstractAuthorizer.php:63`), que por defecto devuelve `false` (`:98-101`). Un authorizer que quiera permitirlo lo sobrescribe apoyándose en:
  - `supervisesAccount(?AccountId $accountId, User $user)` (`:103-116`): `true` si hay política, el usuario actúa como `Owner` (vía `userHasRole()`, que respeta la impersonación), tiene cuenta actual (`currentAccountId()`) y la política dice que esa cuenta consultora supervisa `$accountId`.
  - `supervisedAccountIds(User $user)` (`:121-134`, público): `null` si no hay política, el usuario no es `Owner` efectivo o su cuenta actual no es consultora activa; si no, la lista de `AccountId` asignadas.
- **`ConsultantSupervisionPolicy`** (`src/Security/Application/Account/ConsultantSupervisionPolicy.php`): `isActiveConsultant()` exige cuenta marcada como consultora, activa y no borrada; `supervises()` exige además que la cuenta destino exista, no esté borrada y tenga asignada esa consultora; `supervisedAccountIds()` usa `AccountFilter(consultantAccountId:, limit: 0)` (sin paginación); `supervisorsOf()` devuelve los `Owner` no borrados de la consultora activa asignada a una cuenta. Repositorios opcionales: sin ellos todo devuelve `false`/`[]`.
- **Trampa conocida** (hallazgo B1 de `review.md`, asumido en `review-fix.md`): `AccountAuthorizer` (`src/Security/Application/Account/AccountAuthorizer.php:17-23`) y `AnnouncementAuthorizer` (`src/Changelog/Application/Announcement/AnnouncementAuthorizer.php`) sobrescriben el constructor y llaman a `parent::__construct()` con dos argumentos, así que pierden la política: en ellos `supervisesAccount()` devolvería siempre `false` y `supervisedAccountIds()` siempre `null`. El primer caso de uso que implemente supervisión en esos agregados debe reenviar el 4.º parámetro, como hacen en reporting `AssessmentAuthorizer` y `AssessmentReportAuthorizer`.

## Persistencia y migración

Sin cambios de mapping, repositorios, tipos ni migración. Con los flags en `false` las tablas de Property (`properties`, `companies`, `categories`, `brands`, `countries`, `destinations`) y de HR (`employees`) siguen existiendo porque `config/packages/doctrine.yaml` carga sus mappings siempre y `doctrine:schema:validate` las exige. Apagar un flag no borra datos.

## Configuración y operación

| Variable | Defecto (`.env`) | Efecto en `false` |
|---|---|---|
| `PROPERTIES_ENABLED` | `true` (`.env:108`) | Sin grupo «Propiedades» en el menú de admin y owner; 404 en todas las rutas de `property-web` y `property-api` (`/api/{_locale}/properties*`) |
| `HUMAN_RESOURCES_ENABLED` | `true` (`.env:112`) | Sin «Empleados» en «Administración»; 404 en `human-resources-web`; sin alta ni vinculación automática de empleados al registrar usuarios |

- Los parámetros `app.properties_enabled` y `app.human_resources_enabled` se declaran en el `services.yaml` de su BC y los consume `NodeLister` con `#[Autowire(param: …)]`. Si un proyecto dejara de importar esos `services.yaml`, el contenedor fallaría (mismo acoplamiento que `app.announcements_enabled`).
- Usar siempre `env('bool:…')` en condiciones de ruta: `env('PROPERTIES_ENABLED')` devolvería la cadena `'false'`, que es *truthy*.
- Tras cambiar un flag: `make cc`. Comprobaciones útiles: `make sf c="debug:router admin_properties"` (muestra la condición) y `make sf c="router:match /es/admin/properties"`.
- `symfony/expression-language` es obligatorio desde este plan: sin él, cualquier ruta con `condition` lanza excepción. Al mezclar biohydra en un proyecto hay que ejecutar `make composer c="install"`.
- La documentación OpenAPI (`nelmio/api-doc-bundle`) sigue listando los endpoints de Property aunque respondan 404 (decisión «Documentación OpenAPI» del plan).
- Los comandos de consola de Property (`property:import`, `property:property:load`) no se gatean.
- Gemini: mismas variables `GEMINI_*` (`.env:146-150`); el nivel de razonamiento no es configurable por entorno, lo fija cada prompt.

## Cómo extenderlo

- **Gatear otro BC opcional**: 1) añadir `X_ENABLED=true` a `.env` en su bloque; 2) declarar `app.x_enabled: '%env(bool:X_ENABLED)%'` en `parameters` del `services.yaml` del BC; 3) añadir `condition: "env('bool:X_ENABLED')"` a cada importación de rutas del BC en `config/routes.yaml`; 4) recibir el parámetro en `NodeLister` con `#[Autowire(param: 'app.x_enabled')]` y envolver sus nodos en `if`; 5) gatear con el mismo patrón los suscriptores que creen datos como efecto de eventos de otros BCs; 6) cubrirlo en `NodeListerTest`. Si un acceptance test necesitara el flag en un valor distinto del de `.env`, hay que añadirlo a `TEST_CONT` del `Makefile` (nota de `CLAUDE.md` sobre `env_file`).
- **Nodos de menú por cuenta**: implementar `AccountNodeProvider` (una sola implementación; con dos, el autowiring falla por ambigüedad y hay que declarar alias). Sus nodos se anteponen al menú de owners y usuarios; los ítems que devuelva con `selected: true` se respetan.
- **Supervisión en un agregado**: en su authorizer, sobrescribir `canSupervise()` usando `supervisesAccount($subject->accountId(), $user)` y, para listados, filtrar por `supervisedAccountIds($user)` (que devuelve `null` cuando el usuario no actúa como consultor). Si el authorizer tiene constructor propio, reenviar `?CurrentImpersonationProvider` y `?ConsultantSupervisionPolicy` a `parent::__construct()`.
- **Nuevo prompt Gemini**: implementar `GeminiPrompt` con los cuatro métodos, incluido `thinkingLevel()`. En events y food-loss, al mezclar biohydra, sus seis prompts existentes deben añadir ese método (PHPStan los señala).
- **Colecciones**: el elemento repetido debe ser hijo directo de `.collection`; para posiciones persistidas, un `input` con `data-collection-position`; para el número visible, un elemento con `data-collection-index`; para prototipos anidados, un `placeholder` distinto de `__name__` (`data-collection-placeholder-value`).
- **Ficheros copia de reporting**: cualquier cambio en ellos debe hacerse primero en reporting y traerse byte a byte, o el siguiente merge volverá a dar conflicto.

## Tests

- **Unitarios**:
  - `NodeListerTest`: `testAdminsAndOwnersSeePropertiesWhenEnabled`, `testAdminsAndOwnersDoNotSeePropertiesWhenDisabled`, `testAdminsAndOwnersSeeEmployeesWhenEnabled`, `testAdminsAndOwnersDoNotSeeEmployeesWhenDisabled`, `testAdministrationItemsKeepOrderWhenHumanResourcesDisabled` (`['Cuentas', 'Usuarios', 'Firma de documentos', 'Registro']`), `testOwnersAndUsersReceiveProviderNodesFirst`, `testOwnersAndUsersWithoutProviderNodesKeepTheDefaultMenu`, `testAdminsDoNotReceiveProviderNodes`, `testPreselectedItemRemainsSelectedWhenCurrentPathDoesNotMatch`; helpers `group()` y `accountNodeProvider()` (clase anónima).
  - `LinkEmployeeOnUserRegisteredSubscriberTest`: `testHandleUserRegisteredDoesNothingWhenHumanResourcesDisabled`, `testHandleUserRegisteredDoesNotLinkExistingEmployeeWhenHumanResourcesDisabled`, `testIsSubscribedToUserRegisteredEvenWhenDisabled`.
  - `ConsultantSupervisionPolicyTest`: consultora activa/inactiva/borrada/no marcada, cuenta sin consultora o con otra, cuenta borrada, sin repositorio, `supervisedAccountIds()` y `supervisorsOf()`.
  - `GeminiHttpClientTest`: data provider `thinkingConfigs()` (Gemini 3 flash/pro, 2.5 flash/pro, 2.0 sin config), JSON correcto, respuesta cortada (`MAX_TOKENS`), prompt bloqueado (`SAFETY`) y JSON inválido.
  - `MarkdownExtensionTest`: conversión, escape de HTML, enlaces `javascript:` neutralizados, enlaces y autoenlaces externos en pestaña nueva, relativos en la misma pestaña.
- **Aceptación**: sin escenarios nuevos. Los features de `tests/Acceptance/Property/` y `tests/Acceptance/HumanResources/` cubren el estado activo (valor de `.env`). Los siete pasos de `WebContext.php` (`:114,355,371,382,403,419,509`) no tienen todavía escenarios en biohydra.
- **Ejecutar**: `make test-unit options="--filter 'NodeListerTest|LinkEmployeeOnUserRegisteredSubscriberTest|ConsultantSupervisionPolicyTest|GeminiHttpClientTest|MarkdownExtensionTest'"`; Behat completo con `make test-acceptance` o `make test-parallel`.
- **Huecos**: el estado apagado de las rutas (404) no tiene test automático, porque el flag llega al contenedor desde `.env` y un escenario no puede cambiarlo; se verificó a mano (sección `## Implementación` de `plan.md`). `AbstractAuthorizer::supervisesAccount()`/`supervisedAccountIds()` no tienen test propio en biohydra: solo `ConsultantSupervisionPolicyTest` cubre la política.

## Desviaciones y deuda

Detalle en `## Implementación` de `plan.md`, en `review.md` y en `review-fix.md`:

- `symfony/expression-language` añadido (no previsto en el plan) para que funcionen las `condition` de ruta.
- `.claude/ARCHITECTURE.md` pasó a ser un symlink a `/opt/htdocs/ai/biohydra/ARCHITECTURE.md` (Q1 de la review, aplicado en `review-fix.md`), no a `ai/hydra` como dicen el plan y `CLAUDE.md`.
- B1 (asumido): `AccountAuthorizer` y `AnnouncementAuthorizer` no reenvían la `ConsultantSupervisionPolicy` (ver *Autorización y ámbito*).
- `GeminiPrompt::thinkingLevel()` obligatorio: rompe events y food-loss hasta que sus seis prompts lo implementen al mezclar biohydra (decisión consciente, opción A).
- `LinkEmployeeOnUserRegisteredSubscriber` vive en `Domain` pero depende de `LoggedUser` y ahora del atributo `Autowire` de Symfony.
- En `NodeLister`, el grupo «Novedades» de admin no respeta `announcementsEnabled` (fijado por `testAdminsSeeAnnouncementsWhenDisabled`).
