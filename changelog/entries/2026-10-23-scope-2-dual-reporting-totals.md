---
title: Doble reporte de la electricidad en los resultados
date: "2026-10-23 20:04"
category: improvement
summary: Los resultados y los informes de evento, propiedad y empresa muestran la electricidad por método de mercado y por método de localización, con un total comparable y un aviso cuando falta algún factor.
plan: 2026-10-23-scope-2-dual-reporting-totals
manual: scope-2-dual-reporting-totals.pdf
techManual: scope-2-dual-reporting-totals.tech.md
published: true
---

## Qué cambia

La electricidad comprada se puede contar de dos maneras: con el factor del contrato de tu comercializadora (método de mercado) o con el factor medio de la red eléctrica del país (método de localización). Hasta ahora la huella de evento no mostraba la cifra de localización y ninguna huella ofrecía un total por localización para comparar. Ahora los resultados y los informes en PDF muestran los dos métodos.

La cifra oficial, **CO₂ equivalente total**, sigue calculándose por el método de mercado y no cambia. Tampoco cambian los gráficos, el panel ni la compensación.

## A quién afecta

A los propietarios y usuarios de la cuenta y a los Administradores que consultan huellas de evento, propiedad o empresa. No hay que rellenar nada nuevo: las cifras salen de los datos que ya introdujiste.

## Dónde se encuentra

En el paso **Resultados** de cada huella, dentro de la tarjeta **Totales**, y en el informe en PDF:

- Propiedad y empresa: **Alcance 2 · método de mercado**, **Alcance 2 · método de localización** y **Total · método de localización**.
- Evento: **Electricidad · método de mercado**, **Electricidad · método de localización** y **Total · método de localización**. Solo aparecen si el evento tiene actividades de electricidad.
- El informe de un evento con electricidad incluye una página nueva, **Electricidad: método de mercado y de localización**, con el detalle por actividad.

## Qué conviene saber

- Si alguna actividad de electricidad no tiene factor de mix eléctrico (país sin datos, año sin factor o actividad guardada antes de septiembre de 2026 y no editada desde entonces), verás un recuadro amarillo con el número de actividades afectadas. En el informe aparece una fila con ese número.
- Esas actividades conservan su valor de mercado dentro de **Total · método de localización**, para no hacer desaparecer emisiones reales.
- En un evento se usa el país de su propiedad; sin país no hay cálculo por localización.
- Los informes ya emitidos no se modifican: las cifras nuevas aparecen en los que generes o reemitas a partir de ahora.
- El resumen redactado por la IA del informe puede citar las cifras nuevas.
