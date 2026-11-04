---
title: "Revisión interna más rápida de la plataforma"
date: "2026-11-04 10:53"
category: improvement
summary: "Las comprobaciones internas que protegen el funcionamiento de la plataforma se reparten en varios grupos y terminan antes, de modo que las novedades y correcciones pueden llegar antes."
plan: 2026-11-04-tests-unitarios-en-paralelo
manual: tests-unitarios-en-paralelo.pdf
techManual: tests-unitarios-en-paralelo.tech.md
published: true
---

## Qué cambia

Antes de publicar cualquier novedad o corrección, el equipo técnico pasa dos tipos de comprobaciones automáticas. Unas recorren la plataforma como lo haría una persona; otras revisan, una por una, las reglas internas de cada parte del sistema: que los permisos se respetan, que los datos se guardan bien, que cada proceso hace lo que debe.

Las primeras ya se hacían en varios grupos a la vez. Con esta mejora, las segundas también: se reparten entre varios grupos que trabajan simultáneamente, cada uno con sus propios datos de prueba, y el resultado se reúne al terminar. La revisión completa tarda menos y las correcciones y mejoras pueden llegar antes a la plataforma.

## A quién afecta

A nadie en su trabajo diario. Administradores, propietarios y usuarios siguen viendo los mismos menús, pantallas, permisos y contenidos de siempre. No hay nada que activar ni que configurar: la mejora es interna del equipo técnico.

## Dónde se encuentra

En ninguna pantalla de la plataforma. Lo que se acelera es la revisión previa a cada actualización, no las pantallas que usas, que mantienen la misma velocidad.

## Qué conviene saber

- **Tus datos no intervienen.** Las comprobaciones usan siempre datos de prueba inventados y separados, nunca las cuentas, usuarios ni archivos reales.
- **Mismo nivel de revisión.** Se comprueba exactamente lo mismo que antes; solo cambia que varias comprobaciones avanzan a la vez.
- **Si algo falla, no se publica.** Basta con que una sola comprobación falle para que la revisión entera se dé por no superada, igual que hasta ahora.
- **Datos de prueba más limpios.** Las comprobaciones ya no dejan restos de archivos temporales tras ejecutarse.
