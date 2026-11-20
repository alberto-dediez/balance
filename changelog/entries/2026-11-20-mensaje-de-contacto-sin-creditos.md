---
title: "Solicitudes de contacto cuando no hay créditos para compensar"
date: "2026-11-20 11:38"
category: feature
summary: "Si alguien quiere compensar su huella y no hay créditos disponibles, puede dejar sus datos y los Administradores reciben un aviso para contactarle."
plan: 2026-11-20-mensaje-de-contacto-sin-creditos
manual: mensaje-de-contacto-sin-creditos.pdf
techManual: mensaje-de-contacto-sin-creditos.tech.md
published: true
---

## Qué cambia

Hasta ahora, cuando una persona calculaba su huella y quería compensarla pero no había proyectos o créditos suficientes, la pantalla de resultado no ofrecía nada y se perdía esa demanda. Ahora, si la cuenta tiene activada la compensación y no hay ninguna opción disponible para esa huella, el botón **Compensar mi huella** sigue apareciendo, con el mismo aspecto, y lleva a una página de contacto.

En esa página el visitante ve un aviso que le da las gracias, le explica que la disponibilidad es inferior a la cantidad que desea adquirir y le pide sus datos: nombre y apellidos, email y país son obligatorios; teléfono, datos de empresa (razón social y NIF / NIF-IVA) y comentarios son opcionales. Al enviarlo ve la pantalla **¡Gracias por tu interés!**, con el mensaje de que se pondrán en contacto con él en breve. Cada envío crea una **solicitud de contacto**.

## A quién afecta

- **Visitantes** de la encuesta de asistentes de un evento, de la calculadora de una propiedad o de la calculadora de viajes de una cuenta.
- **Administradores**, que reciben una notificación en la plataforma y un correo por cada solicitud nueva, con un enlace **Ver solicitud**. Los usuarios que además son SuperAdmin no reciben el aviso, aunque sí pueden ver el listado. Propietarios y Usuarios no ven las solicitudes.

## Dónde se encuentra

Los Administradores tienen el listado en **Proyectos → Solicitudes de contacto**. Al entrar solo se ven las pendientes; el filtro **Terminada** permite ver las resueltas o todas. Desde el detalle de cada solicitud se puede **Marcar como gestionada** o **Descartar**.

## Qué conviene saber

- Hay una sola solicitud por cálculo: si el visitante vuelve a enviar el formulario y la solicitud sigue pendiente, se actualizan sus datos sin avisar de nuevo. Si ya estaba resuelta, no se modifica.
- Marcar una solicitud como gestionada o descartada es definitivo.
- Si la compensación está desactivada en la cuenta, no se muestra nada. Si hay opciones disponibles, el botón lleva a la elección de proyecto como siempre.
- El visitante no recibe correo de confirmación.
