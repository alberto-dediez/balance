---
title: "Filtro «Con toneladas disponibles» en Créditos de CO2"
date: "2026-11-11 12:05"
category: improvement
summary: "El listado de Créditos de CO2 muestra por defecto solo los créditos sobre los que aún se puede actuar, con un filtro para ver los agotados."
plan: 2026-11-11-anadir-filtro-a-creditos
manual: anadir-filtro-a-creditos.pdf
techManual: anadir-filtro-a-creditos.tech.md
published: true
---

## Qué cambia

El listado de **Proyectos → Créditos de CO2** incorpora un filtro nuevo, **«Con toneladas disponibles»**, para que el Administrador deje de ver los créditos que ya no necesitan gestión. Con el tiempo se acumulaban créditos con todas sus toneladas compensadas o transferidas, y solicitudes rechazadas o canceladas, que entorpecían el trabajo diario.

El filtro tiene tres valores:

- **Sí** (valor inicial): solicitudes pendientes y créditos validados a los que todavía les queda alguna tonelada libre.
- **No**: lo contrario, es decir, créditos validados agotados y solicitudes rechazadas o canceladas.
- **Todos**: no aplica este filtro.

## A quién afecta

Solo a los Administradores, que son quienes disponen de este listado. Propietarios y Usuarios no ven ningún cambio.

## Dónde se encuentra

En el menú lateral, **Proyectos → Créditos de CO2**, junto al resto de filtros del listado. Se combina con ellos (cuenta, proyecto, estado, origen e identificador). El botón **Resetear filtro** devuelve «Con toneladas disponibles» a «Sí» y quita los demás filtros.

## Qué conviene saber

- Al abrir la pantalla el filtro arranca en **«Sí»**. Si buscas un crédito agotado o una solicitud rechazada y no aparece, cambia el filtro a «No» o a «Todos» y pulsa **Filtrar**.
- Las toneladas libres son las compradas menos las transferidas a otras cuentas y las asignadas a aportaciones, la misma cifra que se ve en el detalle del crédito.
- Un crédito parcialmente compensado sigue visible con «Sí» mientras le quede alguna tonelada libre. Cuando se compensa o transfiere la última, pasa a «No» por sí solo.
- La elección se recuerda durante la sesión: se mantiene al paginar, ordenar o salir del listado y volver.
- El filtro solo cambia lo que ves: no modifica créditos ni afecta a saldos, asignaciones o tesorería.
