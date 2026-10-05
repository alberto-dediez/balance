---
title: Comprobaciones automáticas más rápidas
date: "2026-10-05 12:59"
category: improvement
summary: La revisión automática que se hace antes de cada actualización tarda bastante menos, así que las correcciones y novedades pueden llegar antes.
plan: 2026-10-05-ejecucion-de-tests-en-paralelo
manual: ejecucion-de-tests-en-paralelo.pdf
techManual: ejecucion-de-tests-en-paralelo.tech.md
published: true
---

## Qué cambia

Antes de publicar cualquier novedad o corrección, el equipo técnico pasa una batería de
comprobaciones automáticas que recorre la plataforma como lo haría una persona: entra, rellena
formularios, pulsa botones y revisa que cada pantalla responde como debe.

Hasta ahora esas comprobaciones se hacían una detrás de otra. Con esta mejora se reparten en
varios grupos que trabajan a la vez, cada uno con sus propios datos de prueba, y el resultado
final se reúne al terminar. La revisión completa tarda bastante menos, de modo que las
correcciones y las mejoras pueden llegar antes a la plataforma.

## A quién afecta

A nadie en su trabajo diario. Administradores, propietarios y usuarios siguen viendo los mismos
menús, pantallas, permisos y contenidos de siempre. No hay ninguna opción nueva que activar ni
ningún ajuste que revisar: la mejora es interna del equipo técnico.

## Dónde se encuentra

En ninguna pantalla de la plataforma. Lo que se acelera es la revisión que hace el equipo antes
de cada actualización, no las pantallas que usas, que mantienen la misma velocidad.

## Qué conviene saber

- **Tus datos no intervienen.** Las comprobaciones trabajan siempre con datos de prueba
  inventados y separados, nunca con las cuentas, usuarios ni archivos reales de la plataforma.
- **Mismo nivel de revisión.** Se comprueba exactamente lo mismo que antes; lo único que cambia
  es que varias comprobaciones avanzan a la vez.
- **Si algo falla, no se publica.** Basta con que una sola comprobación falle para que la
  revisión entera se dé por no superada, igual que hasta ahora.
