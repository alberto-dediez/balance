---
title: "Huellas de Carbono"
summary: "Crea y gestiona la huella de carbono anual de tus empresas, que agrupa las de sus propiedades."
profile: owner
menu: "Empresas › Huellas de Carbono"
routes: [company_carbon_footprints, company_carbon_footprints_register, company_carbon_footprints_edit, company_carbon_footprints_remove, company_carbon_footprints_scope_1, company_carbon_footprints_scope_2, company_carbon_footprints_property_footprints_load, company_carbon_footprints_materiality, company_carbon_footprints_scope_3, company_carbon_footprints_operators_invite, company_carbon_footprints_operators_show, company_carbon_footprints_operators_resend, company_carbon_footprints_operators_ignore, company_carbon_footprints_operators_unignore, company_carbon_footprints_operators_remove, company_carbon_footprints_providers, company_carbon_footprints_providers_register, company_carbon_footprints_providers_edit, company_carbon_footprints_providers_show, company_carbon_footprints_providers_resend, company_carbon_footprints_providers_ignore, company_carbon_footprints_providers_unignore, company_carbon_footprints_providers_remove, company_carbon_footprints_employees, company_carbon_footprints_employees_show, company_carbon_footprints_employees_resend, company_carbon_footprints_employees_ignore, company_carbon_footprints_employees_unignore, company_carbon_footprints_mitigation_plan, company_carbon_footprints_results, company_carbon_footprints_reopen, company_carbon_footprints_declare_denominators, company_carbon_footprints_emissions_calculate, carbon_footprint_reports_download, carbon_footprint_reports_reissue, carbon_footprint_reports_texts, carbon_footprint_justified_exclusions_download, export]
updated: "2026-11-14 00:19"
---

# Huellas de Carbono

La huella de una empresa calcula las emisiones de un año natural agrupando las de sus propiedades consolidadas (propias, arrendadas explotadas por la empresa o en gestión). Las propiedades franquiciadas o arrendadas a terceros no entran en los alcances 1 y 2: aparecen en su alcance 3. Esta opción aparece si tu cuenta tiene activada la funcionalidad **Huella de carbono de propiedades y empresas**.

> **Dónde está:** menú **Empresas › Huellas de Carbono**. La pantalla se titula **Huella de empresas**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Empresa** | Empresa calculada. |
| **Año** | Año de la huella. |
| **Estado** | Paso al que ha llegado, de **Iniciada** a **Completada**. |
| **Fecha de registro** | |

Pulsa **Filtrar** para elegir **Empresa**, **Año** o **Estado**.

## Crear una huella

1. Pulsa **Registrar** (o **Cálculo de Huella de Carbono** en el listado de empresas).
2. Elige la **Empresa** y el **Año de cálculo**. Marca **Huella ya calculada** si ya tienes los resultados de alcance 1 y 2.
3. Pulsa **Registrar**.

## El asistente

Tiene los mismos ocho pasos que la huella de una propiedad (**Alcance 1**, **Alcance 2**, **Materialidad**, **Alcance 3**, **Proveedores**, **Empleados**, **Medidas de Reducción y Mitigación** y **Resultados**), explicados en [Asistente de la huella](../propiedades/huellas-de-carbono-asistente.md). Estas son las diferencias:

### Alcances 1 y 2 por propiedad

Cada fuente tiene un bloque por propiedad consolidada y otro **Sin asignar a propiedad** para lo que no corresponde a ninguna.

Además de **Cargar consumos de Bioscore** y **Huella ya calculada**, la cabecera ofrece **Cargar desde huellas de propiedad**: sustituye las actividades del alcance en las propiedades que tienen la huella de propiedad del mismo año completada. Las emisiones se recalculan con los factores de emisión vigentes, así que pueden diferir de las de la huella de propiedad. Tras confirmar verás **Actividades copiadas desde huellas de propiedad** con el número de actividades y propiedades, y, si las hay, **Propiedades omitidas** con su motivo (**Sin huella completada del año**, **Huella ya calculada** o **Sin actividades en este alcance**) y las diferencias de emisiones encontradas. Esta acción no aparece si la huella de empresa está marcada como ya calculada.

### Propiedades franquiciadas y arrendadas a terceros

En el alcance 3, las categorías **Franquicias** y **Activos arrendados aguas abajo** tienen una fila por cada propiedad de la empresa con ese régimen, con su **Origen** (**Huella … completada**, **Aportación del franquiciado o arrendatario** o **Sin datos**), las **Emisiones calculadas (kg CO₂eq)** y las **Emisiones a aplicar (kg CO₂eq)**, que puedes sustituir por un valor manual.

Si la propiedad no tiene huella completada, puedes pedir sus alcances 1 y 2 a quien la explota:

1. Pulsa **Invitar al franquiciado o arrendatario** en su fila. Se precarga el contacto guardado en la propiedad.
2. Confirma. Verás **Invitación enviada a** su email. Recibirá un enlace para aportar sus datos.

Luego puedes ver sus **Respuestas**, **Reenviar** la invitación, **Descartar** o **Reactivar** su aportación y **Eliminar** la invitación.

### Proveedores

Al registrar una aportación de proveedor puedes indicar también la **Propiedad** a la que corresponde, o **Sin asignar a propiedad**.

### Resultados

Los **Datos de actividad del año** llegan precargados con la suma de las huellas de propiedad del año de sus propiedades consolidadas. Revísalos y guarda. La huella de empresa no tiene certificado de huella.

## Informe, exportación y reapertura

Igual que en las huellas de propiedad: al completarla se genera el informe (con revisión de textos y, si procede, del equipo de la plataforma), y desde el menú de acciones puedes **Descargar informe**, **Reemitir informe**, **Exportar**, descargar las **Exclusiones justificadas** o **Ver indicadores**. En **Resultados** puedes **Reabrir cálculo**.

## Relacionado

- [Asistente de la huella](../propiedades/huellas-de-carbono-asistente.md)
- [Huellas de Carbono de propiedades](../propiedades/huellas-de-carbono.md)
- [Indicadores](indicadores.md)
- [Empresas](empresas.md)
