---
title: "Combustibles de vehículos"
summary: "Empareja los combustibles de vehículos del catálogo de Bioscore con los de la plataforma."
profile: admin
menu: "Administración › Bioscore › Combustibles de vehículos"
routes: [admin_bioscore_transport_fuels, admin_bioscore_catalog_items_edit, admin_bioscore_catalogs_sync]
updated: "2026-11-14 00:19"
---

# Combustibles de vehículos

Las propiedades vinculadas a Bioscore pueden traer de allí sus consumos para las huellas de carbono. Para que esos consumos se calculen bien, cada elemento del catálogo de **Combustibles de vehículos** de Bioscore tiene que estar emparejado con su equivalente en la plataforma (un medio de transporte de **Maestros › Transporte › Medios de transporte**). Aquí revisas y corriges ese emparejamiento.

> **Dónde está:** menú **Administración › Bioscore › Combustibles de vehículos**.

## Qué ves en la pantalla

Si quedan elementos sin emparejar, arriba verás **Elementos pendientes de mapear** con su número.

| Columna | Qué significa |
|---|---|
| **Elemento de Bioscore** | Nombre del elemento en Bioscore. Si ya no existe allí verás **Retirado**. |
| **Unidad en Bioscore** | Unidad en la que Bioscore da los consumos. |
| **Estado** | **Sin mapear**, **Mapeado automáticamente**, **Mapeado manualmente** o **Ignorado**. |
| **Destino en la plataforma** | Elemento de la plataforma con el que está emparejado. |
| **Última vez visto** | Última sincronización en la que apareció. |

Pulsa **Filtrar** para buscar por **Nombre**, **Estado** o **Retirado**. Si el catálogo está vacío verás **No hay elementos en este catálogo. Lanza una sincronización.**

## Emparejar un elemento

1. Pulsa **Editar** en la fila. Verás el **Elemento de Bioscore**, su **Unidad en Bioscore** y su **Estado**.
2. Elige el **Destino en la plataforma** (**Sin mapear** para dejarlo sin emparejar), o marca **Ignorar este elemento** si sus consumos no deben cargarse.
3. Pulsa **Guardar** o **Guardar y salir**. Verás **Equivalencia de Bioscore actualizada con éxito**.

## Ignorar o quitar el emparejamiento de varios a la vez

Marca los elementos, elige **Ignorar** o **Quitar mapeo** en **Acción a realizar** y pulsa **Realizar**.

## Sincronizar con Bioscore

Pulsa **Sincronizar ahora** y confirma. Se pondrá en cola la descarga de todos los catálogos de Bioscore y verás **Sincronización de maestros de Bioscore encolada**. La sincronización también se hace sola cada día.

## Relacionado

- [Medios de transporte](../../maestros/transporte/medios-de-transporte.md)
