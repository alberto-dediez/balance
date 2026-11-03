---
title: "Materialidad: filtrado y activación de categorías del alcance 3"
date: "2026-11-02 19:18"
category: feature
summary: "El resultado de la evaluación de materialidad recomienda qué categorías del alcance 3 calcular u omitir, y el Propietario puede discrepar justificándolo."
plan: 2026-11-02-materialidad-filtrado-y-activacion-de-categorias
manual: materialidad-filtrado-y-activacion-de-categorias.pdf
techManual: materialidad-filtrado-y-activacion-de-categorias.tech.md
published: true
---

## Qué cambia

Al completar la evaluación de materialidad, la plataforma ya no se limita a mostrar la relevancia de cada categoría: ahora recomienda cuáles conviene **calcular** y cuáles se pueden **omitir** por no ser significativas. Las categorías que no alcanzan el umbral aparecen ya excluidas en el paso Alcance 3, con el motivo «No significativo» y un comentario que explica la puntuación obtenida.

## A quién afecta

- **Administrador**: fija en **Materialidad → Configuración** el umbral de relevancia (de 1 a 100, por defecto 50) y decide si una magnitud alta obliga a calcular la categoría aunque no llegue al umbral. Vale para toda la plataforma.
- **Propietario**: completa la evaluación, revisa el resumen y puede discrepar de la recomendación.

## Dónde se encuentra

- En el paso **Materialidad** de la huella de una propiedad o de una empresa, la tabla de categorías muestra ahora el umbral aplicado, el veredicto (Calcular u Omitir), el estado en la huella y las anulaciones.
- En el paso **Alcance 3**, cada categoría muestra si es material o no. Para calcular una categoría omitida o excluir una material, el Propietario cambia la casilla de exclusión y debe rellenar el **Motivo de la anulación**.
- En **Resultados**, la tabla de fuentes de emisión excluidas incluye la columna **Origen**: Manual, Recomendación de materialidad o Anulación de la recomendación (con la persona y la fecha).

## Qué conviene saber

- Cada veredicto guarda el umbral con el que se obtuvo. Cambiar el umbral después no modifica las evaluaciones ya completadas; para aplicarlo hay que reabrir la evaluación y completarla de nuevo.
- Una relevancia igual al umbral se calcula.
- Sin motivo no se guarda ninguna anulación. Volver a la recomendación retira la anulación.
- Una exclusión manual previa nunca se retira sola, y las categorías anuladas no se tocan.
- Las categorías propias del Administrador, la 8 y las de alcance 1 o 2 tienen veredicto pero no hay nada que excluir en el alcance 3.
- Las categorías 13 y 14 solo existen en la huella de empresa.
- Si se reinicia el cuestionario, se retiran las exclusiones recomendadas y las anulaciones registradas.
- El informe PDF, la exportación detallada y el certificado no incluyen todavía este origen de las exclusiones.
