---
title: Huella de carbono de propiedades y empresas
date: "2026-09-16 18:10"
category: feature
summary: Calcula la huella de carbono anual de una propiedad o de toda una empresa, con alcance 1, alcance 2 y resultados en pantalla.
plan: 2026-09-16-property-company-carbon-footprint
manual: property-company-carbon-footprint.pdf
techManual: property-company-carbon-footprint.tech.md
published: true
---

## Qué cambia

Hasta ahora la plataforma solo medía la huella de carbono de **eventos**. Se añade la huella de la
**organización**, que no mide un acto puntual sino la actividad de un año natural completo, y que
puedes calcular para dos sujetos: una **propiedad** concreta (un hotel, un edificio, una
instalación) o una **empresa**, entendida como el conjunto de las propiedades que tengas asignadas
a ella más lo que no corresponda a ninguna en particular.

El cálculo se organiza en los dos alcances habituales. El **Alcance 1** recoge las emisiones
directas, con tres fuentes: **Instalaciones fijas**, **Desplazamientos en vehículos** y **Fugas de
equipos de refrigeración**. El **Alcance 2** recoge la **Electricidad edificios**. El asistente se
maneja igual que el de la huella de un evento: una tabla por fuente, filas que puedes añadir y
borrar, cálculo de emisiones en vivo mientras rellenas, e interruptor para **excluir** una fuente
que no te aplique dejando constancia del motivo.

## Dónde se encuentra

Hay dos listados nuevos en el menú lateral, **Propiedades › Huellas de Carbono** y **Empresas ›
Huellas de Carbono**, cada uno justo debajo del listado de su sujeto. También puedes arrancar el
cálculo desde el icono **Cálculo de Huella de Carbono** de cada fila del listado de propiedades o
de empresas. El alta pide solo dos datos, la propiedad o empresa y el **Año de cálculo**, y a
partir de ahí el asistente tiene tres pasos: **Alcance 1**, **Alcance 2** y **Resultados**.

En una huella de empresa, cada tabla aparece repetida una vez por propiedad más un bloque **Sin
asignar a propiedad**, de modo que los resultados muestran, además de los totales, el reparto por
propiedad.

## Qué conviene saber

El sujeto y el año quedan fijos al crear la huella, y solo puede existir una huella por sujeto y
año; si te equivocas, hay que eliminarla y crear otra. Al excluir una fuente se borran las
actividades que tuviera registradas, y no se recuperan al volver a incluirla. La pantalla de
**Resultados** se abre cuando has guardado los dos alcances y se recalcula cada vez que cambias
una cifra.

Es una primera versión: todavía no hay informe en PDF, recomendaciones, acciones de mitigación,
importación de consumos desde hoja de cálculo ni cierre formal de la huella. El acceso es de
**Propietario**; el Administrador trabaja impersonando la cuenta del cliente.
