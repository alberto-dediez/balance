---
title: "Desvincular una propiedad de su cuenta sin perder datos"
date: "2026-11-24 19:06"
category: fix
summary: "Al quitar la cuenta a una propiedad ya no se pierden el número de empleados ni de clientes diarios."
plan: 2026-11-24-preserve-property-data-on-account-unlink
manual: preserve-property-data-on-account-unlink.pdf
techManual: preserve-property-data-on-account-unlink.tech.md
published: true
---

## Qué cambia

Cuando una propiedad deja de pertenecer a una cuenta, la plataforma ahora solo le quita la cuenta y la empresa. Todo lo demás se queda como estaba: el número de empleados, el número de clientes diarios, el nombre, la información general y los identificadores externos.

Antes, al desvincular una propiedad se perdían el número de empleados y el de clientes diarios, datos que alguien había escrito a mano y que no se podían recuperar. Este fallo está corregido.

## A quién afecta

- **Administradores:** pueden desvincular cualquier propiedad, desde la acción masiva **Asignar cuenta** de **Propiedades → Propiedades** o desde las aplicaciones conectadas.
- **Propietarios:** solo pueden desvincular propiedades de su propia cuenta, y únicamente desde las aplicaciones conectadas.
- Los usuarios con rol Usuario no pueden desvincular propiedades.

## Dónde se encuentra

En **Propiedades → Propiedades**, marca las propiedades, elige **Asignar cuenta** y deja el campo **Cuenta** vacío: quedarán sin cuenta y sin empresa, conservando el resto de datos.

## Otras situaciones corregidas

- Al eliminar una cuenta, sus propiedades quedan sin cuenta y sin empresa, pero conservan empleados y clientes diarios.
- Al eliminar una empresa, sus propiedades quedan sin empresa y conservan la cuenta, los empleados y los clientes diarios.
- Al emparejar una propiedad, si se actualiza su información o sus códigos, conserva la empresa, los empleados y los clientes diarios.
- Con **Asignar cuenta** y la misma cuenta que ya tenía, la propiedad mantiene su empresa; con una cuenta distinta, la empresa se quita.

## Qué conviene saber

- Una empresa solo existe dentro de una cuenta, por eso una propiedad sin cuenta nunca conserva empresa. Si más adelante se asigna a una cuenta, habrá que volver a elegir su empresa.
- Desvincular una propiedad que ya no tiene cuenta no cambia nada.
- Los empleados y clientes diarios perdidos antes de este cambio no se recuperan.
- El cambio queda registrado en el historial de la propiedad.
