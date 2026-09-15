---
title: Gases refrigerantes
date: 2026-09-15
category: feature
summary: Nuevo catálogo común de gases de climatización con sus factores de emisión por año, editables en masa, importables y exportables.
plan: 2026-09-15-refrigerant-gases
manual: refrigerant-gases.pdf
techManual: refrigerant-gases.tech.md
published: true
---

## Qué cambia

Las fugas de gas en los equipos de climatización y refrigeración son una partida habitual de la
huella de carbono de un evento o de una propiedad, y hasta ahora no había dónde registrarlas. Se
añade el catálogo **Gases refrigerantes**: se dan de alta los gases que hagan falta —R-410A,
R-134a, R-32…— y, para cada uno, su factor de emisión por año y por grupo de coeficientes. La
unidad es siempre el kilogramo de gas fugado, no se elige ni se puede cambiar, así que todos los
gases del catálogo son comparables entre sí.

## A quién afecta

Es un catálogo común a toda la plataforma, de mantenimiento interno: los gases no pertenecen a
ninguna cuenta y quedan disponibles para todas. Solo entran los administradores y
superadministradores; los propietarios y usuarios no lo ven y reciben un aviso de permiso denegado
si intentan abrir sus pantallas.

## Dónde se encuentra

En el menú lateral, en **Instalaciones › Gases refrigerantes**, entre **Factores GdO** y
**Residuos**. El listado permite filtrar por nombre y ordenar, y desde él salen los cuatro caminos
de trabajo: **Registrar** un gas nuevo, **Factores de emisión** para editar de una vez los valores
de todos los gases de un año y grupo —incluida la copia de los factores de un año a otro que
todavía esté vacío—, **Importar** desde hoja de cálculo e **Exportar** el catálogo, con una fila
por factor. También se puede eliminar un gas desde su fila o en bloque.

## Qué conviene saber

Solo puede haber un factor por gas, año y grupo de coeficientes, y no se admiten años posteriores
al actual. En la edición en masa, dejar una fila totalmente en blanco **elimina** ese factor,
mientras que dejarla a medias da error: los cuatro valores, o ninguno. Al importar, empareja la
columna **Gas refrigerante** cuando los gases ya existan, porque emparejar solo el nombre crea
duplicados. Nada de esto se puede deshacer desde la plataforma, aunque todo queda registrado en el
**Registro de auditoría**.

La partida de gases refrigerantes dentro del asistente de cálculo de huella no forma parte de esta
entrega y llegará más adelante: por ahora se mantienen el catálogo y sus factores.
