---
title: Sectores económicos
date: "2026-10-16 11:01"
category: feature
summary: Nuevo catálogo de sectores económicos con las emisiones por cada euro (u otra divisa) de las empresas en las que se invierte, con su código CNAE/NACE y su factor de emisión por año, en el nuevo apartado Inversiones del menú.
plan: 2026-10-15-mestro-de-sectores-para-empresas-participadas
manual: mestro-de-sectores-para-empresas-participadas.pdf
techManual: mestro-de-sectores-para-empresas-participadas.tech.md
published: true
---

## Qué cambia

La plataforma estrena un nuevo maestro: **Sectores económicos**. Es la lista de actividades
económicas —fabricación de productos metálicos, programación informática, comercio al por mayor…—
con las emisiones que genera cada euro (u otra divisa) de una empresa de ese sector.

Cada sector tiene un **Nombre**, una **Divisa** (por defecto, el euro), un **Código CNAE/NACE**
opcional para identificarlo con la clasificación oficial de actividades, y un factor de emisión por
año y por grupo de coeficientes, igual que los demás maestros.

Por ahora el catálogo solo se prepara y se mantiene: las huellas de eventos, propiedades y
empresas, y sus informes, no cambian. Los sectores económicos se incorporarán más adelante al
cálculo del alcance 3, en la categoría «Inversiones». No hay que confundirlos con el maestro
**Materialidad → Sectores**, que es otro catálogo distinto.

## A quién afecta

- **Administradores:** pueden consultar, registrar, editar, eliminar, importar y exportar sectores
  económicos, y gestionar sus factores de emisión.
- **Propietarios y Usuarios:** no ven la entrada en el menú ni tienen acceso a estas pantallas.

Es un catálogo común a toda la plataforma: lo que registra un Administrador sirve para todas las
cuentas.

## Dónde se encuentra

En el menú lateral hay un apartado nuevo, **Inversiones**, entre «Productos vendidos» e
«Instalaciones». Dentro está la entrada **Inversiones → Sectores económicos**. El listado permite
filtrar por nombre y por código, y ofrece los botones **Factores de emisión**, **Importar**,
**Exportar** y **Registrar**. Desde **Factores de emisión** se rellenan en bloque los factores de
todos los sectores para un año y grupo, y se pueden copiar de un año a otro.

## Qué conviene saber

- La **Divisa** solo admite monedas. Si los factores están en dólares, basta con elegir esa divisa:
  no hay que pasarlos a euros. Cambiar la divisa de un sector no convierte sus factores.
- Dos sectores no pueden compartir el mismo **Código CNAE/NACE**; el de un sector eliminado sí
  puede reutilizarse. El código se compara tal cual: 6201 y 62.01 son códigos distintos.
- En la importación, cada fila es un factor. Si la fila trae un código que ya tiene otro sector, el
  factor se añade a ese sector sin crear otro ni cambiar su nombre; si no, se crea un sector nuevo.
  Si no se indica divisa, se asigna Euro; si se indica algo que no es una divisa, la fila no se carga.
- Cada sector admite un solo factor por año y grupo de coeficientes.
- Eliminar un sector no se puede deshacer.
