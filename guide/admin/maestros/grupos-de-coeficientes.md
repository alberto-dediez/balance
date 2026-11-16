---
title: "Grupos de coeficientes"
summary: "Los conjuntos de factores de emisión y potenciales de calentamiento que usan los cálculos de cada cuenta."
profile: admin
menu: "Maestros › Grupos de coeficientes"
routes: [admin_emission_factor_groups, admin_emission_factor_groups_register, admin_emission_factor_groups_edit, admin_emission_factor_groups_remove, admin_emission_factor_groups_set_default]
updated: "2026-11-14 00:19"
---

# Grupos de coeficientes

Un grupo de coeficientes es un juego completo de factores de emisión. Cada maestro (materiales, combustibles, transportes…) guarda sus factores por grupo y por año. Así puedes tener, por ejemplo, un grupo con fuentes nacionales y otro con fuentes internacionales, y asignar a cada cuenta el que corresponda.

> **Dónde está:** menú **Maestros › Grupos de coeficientes**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Nombre** | Nombre del grupo. |
| **GWP CO2**, **GWP CH4**, **GWP N2O** | Potencial de calentamiento global de cada gas en este grupo. |
| **Fecha de registro** | Cuándo se creó. |
| **Por defecto** | Marca el grupo que se usa cuando una cuenta no tiene otro asignado. |

Pulsa **Filtrar** para buscar por **Nombre**.

## Registrar un grupo

1. Pulsa **Registrar**.
2. Escribe el **Nombre** y los valores de **GWP CO2**, **GWP CH4** y **GWP N2O** (todos obligatorios).
3. Pulsa **Registrar**. Verás **Grupo de coeficientes creado correctamente** con su nombre.

## Marcar un grupo como por defecto

Pulsa **Marcar como por defecto** en la fila. Verás **Se ha marcado** seguido del nombre **como grupo de coeficientes por defecto**.

## Editar un grupo

Pulsa **Editar**, cambia el nombre o los valores y pulsa **Guardar** o **Guardar y salir**. Verás **Grupo de coeficientes actualizado con éxito**. Los textos se guardan en el idioma en que tienes la plataforma.

## Eliminar un grupo

Pulsa **Eliminar** y confirma. Verás **Se ha eliminado el grupo de coeficientes** con su nombre.

## Conviene saber

- El grupo de cada cuenta se elige en el campo **Grupo de coeficientes por defecto** de la ficha de la cuenta.

## Relacionado

- [Cuentas](../administracion/organizacion/cuentas.md)
- [Materiales](materiales/materiales.md)
