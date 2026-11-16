---
title: "Combustible de maquinaria"
summary: "Catálogo de combustible de maquinaria con sus factores de emisión por año y grupo de coeficientes."
profile: admin
menu: "Maestros › Transporte › Combustible de maquinaria"
routes: [admin_machinery_fuels, admin_machinery_fuels_register, admin_machinery_fuels_edit, admin_machinery_fuels_remove, admin_machinery_fuels_emission_factors, import, export]
updated: "2026-11-14 00:19"
---

# Combustible de maquinaria

Catálogo de combustibles usados por maquinaria con sus factores de emisión, para calcular las emisiones del funcionamiento de maquinaria.

> **Dónde está:** menú **Maestros › Transporte › Combustible de maquinaria**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Nombre** | Nombre del elemento. |
| **Tipo de maquinaria** | **Maquinaria agrícola**, **Maquinaria forestal** o **Maquinaria comercial, institucional e industrial**. |
| **Unidad** | Unidad en la que se miden los consumos. |
| **Fecha de registro** / **Fecha de actualización** | Cuándo se creó y cuándo se modificó por última vez. |

Pulsa **Filtrar** para buscar por **Nombre** o **Tipo de maquinaria**.

## Registrar

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
| **Nombre** | Sí | Nombre con el que aparecerá en los formularios. |
   | **Unidad** | Sí | Unidad en la que se introducen los consumos. |
   | **Tipo de maquinaria** | Sí | Tipo de máquina que lo consume. |

3. Pulsa **Registrar**. Verás **Combustible de maquinaria creado correctamente:** seguido del nombre.

## Editar y fijar sus factores de emisión

1. Pulsa **Editar** en la fila.
2. Cambia los datos del formulario de alta si lo necesitas.
3. En **Factores de emisión**, elige arriba a la derecha el grupo de coeficientes (se muestra primero el grupo por defecto). Cada año tiene dos juegos de valores: el **Factor** propio y el **Well-to-tank** (las emisiones de producir y transportar la energía hasta que se usa), cada uno con **Kg CO2eq**, **Kg CO2**, **g CH4**, **g N2O** y **Fuente**. Pulsa **Añadir** para añadir un año.
4. Pulsa **Guardar** o **Guardar y salir**. Verás **Combustible de maquinaria actualizado con éxito**.

Los textos se guardan en el idioma en que tienes la plataforma. Para traducirlos, cambia de idioma en la barra superior y vuelve a editar.

## Editar los factores de todos a la vez

Pulsa **Factores de emisión** en el listado para ver en una sola tabla los factores de un año y un grupo de coeficientes para todos los elementos.

- Muévete entre años con **Año anterior** y **Año siguiente**, y cambia de grupo con el selector junto al año.
- Corrige los valores en la tabla y guarda. Verás **Factores de emisión actualizados con éxito**.
- Para preparar un año nuevo, escribe el **Año destino** y pulsa **Copiar factores**: se copian los factores del año que estás viendo (**Año de origen**). Verás **Nuevo año creado con éxito**. Si ese año ya tiene datos verás **El año destino ya tiene factores de emisión.**

## Importar y exportar

Usa **Importar** para cargar o actualizar elementos desde una hoja de cálculo, y **Exportar** para descargarlos. El proceso se explica en [Importar y exportar](../../general/importar-y-exportar.md).

## Eliminar

Pulsa **Eliminar** en la fila y confirma. Verás **Se ha eliminado el combustible de maquinaria** seguido del nombre.

## Relacionado

- [Grupos de coeficientes](../grupos-de-coeficientes.md)

