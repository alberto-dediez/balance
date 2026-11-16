---
title: "Emails enviados"
summary: "Consulta los correos que ha enviado la plataforma, si se entregaron y si se abrieron."
profile: admin
menu: "Administración › Auditoría › Emails enviados"
routes: [admin_emails, admin_emails_show, admin_emails_history]
updated: "2026-11-14 00:19"
---

# Emails enviados

> Solo para superadministradores.

Registro de todos los correos que ha enviado la plataforma, con su estado de entrega y apertura. Sirve para comprobar si a alguien le ha llegado un correo.

> **Dónde está:** menú **Administración › Auditoría › Emails enviados**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Destinatario** | A quién se envió. |
| **Asunto** | Asunto del correo. |
| **Proveedor** | Servicio de correo que lo envió. |
| **Estado** | **Enviado**, **Entregado**, **Diferido**, **Rebotado**, **Descartado** o **Fallido**. |
| **Enviado** | Cuándo salió. |
| **Entregado** | Cuándo llegó al buzón. |
| **Abierto** | Si se ha abierto, y cuántas veces. |

Pulsa **Filtrar** para buscar por **Destinatario**, **Asunto**, **Estado**, **Proveedor** o fechas (**Enviado desde** / **Enviado hasta**).

## Ver un correo

Pulsa **Detalles**. Verás los datos del **Email** (**Destinatario**, **Asunto**, **Origen**, **Proveedor**) y su **Seguimiento**: **Estado**, **Motivo** si falló, **Enviado**, **Entregado**, **Primera apertura**, **Última apertura**, **Aperturas**, **Primer clic**, **Clics**, **Marcado como spam** y **Último evento**.

Si el proveedor no da esa información verás **El proveedor configurado no envía información de entrega ni de apertura**.

Pulsa **Ver auditoría** (o **Histórico** en el listado) para ver la secuencia de eventos del correo.

## Relacionado

- [Registro](registro.md)
