---
title: Exportaciones protegidas según los datos que pides
date: "2026-10-23 11:55"
category: security
summary: Las exportaciones de cálculos de instalación, encuestas de asistentes y usuarios solo permiten descargar datos de tu propia cuenta, salvo los Administradores.
plan: 2026-10-22-property-trip-export-scoping
manual: property-trip-export-scoping.pdf
techManual: property-trip-export-scoping.tech.md
published: true
---

## Qué cambia

Hasta ahora, la plataforma comprobaba si una persona podía exportar un tipo de datos en general, pero no qué datos concretos estaba pidiendo. A partir de ahora, tres exportaciones a hoja de cálculo verifican que la información solicitada te corresponde: **Cálculos de instalación**, **Encuestas de asistentes** y **Usuarios**.

Si intentas exportar datos que no son de tu cuenta, la exportación no se inicia y verás el aviso «No se ha podido iniciar la exportación.». La comprobación se repite cuando el fichero se prepara, por si tus permisos cambian entre tanto; en ese caso la exportación falla y no se genera ningún fichero.

## A quién afecta

- **Propietarios:** solo pueden exportar instalaciones, eventos y usuarios de su cuenta. En la exportación de **Usuarios** ya no ven el campo **Cuenta**: la exportación queda fijada a su cuenta.
- **Administradores:** pueden elegir cualquier instalación en **Cálculos de instalación** y, en **Usuarios**, cualquier cuenta o la opción **Todas las cuentas**. Las encuestas de asistentes no están disponibles para este rol.
- **Impersonación:** si actúas dentro de otra cuenta, solo puedes exportar los datos de esa cuenta.

## Dónde se encuentra

- **Usuarios:** en el listado de usuarios, botón **Exportar**.
- **Cálculos de instalación:** en la pantalla de respuestas de una instalación, botón **Exportar**. La instalación llega ya seleccionada.
- **Encuestas de asistentes:** en la pantalla de respuestas de la encuesta de un evento, botón **Exportar**.

## Qué conviene saber

- En **Cálculos de instalación** la instalación es obligatoria: sin ella, o con una que no existe, la exportación se rechaza.
- **Todas las cuentas** es exclusivo de los Administradores.
- Los catálogos y el resto de exportaciones funcionan igual que antes.
- Cuando una exportación se permite, recibes un email con el enlace de descarga cuando está lista.
- Si crees que deberías tener acceso a unos datos que se rechazan, contacta con un Administrador.
