# Impedir HTML en el nombre de usuario — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-26 · **Bounded Contexts:** `Security`

## Qué hace y por qué

Un nombre de usuario con HTML (`<img src=x onerror=...>`) se pintaba sin escapar en pantallas de administración porque el filtro Twig `user_name` está declarado `is_safe => ['html']` (`src/Security/Infrastructure/Symfony/Twig/UserExtension.php:30`). Era un XSS almacenado, con escalada desde `Owner` a administradores.

Como la salida de `user_name` debe seguir siendo HTML (un usuario borrado se pinta como `<s>{userId}</s>`), no se quita `is_safe`. La corrección tiene dos capas, ambas decididas en el plan (tabla *Decisiones tomadas*):

1. **Invariante de dominio en el alta:** el value object `Username` rechaza `<` y `>`; `User::register()` lo construye antes de registrar el evento.
2. **Defensa en profundidad en la vista:** `UserExtension::userName()` escapa el nombre y el `{userId}` y deja `<s>…</s>` como único HTML propio. Neutraliza usuarios ya existentes con HTML.

La regla solo se aplica al alta. Cargar un usuario existente o reconstruir sus eventos no la evalúa, de modo que los usuarios antiguos siguen siendo válidos.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Security/Domain/Model/User/Username.php` | VO; `PATTERN = '/^[^<>]*$/'`; lanza la excepción | nuevo |
| Dominio | `src/Security/Domain/Model/User/InvalidUsernameException.php` | `\DomainException` con el mensaje de usuario | nuevo |
| Dominio | `src/Security/Domain/Model/User/User.php:177` | `register()` envuelve el nombre en `new Username(...)` | modificado |
| Infraestructura | `src/Security/Infrastructure/Symfony/Form/RegisterUserType.php`, `AdminRegisterUserType.php` | `Regex` con `Username::PATTERN` en el campo `username` | modificado |
| Infraestructura | `src/Security/Infrastructure/UI/Web/User/RegisterUserTrait.php:62` | `catch (InvalidUsernameException)` -> `FormError` + flash `danger` | modificado |
| Infraestructura | `src/Security/Infrastructure/UI/Api/User/UserRequest.php:18` | `#[Assert\Regex]` sobre `username` | modificado |
| Infraestructura | `src/Security/Infrastructure/UI/Api/User/RegisterController.php:29` | documenta la respuesta `422` (OpenAPI) | modificado |
| Infraestructura | `src/Security/Infrastructure/UI/Console/User/RegisterCommand.php:53` | captura la excepción, muestra error y devuelve `Command::FAILURE` | modificado |
| Infraestructura | `src/Security/Infrastructure/Symfony/Twig/UserExtension.php:58` | escapa nombre y `{userId}` con `htmlspecialchars` | modificado |
| Traducciones | `templates/security/translations/username.html.twig` | plantilla de extracción para que el mensaje llegue a `messages` | nuevo |
| Traducciones | `translations/{messages+intl-icu,validators}.{es,en}.xlf` | mensaje en ES y EN (unidad `dXBo.Go`) | modificado |
| Tests | `tests/Unit/Security/Domain/Model/User/{UsernameTest,UserTest}.php`, `tests/Unit/Security/Infrastructure/Symfony/Twig/UserExtensionTest.php`, `tests/Unit/Security/Application/User/Register/RegisterUserServiceTest.php` | reglas y escape | nuevo / modificado |
| Tests | `tests/Acceptance/Security/{user-register,user-history,recorded-event-list}.feature`, `SecurityContext.php`, `tests/Acceptance/Shared/ApiContext.php` | escenarios web, API, consola y escape | modificado |

No se han tocado `UserRegistered`, `RegisterUserService`, `DoctrineUserRepository` ni los mappings: no hay migración.

## Modelo de dominio

- `Username` (`Username.php:7-22`): `final readonly`, `Stringable`. El constructor comprueba `preg_match(self::PATTERN, $value)` y lanza `InvalidUsernameException` si falla. La cadena vacía es válida para el VO (la obligatoriedad la fija el formulario).
- `InvalidUsernameException` (`InvalidUsernameException.php`): mensaje fijo «El nombre de usuario no puede contener los caracteres < ni >».
- `User::register()` (`User.php:174-181`): `username: (string) new Username($username)` dentro del `new UserRegistered(...)`, por lo que la excepción salta antes de `recordApplyAndPublish`. El payload del evento sigue siendo un `string`; el constructor de `User` y `applyUserRegistered()` no validan, lo que mantiene válidos a los usuarios antiguos (vigilado por `UserTest::testLegacy*`).

## Flujo de una operación

Alta de usuario (web admin `admin_users_register`, web propietario `users_register`, API `POST /api/users`, consola `security:user:register`):

1. **Formulario web:** `RegisterUserType`/`AdminRegisterUserType` aplican `Regex(Username::PATTERN)`. Si falla, el formulario no es válido, se vuelve a pintar con el mensaje traducido por el dominio `validators` y no se llega al servicio.
2. **API:** `#[MapRequestPayload] UserRequest` valida la `Regex` antes de entrar al controlador y responde `422`.
3. **Servicio:** `RegisterUserService` -> `User::register()` -> `Username`. Si una entrada llega sin pasar por (1) o (2), el dominio lanza `InvalidUsernameException`.
4. **Captura:** la web la convierte en `FormError` en `username` más flash `danger` (`RegisterUserTrait.php:62-65`); la consola, en `$io->error()` y código de salida `1` (`RegisterCommand.php:53-59`); la API la deja subir como error no controlado (en la práctica inalcanzable porque la `Regex` ya la ha cortado).
5. **Vista:** `user_name` -> `UserExtension::userName()` (`UserExtension.php:49-63`) devuelve el nombre con `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`; para un usuario inexistente, un `TranslatableMessage('<s>{userId}</s>')` con el id escapado. El resultado se cachea en `$this->users`.

## Autorización y ámbito

Sin cambios: el alta sigue autorizándose en `RegisterUserService` con `Action::Register`; el controlador API conserva `denyAccessUnlessGranted(Role::Admin->value)`. La regla se aplica igual a todos los roles.

## Persistencia y migración

Ninguna. No hay cambios de esquema ni backfill; los usuarios existentes con HTML no se modifican (solo se escapan al pintarlos).

## Configuración y operación

Sin variables de entorno ni colas. Consola: `make sf c="security:user:register <username> <password>"`; con `<` o `>` imprime el error y sale con `1`. Traducciones: tras tocar textos, `make translation`.

## Cómo extenderlo

- Para endurecer la regla (más caracteres prohibidos, longitud), cambiar solo `Username::PATTERN` o el constructor: formularios y API leen la misma constante.
- Si se añade un nuevo punto de alta o un cambio de nombre, debe pasar por `new Username(...)` en el agregado; añadir además la `Regex` en su formulario/DTO para dar un error amigable.
- Trampa: cualquier plantilla que pinte `username` con `|raw` fuera de `user_name` volvería a ser vulnerable. `FilterBar` escapa por segunda vez el valor ya escapado (doble escape aceptado en el plan, riesgo *Portabilidad*).
- Trampa: el mensaje se repite como cadena literal en `Username`/excepción, formularios, `UserRequest` y trait; si cambia el texto, actualizar todos y las traducciones.

## Tests

- Unitarios: `UsernameTest` (valores válidos/inválidos), `UserTest` (el alta registra el nombre; usuarios y eventos antiguos con HTML no se validan), `RegisterUserServiceTest::testUsernameWithHtmlIsRejected`, `UserExtensionTest` (escape de nombre e id).
- Behat: `user-register.feature` (admin web, propietario web, API `422`, consola código 1), `user-history.feature` y `recorded-event-list.feature` (escape en pantalla). `ApiContext.php` pasa a usar `getStatusCode()` para `HttpExceptionInterface`.
- Ejecución: `make test-unit options="--filter Username"`, `make test-unit options="--filter UserExtension"`, `make test-acceptance options="tests/Acceptance/Security/user-register.feature"`.

## Desviaciones y deuda

Ver `plan.md` (sección *Implementación*) y `review.md`.

- Fuera del inventario: ajuste de `ApiContext.php` y plantilla de extracción de traducciones.
- Abierto B1 (bajo): los `FormError` del trait se pintan sin traducir (también «El nombre de usuario ya existe»); en `/en/` saldrían en español.
- Abierto B2 (bajo): en `POST /api/users` la validación del payload ocurre antes de autorizar, de modo que un usuario sin permisos recibe `422` en lugar de `403` con un nombre inválido.
- Deuda previa: `RegisterCommand.php` importa `UserMother` de `tests/`.
