---
title: Informe de huella de carbono con textos revisables y aprobación
date: "2026-09-23 16:40"
category: feature
summary: Las huellas de eventos, propiedades y empresas generan un informe PDF con índice y cifras clave, cuyos textos se pueden revisar y aprobar antes de emitirlo.
plan: 2026-09-18-carbon-footprint-report
manual: carbon-footprint-report.pdf
techManual: carbon-footprint-report.tech.md
published: true
---

## Qué cambia

Al completar la huella de carbono de un evento, de una propiedad o de una empresa, la plataforma
prepara un informe en PDF con portada, índice con número de página, una página de cifras clave,
textos explicativos redactados automáticamente, los datos y resultados de la huella y un anexo con
los factores de emisión. Hasta ahora solo los eventos tenían informe; las propiedades y las
empresas lo estrenan, y sus huellas se dan por completadas al guardar el **Alcance 2**.

Los textos quedan guardados y, según la configuración de la cuenta, el informe se emite solo, lo
corrige primero el Propietario o lo revisa un Administrador antes de emitirlo. Una vez aprobado,
el informe está disponible en todos los idiomas de la plataforma.

## A quién afecta

Los **Propietarios** ven el estado del informe en la tarjeta **Informe de huella de carbono** del
paso **Resultados**, lo descargan, lo reemiten y, si su cuenta lo permite, revisan los textos en
la pantalla **Textos del informe**. Los **Administradores** deciden por cuenta si se pueden editar
los textos y si hay autorrevisión, y aprueban o rechazan los informes pendientes.

## Dónde se encuentra

- Informe de cada huella: paso **Resultados** del asistente, desde **Eventos → Eventos**,
  **Propiedades → Huellas de Carbono** o **Empresas → Huellas de Carbono**. También hay un botón
  **Descargar** en la fila de los listados.
- Revisión: **Administración → Informes de huella**, filtrado por defecto a los informes
  **Pendiente de revisión**, con los botones **Aprobar y emitir** y **Rechazar**.
- Permisos: **Administración → Cuentas**, **Editar**, apartado **Informe de huella de carbono**.
- Maquetación apaisada: **Administración → Cuentas**, **Personalización**, **Tema visual**
  **Classic**.

## Qué conviene saber

Por defecto las cuentas tienen autorrevisión y no editan los textos, así que el informe se sigue
emitiendo solo, como hasta ahora. Rechazar exige un comentario para el cliente y reabre la huella
completa; al volver a completarla se prepara un informe nuevo. **Reabrir cálculo** desbloquea una
huella completada y elimina su informe, y eliminar una huella elimina también su informe. Los
textos se revisan solo en el idioma principal y se traducen al aprobar. El PDF mantiene siempre
los colores y logotipos de la plataforma, aunque la cuenta tenga marca blanca: con el tema
**Classic** el informe sale en formato apaisado, con fotografías y tablas y sin gráficas; con
cualquier otro tema, en vertical. Los informes de evento ya emitidos se siguen descargando.
