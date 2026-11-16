---
title: "Usuarios"
summary: "Alta, edición, roles, tokens de API y baja de los usuarios de tu cuenta."
profile: owner
menu: "Administración › Usuarios"
routes: [users, users_register, users_edit, users_remove, users_api_tokens, users_api_tokens_revoke]
updated: "2026-11-14 00:19"
---

# Usuarios

Aquí das acceso a la plataforma a las personas de tu organización y decides qué pueden hacer. Solo ves los usuarios de tu cuenta.

> **Dónde está:** menú **Administración › Usuarios**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Nombre de usuario** | Email con el que entra. |
| **Roles** | **Usuario**, **Propietario** u otros roles. |
| **Activo** | Si puede entrar. |
| **Fecha de registro** | Cuándo se dio de alta. |

Pulsa **Filtrar** para buscar por **Nombre de usuario** o **Rol de usuario**.

## Registrar un usuario

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
   | **Nombre de usuario** | Sí | Su email. |
   | **Contraseña** | Sí | Contraseña inicial. |
   | **Rol de usuario** | No | **Usuario** (acceso básico) o **Propietario** (gestiona la cuenta como tú). También puedes darle el rol de proveedor. |

3. Pulsa **Registrar**. Verás **Usuario creado correctamente** con su email. El nuevo usuario recibe un correo de bienvenida con un enlace **Generar nueva contraseña** por si no conoce la suya.

## Editar un usuario

1. Pulsa **Editar** en la fila.
2. Cambia el **Rol de usuario**.
3. Pulsa **Guardar** o **Guardar y salir**.

Desde la ficha, el botón **Tokens de API** muestra los tokens que ha creado ese usuario (**Nombre**, **Token**, **Estado**, **Caducidad**, **Último uso**, **Fecha de creación**) y te deja **Revocar** cualquiera. Verás **Token revocado**.

## Cambiar roles a varios usuarios

Marca los usuarios, elige **Asignar rol** o **Eliminar rol** en **Acción a realizar**, escoge el **Rol de usuario** y pulsa **Realizar**.

## Eliminar usuarios

- **Uno:** pulsa **Eliminar** y confirma. Verás **Se ha eliminado al usuario** con su email.
- **Varios:** márcalos, elige **Eliminar** en **Acción a realizar** y pulsa **Realizar**.

## Conviene saber

- Si estás trabajando dentro de otra cuenta como consultora, no puedes dar de alta usuarios ni cambiar los de esa cuenta.

## Relacionado

- [Empleados](empleados.md)
- [Mi perfil](../general/mi-perfil.md)
