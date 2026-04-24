# Informe de Placeholders en GeminiPrompts

## SuggestActionsPrompt
**Sin placeholders.** El prompt construye el texto 100% con datos reales del sistema (IDs, nombres, etapas, prioridades de `CriticalPoint` y `Action`).

---

## GenerateIntroductionSectionPrompt
Placeholders que se piden al modelo que incluya en el output generado:

| Placeholder | Propósito |
|---|---|
| `[Responsible person for plan implementation]` | Responsable del plan |
| `[Approximate number of employees]` | Nº de empleados |
| `[Approximate number of daily diners / guests]` | Nº de comensales/día |


---

## GenerateMeasurementSectionPrompt
| Placeholder | Propósito |
|---|---|
| `[KPI 1: specific name and measurement unit]` | KPI a completar manualmente |
| `[KPI 2: specific name and measurement unit]` | KPI a completar manualmente |
| `[Plan review frequency: quarterly / annual]` | Frecuencia de revisión |
| `[Person responsible for monitoring]` | Responsable de seguimiento |
| `[Staff training plan: courses, workshops, frequency]` | Plan de formación |
| `[Date of next scheduled plan review]` | Fecha próxima revisión |


---

## GenerateDonationsSectionPrompt
| Placeholder | Propósito |
|---|---|
| `[Name of receiving entity (Food Bank, NGO, social canteen)]` | Entidad receptora de donaciones |
| `[Date of donation agreement signature]` | Fecha firma acuerdo |
| `[Collection frequency: daily / weekly / as needed]` | Frecuencia de recogida |
| `[Person responsible for cold chain during handover]` | Responsable cadena de frío |
| `[Maximum time between preparation and handover]` | Tiempo máx. preparación-entrega |

---

## GenerateCriticalPointsSectionPrompt
**Sin placeholders explícitos.** Toda la información se construye con datos reales (propiedades, puntos críticos, acciones propuestas, razones).

---

## GenerateActionPlanSectionPrompt
**Sin placeholders explícitos.** Los únicos fallbacks son `[Unknown property]` y `[Unknown critical point]` como valores de guardia cuando los mapas no contienen la clave — son placeholders de error, no de completado manual.

---

## Resumen

| Prompt | Placeholders para completar manualmente | Placeholders de error/guardia |
|---|---|---|
| `SuggestActionsPrompt` | 0 | 0 |
| `GenerateDonationsSectionPrompt` | 5 | 0 |
| `GenerateCriticalPointsSectionPrompt` | 0 | 0 |
| `GenerateActionPlanSectionPrompt` | 0 | 2 (`[Unknown property/critical point]`) |
