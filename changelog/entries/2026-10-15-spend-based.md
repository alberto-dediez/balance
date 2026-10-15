---
title: Gasto en divisas y tipos de cambio
date: "2026-10-15 11:54"
category: feature
summary: Calcula la huella de carbono a partir de lo gastado, en euros o en cualquiera de las 29 divisas del Banco Central Europeo, con tipos de cambio que se actualizan solos cada día.
plan: 2026-10-15-spend-based
manual: spend-based.pdf
techManual: spend-based.tech.md
published: true
---

## Qué cambia

El dinero pasa a ser una unidad más en la huella de carbono. Un maestro puede llevar su factor de
emisión por euro gastado, y una actividad puede anotarse con el importe gastado en euros o en otra
divisa. Así se puede calcular la huella a partir del gasto cuando no se conoce la cantidad física
(kilos, litros, kilómetros…) pero sí lo que costó.

Si el importe está en otra divisa, la plataforma lo pasa a euros con el último tipo de cambio
oficial del Banco Central Europeo (BCE) y aplica después el factor de emisión. Por ejemplo, con un
tipo de 1,25 dólares por euro, 250 dólares son 200 euros; con un factor de 0,5 kg CO₂e por euro,
la actividad emite 100 kg CO₂e.

## A quién afecta

- **Administradores:** pueden dar de alta maestros con la unidad **Euro**, consultar los tipos
  vigentes, forzar su descarga y reciben un aviso si la descarga diaria falla.
- **Propietarios y Usuarios:** al registrar una actividad de un elemento cuyo factor va por euro,
  eligen la divisa del importe en el desplegable de unidad.

## Dónde se encuentra

- Pantalla **Administración → Tipos de cambio** (solo Administradores): muestra cada divisa, cuántas
  unidades vale un euro, cuántos euros vale una unidad, la fecha de publicación y cuándo se
  descargó. El botón **Sincronizar ahora** lanza la descarga en segundo plano.
- Las divisas se eligen en el campo **Unidad** de la ficha de los maestros con factor de emisión y
  en el desplegable de unidad de cada fila de actividad de una huella.

## Qué conviene saber

- Los tipos de cambio se descargan automáticamente cada día a las 17:00 UTC. En fines de semana y
  festivos, si el BCE no publica nada nuevo, se mantienen los valores anteriores.
- Siempre se usa el último tipo publicado en el momento de guardar, no el del día del gasto. No se
  guarda historial, y las emisiones ya calculadas no cambian cuando llega un tipo nuevo.
- Mientras no se haya descargado ningún tipo, solo se pueden calcular importes en euros.
- Los tipos son comunes a toda la plataforma y no se pueden editar a mano.
- No se mezclan magnitudes: un importe en dinero solo se aplica a elementos cuyo factor va por euro.
- Si la descarga falla, se conservan los tipos anteriores y todos los Administradores reciben una
  notificación interna; la descarga se repite al día siguiente.
