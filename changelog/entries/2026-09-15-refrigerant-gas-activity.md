---
title: Fugas de gas refrigerante
date: 2026-09-15
category: feature
summary: Nuevo tipo de actividad que convierte los kilogramos de gas fugado en un equipo de climatización en emisiones de la huella del evento.
plan: 2026-09-15-refrigerant-gas-activity
manual: refrigerant-gas-activity.pdf
techManual: refrigerant-gas-activity.tech.md
published: true
---

## Qué cambia

Los equipos de climatización y refrigeración pierden gas con el uso, y ese gas cuenta como
emisión directa del evento. Hasta ahora la huella se componía de diez tipos de actividad
—desplazamientos, alojamiento, catering, materiales, residuos, electricidad, combustibles…— y
ninguno servía para anotar una fuga. Se añade el undécimo: la **fuga de gas refrigerante**. Cada
fuga se anota con la **Fuente** de la que procede (el equipo o la instalación, en texto libre), el
**Gas refrigerante** del catálogo, la **Cantidad** y su unidad, y la plataforma calcula sola la
columna **Emisiones (kg CO₂eq)** a partir del factor que ese gas tenga registrado para el año y el
grupo de coeficientes de la huella.

## Qué se ve hoy

**Ninguna pantalla cambia todavía.** Esta entrega deja el cálculo listo y probado, pero el
apartado para anotar fugas aún no aparece en ningún paso del asistente de huella, que sigue
mostrando exactamente los mismos apartados que antes. Las fugas forman parte del bloque de
emisiones directas del evento, que llegará en una entrega posterior; cuando ese bloque se publique,
el apartado aparecerá sin ninguna configuración adicional y funcionará tal y como describe el
manual adjunto.

Lo que sí conviene hacer desde ya es preparar el catálogo desde **Instalaciones › Gases
refrigerantes**: comprobar que los gases que usan tus instalaciones están dados de alta y que
tienen factor de emisión para el año que vas a calcular. Sin eso, el día que el apartado aparezca
no habrá nada que seleccionar.

## Qué conviene saber

Una fuga se mide siempre en peso: el selector de unidad solo ofrece kilogramo, gramo y tonelada, y
la conversión la hace la plataforma. Cada fuga guarda el factor con el que se calculó, así que
corregir después el catálogo no altera lo ya anotado. Podrán anotarlas los Propietarios y los
Proveedores de la cuenta en la que se esté trabajando; los Usuarios no tienen acceso al cálculo de
la huella.
