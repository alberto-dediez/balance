---
title: Registro de emails enviados
date: "2026-10-20 22:27"
category: feature
summary: Los super administradores pueden comprobar cada email que envía la plataforma, a quién, con qué asunto, si llegó, si rebotó y si se abrió.
plan: 2026-10-20-log-de-emails
manual: log-de-emails.pdf
techManual: log-de-emails.tech.md
published: true
---

## Qué cambia

Cuando alguien dice «no me ha llegado el correo», ahora se puede comprobar desde la propia
plataforma. La nueva pantalla **Emails enviados** guarda una línea por cada email que sale, sea
cual sea su motivo: avisos de notificaciones, recuperación de contraseña, códigos de la
verificación en dos pasos, solicitudes de firma y sus códigos, e informes de error.

De cada email se ve el destinatario, el asunto, el tipo de correo, el servicio que lo envió, el
estado y la fecha de salida. Si el servicio de correo de la plataforma informa de lo que pasa
después, también se ve si se entregó, si rebotó o se descartó, cuántas veces se abrió, si se pulsó
algún enlace y si el destinatario lo marcó como correo no deseado. Con otros servicios, el email
queda como «Enviado» o «Fallido».

## A quién afecta

Solo a los **super administradores**. Administradores, propietarios y usuarios no ven la entrada
del menú y no pueden abrir la pantalla. Los usuarios siguen recibiendo los mismos emails que
antes: registrar un envío nunca impide que salga.

## Dónde se encuentra

En el menú lateral, **Seguridad → Emails enviados**, justo antes de «Registro». El listado se puede
filtrar por destinatario, asunto, estado, proveedor y fechas, y ordenar por varias columnas. Cada
fila tiene **Detalles**, con todos los datos del envío y el motivo de cualquier problema, e
**Histórico**, con todo lo que ha ido ocurriendo con ese email por orden de fecha (también desde
**Ver auditoría** en el detalle).

## Qué conviene saber

- Es solo de consulta: nadie puede editar, eliminar ni reenviar un email desde aquí.
- No se guarda el contenido del mensaje ni sus datos internos (códigos, enlaces), por seguridad.
- Solo aparecen los emails enviados desde la puesta en marcha de esta sección.
- «Fallido», «Rebotado» y «Descartado» son estados definitivos.
- Aperturas y clics son orientativos: algunos programas de correo bloquean el aviso de apertura.
- Cada noche se eliminan los registros de más de 180 días, o el plazo que tenga configurado la
  plataforma.
- En el filtro **Enviado hasta** no se incluyen los emails de ese mismo día: elige el día
  siguiente.
