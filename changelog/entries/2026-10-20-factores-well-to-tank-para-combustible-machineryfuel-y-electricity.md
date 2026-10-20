---
title: Factores well-to-tank para combustibles y electricidad
date: "2026-10-20 22:30"
category: feature
summary: Los combustibles, los combustibles de maquinaria y la electricidad admiten un segundo factor de emisión, el well-to-tank, y las huellas guardan ya esas emisiones para el futuro cálculo del alcance 3.
plan: 2026-10-20-factores-well-to-tank-para-combustible-machineryfuel-y-electricity
manual: factores-well-to-tank-para-combustible-machineryfuel-y-electricity.pdf
techManual: factores-well-to-tank-para-combustible-machineryfuel-y-electricity.tech.md
published: true
---

## Qué cambia

Antes de llegar al depósito o a la red, un combustible o la electricidad ya han generado emisiones: extracción, refinado, transporte y distribución. Son las emisiones **well-to-tank** («del pozo al depósito»).

Ahora cada factor de emisión de cinco maestros tiene, junto al factor de siempre, un bloque **Well-to-tank** con los mismos cuatro valores (Kg CO2eq, Kg CO2, g CH4, g N2O) y su propia fuente, para el mismo año, grupo de coeficientes y unidad. Los cinco maestros son: combustibles, combustibles de maquinaria, comercializadoras eléctricas, factores GdO y mix eléctrico.

Al guardar un paso de una huella con consumos de combustibles, combustibles de maquinaria o electricidad, la plataforma guarda también sus emisiones well-to-tank. Por ahora **no se suman a ningún total** ni aparecen en resultados, paneles, informes ni compensaciones: se preparan para calcular más adelante la categoría «Actividades relacionadas con combustibles y energía» del alcance 3.

## A quién afecta

- **Administradores**: ven el bloque «Well-to-tank» en la ficha y en la tabla de **Factores de emisión** por año de los cinco maestros, y las columnas nuevas en **Importar** y **Exportar**.
- **Propietarios y usuarios**: no ven nada nuevo; sus huellas dan las mismas cifras que antes.

## Dónde se encuentra

- **Instalaciones → Combustibles**, **Comercializadoras eléctricas**, **Factores GdO** y **Mix eléctrico**.
- **Transporte → Combustible de maquinaria**.

En cada listado, el icono de editar abre la ficha y el botón **Factores de emisión** abre la tabla por año. **Copiar factores** copia a la vez el factor y el well-to-tank. Al importar y exportar aparecen los campos «CO2eq WTT», «CO2 WTT», «CH4 WTT», «N2O WTT» y «Fuente WTT».

## Qué conviene saber

- El well-to-tank es opcional, pero es «todo o nada»: si rellenas uno de sus cuatro valores, hay que rellenar los cuatro. Tampoco se puede guardar sin el factor principal.
- Un well-to-tank vacío se guarda a cero, y los factores que ya existían empiezan a cero.
- Importar un factor existente sin las columnas WTT pone su well-to-tank a cero: exporta primero para conservarlo.
- Las huellas ya guardadas no se recalculan: sus emisiones well-to-tank quedan a cero hasta que se vuelva a guardar el paso correspondiente.
