---
title: Bienes de capital
date: "2026-10-15 14:50"
category: feature
summary: Nuevo catálogo de bienes de capital (reformas y obra nueva, mobiliario y equipamiento, vehículos y maquinaria) con su factor de emisión por año, y una unidad nueva, el metro cuadrado.
plan: 2026-10-15-bienes-de-capital
manual: bienes-de-capital.pdf
techManual: bienes-de-capital.tech.md
published: true
---

## Qué cambia

La plataforma estrena un nuevo maestro: **Bienes de capital**. Es la lista de los bienes duraderos
que adquiere una organización —una reforma de oficinas, una obra nueva, mobiliario, equipos, un
vehículo o una máquina— con el factor de emisión de cada uno, uno por año y por grupo de
coeficientes, igual que los materiales o los bienes y servicios comprados.

Cada elemento lleva su propia unidad según cómo esté expresado su factor: por euro invertido, por
metro cuadrado reformado, por unidad o por kilogramo. Para la superficie se añade a toda la
plataforma la unidad **Metro cuadrado**, que aparece en todos los desplegables de Unidad.

Por ahora el catálogo solo se prepara y se mantiene: las huellas de eventos, propiedades y
empresas, y sus informes, no cambian. Los bienes de capital se incorporarán al cálculo más adelante.

## A quién afecta

- **Administradores:** pueden consultar, registrar, editar, eliminar, importar y exportar bienes de
  capital, y gestionar sus factores de emisión.
- **Propietarios y Usuarios:** no ven la entrada en el menú ni tienen acceso a estas pantallas.

Es un catálogo común a toda la plataforma: lo que registra un Administrador sirve para todas las
cuentas.

## Dónde se encuentra

En el menú lateral, **Materiales → Bienes de capital**, debajo de «Bienes y servicios comprados».
El listado permite filtrar por nombre y categoría y ofrece los botones **Factores de emisión**,
**Importar**, **Exportar** y **Registrar**. Desde **Factores de emisión** se rellenan en bloque los
factores de todos los elementos para un año y grupo, y se pueden copiar de un año a otro.

## Qué conviene saber

- Las categorías disponibles son Reformas y obra nueva, Mobiliario y equipamiento, Vehículos y
  maquinaria, y Otros.
- La unidad propuesta es **Euro**. Si el factor es por superficie, elige **Metro cuadrado**; si
  dispones de ambos factores para una reforma, registra dos elementos, uno con cada unidad.
- El metro cuadrado solo se combina con otras cantidades en metros cuadrados: no se convierte a
  kilogramos, euros ni ninguna otra unidad.
- Si se cambia la unidad de un elemento, sus factores pasan a la nueva unidad con los mismos
  valores: la plataforma no convierte los números.
- Cada elemento admite un solo factor por año y grupo de coeficientes.
- En la importación, cada fila es un factor. Una fila que no indique un bien de capital existente
  crea siempre un elemento nuevo, aunque ya exista otro con el mismo nombre; si no trae categoría o
  unidad, se asignan Otros y Euro.
- La exportación genera una fila por factor; un elemento sin factores sale en una sola fila.
- Eliminar un elemento no se puede deshacer.
