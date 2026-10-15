---
title: Perfiles de uso
date: "2026-10-15 17:00"
category: feature
summary: Nuevo catálogo de perfiles de uso que recoge las emisiones del uso de los productos vendidos durante toda su vida útil, con su factor de emisión por año, en el nuevo apartado Productos vendidos del menú.
plan: 2026-10-15-maestro-de-perfiles-de-uso-para-categoria-11
manual: maestro-de-perfiles-de-uso-para-categoria-11.pdf
techManual: maestro-de-perfiles-de-uso-para-categoria-11.tech.md
published: true
---

## Qué cambia

La plataforma estrena un nuevo maestro: **Perfiles de uso**. Es la lista de formas de uso de los
productos que vende una organización —una lavadora, un frigorífico, un equipo electrónico— con las
emisiones que genera ese uso a lo largo de toda su vida útil.

Cada perfil tiene un **Nombre**, una **Unidad** (por defecto, la unidad vendida) y un factor de
emisión por año y por grupo de coeficientes, igual que los materiales o los bienes de capital.

Por ahora el catálogo solo se prepara y se mantiene: las huellas de eventos, propiedades y
empresas, y sus informes, no cambian. Los perfiles de uso se incorporarán más adelante al cálculo
del alcance 3, en la categoría «Uso de productos vendidos».

## A quién afecta

- **Administradores:** pueden consultar, registrar, editar, eliminar, importar y exportar perfiles
  de uso, y gestionar sus factores de emisión.
- **Propietarios y Usuarios:** no ven la entrada en el menú ni tienen acceso a estas pantallas.

Es un catálogo común a toda la plataforma: lo que registra un Administrador sirve para todas las
cuentas.

## Dónde se encuentra

En el menú lateral hay un apartado nuevo, **Productos vendidos**, entre «Materiales» e
«Instalaciones». Dentro está la entrada **Productos vendidos → Perfiles de uso**. El listado permite
filtrar por nombre y ofrece los botones **Factores de emisión**, **Importar**, **Exportar** y
**Registrar**. Desde **Factores de emisión** se rellenan en bloque los factores de todos los
perfiles para un año y grupo, y se pueden copiar de un año a otro.

## Qué conviene saber

- El factor recoge las emisiones de **toda la vida útil** del producto por cada unidad: no se
  reparte entre años ni hay campos de vida útil o consumo anual.
- La unidad propuesta es **Unidad**. Si se cambia la unidad de un perfil, sus factores pasan a la
  nueva unidad con los mismos valores: la plataforma no convierte los números.
- Cada perfil admite un solo factor por año y grupo de coeficientes.
- En la importación, cada fila es un factor. Una fila que no indique un perfil existente crea
  siempre un perfil nuevo, aunque ya exista otro con el mismo nombre; si no trae unidad, se asigna
  Unidad.
- La exportación genera una fila por factor; un perfil sin factores sale en una sola fila.
- Eliminar un perfil no se puede deshacer.
