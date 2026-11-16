---
title: "Usuarios"
summary: "Alta, edición, roles, tokens, segundo factor, historial y baja de los usuarios de todas las cuentas."
profile: admin
menu: "Administración › Organización › Usuarios"
routes: [admin_users, admin_users_register, admin_users_edit, admin_users_remove, admin_user_history, admin_users_api_tokens, admin_users_api_tokens_revoke, admin_users_two_factor_reset, export]
updated: "2026-11-14 00:19"
---

# Usuarios

Aquí gestionas los usuarios de todas las cuentas: los das de alta, cambias sus roles, revisas sus tokens de API y los das de baja.

> **Dónde está:** menú **Administración › Organización › Usuarios**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Nombre de usuario** | Email con el que entra. |
| **Roles** | **Usuario**, **Propietario**, **Administrador**, **Super administrador**… |
| **Cuenta** | Cuenta a la que pertenece. |
| **Activo** | Si puede entrar. |
| **Fecha de registro** | Cuándo se dio de alta. |

Pulsa **Filtrar** para buscar por **Nombre de usuario**, **Cuenta** o **Rol de usuario**.

## Registrar un usuario

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
   | **Nombre de usuario** | Sí | Su email. |
   | **Contraseña** | Sí | Contraseña inicial. |
   | **Cuenta** | Sí | Cuenta a la que pertenece. |
   | **Rol de usuario** | No | Uno o varios roles. |
   | **Idioma preferido** | No | Idioma de sus correos. |

3. Pulsa **Registrar**. Verás **Usuario creado correctamente** con su email.

## Editar un usuario

1. Pulsa **Editar** en la fila. Verás su **Nombre de usuario**, su **Cuenta** y el estado de su **Segundo factor**.
2. Cambia el **Rol de usuario** o el **Idioma preferido**.
3. Pulsa **Guardar** o **Guardar y salir**.

Desde la ficha también puedes:

- **Tokens de API**: ver los tokens del usuario (**Nombre**, **Token**, **Estado**, **Caducidad**, **Último uso**, **Fecha de creación**) y **Revocar** los que quieras. Verás **Token revocado**.
- **Restablecer segundo factor**: aparece si el usuario tiene la verificación en dos pasos activada. Úsalo si ha perdido su móvil. Tras confirmar verás **Segundo factor restablecido**.

## Cambiar roles a varios usuarios

Marca los usuarios, elige en **Acción a realizar** **Asignar rol** o **Eliminar rol**, escoge el **Rol de usuario** y pulsa **Realizar**. Un administrador puede asignar cualquier rol salvo el de superadministrador; un superadministrador puede asignar todos.

## Ver el historial de un usuario

Pulsa **Histórico** en la fila para ver todo lo que ha ocurrido con ese usuario (alta, cambios de rol, etc.).

## Exportar el listado

Pulsa **Exportar**, filtra por **Cuenta** o **Rol de usuario** si quieres, elige columnas y formato. Más detalles en [Importar y exportar](../../general/importar-y-exportar.md).

## Eliminar usuarios

- **Uno:** pulsa **Eliminar** y confirma. Verás **Se ha eliminado al usuario** con su email.
- **Varios:** márcalos, elige **Eliminar** en **Acción a realizar** y pulsa **Realizar**.

## Entrar como un usuario

Solo para superadministradores. Pulsa **Suplantar** en la fila de un usuario activo (no aparece en tu propia fila). Entrarás en la plataforma como ese usuario, viendo exactamente lo que él ve. Para volver, pulsa **Suplantando a** en la barra superior o **Volver a mi usuario** en el menú de tu usuario.

## Relacionado

- [Cuentas](cuentas.md)
- [Registro](../auditoria/registro.md)
- [Barra superior](../../general/barra-superior.md)
