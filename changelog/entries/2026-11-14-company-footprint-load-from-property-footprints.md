---
title: "Cargar en la huella de empresa los consumos de las huellas de propiedad"
date: "2026-11-14 00:20"
category: feature
summary: "Copia en los alcances 1 y 2 de la huella de empresa las actividades ya calculadas en las huellas de propiedad completadas, sin volver a teclearlas."
plan: 2026-11-13-company-footprint-load-from-property-footprints
manual: company-footprint-load-from-property-footprints.pdf
techManual: company-footprint-load-from-property-footprints.tech.md
published: true
---

## Qué cambia

Si ya has calculado la huella de cada una de tus propiedades, ya no hace falta volver a teclear esos mismos consumos en la huella de empresa. En los pasos **Alcance 1** y **Alcance 2** hay una nueva acción, **Cargar desde huellas de propiedad**, que copia las actividades (combustibles, vehículos, gases refrigerantes y electricidad) de cada propiedad cuya huella del mismo año esté completada. Así, la huella de empresa y las de propiedad parten de los mismos datos.

## A quién afecta

A los Propietarios, que la ven en el desplegable **Acciones**, junto a la carga de consumos que ya existía. Un Administrador solo puede usarla si impersona a un Propietario. La cuenta debe tener activa la funcionalidad de huella de carbono de propiedades y empresas.

## Dónde se encuentra

En **Empresas → Huellas de Carbono**, dentro de una huella de empresa, en el paso Alcance 1 o Alcance 2. Cada paso copia solo su propio alcance. La acción no aparece si la huella está completada o marcada como «Huella ya calculada». Tras ejecutarla, revisa las filas y pulsa **Siguiente** para guardar el paso, como siempre.

## Qué conviene saber

- Se copia, no se enlaza: si después cambias la huella de una propiedad, la de empresa no se actualiza sola. Puedes volver a lanzar la acción para refrescarla.
- Solo se sustituyen las filas de las propiedades copiadas. «Sin asignar a propiedad» y las propiedades omitidas no se tocan, pero se pierden las filas tecleadas a mano de las propiedades copiadas en ese alcance.
- Se omiten, con aviso y motivo, las propiedades sin huella completada del año, con «Huella ya calculada» o sin actividades en ese alcance.
- Las emisiones se recalculan con los factores de emisión vigentes. Si el total de una propiedad difiere del de su huella, se muestran ambas cifras.
- Si una fuente excluida en la huella de empresa recibe filas, se vuelve a incluir y se avisa.
- Todo o nada: si algo falla, no se guarda ningún cambio a medias.
- La acción no completa ni bloquea la huella.

Más detalle en el manual de usuario adjunto.
