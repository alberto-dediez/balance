---
title: "Firma de documentos"
summary: "Envía un PDF para que lo firme una persona, sigue su estado y descarga el documento firmado."
profile: admin
menu: "Administración › Auditoría › Firma de documentos"
routes: [admin_signature_requests, admin_signature_requests_register, admin_signature_requests_show, admin_signature_requests_download, admin_signature_requests_history, admin_signature_requests_remove, public_sign_verify]
updated: "2026-11-14 00:19"
---

# Firma de documentos

Aquí pides a una persona que firme electrónicamente un documento PDF. La persona recibe un enlace, ve el documento y lo firma; tú sigues el estado y descargas el documento firmado con sus datos de verificación.

> **Dónde está:** menú **Administración › Auditoría › Firma de documentos**. La pantalla se titula **Solicitudes de firma**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Documento** | Documento enviado a firmar. |
| **Firmante** | Persona que debe firmarlo. |
| **Cuenta** | Cuenta relacionada. |
| **Estado** | **Pendiente**, **Visto**, **Firmado** o **Expirado**. |
| **Fecha de expiración** | Hasta cuándo puede firmarse. |
| **Fecha de registro** | Cuándo se creó la solicitud. |

Pulsa **Filtrar** para filtrar por **Estado**.

## Registrar una solicitud de firma

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
   | **Documento a firmar (PDF)** | Sí | El fichero PDF. |
   | **Nombre del firmante** | Sí | Nombre de la persona. |
   | **Idioma del firmante** | Sí | Idioma de los mensajes que recibirá. |
   | **Canal de notificación** | Sí | Por dónde le llega el enlace: email o, si tu plataforma lo tiene activado, SMS o WhatsApp. |
   | **Email del firmante** | Si el canal es email | Su correo. |
   | **Teléfono del firmante** | Si el canal es SMS o WhatsApp | Aparece si tu plataforma lo tiene activado. |
   | **Requerir verificación OTP** | No | Si lo marcas, antes de firmar tendrá que escribir un código de 6 dígitos que le enviaremos. |

3. Pulsa **Registrar**. Verás **Solicitud de firma creada correctamente**.

El firmante recibe el mensaje **Solicitud de firma de documento** con el botón **Ver y firmar documento**. Al firmar verá **Documento firmado correctamente**.

## Ver una solicitud firmada

Cuando el estado es **Firmado**, pulsa **Detalles**. Verás:

- **Firmante**: **Nombre**, **Email** o **Teléfono** y **Canal**.
- **Solicitud**: **Estado**, **Registrada**, **Actualizada** y **Expira el**.
- **Datos forenses de la firma**: **Firmado el**, las huellas del documento original y firmado, la **IP del firmante** y su navegador. Abajo verás **La firma es válida.** o **La firma NO es válida.**

Arriba tienes **Descargar documento firmado** y **Verificar firma**.

## Ver el historial y eliminar

- **Histórico** muestra todo lo ocurrido con la solicitud (envío, visualización, firma).
- **Eliminar** solo está disponible mientras la solicitud está **Pendiente**. Verás **Se ha eliminado la solicitud de firma**.

## Conviene saber

- Si el firmante se equivoca demasiadas veces con el código, verá **Has superado el número máximo de intentos. El enlace ha quedado bloqueado.**
- Si el enlace ha caducado, verá **Este enlace no es válido o ha expirado**.

## Relacionado

- [Registro](registro.md)
