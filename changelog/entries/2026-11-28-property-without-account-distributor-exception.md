---
title: "Aviso claro al asignar un distribuidor a una propiedad sin cuenta"
date: "2026-11-28 17:57"
category: fix
summary: "Si una propiedad no se puede adherir a un distribuidor por no tener cuenta, el aviso ahora dice cuál es y qué hacer para resolverlo."
plan: 2026-11-27-property-without-account-distributor-exception
manual: property-without-account-distributor-exception.pdf
techManual: property-without-account-distributor-exception.tech.md
published: true
---

## Qué cambia

Al intentar adherir a un distribuidor una propiedad que no tiene cuenta, el aviso daba a entender que la propiedad no existía. Ahora indica su nombre y su identificador, explica que no tiene cuenta asignada y pide asignarle una antes de adherirla.

La regla es la misma de siempre: sin cuenta no hay adhesión.

## A quién afecta

A los **Administradores**, que son quienes asignan distribuidores. Propietarios y usuarios no notan ningún cambio.

## Dónde se encuentra

En **Propiedades → Propiedades**, al marcar propiedades, elegir **Asignar distribuidor** en **Acción a realizar**, escoger el **Distribuidor** y pulsar **Realizar**.

## Cómo resolverlo

1. Edita la propiedad, elige su cuenta en el campo **Cuenta** y pulsa **Guardar y salir**.
2. Vuelve al listado y aplica de nuevo **Asignar distribuidor**. Asignar la cuenta no adhiere la propiedad por sí solo.

## Qué conviene saber

- Cada propiedad marcada tiene su propio resultado: las demás se adhieren aunque una falle.
- Además de la explicación, aparece el aviso de que no se ha podido asignar el distribuidor a esa propiedad.
- Tras la explicación, el mismo aviso muestra un texto largo de uso interno que no necesitas leer.
- Los avisos desaparecen solos a los pocos segundos y uno puede tapar al otro. La columna **Cuenta** vacía también indica qué propiedades no la tienen.
- Si usas la acción **Asignar cuenta** para varias propiedades, elige siempre la cuenta antes de pulsar **Realizar**: con el selector vacío, se les quita la cuenta.
