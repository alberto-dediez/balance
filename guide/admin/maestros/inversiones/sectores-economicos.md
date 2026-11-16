---
title: "Sectores económicos"
summary: "Catálogo de sectores económicos con sus factores de emisión por año y grupo de coeficientes."
profile: admin
menu: "Maestros › Inversiones › Sectores económicos"
routes: [admin_economic_sectors, admin_economic_sectors_register, admin_economic_sectors_edit, admin_economic_sectors_remove, admin_economic_sectors_emission_factors, import, export]
updated: "2026-11-14 00:19"
---

# Sectores económicos

Catálogo de sectores económicos con factores de emisión por importe invertido, para calcular la categoría **Inversiones** del alcance 3.

> **Dónde está:** menú **Maestros › Inversiones › Sectores económicos**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Código CNAE/NACE** | Código oficial del sector, si lo tiene. |
| **Nombre** | Nombre del elemento. |
| **Divisa** | Moneda en la que se expresa el importe. |
| **Fecha de registro** / **Fecha de actualización** | Cuándo se creó y cuándo se modificó por última vez. |

Pulsa **Filtrar** para buscar por **Nombre** o **Código CNAE/NACE**.

## Registrar

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
| **Nombre** | Sí | Nombre con el que aparecerá en los formularios. |
   | **Código CNAE/NACE** | No | Código CNAE 2009 o NACE Rev. 2. Debe ser único. |
   | **Divisa** | Sí | Moneda del importe invertido. |

3. Pulsa **Registrar**. Verás **Sector económico creado correctamente:** seguido del nombre.

## Editar y fijar sus factores de emisión

1. Pulsa **Editar** en la fila.
2. Cambia los datos del formulario de alta si lo necesitas.
3. En **Factores de emisión**, elige arriba a la derecha el grupo de coeficientes (se muestra primero el grupo por defecto). Cada fila es un año, con **Año**, **Kg CO2eq**, **Kg CO2**, **g CH4**, **g N2O** y **Fuente** (de dónde sale el dato). Pulsa **Añadir** para añadir un año.
4. Pulsa **Guardar** o **Guardar y salir**. Verás **Sector económico actualizado con éxito**.

Los textos se guardan en el idioma en que tienes la plataforma. Para traducirlos, cambia de idioma en la barra superior y vuelve a editar.

## Editar los factores de todos a la vez

Pulsa **Factores de emisión** en el listado para ver en una sola tabla los factores de un año y un grupo de coeficientes para todos los elementos.

- Muévete entre años con **Año anterior** y **Año siguiente**, y cambia de grupo con el selector junto al año.
- Corrige los valores en la tabla y guarda. Verás **Factores de emisión actualizados con éxito**.
- Para preparar un año nuevo, escribe el **Año destino** y pulsa **Copiar factores**: se copian los factores del año que estás viendo (**Año de origen**). Verás **Nuevo año creado con éxito**. Si ese año ya tiene datos verás **El año destino ya tiene factores de emisión.**

## Importar y exportar

Usa **Importar** para cargar o actualizar elementos desde una hoja de cálculo, y **Exportar** para descargarlos. El proceso se explica en [Importar y exportar](../../general/importar-y-exportar.md).

## Eliminar

Pulsa **Eliminar** en la fila y confirma. Verás **Se ha eliminado el sector económico** seguido del nombre.

## Relacionado

- [Grupos de coeficientes](../grupos-de-coeficientes.md)

