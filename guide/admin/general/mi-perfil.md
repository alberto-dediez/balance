---
title: "Mi perfil"
summary: "Tu idioma, tu imagen, tu contraseña, la verificación en dos pasos y los tokens de API."
profile: admin
menu: "General"
routes: [profile, profile_change_password, profile_two_factor, profile_two_factor_email, profile_two_factor_totp, profile_two_factor_confirm, profile_two_factor_cancel, profile_two_factor_disable, profile_api_tokens, profile_api_tokens_register, profile_api_tokens_revoke]
updated: "2026-11-14 00:19"
---

# Mi perfil

Desde el menú de tu usuario (icono de la persona, arriba a la derecha) puedes cambiar tus preferencias y la seguridad de tu acceso. El menú muestra tu nombre de usuario y estas opciones: **Editar perfil**, **Cambiar contraseña**, **Segundo factor** y **Tokens de API**.

## Editar tu perfil

1. Pulsa **Editar perfil**. Se abre **Mi perfil**.
2. Cambia lo que necesites:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
   | **Idioma preferido** | Sí | Idioma de los correos y avisos que recibes fuera de la plataforma. |
   | **Imagen de perfil** | No | Una imagen de tu biblioteca. Si no tienes, verás **No tienes ninguna imagen de perfil**. |

3. Pulsa **Guardar**.

Tu **Nombre de usuario** (tu email) aparece en la pantalla pero no se puede cambiar.

## Cambiar la contraseña

1. Pulsa **Cambiar contraseña**.
2. Escribe la **Contraseña antigua**, la **Contraseña nueva** y repítela en **Repetir Contraseña**.
3. Pulsa **Guardar**.

## Activar la verificación en dos pasos

Pulsa **Segundo factor**. La pantalla muestra el **Estado**: **No activado**, **Activado** con el método elegido u **Obligatorio: código por email** si tu plataforma lo exige.

- **Por email**: pulsa **Email** y confirma. Cada vez que entres te enviaremos un código a tu correo.
- **Con una aplicación**: pulsa **Aplicación de autenticación**. Escanea el código QR con Google Authenticator o cualquier aplicación compatible (o usa la clave manual que aparece debajo) y escribe el código que te muestra. Tu segundo factor anterior sigue activo hasta que verifiques el nuevo; si pulsas **Cancelar**, no cambia nada.
- **Cambiar de móvil**: si ya usas la aplicación, pulsa **Vincular otro dispositivo** y repite el proceso.
- **Desactivar**: pulsa **Desactivar segundo factor** y confirma. Esta opción no aparece si tu plataforma obliga a usar el segundo factor.

## Tokens de API

Un token sirve para que otra aplicación acceda a la plataforma en tu nombre sin usar tu contraseña.

En **Tokens de API** ves tus tokens con estas columnas: **Nombre**, **Token**, **Estado**, **Caducidad**, **Último uso** y **Fecha de creación**.

### Crear un token

1. Pulsa **Registrar**.
2. Escribe un **Nombre** (obligatorio) y, si quieres, una **Fecha de caducidad** posterior a hoy. Déjala vacía para que no caduque.
3. Pulsa **Crear token**.
4. Verás **Token creado** con el token completo. **Cópialo ahora: no podrás volver a verlo.** Pulsa **Volver a mis tokens** al terminar.

### Revocar un token

Pulsa **Revocar** en la fila del token y confirma. Dejará de funcionar de inmediato.

## Relacionado

- [Entrar en la plataforma](acceso.md)
- [Barra superior](barra-superior.md)
