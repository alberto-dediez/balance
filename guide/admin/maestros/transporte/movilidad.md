---
title: "Movilidad"
summary: "Catálogo de movilidad con sus factores de emisión por año y grupo de coeficientes."
profile: admin
menu: "Maestros › Transporte › Movilidad"
routes: [admin_mobilities, admin_mobilities_register, admin_mobilities_edit, admin_mobilities_remove, admin_mobilities_emission_factors, import, export]
updated: "2026-11-14 00:19"
---

# Movilidad

Catálogo de medios de desplazamiento de personas (coche, tren, avión…) con sus factores de emisión, para calcular la movilidad de asistentes, empleados y huéspedes.

> **Dónde está:** menú **Maestros › Transporte › Movilidad**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Nombre** | Nombre del elemento. |
| **Unidad** | Unidad en la que se miden los consumos. |
| **Modo de cálculo de rutas** | Cómo se calculan las distancias automáticas: **Carretera**, **Transporte público**, **Bicicleta**, **A pie** o **Avión**. |
| **Base del factor de emisión** | **Por pasajero (pax·km)** o **Por vehículo (veh·km)**. |
| **Fecha de registro** / **Fecha de actualización** | Cuándo se creó y cuándo se modificó por última vez. |

Pulsa **Filtrar** para buscar por **Nombre**.

## Registrar

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
| **Nombre** | Sí | Nombre con el que aparecerá en los formularios. |
   | **Unidad** | Sí | Unidad en la que se introducen los consumos. |
   | **Modo de cálculo de rutas** | No | Si lo dejas en **Sin cálculo automático**, no se calcularán distancias automáticas para este medio. |
   | **Base del factor de emisión** | Sí | Por pasajero: el factor ya es por persona y los ocupantes no reducen las emisiones. Por vehículo: el factor es del vehículo completo y se reparte entre sus ocupantes. |

3. Pulsa **Registrar**. Verás **Movilidad creada correctamente:** seguido del nombre.

## Editar y fijar sus factores de emisión

1. Pulsa **Editar** en la fila.
2. Cambia los datos del formulario de alta si lo necesitas.
3. En **Factores de emisión**, elige arriba a la derecha el grupo de coeficientes (se muestra primero el grupo por defecto). Cada fila es un año, con **Año**, **Kg CO2eq**, **Kg CO2**, **g CH4**, **g N2O** y **Fuente** (de dónde sale el dato). Pulsa **Añadir** para añadir un año.
4. Pulsa **Guardar** o **Guardar y salir**. Verás **Movilidad actualizada con éxito**.

Los textos se guardan en el idioma en que tienes la plataforma. Para traducirlos, cambia de idioma en la barra superior y vuelve a editar.

## Editar los factores de todos a la vez

Pulsa **Factores de emisión** en el listado para ver en una sola tabla los factores de un año y un grupo de coeficientes para todos los elementos.

- Muévete entre años con **Año anterior** y **Año siguiente**, y cambia de grupo con el selector junto al año.
- Corrige los valores en la tabla y guarda. Verás **Factores de emisión actualizados con éxito**.
- Para preparar un año nuevo, escribe el **Año destino** y pulsa **Copiar factores**: se copian los factores del año que estás viendo (**Año de origen**). Verás **Nuevo año creado con éxito**. Si ese año ya tiene datos verás **El año destino ya tiene factores de emisión.**

## Importar y exportar

Usa **Importar** para cargar o actualizar elementos desde una hoja de cálculo, y **Exportar** para descargarlos. El proceso se explica en [Importar y exportar](../../general/importar-y-exportar.md).

## Eliminar

Pulsa **Eliminar** en la fila y confirma. Verás **Se ha eliminado la movilidad** seguido del nombre.

## Relacionado

- [Grupos de coeficientes](../grupos-de-coeficientes.md)
- [Pernoctaciones](pernoctaciones.md)
