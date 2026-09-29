---
title: "Materialidad: sectores"
date: "2026-09-29 05:12"
category: feature
summary: Los administradores ya pueden crear sectores de actividad y decidir a qué sectores se dirige cada pregunta de materialidad.
plan: 2026-09-28-incorporar-sectores-a-materialidad
manual: incorporar-sectores-a-materialidad.pdf
techManual: incorporar-sectores-a-materialidad.tech.md
published: true
---

## Qué cambia

No todas las preguntas de materialidad tienen sentido para todas las organizaciones: una pregunta
sobre lavandería externa interesa a un hotel, pero no a una oficina. Ahora puedes indicar a qué tipo
de organización se dirige cada pregunta:

- **Una lista de sectores** común a toda la plataforma. Tú creas los que necesites (Hoteles,
  Eventos, Oficinas…); la plataforma no trae ninguno de serie.
- **Los sectores de cada pregunta.** Al registrar o editar una pregunta marcas los sectores en los
  que aplica. Si no marcas ninguno, la pregunta aplica a todos los sectores.

Por ahora solo se preparan los datos: no cambia ningún cálculo, informe ni pantalla de los clientes.
El cuestionario para los clientes llegará más adelante, y entonces cada organización verá las
preguntas de su sector y las que aplican a todos.

## A quién afecta

Solo a los **administradores**. Propietarios y usuarios no ven nada de esta sección. Los sectores
son los mismos para todas las cuentas.

## Dónde se encuentra

- **Materialidad → Sectores**: nuevo listado para registrar, editar y eliminar sectores.
- **Materialidad → Preguntas**: el formulario tiene un campo nuevo, **Sectores**, con una casilla
  por sector; el listado muestra la columna **Sectores** (o «Todos los sectores») y en **Filtrar**
  hay un campo **Sector**.

## Qué conviene saber

- Las preguntas que ya existían, incluidas las iniciales, no tienen sectores y siguen aplicando a
  todos. No hace falta tocarlas.
- El filtro **Sector** muestra solo las preguntas que tienen ese sector marcado; no incluye las que
  aplican a todos los sectores.
- **Eliminar un sector puede eliminar preguntas.** Si una pregunta tenía otros sectores, solo pierde
  el eliminado; si ese era su único sector, la pregunta se elimina. Las preguntas para todos los
  sectores no cambian. Al eliminar desde la fila se pide confirmación con este aviso; la
  eliminación de varios sectores a la vez no lo muestra. Si quieres conservar una pregunta, edítala
  antes y márcale otro sector.
- El nombre del sector se puede traducir a los idiomas de la plataforma, igual que las categorías y
  las preguntas.
- Todos los cambios quedan en **Administración → Registro** con el usuario que los hizo.
