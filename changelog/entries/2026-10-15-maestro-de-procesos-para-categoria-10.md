---
title: Procesos
date: "2026-10-15 19:01"
category: feature
summary: Nuevo catálogo de procesos que recoge las emisiones de las transformaciones que terceros aplican a los productos intermedios vendidos, con su factor de emisión por año, dentro del apartado Productos vendidos del menú.
plan: 2026-10-15-maestro-de-procesos-para-categoria-10
manual: maestro-de-procesos-para-categoria-10.pdf
techManual: maestro-de-procesos-para-categoria-10.tech.md
published: true
---

## Qué cambia

La plataforma estrena un nuevo maestro: **Procesos**. Es la lista de transformaciones que otras
empresas aplican a los productos intermedios que vende una organización —el mecanizado de una
pieza, el envasado de un producto— junto con las emisiones que genera cada transformación.

Cada proceso tiene un **Nombre**, una **Unidad** (por defecto, cada unidad de producto vendido) y
un factor de emisión por año y por grupo de coeficientes, igual que los materiales o los perfiles
de uso.

Por ahora el catálogo solo se prepara y se mantiene: las huellas de eventos, propiedades y
empresas, y sus informes, no cambian. Los procesos se incorporarán más adelante al cálculo del
alcance 3, en la categoría «Procesamiento de los productos vendidos».

## A quién afecta

- **Administradores:** pueden consultar, registrar, editar, eliminar, importar y exportar
  procesos, y gestionar sus factores de emisión.
- **Propietarios y Usuarios:** no ven la entrada en el menú ni tienen acceso a estas pantallas.

Es un catálogo común a toda la plataforma: lo que registra un Administrador sirve para todas las
cuentas.

## Dónde se encuentra

En el menú lateral, dentro del apartado **Productos vendidos**, hay una entrada nueva justo encima
de «Perfiles de uso»: **Productos vendidos → Procesos**. El listado permite filtrar por nombre y
ofrece los botones **Factores de emisión**, **Importar**, **Exportar** y **Registrar**. Desde
**Factores de emisión** se rellenan en bloque los factores de todos los procesos para un año y
grupo, y se pueden copiar de un año a otro.

## Qué conviene saber

- La unidad propuesta es **Unidad**. Si se cambia la unidad de un proceso, sus factores pasan a la
  nueva unidad con los mismos valores: la plataforma no convierte los números.
- Cada proceso admite un solo factor por año y grupo de coeficientes.
- En la importación, cada fila es un factor. Una fila que no indique un proceso existente crea
  siempre un proceso nuevo, aunque ya exista otro con el mismo nombre; si no trae unidad, se asigna
  Unidad.
- La exportación genera una fila por factor; un proceso sin factores sale en una sola fila.
- Eliminar un proceso no se puede deshacer.
