# Conservar los roles que el editor no puede gestionar — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-15 · **Correcciones de review:** 2026-09-16 · **Bounded Contexts:** `Security`

## Qué hace y por qué

El formulario de edición de usuario ofrece al editor solo un subconjunto de roles (`Role::casesForAdmins()` para la pantalla de administración, `Role::casesForOwners()` para la de cuenta). Antes de este cambio, `EditUserTrait::ungrantRoles()` comparaba `$user->roles()` contra lo enviado por el formulario sin saber qué roles ofrecía ese formulario, así que interpretaba como "desmarcado" todo rol que el editor ni siquiera veía. Consecuencia: un `Admin` que solo cambiaba el idioma de un `SuperAdmin` le retiraba `ROLE_SUPER_ADMIN` sin aviso, y un `Owner` que editaba a un `Admin` de su cuenta veía un flash rojo "No se ha podido retirar acceso como Administrador" en cada guardado.

La solución cierra el agujero en dos capas independientes:

1. **Autorización (la que manda).** `UserAuthorizer::canChangeRole()` limita al `Admin` a `Role::casesForAdmins()`, que excluye `SuperAdmin` (`src/Security/Application/User/UserAuthorizer.php:81-83`). Como `GrantRoleService` y `UngrantRoleService` ya consultan ese método, la regla cubre a la vez web, API, acciones masivas y consola con una sola línea.
2. **Formulario web.** `EditUserTrait::updateRoles()` / `ungrantRoles()` reciben además la lista de roles que el formulario ofrecía (`$manageableRoles`) y solo retiran roles de esa lista (`src/Security/Infrastructure/UI/Web/User/EditUserTrait.php:79-81`). Los roles fuera de ella se conservan sin llamar al servicio, así que no se produce ni el cambio silencioso ni el flash rojo.

Dos decisiones complementarias: los controladores de API capturan `UnauthorizedActionException` y devuelven 403 en lugar del 500 que produciría `ExceptionListener` (ver "Autorización y ámbito"), y el usuario de sistema usado por los comandos de consola pasa a tener `[Role::Admin, Role::SuperAdmin]` para que `security:user:grant-role` y `security:user:ungrant-role` sigan pudiendo tocar `SuperAdmin`.

La justificación de cada elección está en la tabla "Decisiones tomadas" de `plan.md`.

## Mapa de piezas

| Capa | Fichero | Rol en la funcionalidad | Estado |
|---|---|---|---|
| Aplicación | `src/Security/Application/User/UserAuthorizer.php` | `canChangeRole()` restringe al `Admin` a `Role::casesForAdmins()` | Modificado |
| Aplicación | `src/Security/Application/User/GrantRole/GrantRoleService.php` | Consulta `canChangeRole()` y lanza `UnauthorizedActionException` | Sin cambios |
| Aplicación | `src/Security/Application/User/UngrantRole/UngrantRoleService.php` | Ídem | Sin cambios |
| Infra · Web | `src/Security/Infrastructure/UI/Web/User/EditUserTrait.php` | `updateRoles()`/`ungrantRoles()` con `$manageableRoles`; guarda de `Role::User` | Modificado |
| Infra · Web | `src/Security/Infrastructure/UI/Web/Admin/User/EditController.php` | Deriva `$manageableRoles` de las `choices` del formulario y las pasa al trait | Modificado |
| Infra · Web | `src/Security/Infrastructure/UI/Web/User/EditController.php` | Ídem para la pantalla de cuenta | Modificado |
| Infra · API | `src/Security/Infrastructure/UI/Api/UserGrantRole/UserGrantRoleController.php` | `catch (UnauthorizedActionException)` → 403 | Modificado |
| Infra · API | `src/Security/Infrastructure/UI/Api/UserUngrantRole/UserUngrantRoleController.php` | Ídem | Modificado |
| Infra · Form | `src/Security/Infrastructure/Symfony/Form/AdminEditUserType.php` | Fuente de verdad de los roles gestionables (`Role::casesForAdmins()`) | Sin cambios (se lee) |
| Infra · Form | `src/Security/Infrastructure/Symfony/Form/EditUserType.php` | Hereda las `choices` de `RoleType` (`Role::casesForOwners()`) | Sin cambios (se lee) |
| Tests | `tests/Unit/Security/Domain/Model/User/UserMother.php` | `systemUser()` con `[Role::Admin, Role::SuperAdmin]` | Modificado |
| Tests | `tests/Unit/Security/Application/User/UserAuthorizerTest.php` | 3 casos nuevos sobre `canChangeRole()` | Modificado |
| Tests | `tests/Unit/Security/Application/User/GrantRole/GrantRoleServiceTest.php` | 2 casos nuevos | Modificado |
| Tests | `tests/Unit/Security/Application/User/UngrantRole/UngrantRoleServiceTest.php` | 2 casos nuevos | Modificado |
| Tests | `tests/Acceptance/Security/SecurityContext.php` | Paso `user :username with role :role with id :id exists` | Modificado |
| Tests | `tests/Acceptance/Security/user-edit.feature` | 3 escenarios nuevos (2 del plan + 1 de la corrección B1) | Modificado |
| Tests | `tests/Acceptance/Security/user-grant-role.feature` | 2 escenarios nuevos (API 403 y consola) | Modificado |
| Tests | `tests/Acceptance/Security/user-ungrant-role.feature` | 2 escenarios nuevos (API 403 y consola) | Modificado |

Las plantillas `templates/security/admin/users/edit.html.twig` y `templates/security/users/edit.html.twig` **no** muestran los roles no gestionables: el campo de solo lectura que describía `plan.md` no está en el código actual (el de `users/edit.html.twig` solo tiene cambios de formato). No existe ninguna clave de traducción "Roles no gestionables" en `translations/`.

## Modelo de dominio

No hay cambios de dominio. Lo relevante que el dominio ya aportaba:

- `src/Security/Domain/Model/User/Role.php:20-33` — `casesForAdmins()` devuelve todos los casos menos `SuperAdmin`, indexados por `$role->value`.
- `src/Security/Domain/Model/User/Role.php:38-53` — `casesForOwners()` excluye además `Admin`.
- `src/Security/Domain/Model/User/User.php:83-96` — `roles()` devuelve `[Role::User]` si la lista está vacía y re-añade `Role::User` cuando falta: `Role::User` es un rol **implícito**, nunca ausente de la lectura.
- `src/Security/Domain/Model/User/User.php` — `grantRole()`/`ungrantRole()` publican `RoleGranted` / `RoleUngranted` vía `recordApplyAndPublish()`; `hasRole()` es un `in_array` literal, sin jerarquía (la jerarquía de `config/packages/security.yaml:72-74` solo afecta a `IsGranted` en la capa web).

La invariante que introduce este plan no vive en el agregado sino en el `Authorizer`: **un editor solo puede conceder o retirar roles de su propio conjunto gestionable**.

## Flujo de una operación

### Guardar el formulario de edición (Admin: `/{_locale}/admin/users/edit/{userId}`)

```
GET|POST /admin/users/edit/{userId}
  → Admin\User\EditController::__invoke()            EditController.php:34
    · getUserFromId()                                 :41
    · denyAccessUnlessGranted(Action::Edit, $user)    :43
    · createForm(AdminEditUserType, new EditUser(...)):47
    · $manageableRoles = choices del campo 'roles'    :49-50   ← Role::casesForAdmins()
    · EditTrait::edit(..., closure)                   :53
        → updateRoles($user, $form->get('roles')->getData(), $manageableRoles, $performBy)   :54
            → ungrantRoles()                          EditUserTrait.php:66
                · if 0 === count($roles) return       :68-70   (guarda preexistente: no retira nada si no llega ninguno)
                · foreach ($user->roles() as $role)   :74
                    - Role::User === $role  → continue          :75-77  ← corrección B1
                    - !in_array($role, $manageableRoles, true) → continue  :79-81  ← núcleo del plan
                    - marcado en el formulario → continue       :83-87
                    - UngrantRoleService($request, $performBy)  :92
                      flash 'warning' o, si lanza, flash 'danger'  :94-96
            → grantRoles()                            EditUserTrait.php:35
                · concede los roles marcados que el usuario no tenga, flash 'success'/'danger'  :43-59
        → UpdateProfileService si cambia el idioma    EditController.php:59-61
    · redirect a admin_users si "Guardar y salir"     :64-66
  catch UnauthorizedActionException → 403             :67-69
```

`GrantRoleService` / `UngrantRoleService` (`:32-34` en ambos) consultan `UserAuthorizer::canChangeRole()` y lanzan `UnauthorizedActionException($user, Action::Admin)` si devuelve `false`.

La pantalla de cuenta (`/{_locale}/users/edit/{userId}`, `src/Security/Infrastructure/UI/Web/User/EditController.php:30-67`) es idéntica salvo que usa `EditUserType` —cuyas `choices` son `Role::casesForOwners()` por el defecto de `RoleType` (`src/Security/Infrastructure/Symfony/Form/Type/RoleType.php:19-22`)— y no toca el idioma.

Efecto combinado: al guardar como `Admin` sobre un `SuperAdmin`, `ROLE_SUPER_ADMIN` no está en `$manageableRoles`, el bucle lo salta y el rol se conserva sin llamar al servicio. Al guardar como `Owner` sobre un `Admin` de su cuenta ocurre lo mismo con `ROLE_ADMIN`, y desaparece el flash rojo que aparecía antes.

### Conceder/retirar rol por API

```
PUT /api/users/{userId}/grant-role   { "role": "ROLE_SUPER_ADMIN" }
  → UserGrantRoleController::__invoke()                  UserGrantRoleController.php:31
    · GrantRoleService(new GrantRoleRequest(...), $loggedUser())   :39-45
    · catch UnauthorizedActionException → createAccessDeniedException()  :46-48  → 403
    · 200 con UserGrantRoleResponse{userId, username, roles}       :50-54
```

`UserUngrantRoleController.php:38-48` es simétrico. Sin ese `catch`, `UnauthorizedActionException` (un `\RuntimeException` con código 403, no `HttpExceptionInterface`) acabaría en 500 por `src/Shared/Infrastructure/Symfony/Event/ExceptionListener.php:41-46`.

### Consola

`security:user:grant-role` y `security:user:ungrant-role` (`src/Security/Infrastructure/UI/Console/User/GrantRoleCommand.php:57`, `UngrantRoleCommand.php`) ejecutan como `UserMother::systemUser()`, que ahora tiene `[Role::Admin, Role::SuperAdmin]` (`tests/Unit/Security/Domain/Model/User/UserMother.php:24-31`): entran por la rama `SuperAdmin` de `canChangeRole()` y pueden seguir tocando `ROLE_SUPER_ADMIN`.

## Autorización y ámbito

`src/Security/Application/User/UserAuthorizer.php:76-92`:

```php
public function canChangeRole(User $subject, Role $role, User $user): bool
{
    if ($this->userHasRole($user, Role::SuperAdmin)) {
        return true;
    }
    if ($this->userHasRole($user, Role::Admin)) {
        return in_array($role, Role::casesForAdmins(), true);
    }
    // ... rama Owner: misma cuenta + Role::casesForOwners()
}
```

| Editor | Roles que puede conceder/retirar | Sobre quién |
|---|---|---|
| `SuperAdmin` | Todos, `ROLE_SUPER_ADMIN` incluido | Cualquier usuario |
| `Admin` | `Role::casesForAdmins()` = `User`, `Owner`, `Admin` | Cualquier usuario |
| `Owner` | `Role::casesForOwners()` = `User`, `Owner` | Solo usuarios de su cuenta actual |
| `User` | Ninguno | — |
| Usuario de sistema (consola) | Todos (rama `SuperAdmin`) | Cualquier usuario |

`userHasRole()` (`src/Security/Application/AbstractAuthorizer.php:25-34`) consulta `CurrentRolesProvider`, así que respeta la impersonación de cuentas: `src/Security/Infrastructure/Symfony/Security/CurrentRoles.php:20-35` devuelve `[Owner, User]` al impersonador, que por tanto cae en la rama `Owner` y no puede tocar `Admin` ni `SuperAdmin` aunque su usuario real sea `Admin`. La cuenta efectiva se resuelve con `currentAccountId()`, no con `$user->accountId()`.

Cobertura por superficie:

- **Web (edición individual):** el formulario no ofrece los roles fuera de alcance y el trait ya no los retira.
- **Web (acciones masivas):** `AdminBulkUserType` limita el desplegable a `Role::casesForAdmins()` (`src/Security/Infrastructure/Symfony/Form/AdminBulkUserType.php:25-35`); `BulkUserType` (listado de cuenta) ni siquiera expone "Asignar rol"/"Eliminar rol" (`BulkUserType.php:39-45` solo declara `RemoveUser`). En cualquier caso los comandos pasan por los servicios y el authorizer.
- **API:** el authorizer deniega y el controlador traduce a 403.
- **Consola:** el usuario de sistema es `SuperAdmin`.

No hay feature flags ni variables de entorno implicadas.

## Persistencia y migración

Ninguna. No cambia el esquema ni hay backfill: `plan.md` declara `Migración: no` y `review-fix.md` confirma "Migraciones pendientes: ninguna".

## Configuración y operación

Nada nuevo en `services.yaml`, `messenger.yaml` ni `CommandSchedule`. Los comandos de consola relacionados, sin cambios de firma:

```bash
make sf c="security:user:grant-role <username> ROLE_SUPER_ADMIN"
make sf c="security:user:ungrant-role <username> ROLE_SUPER_ADMIN"
```

## Cómo extenderlo

- **Añadir un rol nuevo al enum:** basta con incluirlo en `Role` y decidir si `casesForAdmins()` / `casesForOwners()` lo excluyen. Tanto las `choices` de los formularios como `canChangeRole()` derivan de esos dos métodos, así que un rol nuevo queda coherente en web, API, acciones masivas y consola sin tocar nada más. Ojo: al alterar el enum se reindexan las `choices` de `ChoiceType` y hay que revisar los `roles:[n]` de `tests/Acceptance/Security/user-edit.feature`.
- **Añadir otra pantalla de edición de roles:** usar `EditUserTrait` y derivar `$manageableRoles` de `$form->get('roles')->getConfig()->getOption('choices')`, nunca de `Role::casesForAdmins()` escrito a mano en el controlador; el formulario es la única fuente de verdad de lo que el editor ve. PHPStan nivel 8 exige el `/** @var Role[] */` antes de `array_values(...)` porque `getOption()` devuelve `mixed`.
- **Permitir a un `SuperAdmin` gestionar `SuperAdmin` desde la web:** hoy `AdminEditUserType` fija `Role::casesForAdmins()` de forma estática, sin mirar quién edita; habría que pasarle los roles del editor (opción del form o `CurrentRolesProvider`). El authorizer ya lo permitiría. Es un cambio funcional que merece plan propio (ver B2 en `review.md`).

**Trampas conocidas:**

- Las comparaciones con enums deben usar `in_array(..., true)`. La rama `Owner` de `canChangeRole()` (`UserAuthorizer.php:91`) usa la variante no estricta por herencia; con enums instanciados el resultado es idéntico.
- `ungrantRoles()` conserva dos guardas preexistentes que conviene no confundir con la del plan: la de lista vacía (`:68-70`, impide "deseleccionar todo") y la de `Role::User` (`:75-77`, añadida por la corrección B1 porque el dominio re-añade siempre ese rol).
- `UserMother` es una clase de `tests/` usada desde código de producción como usuario de sistema. Es deuda preexistente; este plan solo cambió sus roles.

## Tests

**Unitarios**

- `tests/Unit/Security/Application/User/UserAuthorizerTest.php` — `testAdminCannotChangeSuperAdminRole`, `testAdminCanChangeAdminRole`, `testSuperAdminCanChangeSuperAdminRole`.
- `tests/Unit/Security/Application/User/GrantRole/GrantRoleServiceTest.php` — `testAdminCannotGrantSuperAdminRole` (espera `UnauthorizedActionException`), `testSystemUserCanGrantSuperAdminRole`.
- `tests/Unit/Security/Application/User/UngrantRole/UngrantRoleServiceTest.php` — `testAdminCannotUngrantSuperAdminRole`, `testSuperAdminCanUngrantSuperAdminRole` (comprueba además que el último evento publicado es `RoleUngranted`).

```bash
make test-unit options="--filter UserAuthorizerTest"
make test-unit options="--filter RoleServiceTest"
```

**Aceptación**

- `tests/Acceptance/Security/user-edit.feature:57-66` — un `Admin` guarda sobre un `SuperAdmin` y no ve "Retirado acceso como" ni "No se ha podido retirar acceso".
- `tests/Acceptance/Security/user-edit.feature:68-77` — un `Owner` guarda sobre un `Admin` de su cuenta y no ve "No se ha podido retirar acceso".
- `tests/Acceptance/Security/user-edit.feature:79-87` — "unchecking the user role does not ungrant it": escenario de la corrección B1; se envía `roles:[1]` (solo `ROLE_OWNER`) y no debe aparecer "Retirado acceso como".
- `tests/Acceptance/Security/user-grant-role.feature:63-80` — API 403 para un `Admin` que intenta conceder `ROLE_SUPER_ADMIN`; `security:user:grant-role` sí puede concederlo.
- `tests/Acceptance/Security/user-ungrant-role.feature:63-80` — simétrico para retirar.
- Paso nuevo en `tests/Acceptance/Security/SecurityContext.php:123-131`: `user :username with role :role with id :id exists`.

```bash
make test-acceptance options="tests/Acceptance/Security/user-edit.feature"
make test-acceptance options="tests/Acceptance/Security/user-grant-role.feature"
make test-acceptance options="tests/Acceptance/Security/user-ungrant-role.feature"
```

**Huecos de cobertura:** no hay test que ejercite un `SuperAdmin` editando por web a otro `SuperAdmin` (el caso de B2), ni tests de las acciones masivas frente a `ROLE_SUPER_ADMIN` (la protección es la misma del authorizer, ya cubierta en unitarios).

## Desviaciones y deuda

- **Desviación declarada en `plan.md` (`## Implementación`):** los dos escenarios nuevos de `user-edit.feature` envían `roles:[0,1]` en lugar de `roles:[1]`, para mantener marcado `ROLE_USER` y no disparar el comportamiento del hallazgo B1. Los índices no se han simplificado tras corregir B1.
- **Desviación respecto a `plan.md` (posterior a la implementación):** el plan describía un campo de solo lectura "Roles no gestionables" en ambas pantallas de edición; ese campo, el método `EditUserTrait::protectedRoles()`, la variable `protectedRoles` de la vista y la clave de traducción asociada **no existen en el código actual**. El comportamiento vigente es que los roles fuera del alcance del editor simplemente no se muestran ni se pueden gestionar, y se conservan al guardar.
- **B1 (`review.md` → `review-fix.md`): corregido** el 2026-09-16 con el guard `Role::User === $role → continue` en `ungrantRoles()` (`EditUserTrait.php:75-77`) y un escenario Behat propio. Desmarcar "Usuario" ya no publica un `RoleUngranted` espurio ni muestra el flash "Retirado acceso como Usuario".
- **B2 (`review.md`): descartado por el usuario** el 2026-09-16 como asumible. Su enunciado se refería a la etiqueta del campo de solo lectura, que ya no existe; la causa subyacente que sigue viva es que `AdminEditUserType` no ofrece `SuperAdmin` ni siquiera a un editor `SuperAdmin`, que debe usar API o consola para ese rol.
- **Fuera de alcance (sigue abierto):** mapear `UnauthorizedActionException` a 403 de forma global en `ExceptionListener` para toda la API; la guarda de lista vacía de `ungrantRoles()`; impedir que un `Owner` abra la pantalla de edición de un `Admin` de su cuenta (`canEdit()` lo permite).

Detalle completo en `review.md` y `review-fix.md` (misma carpeta).
