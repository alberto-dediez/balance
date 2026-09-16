# Acceso con Google y Microsoft (SSO) en la web — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Revisión:** `review.md`, `review-fix.md` · **Implementado:** 2026-09-15 · **Bounded Context:** `Security` · **Migración:** ninguna

## Qué hace y por qué

Un usuario ya dado de alta en la plataforma puede entrar en la zona privada web con su cuenta de Google o de Microsoft, sin teclear la contraseña de Hydra. La pantalla de login (`templates/security/login.html.twig:54-65`) pinta, bajo el botón «Acceder», un botón por cada proveedor habilitado; al pulsarlo el usuario se autentica en el proveedor y vuelve a Hydra ya identificado.

El emparejado es **exclusivamente por email**: el email que devuelve el proveedor, normalizado a minúsculas, tiene que coincidir con el `username` de un usuario activo (`UserProvider::loadUserByIdentifier()`, `src/Security/Infrastructure/Symfony/Security/UserProvider.php:20-29`). **No hay alta automática (JIT)**: si el email no corresponde a ningún usuario activo, el acceso se rechaza con un mensaje en el propio login y no se crea nada. No se persiste el `sub` del proveedor, por lo que no hubo cambio de esquema ni de persistencia del agregado `User`.

El diseño gira alrededor de un puerto de infraestructura `SsoProvider` con clave por proveedor, un `SsoProviderRegistry` alimentado por tag, **un único** `SsoAuthenticator` en el firewall `main` y **un único** controlador con `{provider}` en la ruta. El motivo, recogido en la tabla de decisiones de `plan.md`, es que los proyectos derivados (`vsme`, `waste`) puedan absorber su «SSO interno» contra Bioscore como un `BioscoreSsoProvider` más, sin tocar el flujo común. Todo lo que ya existía alrededor del login (`UserProvider`, `UserChecker`, `LoginSuccessEventListener`, `SetPreferredLocaleOnLoginListener`, `login_throttling`, `remember_me`) funciona con cualquier authenticator del firewall y no se modificó.

## Mapa de piezas

### Puerto y proveedores — `src/Security/Infrastructure/Sso/`

| Fichero | Rol | Estado |
|---|---|---|
| `SsoProvider.php` | Interfaz del puerto: `key()`, `label()`, `isEnabled()`, `loginUrl()`, `authenticate(Request)`. Lleva `#[AutoconfigureTag('security.sso_provider')]` | Nuevo |
| `SsoIdentity.php` | `final readonly class` con `public string $email` y `public ?string $name` | Nuevo |
| `SsoProviderRegistry.php` | Resuelve proveedores por clave (`get()`), lista los habilitados (`enabled()`) y todos (`all()`), vía `#[AutowireIterator('security.sso_provider')]` | Nuevo |
| `SsoAuthenticationFailedException.php` | `\RuntimeException` con constructores nominales `unverifiedEmail()`, `missingEmail()`, `fromProvider()` | Nuevo |
| `UnknownSsoProviderException.php` | `\RuntimeException` lanzada por `SsoProviderRegistry::get()` | Nuevo |
| `OAuth2/GoogleSsoProvider.php` | Proveedor Google sobre `KnpU\OAuth2ClientBundle\Client\ClientRegistry`. `#[When(env: 'dev')]` + `#[When(env: 'prod')]` | Nuevo |
| `OAuth2/MicrosoftSsoProvider.php` | Proveedor Microsoft, mismo esqueleto | Nuevo |
| `InMemory/InMemorySsoProvider.php` | Doble de test configurable (`identifyAs()`, `fail()`, `disable()`, `enable()`, `reset()`) | Nuevo |

### Symfony Security e interfaz web

| Fichero | Rol | Estado |
|---|---|---|
| `src/Security/Infrastructure/Symfony/Security/SsoAuthenticator.php` | Authenticator único del flujo: resuelve el proveedor por `{provider}`, delega en él, construye el `SelfValidatingPassport` y gestiona éxito y fallo | Nuevo |
| `src/Security/Infrastructure/UI/Web/Sso/SsoController.php` | Rutas `app_login_sso` y `app_login_sso_callback`, sin prefijo de locale | Nuevo |
| `src/Security/Infrastructure/UI/Web/LoginController.php` | Inyecta `SsoProviderRegistry` y pasa `sso_providers` a la plantilla (`:16,:27`) | Modificado |
| `templates/security/login.html.twig` | Separador «o» y un enlace-botón por proveedor habilitado (`:54-65`) | Modificado |
| `translations/messages+intl-icu.{es,en}.xlf` | Claves `o` y `Continuar con {provider}` | Modificado |
| `translations/security.{es,en}.xlf` | Claves `SSO authentication could not be completed.` y `No user is registered with the identity provider email.` | Modificado |

### Configuración

| Fichero | Rol | Estado |
|---|---|---|
| `composer.json` | `knpuniversity/oauth2-client-bundle ^2.20`, `league/oauth2-google ^5.0`, `thenetworg/oauth2-azure ^2.2` | Modificado |
| `config/bundles.php` | `KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle::class => ['all' => true]` (registrado a mano: la receta de Flex es de `recipes-contrib` y quedó `IGNORING`) | Modificado |
| `config/packages/knpu_oauth2_client.yaml` | Clientes `google` (tipo `google`) y `microsoft` (tipo `azure`, `default_end_point_version: '2.0'`) | Nuevo |
| `config/packages/security.yaml` | `entry_point: form_login` y `custom_authenticators: [SsoAuthenticator]` en el firewall `main` (`:33-36`) | Modificado |
| `config/routes.yaml` | `security-web` excluye `UI/Web/Sso/`; nuevo recurso `security-sso` **sin** prefijo `/{_locale}` (`:25,:44-48`) | Modificado |
| `src/Security/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros de flag, `exclude` del `InMemory`, `$defaultLocale` del authenticator y del controlador, y los bloques `when@dev` / `when@prod` / `when@test` | Modificado |
| `.env` | `SSO_GOOGLE_*`, `SSO_MICROSOFT_*`, `SSO_MICROSOFT_TENANT=common` (`:88-94`) | Modificado |
| `phpstan.dist.neon` | `ignoreErrors` acotado `nullsafe.neverNull` para `SsoAuthenticator.php` | Modificado |
| `config/reference.php` | `KnpuOauth2ClientConfig` (regenerado al limpiar caché) | Modificado |
| `assets/icons/simple-icons/{google,microsoft}.svg` | Iconos de UX Icons usados por `ux_icon('simple-icons:' ~ provider.key)` | Nuevos |

### Corrección fuera del alcance original (Q2 de `review-fix.md`)

`src/Security/Infrastructure/Persistence/Doctrine/Repository/DoctrineUserRepository.php:44-53` — al reconstruir el `User` para resolver su `Account`, `add()` propaga ahora también `recoverToken`, `preferredLocale` y `pictureId`, que antes se perdían.

## Modelo de dominio

**Sin cambios.** No hay agregado, evento ni caso de uso nuevo:

- La autenticación es responsabilidad del firewall (infraestructura), igual que ya lo era `form_login`.
- El emparejado usa `UserRepository::findByUsername()` a través de `UserProvider`.
- El login SSO produce el mismo `UserLogged` que el login por contraseña, publicado por `LoginSuccessEventListener` para cualquier authenticator del firewall `main`. El payload **no** incluye el proveedor (decisión del plan, para no tocar `RecordedEvent`).
- No hay `Authorizer` implicado: `/login/sso/*` es público y la decisión de quién entra la toman `UserProvider` (usuario existente y activo) y `UserChecker`.

El único tipo nuevo con semántica propia es `SsoIdentity` (email + nombre opcional), que vive en `Infrastructure/Sso/` y no en `Domain/` porque depende de `Symfony\Component\HttpFoundation\Request` y de OAuth2.

## Flujo de una operación

### 1. Pintar los botones

`LoginController::login()` (`src/Security/Infrastructure/UI/Web/LoginController.php:16-28`) inyecta `SsoProviderRegistry` y pasa `$ssoProviders->enabled()` como `sso_providers`. La plantilla, si la lista no está vacía, pinta el separador «o» y un `<a>` por proveedor a `path('app_login_sso', {provider: provider.key, locale: app.request.locale})` con el icono `simple-icons:<key>` y el texto `'Continuar con {provider}'|trans({'provider': provider.label})` (`templates/security/login.html.twig:54-65`).

El `locale` de la query **no** es el prefijo de la ruta: viaja como parámetro para que un fallo posterior devuelva al login del idioma desde el que se arrancó (corrección B3).

### 2. Arranque — `GET /login/sso/{provider}`

`SsoController::start()` (`src/Security/Infrastructure/UI/Web/Sso/SsoController.php:23-39`):

1. `$this->providers->get($provider)`; si lanza `UnknownSsoProviderException` → **404**.
2. Si `!$ssoProvider->isEnabled()` → **404**.
3. Guarda en sesión, bajo `SsoAuthenticator::LOCALE_SESSION_KEY` (`'sso.locale'`), el locale de la query validado con `Locale::tryFrom()`, o `%app.locale_default%` si falta o no es soportado (`:47-52`).
4. Redirige a `$ssoProvider->loginUrl()`.

En los proveedores OAuth2 `loginUrl()` es `$clientRegistry->getClient(KEY)->redirect($scopes, [])->getTargetUrl()`, lo que además deja el `state` en sesión (`GoogleSsoProvider.php:40-43`, `MicrosoftSsoProvider.php:40-43`). Scopes: Google `['openid', 'email', 'profile']`; Microsoft `['openid', 'profile', 'email']`.

La ruta acepta `requirements: ['provider' => '[a-z]+']` y solo `GET`.

### 3. Vuelta — `GET /login/sso/{provider}/callback`

`SsoController::callback()` es un `never` que lanza `\LogicException`: la petición la intercepta antes el firewall. `SsoAuthenticator::supports()` devuelve `true` solo si `_route === 'app_login_sso_callback'` (`SsoAuthenticator.php:44-47`).

`SsoAuthenticator::authenticate()` (`:49-79`):

1. Lee `{provider}` de los atributos de la petición y lo resuelve en el registro. `UnknownSsoProviderException` → `CustomUserMessageAuthenticationException(FAILURE_MESSAGE)`.
2. Proveedor deshabilitado → mismo `FAILURE_MESSAGE`.
3. `$provider->authenticate($request)`; `SsoAuthenticationFailedException` → `FAILURE_MESSAGE`, conservando la excepción original como `previous`.
4. Devuelve un `SelfValidatingPassport` con:
   - `UserBadge($identity->email, fn)` cuyo *loader* llama a `UserProvider::loadUserByIdentifier()` y convierte `UserNotFoundException` en `CustomUserMessageAuthenticationException(UNKNOWN_USER_MESSAGE)`;
   - `(new RememberMeBadge())->enable()`, de modo que **todo login SSO** deja cookie `remember_me` (7 días, `security.yaml:49-51`), sin casilla que marcar.

Los dos mensajes son constantes privadas de la clase (`:33,:35`) y se traducen en el dominio `security`:

| Constante | Clave | ES | EN |
|---|---|---|---|
| `FAILURE_MESSAGE` | `SSO authentication could not be completed.` | No se ha podido completar el acceso con el proveedor de identidad. | Sign-in with the identity provider could not be completed. |
| `UNKNOWN_USER_MESSAGE` | `No user is registered with the identity provider email.` | No existe ningún usuario con el email del proveedor de identidad. | No user is registered with the identity provider email. |

### 4. Qué hace cada proveedor en `authenticate()`

**Google** (`GoogleSsoProvider.php:45-68`): `fetchUser()` (intercambio `code`→`access_token`, validando el `state`, más consulta del `userinfo` de OIDC) dentro de un `try` que envuelve cualquier `\Throwable` en `fromProvider('google', $e)`. Después, sobre `$user->toArray()`:

- `true !== ($claims['email_verified'] ?? false)` → `unverifiedEmail('google')`;
- `email` ausente, no string o vacío → `missingEmail('google')`;
- devuelve `new SsoIdentity(mb_strtolower($email), is_string($name) ? $name : null)`.

**Microsoft** (`MicrosoftSsoProvider.php:45-64`): idéntico salvo que **no comprueba `email_verified`** — Microsoft v2.0 no expone ese claim, así que se acepta `email` tal cual y, si falta, `missingEmail('microsoft')`. El email se lee de `toArray()['email']` y no de `AzureResourceOwner::claim()`, porque `fetchUser()` está tipado a `ResourceOwnerInterface` y `claim()` no pertenece a esa interfaz (PHPStan nivel 8).

### 5. Éxito

`onAuthenticationSuccess()` (`:81-93`):

1. Si hay *target path* guardado para el firewall (`TargetPathTrait`), redirige a él.
2. Si no, redirige a `home` con `_locale` = `preferredLocale` del `SecurityUser`, o `%app.locale_default%` si es nulo. La ruta `home` es `/{_locale}/`, de ahí que el destino por defecto sea `/es/`.

Además, sin código propio de esta funcionalidad: `LoginSuccessEventListener` publica `UserLogged` y `SetPreferredLocaleOnLoginListener` fija el `preferredLocale` si estaba a nulo (en el callback, sin `_locale` en la ruta, `LocaleGetter::locale()` devuelve el locale por defecto).

### 6. Fallo

`onAuthenticationFailure()` (`:95-105`) guarda la excepción en sesión bajo `SecurityRequestAttributes::AUTHENTICATION_ERROR` y redirige a `app_login` con el locale que `SsoController::start()` dejó en `sso.locale` (o el por defecto). `LoginController` la recupera con `AuthenticationUtils::getLastAuthenticationError()` y la plantilla la pinta con `error.messageKey|trans(error.messageData, 'security')` (`templates/security/login.html.twig:5-7`). No se usan flashes: el layout de seguridad no los renderiza.

## Autorización y ámbito

- **Acceso público:** la regla `- { path: /login, roles: PUBLIC_ACCESS }` de `config/packages/security.yaml:67` es una regex sin anclar, así que `/login/sso/google` y `/login/sso/google/callback` ya quedaban cubiertas; no se tocó `access_control`.
- **Firewall:** `main` gana `entry_point: form_login` (obligatorio al haber dos authenticators) y `custom_authenticators: [SsoAuthenticator]`. El resto (`form_login`, `logout`, `login_throttling` de 3 intentos, `remember_me`, `switch_user`) no cambia.
- **Quién puede entrar:** cualquier usuario cuyo `username` coincida con el email del proveedor y que esté activo — lo que incluye que su cuenta esté activa, porque `SecurityUser::isActive()` se comprueba en `UserProvider` y otra vez en `UserChecker::checkPreAuth()`. No hay distinción por rol: los cuatro roles entran igual.
- **Roles y permisos posteriores:** los del usuario emparejado; el SSO no concede ni altera roles. Tampoco interviene en la suplantación de cuentas.
- **Feature flags:** `SSO_GOOGLE_ENABLED` y `SSO_MICROSOFT_ENABLED`. Con el flag a `false` el proveedor no sale de `SsoProviderRegistry::enabled()` (no hay botón) y tanto `SsoController::start()` como `SsoAuthenticator::authenticate()` lo rechazan (404 y mensaje de fallo respectivamente).

## Persistencia y migración

**Ninguna migración.** No hay esquema nuevo: la identidad del proveedor no se persiste y el emparejado se hace contra el `username` existente. El único cambio en persistencia es la corrección Q2 descrita arriba, que no altera el esquema.

## Configuración y operación

### Variables de entorno (`.env:88-94`)

| Variable | Valor versionado | Para qué |
|---|---|---|
| `SSO_GOOGLE_ENABLED` | `false` | Habilita el proveedor Google |
| `SSO_GOOGLE_CLIENT_ID` | *(vacío)* | ID de cliente OAuth de Google |
| `SSO_GOOGLE_CLIENT_SECRET` | *(vacío)* | Secreto de cliente de Google |
| `SSO_MICROSOFT_ENABLED` | `false` | Habilita el proveedor Microsoft |
| `SSO_MICROSOFT_CLIENT_ID` | *(vacío)* | Id. de aplicación (cliente) de Entra ID |
| `SSO_MICROSOFT_CLIENT_SECRET` | *(vacío)* | Valor del secreto de cliente de Entra ID |
| `SSO_MICROSOFT_TENANT` | `common` | Tenant de Microsoft: `common`, o un GUID / dominio para restringir a una organización |

Los flags se exponen como parámetros `app.sso_google_enabled` / `app.sso_microsoft_enabled` (`services.yaml:6-7`) y se inyectan como `$enabled` en cada proveedor. `BASE_URL` (`.env:53`) es además `framework.default_uri` (`config/packages/framework.yaml:18`).

**Los secretos nunca se escriben en el `.env` versionado**: van en `.env.local` (ignorado por git) o en el entorno del despliegue. Tras cambiarlos, `make cc`.

### Registro de servicios (`services.yaml`)

- `App\Security\` se carga por recurso con `exclude` de `Infrastructure/Sso/InMemory/` (`:22`).
- `GoogleSsoProvider` y `MicrosoftSsoProvider` se definen **explícitamente en `when@dev` y `when@prod`** (`:124-150`), no en el bloque común. Motivo (desviación declarada en `plan.md`): una definición explícita anula el filtro de `#[When]`, y en `test` los proveedores OAuth2 convivían con los `InMemory` bajo la misma clave del registro.
- En `when@test` (`:152-173`) se registran dos `InMemorySsoProvider` con las claves `google` y `microsoft`, con el tag `security.sso_provider`, y `SsoProviderRegistry` se hace `public: true` para que Behat lo recupere del contenedor.
- `SsoAuthenticator` y `SsoController` reciben `$defaultLocale: '%app.locale_default%'` (`:116-122`).

### Clientes OAuth2 (`config/packages/knpu_oauth2_client.yaml`)

Los dos clientes usan `redirect_route: app_login_sso_callback` con `redirect_params: {provider: google|microsoft}`, de modo que el `redirect_uri` que se envía al proveedor es la URL absoluta de esa ruta. El cliente `microsoft` fija `tenant: '%env(SSO_MICROSOFT_TENANT)%'`, `default_end_point_version: '2.0'` y `scope: ['openid', 'profile', 'email']`.

`default_end_point_version: '2.0'` es imprescindible: sin él el `id_token` no trae el claim `email` y el endpoint `common` no acepta cuentas personales de Microsoft.

### Colas, consola, correos

Ninguno. Esta funcionalidad no añade comandos, tareas programadas, mensajes de Messenger, correos ni notificaciones.

---

## Alta de la aplicación en los proveedores de identidad

Procedimiento de referencia para dar de alta Hydra en Google y en Microsoft y obtener las credenciales. Todos los valores están contrastados contra `config/packages/knpu_oauth2_client.yaml`, `src/Security/Infrastructure/Sso/OAuth2/*.php`, `src/Security/Infrastructure/UI/Web/Sso/SsoController.php` y `.env`.

### Google (Google Cloud Console)

Consola: `console.cloud.google.com`. El alta es gratuita y no requiere activar facturación.

1. **Proyecto.** Crear un proyecto nuevo o elegir uno existente.
2. **Pantalla de consentimiento de OAuth** (*APIs y servicios → Pantalla de consentimiento de OAuth*).
   - **Tipo de usuario:** *Interno* si solo van a entrar cuentas del propio Google Workspace; *Externo* en cualquier otro caso.
   - **Datos de la app:** nombre de la aplicación, correo de asistencia al usuario y correo de contacto del desarrollador.
   - **Ámbitos:** añadir `openid`, `.../auth/userinfo.email` y `.../auth/userinfo.profile`. Son exactamente los tres scopes que pide `GoogleSsoProvider::loginUrl()` (`['openid', 'email', 'profile']`, `GoogleSsoProvider.php:42`) y los tres son **no sensibles**: no requieren verificación ni revisión por parte de Google.
   - Si el tipo es *Externo*, la aplicación arranca en modo **Prueba** y solo pueden entrar los correos añadidos como usuarios de prueba (máximo 100). El botón **Publicar aplicación** la abre a cualquiera y, con estos tres scopes, el cambio es inmediato.
3. **Credenciales** (*APIs y servicios → Credenciales → Crear credenciales → ID de cliente de OAuth*), tipo **Aplicación web**.
   - **Orígenes autorizados de JavaScript:** la `BASE_URL` del entorno.
   - **URI de redirección autorizados**, exacto: `{BASE_URL}/login/sso/google/callback`.
4. **Credenciales obtenidas.** Google entrega el **ID de cliente** (termina en `.apps.googleusercontent.com`) → `SSO_GOOGLE_CLIENT_ID`, y el **secreto de cliente** → `SSO_GOOGLE_CLIENT_SECRET`. Ambos se pueden volver a consultar y regenerar desde la misma credencial.

### Microsoft / Azure (Microsoft Entra ID)

Portal: `portal.azure.com` → **Microsoft Entra ID** → **Registros de aplicaciones**.

1. **Nuevo registro.** Nombre de la aplicación y **Tipos de cuenta compatibles**. La elección debe ser coherente con `SSO_MICROSOFT_TENANT`: `common` (el valor por defecto de `.env:94`) significa cualquier organización **y** cuentas personales de Microsoft; un GUID o un dominio de tenant restringe el acceso a esa organización.
2. **URI de redirección:** plataforma **Web**, valor exacto `{BASE_URL}/login/sso/microsoft/callback`.
3. **Identificadores.** En el panel *Información general* se copian:
   - **Id. de aplicación (cliente)** → `SSO_MICROSOFT_CLIENT_ID`.
   - **Id. de directorio (tenant)** → `SSO_MICROSOFT_TENANT`, solo si se ha decidido restringir el tenant.
4. **Secreto** (*Certificados y secretos → Nuevo secreto de cliente*). El campo que hay que copiar es el **Valor**, no el **Id. de secreto** → `SSO_MICROSOFT_CLIENT_SECRET`. Solo se muestra una vez y **caduca**: hay que anotar la fecha de caducidad y renovarlo antes de que expire.
5. **Permisos de API.** Bastan los permisos **delegados** `openid`, `profile` y `email` de Microsoft Graph, que son de consentimiento del usuario y no requieren consentimiento del administrador. El cliente está configurado con `default_end_point_version: '2.0'` (`config/packages/knpu_oauth2_client.yaml:16`), imprescindible para que el `id_token` traiga el claim `email` y para que `common` acepte cuentas personales. Microsoft **no** expone `email_verified`, de modo que `MicrosoftSsoProvider::authenticate()` acepta el claim `email` tal cual y, si falta, rechaza el acceso (`MicrosoftSsoProvider.php:55-59`).

### Común a ambos proveedores

- **Dónde van las variables.** `SSO_GOOGLE_ENABLED`, `SSO_GOOGLE_CLIENT_ID`, `SSO_GOOGLE_CLIENT_SECRET`, `SSO_MICROSOFT_ENABLED`, `SSO_MICROSOFT_CLIENT_ID`, `SSO_MICROSOFT_CLIENT_SECRET` y `SSO_MICROSOFT_TENANT` se rellenan en `.env.local` (ignorado por git) o en el entorno del despliegue, **nunca** en el `.env` versionado, que solo lleva las claves con valor vacío. Tras cambiarlas, `make cc`.
- **Activación.** Con el flag del proveedor a `false` el botón no aparece en el login y `/login/sso/{provider}` devuelve 404.
- **Exactitud del URI de redirección.** Debe coincidir carácter a carácter con el que genera `BASE_URL` + la ruta: mismo esquema, mismo host, mismo puerto y sin barra final. El error típico de una discrepancia es `redirect_uri_mismatch`.
- **Un entorno, un URI.** O se añaden varios URIs de redirección a la misma credencial, o —recomendado— se crea una credencial por entorno.
- **HTTPS.** Ambos proveedores exigen HTTPS en el URI de redirección; la única excepción es `http://localhost` en Google.
- **Sin alta automática de usuarios.** El email devuelto por el proveedor debe corresponder a un usuario existente y activo de la plataforma; si no, el acceso se rechaza con un mensaje en el propio login y no se crea nada.

---

## Cómo extenderlo

### Añadir un proveedor OAuth2 nuevo

1. Instalar el paquete `league/oauth2-*` correspondiente y declarar el cliente en `config/packages/knpu_oauth2_client.yaml` con `redirect_route: app_login_sso_callback` y `redirect_params: {provider: <clave>}`.
2. Crear `src/Security/Infrastructure/Sso/OAuth2/<X>SsoProvider.php` implementando `SsoProvider`, con `#[When(env: 'dev')]` y `#[When(env: 'prod')]`, y `KEY` igual a la clave de la ruta (debe casar con `[a-z]+`).
3. Añadir el flag `SSO_<X>_ENABLED` a `.env` (a `false`), su parámetro `app.sso_<x>_enabled` y la definición explícita del servicio en los bloques `when@dev` y `when@prod` de `services.yaml`, con `$enabled`.
4. Registrar un `InMemorySsoProvider` con esa clave en el bloque `when@test`.
5. Añadir el icono con `make icon-import p="simple-icons:<clave>"` y `make icon-lock`; la plantilla lo resuelve sola con `ux_icon('simple-icons:' ~ provider.key)`.
6. No hace falta tocar ni el authenticator, ni el controlador, ni las rutas, ni el firewall.

### Absorber el «SSO interno» de los derivados

En `vsme` / `waste`, el par `BioscoreSSOClient` + `BioscoreSSOJwtDecoder` se convierte en un `BioscoreSsoProvider` que implementa `SsoProvider`: `loginUrl()` devuelve la URL de Bioscore y `authenticate(Request)` valida el JWT de la query y devuelve un `SsoIdentity`. Hay que actualizar en Bioscore la URL de callback registrada para ese origen.

### Trampas conocidas

- **No definir los proveedores OAuth2 en el bloque `services` común.** Una definición explícita anula `#[When]` y en `test` acabarían conviviendo con los `InMemory` bajo la misma clave del registro.
- **El dominio `messages` es `+intl-icu`.** Los placeholders son `{provider}`, no `%provider%`; los de `security` (dominio no ICU) sí usan `%...%`.
- **La ruta de callback no lleva `_locale`.** Es deliberado: el URI registrado en Google y Azure debe ser único. El idioma del login de retorno viaja en sesión (`sso.locale`), puesto por `SsoController::start()` desde el parámetro `locale` de la query.
- **`SsoAuthenticator` es `final`** y sus tres métodos devuelven tipos estrechados (`bool`, `Response`, `Response`) en lugar de los nullables del contrato de Symfony; son covariantes, pero cambiarlos rompería PHPStan (`return.unusedType`).
- **`InMemorySsoProvider` es estado global entre escenarios.** El contenedor de Behat no se reinicia (`AcceptanceClient::disableReboot()`), por eso `SecurityContext::after()` recorre `SsoProviderRegistry::all()` y llama a `reset()` en cada doble.

## Tests

### Unitarios (`tests/Unit/Security/Infrastructure/`)

| Fichero | Cubre |
|---|---|
| `Sso/OAuth2/GoogleSsoProviderTest.php` | URL de autorización, identidad con email verificado, fallo por `email_verified` falso, por claim ausente, por email ausente, encadenado de la excepción previa, flag `enabled` |
| `Sso/OAuth2/MicrosoftSsoProviderTest.php` | Lo mismo salvo la verificación de email, que Microsoft no expone |
| `Sso/SsoProviderRegistryTest.php` | `get()` por clave, `UnknownSsoProviderException`, `enabled()` en orden de registro, `all()` |
| `Sso/InMemory/InMemorySsoProviderTest.php` | Fallo sin identidad configurada, identidad configurada, normalización a minúsculas, modo `fail()`, `reset()`, clave/etiqueta/URL |
| `Symfony/Security/SsoAuthenticatorTest.php` | `supports()` solo en la ruta de callback; proveedor desconocido y deshabilitado; fallo del proveedor con `previous`; passport con `UserBadge` y `RememberMeBadge` habilitado; email sin usuario; usuario de cuenta inactiva; redirección al *target path*, a `home` en el idioma preferido y en el por defecto; error guardado en sesión; redirección al login del idioma desde el que arrancó el flujo |

Ejecución: `make test-unit options="--filter Sso"`.

### Aceptación (`tests/Acceptance/Security/login-sso.feature`, 12 escenarios)

Cubren: botones en `/es/login` y `/en/login`; proveedor deshabilitado sin botón; arranque con 302; arranque de proveedor deshabilitado y desconocido con 404; email conocido que entra y aterriza en `/es/`; aterrizaje en el *target path* (`/es/profile`); email desconocido rechazado; usuario de cuenta inactiva rechazado; fallo del proveedor; fallo que vuelve al login en inglés; callback de proveedor desconocido.

Steps nuevos en `tests/Acceptance/Security/SecurityContext.php`: `the SSO provider :key identifies the user as :email`, `the SSO provider :key fails`, `the SSO provider :key is disabled`, `inactive account :name with id :id exists` y `the page :path was requested before signing in` (apoyado en `AcceptanceClient::setSessionValue()`, necesario porque el `KernelBrowser` corre con `catchExceptions(false)` y el 403 anónimo nunca llega a guardar el *target path*).

Ejecución: `make test-acceptance options="tests/Acceptance/Security/login-sso.feature"`.

### Huecos de cobertura

No queda ninguno señalado: `review.md` cerró sus cinco hallazgos de tests como ✅ Corregido. Lo que por naturaleza no se prueba en automático es el diálogo real con Google y Azure — en `test` siempre corre `InMemorySsoProvider` —, así que la configuración de credenciales y URIs de redirección solo se valida en un entorno real.

## Desviaciones y deuda

De `## Implementación` de [`plan.md`](plan.md) y de [`review-fix.md`](review-fix.md), todo ya aplicado:

- El bundle de Knp se registró a mano en `config/bundles.php` y `knpu_oauth2_client.yaml` se escribió desde cero: su receta de Flex es de `recipes-contrib` y quedó `IGNORING`.
- Los proveedores OAuth2 se definen en `when@dev` / `when@prod` en vez del bloque `services` común.
- `SsoProviderRegistry` añade `all()`, no previsto en el plan, para resetear los dobles en el `#[AfterScenario]`.
- Microsoft lee el email de `toArray()['email']` y Google el nombre de `toArray()['name']`, en vez de `claim()` / `getName()`.
- Clave de traducción `Continuar con {provider}` (ICU) en vez de `%provider%`.
- `UserMother::from()` acepta `?Account $account`, y el step de usuario con cuenta la resuelve en el repositorio; sin ello, en `test` el usuario de cuenta inactiva nunca se rechazaba.
- `phpstan.dist.neon` suma un `ignoreErrors` acotado (`nullsafe.neverNull`, `SsoAuthenticator.php`).

Dos correcciones aprobadas **contradicen el texto del plan** y quedan anotadas para una iteración vía `feature-planner` (ver el cierre de `review-fix.md`): la normalización del email en el fragmento de `InMemorySsoProvider`, y el `onAuthenticationFailure` que ya no redirige siempre al locale por defecto.

Pendiente del usuario, no de código: rellenar `SSO_*_CLIENT_ID` / `SSO_*_CLIENT_SECRET` en `.env.local`, registrar las URIs de callback en Google Cloud Console y Azure antes de activar los flags, y commitear los 31 iconos que `make icon-lock` descargó en `assets/icons/{ph,simple-icons,solar}/` (decisión Q1: versionarlos).
