---
title: "Métodos de cálculo del alcance 3 con los nombres del GHG Protocol"
date: "2026-11-02 07:12"
category: improvement
summary: "El informe PDF de la huella de propiedad y de empresa nombra ahora el método de cada categoría del alcance 3 como lo hace el GHG Protocol, en lugar de un genérico «datos de actividad»."
plan: 2026-11-01-alcance-3-deducir-metodo-aplicado
manual: alcance-3-deducir-metodo-aplicado.pdf
techManual: alcance-3-deducir-metodo-aplicado.tech.md
published: true
---

## Qué cambia

La tabla **Métodos de cálculo por categoría** del informe PDF de la huella de una propiedad o de una empresa usa ahora los nombres del estándar internacional GHG Protocol para el alcance 3. Antes, casi todos los cálculos con cantidades físicas aparecían como «Basado en datos de actividad»; ahora un transporte calculado por distancia, un residuo o una pernoctación se distinguen entre sí. Quien verifique la huella lee en el informe el método que espera encontrar.

No hay que hacer nada para activarlo: la plataforma decide el método de cada partida con los datos que ya están introducidos.

## A quién afecta

A Propietarios, Usuarios y Administradores, que verán los nombres nuevos en las tablas **Métodos de cálculo por categoría** y **Calidad de los datos**, en español, inglés y turco, tanto en el diseño vertical como en el apaisado. El informe de huella de un evento no cambia, porque no muestra métodos de cálculo.

## Cómo se decide el método

Se aplica la primera regla que encaja: emisiones comunicadas por un proveedor o por la propia organización; importe en una divisa (**Basado en gasto**); partidas de la encuesta de empleados; transporte y movilidad por distancia (**Basado en distancia**) o por litros, kWh o kilos (**Basado en combustible**); residuos (**Específico por tipo de residuo**); y el resto (**Basado en datos medios**). Los alcances 1 y 2 siguen mostrando «Basado en datos de actividad».

## Qué conviene saber

- El método no se elige ni se corrige a mano. La unidad con la que está definido el elemento en el maestro influye: el mismo tipo de partida puede salir como gasto o como físico.
- Un transporte en kilos se interpreta como combustible, no como mercancía.
- La calidad de los datos de una categoría es la de su método menos fiable: si mezcla distancia y gasto, es Baja.
- Los informes ya generados no se modifican. Los que estén en borrador o pendientes de revisión conservan los textos redactados, que pueden hablar de «datos de actividad», mientras que el PDF mostrará los nombres nuevos: conviene revisarlos antes de publicar.
- Los textos redactados por IA nombran ahora los métodos aplicados y hablan de las limitaciones del gasto y de los datos medios.
