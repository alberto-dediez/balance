---
title: "Asistente de la huella"
summary: "Los ocho pasos para calcular la huella de carbono de una propiedad, de los alcances 1 y 2 a los resultados."
profile: owner
menu: "Propiedades › Huellas de Carbono"
routes: [property_carbon_footprints_scope_1, property_carbon_footprints_scope_2, property_carbon_footprints_materiality, materiality_assessments_start, materiality_assessments_topic, materiality_assessments_complete, materiality_assessments_reopen, materiality_assessments_remove, property_carbon_footprints_scope_3, property_carbon_footprints_providers, property_carbon_footprints_providers_register, property_carbon_footprints_providers_edit, property_carbon_footprints_providers_show, property_carbon_footprints_providers_resend, property_carbon_footprints_providers_ignore, property_carbon_footprints_providers_unignore, property_carbon_footprints_providers_remove, property_carbon_footprints_employees, property_carbon_footprints_employees_show, property_carbon_footprints_employees_resend, property_carbon_footprints_employees_ignore, property_carbon_footprints_employees_unignore, property_carbon_footprints_mitigation_plan, mitigation_actions_register, mitigation_actions_edit, mitigation_actions_remove, mitigation_actions_import, property_carbon_footprints_results, property_carbon_footprints_declare_denominators, property_carbon_footprints_emissions_calculate]
updated: "2026-11-14 00:19"
---

# Asistente de la huella

El asistente guía el cálculo de la huella de una propiedad en ocho pasos. Cada paso se guarda al pulsar **Siguiente**; con **Anterior** vuelves atrás. Una vez completada, la huella no se puede modificar hasta que la reabres (verás **La huella está completada. Reábrela para modificarla.**).

> **Dónde está:** menú **Propiedades › Huellas de Carbono**, botón **Editar** de una huella.

## Cómo se rellenan las actividades

En los alcances, cada fuente de emisión tiene una tabla de actividades. Pulsa **Añadir** para cada línea, elige el elemento (combustible, transporte, gas, comercializadora…), escribe la **Cantidad** y la plataforma calcula las **Emisiones (kg CO₂eq)**. Si no hay ninguna verás **No hay actividades registradas.**

Si una fuente no aplica, marca **Excluir esta fuente de emisión** e indica el **Motivo de la exclusión** (**No aplica**, **Sin datos disponibles**, **No significativa**, **Fuera de alcance** u **Otro**) y, si quieres, un **Comentario (opcional)**.

## 1. Alcance 1

Emisiones directas de la propiedad, en tres fuentes:

- **Instalaciones**: combustibles quemados (elige el **Combustible** y la **Cantidad**).
- **Desplazamientos en vehículos**: vehículos propios (elige el **Transporte**; según el tipo, indica la cantidad o el **Peso** y la **Distancia (km)**).
- **Fugas de equipos de refrigeración**: kilos de **Gas refrigerante** recargados.

Acciones de la cabecera:

- **Cargar consumos de Bioscore**: si la propiedad tiene identificador de Bioscore, sustituye las actividades de este alcance por los consumos registrados en Bioscore (se pierden las filas introducidas a mano). Verás cuántas actividades se han cargado y, si alguna no se pudo convertir, **Consumos de Bioscore omitidos**.
- **Huella ya calculada** / **Introducir datos por actividades**: cambia el modo de introducción. En modo «ya calculada» escribes por cada fuente el **Concepto** y las **Emisiones (CO₂eq)** que ya tienes calculadas. Al cambiar de modo se eliminan las actividades de los alcances 1 y 2.

## 2. Alcance 2

**Consumo eléctrico**: para cada línea elige la **Comercializadora**, la **Cantidad** y si la electricidad tiene garantía de origen (**¿Dispone de garantía de origen?**). Tiene las mismas acciones de cabecera que el alcance 1.

## 3. Materialidad

La evaluación de materialidad mide, con un breve cuestionario, qué categorías del alcance 3 son relevantes para tu organización y te recomienda cuáles calcular.

1. Elige el **Sector** (o **Sin sector específico**). Si ya evaluaste otro año, puedes marcar **Partir de la evaluación de** ese año.
2. Pulsa **Comenzar cuestionario**.
3. Responde categoría a categoría, en la vista **Acordeón** o **Matriz**. Las respuestas se guardan solas. **Marcar toda la categoría como Bajo/No** responde de golpe una categoría poco relevante.
4. Pulsa **Completar evaluación**. Verás para cada categoría su **Relevancia**, el **Umbral** y el **Veredicto**.

También puedes **Reabrir evaluación**, **Reiniciar cuestionario** (se pierden las respuestas) o **Continuar sin evaluar**.

## 4. Alcance 3

Emisiones de tu cadena de valor, en **Aguas arriba** y **Aguas abajo**, con una sección por categoría (bienes y servicios adquiridos, bienes de capital, residuos, viajes de negocios…). La categoría **Emisiones aguas arriba de combustibles y energía** se calcula sola a partir de los alcances 1 y 2.

- Si completaste la materialidad, cada categoría muestra su veredicto (**Material** o **No material**) y las no materiales llegan excluidas. Si incluyes una no material o excluyes una material, verás **Estás anulando la recomendación de materialidad** y tendrás que indicar el **Motivo de la anulación**.
- Si no quieres calcular este alcance, marca **No calcular el alcance 3** e indica el motivo. En ese caso desaparecen los pasos Proveedores y Empleados.

## 5. Proveedores

Registra los datos que te han comunicado tus proveedores o pídeselos por correo. Sus emisiones se suman en la categoría del alcance 3 que indiques.

1. Pulsa **Añadir proveedor**.
2. Elige el **Proveedor** (de tu lista de **Proveedores**), el **Modo** (**Manual**: tú introduces los datos; **Solicitado al proveedor**: le llega un correo con un enlace) y las **Categorías** que aporta.
3. Pulsa **Registrar**. Verás **Aportación de proveedor registrada**.

La tabla muestra **Proveedor**, **Email de contacto**, **Modo**, **Categorías**, **Estado** (**Pendiente**, **Contestado** o **Descartado**) y **Emisiones (kg CO₂eq)**. Por fila puedes ver **Respuestas**, **Reenviar** la solicitud, **Descartar** o **Reactivar**, **Editar** y **Eliminar**. Puedes continuar con solicitudes pendientes: el proveedor podrá contestar hasta que completes la huella.

## 6. Empleados

Pide a tus empleados sus desplazamientos al trabajo y sus viajes de negocio. Sus respuestas se extrapolan al total de plantilla y se suman en las categorías 6 y 7 del alcance 3.

1. Indica el **Total de empleados** y el **Nivel de confianza**.
2. Marca los empleados a los que quieres invitar (si no tienes ninguno, pulsa **Registrar empleado**).
3. Pulsa **Siguiente**. Cada empleado recibe un correo con su encuesta.

En **Contribuciones** ves las **Pendientes**, las **Contestadas**, el **% contestado**, el **Margen de error** y las **Emisiones extrapoladas (kg CO₂eq)**. Por empleado puedes ver sus **Respuestas**, **Reenviar** la encuesta y **Descartar** o **Reactivar** su respuesta.

## 7. Medidas de Reducción y Mitigación

Define, si quieres, tu plan para reducir emisiones.

1. Pulsa **Añadir medida al plan**.
2. En **Medida**, escoge una de las sugerencias o deja el campo vacío para crear una medida manual con su **Nombre** y **Descripción**. Indica el **Año objetivo**.
3. Pulsa **Registrar**. Verás **Medida añadida al plan**. Después pulsa **Editar** en la medida para indicar sus efectos sobre cada fuente de emisión.

La tabla muestra **Medida a realizar**, **Origen**, **Año base**, **Año objetivo**, **Efectos** y **Reducción esperada (kg CO₂eq)**, y debajo las **Emisiones esperadas** año a año. **Importar acciones previas** sustituye el plan por las medidas de la huella anterior de la misma propiedad.

Al pulsar **Siguiente** en este paso, la huella queda completada.

## 8. Resultados

- **Totales**: **CO₂ equivalente total (kg)**, **CO₂ (kg)**, **CH₄ (kg)**, **N₂O (kg)**, el alcance 2 por **método de mercado** y por **método de localización**, y el **Total · método de localización**.
- Gráficos de **Emisiones por fuente** y **Emisiones por alcance**.
- **Fuentes de emisión excluidas**, con **Motivo**, **Comentario** y **Origen**.
- **Matriz alcance × fuente**.
- **Datos de actividad del año**: habitaciones ocupadas, pernoctaciones, superficie, cubiertos, plantilla media, facturación y clientes. Pulsa **Guardar datos de actividad** para calcular los indicadores de intensidad.
- El estado del informe y el certificado de huella, explicados en [Huellas de Carbono](huellas-de-carbono.md).

## Relacionado

- [Huellas de Carbono](huellas-de-carbono.md)
- [Proveedores](../proveedores/proveedores.md)
- [Empleados](../administracion/empleados.md)
- [Indicadores](indicadores.md)
