# Tokens de API — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-10 (revisado y corregido el 2026-11-11, ver `review.md`) · **Bounded Contexts:** `Security`

## Qué hace y por qué

Antes, la única forma de obtener un JWT para la API era `POST /api/login` con usuario y contraseña, lo que obligaba a guardar la contraseña personal (que también da acceso a la web) en el sistema integrador. Ahora cada usuario puede crear **tokens de API** (nombre, caducidad opcional) desde su perfil y enviarlos a `POST /api/login` **en el campo `password`**. El contrato de la API no cambia y el resultado es el mismo JWT de siempre.

Decisiones clave (tabla «Decisiones tomadas» de `plan.md`):

- Agregado propio `ApiToken` con `userId`, no una colección en `User`, para no tocar la copia de campos de `SecurityUser` ni cargar tokens en cada petición.
- Se guarda solo el hash SHA-256 y un prefijo visible de 12 caracteres; el valor en claro se muestra una única vez en la respuesta al POST de alta.
- La integración con el login es un **decorador** del autenticador `security.authenticator.json_login.api_login` (no un autenticador nuevo), de modo que se conservan `supports`, handlers, límite de intentos (`login_throttling`) y los eventos `LoginFailure`/`LoginSuccess`.
- El JWT emitido con token lleva el claim `api_token_id`; un listener lo valida en cada petición, así que revocar un token invalida los JWT ya emitidos con él.
- Funcionalidad por cuenta: `Account::$apiTokensEnabled` (falso por defecto). Los usuarios sin cuenta (`Admin`/`SuperAdmin`) pueden usarla siempre.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Security/Domain/Model/ApiToken/ApiToken.php` | Agregado (`MAX_ACTIVE_PER_USER = 10`) | nuevo |
| Dominio | `src/Security/Domain/Model/ApiToken/ApiTokenSecret.php` | VO no persistido: genera, hashea y da el prefijo | nuevo |
| Dominio | `.../ApiToken/{ApiTokenId,ApiTokenStatus,ApiTokenFilter,ApiTokenRepository}.php` | Id, estado (`Active`/`Expired`/`Revoked`), filtro, puerto | nuevo |
| Dominio | `.../ApiToken/{ApiTokenRegistered,ApiTokenUsed,ApiTokenRevoked}.php` | Eventos | nuevo |
| Dominio | `.../ApiToken/{ApiTokenLimitReached,ApiTokenExpirationInvalid,ApiTokensNotAllowed}Exception.php` | Excepciones | nuevo |
| Dominio | `src/Security/Domain/Model/Account/Account.php`, `AccountApiTokensPermissionChanged.php` | Indicador `apiTokensEnabled`, `allowsApiTokens()`, `changeApiTokensPermission()` | modificado / nuevo |
| Dominio | `src/Security/Domain/Event/ApiToken/RevokeApiTokensOnUserRemoved.php` | Suscriptor de `UserRemoved` | nuevo |
| Aplicación | `src/Security/Application/ApiToken/{ApiTokenAuthorizer,ApiTokenPolicy}.php` | Autorización por acción y política «¿puede/está utilizable?» | nuevo |
| Aplicación | `.../ApiToken/Register/*`, `Revoke/*`, `List/*` | Casos de uso con usuario autenticado | nuevo |
| Aplicación | `.../ApiToken/Authenticate/*`, `MarkAsUsed/*` | Casos de uso del login con token (sin authorizer) | nuevo |
| Aplicación | `.../ApiToken/RevokeAll/RevokeApiTokensOfRemovedUser*` | Revocado masivo al eliminar un usuario (sin authorizer) | nuevo |
| Aplicación | `src/Security/Application/Account/Update/UpdateAccount{Request,Response,Service}.php` | Campo `apiTokensEnabled` con `Action::Admin` | modificado |
| Persistencia | `src/Security/Infrastructure/Persistence/Doctrine/Mapping/ApiToken.ApiToken.orm.xml`, `Account.Account.orm.xml` | Tabla `api_tokens`; columna `api_tokens_enabled` | nuevo / modificado |
| Persistencia | `.../Doctrine/Type/ApiTokenIdType.php`, `config/packages/doctrine.yaml` | Tipo `api_token_id` | nuevo / modificado |
| Persistencia | `.../Doctrine/Repository/DoctrineApiTokenRepository.php`, `.../InMemory/Repository/InMemoryApiTokenRepository.php` | Repositorios (Doctrine en dev/prod, InMemory en test) | nuevo |
| Persistencia | `migrations/Version20261110225038.php` | Migración aplicada | nuevo |
| Autenticación | `src/Security/Infrastructure/Symfony/Security/ApiToken/{ApiLoginAuthenticator,ApiTokenBadge}.php` | Decorador del autenticador y badge | nuevo |
| Autenticación | `src/Security/Infrastructure/Symfony/Event/ApiTokenJwtDecodedListener.php` | Rechaza JWT con token no utilizable | nuevo |
| Web (perfil) | `src/Security/Infrastructure/UI/Web/ApiToken/{ListController,RegisterController,RevokeController}.php` | Mis tokens | nuevo |
| Web (terceros) | `.../UI/Web/User/ApiToken/{ListUserApiTokensTrait,RevokeUserApiTokenTrait,ListController,RevokeController}.php`, `.../UI/Web/Admin/User/ApiToken/{ListController,RevokeController}.php` | Tokens de otro usuario (Owner y Admin) | nuevo |
| Web | `src/Security/Infrastructure/Symfony/Form/{RegisterApiTokenType,ListApiTokensType}.php`, `Form/Type/ApiTokenStatusType.php`, `Form/UpdateAccountType.php` | Formularios | nuevo / modificado |
| Web | `src/Security/Infrastructure/Symfony/Twig/ApiTokenExtension.php` | Función Twig `can_have_api_tokens()` | nuevo |
| Plantillas | `templates/security/api-tokens/{list,register,created,_table,_status_badge}.html.twig`, `templates/security/admin/users/api-tokens.html.twig`, `templates/security/users/api-tokens.html.twig`, `templates/components/List/Action/Revoke.html.twig`, `templates/security/translations/apiTokenStatus.html.twig` | Pantallas | nuevo |
| Plantillas | `templates/components/Topbar/Profile.html.twig`, `templates/security/{admin/,}users/edit.html.twig`, `templates/security/admin/accounts/{edit,list}.html.twig` | Entrada de menú, acción de cabecera, campo y columna | modificado |
| Config | `config/packages/csrf.yaml` (`revoke`), `config/packages/nelmio_api_doc.yaml` (descripción de `password`) | | modificado |

## Modelo de dominio

**`ApiToken`** (`ApiToken.php`): `id`, `userId`, `accountId` (copia de la cuenta propietaria del usuario al crear), `name` (≤ 100), `tokenHash` (64), `tokenPrefix` (12), `expiresAt`, `lastUsedAt`, `revokedAt`, `revokedBy`, `registeredAt`. Usa el trait `AggregateRoot`; el estado se reconstituye en `applyApiTokenRegistered/Used/Revoked`.

| Evento | Payload | Notas |
|---|---|---|
| `ApiTokenRegistered` | `apiTokenId`, `userId`, `accountId`, `name`, `tokenPrefix`, `expiresAt` | Lleva `tokenHash` como propiedad, pero **no** en `payload()`: el hash no llega a la auditoría |
| `ApiTokenUsed` | `apiTokenId`, `userId` | Fija `lastUsedAt` |
| `ApiTokenRevoked` | `apiTokenId`, `userId`, `revokedBy` | Fija `revokedAt` y `revokedBy` |

Los eventos se crean con `occurredOn: $now`, y los `apply*` toman las fechas de `occurredOn()`.

**Estado** (`ApiToken::status()`): `Revoked` si `revokedAt` no es nulo (gana sobre caducado); `Expired` si `expiresAt <= now`; `Active` en otro caso. `currentStatus()` lo evalúa con la hora actual para las plantillas.

**Invariantes y dónde se validan**

- `register()`: `expiresAt` no nulo y `<= now` lanza `ApiTokenExpirationInvalidException`; nombre vacío tras `trim` o de más de 100 caracteres lanza `\InvalidArgumentException`.
- `revoke()` es idempotente: si ya está revocado no hace nada ni publica evento.
- Máximo 10 activos por usuario y cuenta con permiso: se comprueban en `RegisterApiTokenService` (`ApiTokenLimitReachedException`, `ApiTokensNotAllowedException`), no en el agregado.
- `token_hash` único: restricción `uq_api_token_token_hash`.

**`ApiTokenSecret`**: `generate()` produce `hyd_` + base64url de 32 bytes aleatorios (43 caracteres); `hashFor()` es SHA-256 simple sin HMAC (decisión del plan: 256 bits de entropía, y rotar `APP_SECRET` no debe invalidar los tokens); `prefix()` devuelve los 12 primeros caracteres. Constructor privado y `#[\SensitiveParameter]`; `fromPlainValue()` existe para que Behat registre tokens con un valor conocido.

## Flujo de una operación

### Crear un token

`GET|POST /{_locale}/profile/api-tokens/register` (`profile_api_tokens_register`) → `RegisterController.php` (403 si `ApiTokenPolicy::userCanHaveTokens()` es falso) → `RegisterApiTokenType` (`name` obligatorio, `expiresAt` opcional con `GreaterThan('today')`) → `RegisterApiTokenService.php`:

1. Autoriza `Action::Register` sobre un `ApiToken` sin persistir con `userId`/`accountId` del usuario (solo el propio usuario).
2. `ApiTokensNotAllowedException` si la cuenta no permite tokens.
3. `ApiTokenLimitReachedException` si `countActiveByUserId() >= MAX_ACTIVE_PER_USER`.
4. `expiresAt->setTime(23, 59, 59)`, genera `ApiTokenSecret`, `register()` y `repository->add()`.
5. Devuelve `RegisterApiTokenResponse` con `plainValue`.

El controlador renderiza `created.html.twig` **en la propia respuesta al POST** con `Cache-Control: no-store`, sin redirección, de modo que el valor en claro no pasa por sesión, flash ni URL. El error del límite se añade al campo `name` (el layout `register.html.twig` no pinta errores globales) y el de caducidad a `expiresAt` (`RegisterController.php`).

### Login con token

`POST /api/login` → `ApiLoginAuthenticator.php` (decora `security.authenticator.json_login.api_login`, `#[AsDecorator]`):

1. `authenticate()` delega en el autenticador original, toma `UserBadge` y `PasswordCredentials` y devuelve un `Passport` con `CustomCredentials` y un `ApiTokenBadge`.
2. El checker: cadena vacía → `false`; contraseña válida (`isPasswordValid`) → `true` sin consultar tokens; si no, `AuthenticateWithApiTokenService` (busca por hash, exige que pertenezca al usuario y `ApiTokenPolicy::isUsable()`); si da id, lo guarda en el badge y devuelve `true`. El checker no tiene efectos secundarios.
3. `createToken()` copia el id al atributo `api_token_id` del token de seguridad.
4. `onAuthenticationSuccess()` (ya con el token en `TokenStorage`, por lo que la auditoría atribuye `ApiTokenUsed` al usuario) invoca `MarkApiTokenAsUsedService`, crea el JWT con `JWTTokenManager::createFromPayload($user, ['api_token_id' => id])` y lo entrega con `AuthenticationSuccessHandler`.
5. Cualquier fallo es el 401 estándar de LexikJWT, sin distinguir motivo; cuenta una sola vez para `login_throttling` (3 intentos).

El pasaporte ya no lleva `PasswordCredentials`, así que `TwoFactorTokenCreatedListener` no actúa: `/api/login` sigue exento de segundo factor (plan `2026-09-23-2fa`). No añadir `PasswordCredentials` «por compatibilidad».

### Peticiones con el JWT

`ApiTokenJwtDecodedListener.php` (evento `Events::JWT_DECODED`): sin claim `api_token_id` no hace nada (JWT por contraseña); con claim vacío o no cadena, `markAsInvalid()`; si el token no existe o `!ApiTokenPolicy::isUsable()` (revocado, caducado o cuenta sin permiso) → `markAsInvalid()` y la API responde 401. Coste: una consulta por petición solo para JWT obtenidos con token. El JWT vive 3600 s (`config/packages/lexik_jwt_authentication.yaml`).

### Revocar

`GET /{_locale}/profile/api-tokens/{apiTokenId}/revoke` (`#[CsrfProtected('revoke')]`, `_token` añadido por `List:Action:Revoke`) → `RevokeController.php` → `RevokeApiTokenService.php` (carga, autoriza `Action::Remove`, `revoke()`, `add()`); `InvalidArgumentException` → 404, `UnauthorizedActionException` → 403; flash `Token revocado` y redirección al listado. Las rutas de terceros (`RevokeUserApiTokenTrait.php`) exigen además `denyAccessUnlessGranted(Action::Edit, $user)` y que el token pertenezca al usuario de la URL (404 si no: evita IDOR).

### Listar

`ListApiTokensService.php` exige `userId`, carga el usuario objetivo y autoriza `Action::List` sobre un `ApiToken` sintético con su cuenta. Los controladores fuerzan `$filter->userId` después de `FilterTrait::filter()`, porque la clave de sesión del filtro no incluye el `{userId}` de la ruta (deuda conocida, `src/Shared/Infrastructure/UI/Web/CRUD/FilterTrait.php`).

### Eliminar un usuario

`RevokeApiTokensOnUserRemoved.php` se suscribe a `UserRemoved` y llama a `RevokeApiTokensOfRemovedUserService.php` (sin authorizer: el borrado ya lo autorizó `RemoveUserService`) con el usuario conectado o, si no lo hay (consola), `UserMother::systemUser()` como actor. Revoca todos los tokens activos del usuario.

## Autorización y ámbito

`ApiTokenAuthorizer.php` (extiende `AbstractAuthorizer`; usa `userHasRole()` y `currentAccountId()`, no `$user->accountId()`):

| Acción | Regla |
|---|---|
| `Register` | solo el propio usuario |
| `List`, `View`, `Remove` | el propio usuario, o un gestor |
| `Edit` | nunca (los tokens no se editan; se usa en la web de terceros solo vía `UserAuthorizer`) |

Gestor (`isManagedBy`): `SuperAdmin` siempre; `Admin` solo si el dueño existe y **no** es `SuperAdmin` (hallazgo A2 de la review: la regla difiere de lo que decía el plan); `Owner` si la cuenta actual (impersonación incluida) coincide con la `accountId` del token. El `Owner` no mira el rol del dueño (duda Q2 de la review, descartada por ahora).

`ApiTokenPolicy.php`: `userCanHaveTokens()` = usuario sin cuenta o cuenta con `allowsApiTokens()` (usa la cuenta **propietaria**, no la impersonada); `isUsable()` = token activo y (sin `accountId` o cuenta existente con permiso).

`UpdateAccountService.php:29-49`: autoriza `Action::Edit` y, si cambia `apiTokensEnabled`, `Action::Admin` (solo `Admin`/`SuperAdmin`) **antes** de aplicar cambio alguno. Un `Owner` puede editar su cuenta (`AccountAuthorizer::canEdit`), pero no el indicador.

Desactivar el indicador no revoca los tokens: quedan en suspenso (no sirven para login y sus JWT se rechazan) y vuelven a funcionar al reactivarlo.

Rutas web: `profile_api_tokens*` (`ROLE_USER`), `users_api_tokens*` (`ROLE_OWNER`), `admin_users_api_tokens*` (`ROLE_ADMIN`). La suplantación no se bloquea: un `SuperAdmin` que suplanta puede crear tokens a nombre del usuario suplantado y queda auditado.

## Persistencia y migración

- Tabla `api_tokens` (mapping `ApiToken.ApiToken.orm.xml`): 11 columnas, índice `idx_api_token_user (user_id)` y único `uq_api_token_token_hash (token_hash)`. Nunca se borra una fila (el puerto no tiene `remove()`).
- Columna `account.api_tokens_enabled TINYINT(1) NOT NULL` (sin `DEFAULT 0` explícito: MySQL rellena con 0 las filas existentes; verificado en la review).
- Migración aplicada: `migrations/Version20261110225038.php`. `migration.sql` de la carpeta del plan es la propuesta original.
- `DoctrineApiTokenRepository.php` filtra por usuario y estado (Revoked: `revokedAt IS NOT NULL`; Expired: sin revocar y `expiresAt <= :now`; Active: sin revocar y sin caducidad o futura); ordena por `name`, `expiresAt`, `lastUsedAt` o `registeredAt` (por defecto `registeredAt DESC`). `InMemoryApiTokenRepository.php` replica la semántica con `ApiToken::status($now)`; los tests de aceptación usan el InMemory.

## Configuración y operación

- Sin variables de entorno ni flags globales nuevos; `config/packages/security.yaml` no cambia. Todo se cablea por autowiring y atributos.
- `config/packages/csrf.yaml`: id `revoke` en `stateless_token_ids`.
- Para comprobar el decorador: `make sf c="debug:container security.authenticator.json_login.api_login"`. Si no se aplicara, el login por contraseña seguiría igual y fallaría solo el login con token (lo cubre `login.feature`).
- No hay comandos de consola ni tareas programadas; no hay purga de tokens caducados/revocados.
- No se envían correos ni notificaciones al crear o revocar.
- Zona horaria: la caducidad es 23:59:59 en la hora del servidor.

## Cómo extenderlo

- **Nueva pantalla o acción sobre tokens:** autorizar siempre con `ApiTokenAuthorizer` (nueva acción en el authorizer, no en el controlador) y, si es GET con efectos, `#[CsrfProtected('revoke')]` o un id nuevo añadido a `stateless_token_ids`.
- **Ámbitos de permisos por token:** el JWT ya lleva `api_token_id`; el punto natural es `ApiTokenJwtDecodedListener` (cargar el token y reducir roles) y `ApiLoginAuthenticator::onAuthenticationSuccess` (payload). Hoy el JWT hereda todos los permisos del usuario.
- **Token como `Authorization: Bearer` directo:** fuera de alcance; implicaría un autenticador propio en el firewall `api`.
- **Gestión por API REST:** no existe; crearía endpoints con `create-api-endpoint` reutilizando los servicios `Register`/`Revoke`/`List`.
- Trampas: no cambiar la forma del hash sin migrar los tokens existentes; no loguear ni poner en eventos el valor en claro; mantener el checker sin efectos y con respuesta uniforme (solo `true`/`false`); `InMemoryUserRepository::find()` excluye usuarios eliminados y `DoctrineUserRepository::find()` no durante `UserRemoved` — por eso el revocado por borrado no depende del actor ni del repositorio de usuarios.

## Tests

- **Unitarios** (`tests/Unit/Security/...`): dominio (`ApiTokenTest`, `ApiTokenSecretTest`, repositorios Doctrine e InMemory con la suite abstracta `ApiTokenRepositoryTest`), aplicación (`ApiTokenAuthorizerTest`, `ApiTokenPolicyTest`, servicios `Register`, `Revoke`, `List`, `Authenticate`, `MarkAsUsed`, `RevokeAll`), `RevokeApiTokensOnUserRemovedTest`, `ApiLoginAuthenticatorTest`, `ApiTokenJwtDecodedListenerTest` y `UpdateAccountServiceTest` (indicador). Dobles: `ApiTokenMother`, `ApiTokenRepositoryMother`, `AccountMother::withApiTokensEnabled`.
- **Behat** (`tests/Acceptance/Security/`): `login.feature` (login con contraseña errónea, con token, revocado, caducado, de otro usuario, cuenta sin permiso, suspensión/reactivación, extremo a extremo con el JWT devuelto), `api-token-register.feature`, `api-token-list.feature`, `api-token-revoke.feature`, `user-api-tokens.feature`, y ampliaciones de `account-edit.feature`, `user-remove.feature` y `change-password.feature`. Pasos nuevos en `SecurityContext.php` y `ApiContext.php` (p. ej. `When I use the token of the response as bearer`).
- Ejecución: `make test-unit options="--filter ApiToken"`, `make test-acceptance options="tests/Acceptance/Security/login.feature"` (o cada `api-token-*.feature` / `user-api-tokens.feature`); completo con `make test-parallel`.
- Huecos: ningún test comprueba de extremo a extremo el actor del `RecordedEvent` de `ApiTokenUsed` (lo garantiza el orden de Symfony, `review.md` B2).

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`) y `review.md` (sin hallazgos abiertos). Lo relevante respecto a lo que decía el plan, ya corregido en el código:

- `Admin` no gestiona los tokens de un `SuperAdmin` (A2).
- `AuthenticateWithApiTokenService` solo valida; el uso se marca en `onAuthenticationSuccess` mediante `MarkApiTokenAsUsedService` (B2).
- El revocado por borrado usa `RevokeApiTokensOfRemovedUserService` sin authorizer y actor `systemUser` si no hay usuario conectado; `RevokeUserApiTokensService` fue eliminado (B1, B4).
- `UpdateAccountService` autoriza `Action::Admin` antes de modificar nada (M2).
- `ApiTokenJwtDecodedListener` también invalida claims vacíos o no cadena.

Deuda y aceptados:

- La acción «Tokens de API» de la edición de usuario sigue visible para un `Admin` sobre un `SuperAdmin` y devuelve 403 (M3, aceptado).
- `Owner` puede gestionar tokens de un `Admin`/`SuperAdmin` asignado a su cuenta (Q2, descartado por ahora).
- `FilterTrait` no incluye parámetros de ruta en la clave de sesión del filtro; `templates/shared/layout/register.html.twig` no pinta errores globales; `Account::applyAccountRemoved()` usa `new \DateTimeImmutable('NOW')`.
- `src/Security/Domain/Event/ApiToken/RevokeApiTokensOnUserRemoved.php` importa `UserMother` (clase de `tests/`) para el actor de sistema, igual que `RecordEventSubscriber`.
