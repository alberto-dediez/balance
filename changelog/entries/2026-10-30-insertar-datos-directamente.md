---
title: "Huella ya calculada"
date: "2026-10-30 07:50"
category: feature
summary: "Introduce directamente tus resultados de alcance 1 y alcance 2 ya calculados, sin desglosar actividades."
plan: 2026-10-29-insertar-datos-directamente
manual: insertar-datos-directamente.pdf
techManual: insertar-datos-directamente.tech.md
published: true
---

## Qué cambia

Si ya has calculado por otros medios las emisiones de alcance 1 y alcance 2, ya no tienes que inventar un desglose de combustibles, vehículos o electricidad. Al registrar una huella puedes marcar la casilla **Huella ya calculada** y escribir tus cifras tal cual.

Con esta opción, los pasos **Alcance 1** y **Alcance 2** dejan de pedir actividades. En su lugar, para cada fuente de emisión te piden filas sencillas con un **Concepto** y una cantidad de CO₂eq en kg o en toneladas. En el paso **Alcance 3**, la categoría «Actividades relacionadas con combustibles y energía» también admite este tipo de filas, en lugar de calcularse sola. El resto del asistente funciona igual que siempre.

## A quién afecta y dónde se encuentra

Está disponible para el Propietario de la cuenta, tanto en la huella de una propiedad (Propiedades → Huellas de Carbono) como en la de una empresa (Empresas → Huellas de Carbono). En la huella de empresa, las cifras se introducen por cada propiedad incluida y en el bloque «Sin asignar a propiedad».

Si la huella ya está empezada, puedes cambiar de modo desde la parte superior de los pasos Alcance 1 y Alcance 2, con la acción **Huella ya calculada** o **Introducir datos por actividades**.

## Qué conviene saber

- Las toneladas se convierten: 2,5 t se cuentan como 2.500 kg de CO₂eq.
- Cambiar de modo elimina lo introducido en los alcances 1 y 2 y las filas de la categoría «Actividades relacionadas con combustibles y energía». El resto del alcance 3, el estado de la huella y las exclusiones se conservan. La plataforma pide confirmación antes de hacerlo.
- No se pueden mezclar los dos modos en una misma huella, y no se puede cambiar de modo con la huella completada: hay que reabrirla antes.
- En una huella ya calculada no está disponible la acción «Cargar consumos de Bioscore».
- Como no hay desglose de electricidad, no se calcula una cifra propia de alcance 2 por método de localización y se muestra «---».
- Tus filas se suman al total, a las gráficas de resultados y al plan de mitigación (agrupadas como «Emisiones comunicadas»). El informe en PDF indica que los alcances 1 y 2 se aportaron como resultados ya calculados, y los textos generados automáticamente lo tienen en cuenta.
