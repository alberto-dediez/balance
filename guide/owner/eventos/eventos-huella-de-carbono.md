---
title: "Huella de carbono de un evento"
summary: "Los pasos para calcular la huella de un evento: materiales, fases, proveedores, empleados, asistentes y resultados."
profile: owner
menu: "Eventos › Eventos"
routes: [events_carbon_footprint_edit, events_carbon_footprint_materials, events_carbon_footprint_assembly, events_carbon_footprint_celebration, events_carbon_footprint_breakdown, events_carbon_footprint_providers, events_carbon_footprint_provider_contribution_show, events_carbon_footprint_provider_contribution_resend, events_carbon_footprint_provider_contribution_ignore, events_carbon_footprint_provider_contribution_unignore, events_carbon_footprint_employees, events_carbon_footprint_employee_contribution_show, events_carbon_footprint_employee_contribution_resend, events_carbon_footprint_employee_contribution_ignore, events_carbon_footprint_employee_contribution_unignore, events_carbon_footprint_attendees, events_carbon_footprint_results, events_carbon_footprint_reopen, events_carbon_footprint_emissions_calculate, carbon_footprint_reports_download, carbon_footprint_reports_reissue, carbon_footprint_reports_texts, owner_contribution_choose, export]
updated: "2026-11-14 00:19"
---

# Huella de carbono de un evento

La huella de un evento se calcula en ocho pasos que siguen la vida del evento. Cada paso se guarda al pulsar **Siguiente**; con **Anterior** vuelves atrás.

> **Dónde está:** menú **Eventos › Eventos**, botón **Cálculo de Huella de Carbono** de un evento.

## Cómo se rellenan las actividades

Cada fuente de emisión tiene una tabla. Pulsa **Añadir**, elige el elemento del catálogo y escribe la cantidad; la plataforma calcula las **Emisiones (kg CO₂eq)**. Si una fuente no aplica, márcala con **Excluir esta fuente de emisión** e indica el **Motivo de la exclusión**.

## 1. Materiales

- **Materiales fabricados y utilizados para el evento**
- **Merchandising fabricado y utilizado para el evento**
- **Catering adquirido para el evento**

## 2. Montaje, 3. Celebración y 4. Desmontaje

Cada fase tiene las mismas fuentes:

- **Instalaciones fijas (calderas, motores estacionarios, etc)**
- **Transporte de mercancías** (peso de la carga y distancia)
- **Maquinaria** y **Consumo de electricidad en vehículos y maquinaria**
- **Consumo de electricidad en las instalaciones**
- **Movilidad de proveedores y equipo**
- **Pernoctaciones**
- **Gestión de residuos generados**

En la celebración aparecen además **Movilidad de asistentes (generada por la encuesta)** y **Pernoctaciones de asistentes (generadas por la encuesta)**, que se rellenan solas a partir de las respuestas de la encuesta.

## 5. Proveedores

Marca los proveedores que deben aportar sus propios datos (combustible, maquinaria, pernoctación y movilidad) y pulsa **Siguiente**. El enlace se envía al email de contacto de cada uno. En **Contribuciones** ves su **Estado** (**Pendiente**, **Contestado** o **Descartado**) y puedes **Ver** sus datos, **Reenviar** la solicitud, **Descartar** o **Reactivar**.

## 6. Empleados

Indica el **Total de empleados** que participan en el evento y el **Nivel de confianza**, marca los empleados que deben aportar su movilidad y pernoctaciones y pulsa **Siguiente**. Cada uno recibe un enlace por correo. En **Contribuciones** ves **Pendientes**, **Contestadas**, **% contestado** y **Margen de error**, y por empleado puedes **Ver**, **Reenviar**, **Descartar** o **Reactivar**.

## 7. Asistentes

Comparte la **Encuesta de asistentes** (QR y enlace) para recoger su movilidad y pernoctaciones. Verás las **Respuestas recibidas**, los **Asistentes estimados**, el **% encuestados**, de dónde salen las distancias (**Ruta real**, **Distancia aproximada**, **Introducidos a mano**), el **Estado de la encuesta** y las **Aportaciones de los asistentes** que han compensado.

Si el evento no tiene sede indicada verás **Sin ubicación de la sede no se calcularán distancias automáticas; los asistentes tendrán que introducir sus km.**

Cuando tengas suficientes respuestas, cierra la encuesta:

1. En **Cerrar la encuesta**, indica los **Asistentes reales** y el **Nivel de confianza**.
2. Pulsa **Cerrar encuesta y continuar** y confirma. Las respuestas se extrapolan al total de asistentes.

Si la encuesta no tiene respuestas, se conservan las actividades de asistentes que hayas introducido a mano.

## 8. Resultados

- **Totales**: **CO₂ equivalente total (kg)**, **CO₂ (kg)**, **CH₄ (kg)**, **N₂O (kg)**, la electricidad por método de mercado y de localización, el **Total · método de localización** y el **CO₂ equivalente por asistente (kg)**.
- Gráficos de **Emisiones por fuente** y **Emisiones por fase**, y los **Principales orígenes de los asistentes**.
- El estado del **Informe de huella de carbono**: revisión de textos, revisión de la plataforma, emisión y descarga, igual que en las huellas de propiedades. Si hay compensaciones posteriores al informe verás **Se han registrado compensaciones posteriores a la última emisión del informe. Reemítelo para incluirlas.**

Desde el menú de acciones de arriba puedes **Descargar informe**, **Reemitir informe**, **Exportar** el detalle y, si tu cuenta tiene activada la funcionalidad **Compensación**, **Compensar emisiones**.

## Reabrir el cálculo

En **Resultados**, pulsa **Reabrir cálculo** y confirma. Podrás volver a editar todas las fases; se conservan los datos, pero se elimina el informe generado.

## Relacionado

- [Eventos](eventos.md)
- [Proveedores](../proveedores/proveedores.md)
- [Empleados](../administracion/empleados.md)
- [Compensar emisiones](../proyectos/proyectos-compensar.md)
