---
title: Mix eléctrico
date: 2026-09-15
category: feature
summary: Nuevo catálogo de mix eléctrico por país que permite guardar, junto al resultado oficial, el resultado de electricidad por localización.
plan: 2026-09-15-electricity-mix-location-based
manual: electricity-mix-location-based.pdf
techManual: electricity-mix-location-based.tech.md
published: true
---

## Qué cambia

La electricidad se puede contabilizar de dos maneras, y las normas internacionales de huella de
carbono piden las dos: por el contrato —qué comercializadora y si hay garantía de origen— y por el
lugar —cómo de limpia es la red eléctrica del país donde se consume—. Al guardar el paso de
electricidad de la huella de un evento se guardan ahora dos cifras: el resultado por mercado de
siempre y, además, el resultado por localización.

Para alimentarlo se añade el catálogo **Mix eléctrico**: un registro por país con el factor medio
de emisión de su red, por año y grupo de coeficientes. Un mix no tiene nombre propio —lo identifica
su país, y solo hay uno por país— y la unidad es siempre el kilovatio hora.

## A quién afecta

Es un catálogo común a toda la plataforma y de mantenimiento interno: solo entran administradores y
superadministradores; propietarios y usuarios reciben un aviso de permiso denegado. Quien rellena
una huella no ve ningún cambio.

## Dónde se encuentra

En el menú lateral, en **Instalaciones › Mix eléctrico**, bajo **Factores GdO**. Del listado salen
los cuatro caminos habituales: **Registrar** el mix de un país, **Factores de emisión** para editar
de una vez los valores de todos los países de un año y grupo —con copia de un año a otro que aún
esté vacío—, **Importar** desde hoja de cálculo y **Exportar**, con una fila por factor. También se
elimina desde la fila o en bloque.

## Qué conviene saber

Ninguna cifra que hoy se ve cambia de valor: los totales, el informe y la compensación siguen
calculándose con el resultado por mercado. El resultado por localización se guarda aparte y aún no
se muestra en ninguna pantalla.

El país se deduce del evento, de su propiedad y del país de esa propiedad. Si falta alguno de esos
datos, o el país no tiene mix o factor para el año y grupo de la huella, la actividad se guarda
igual con el resultado por localización vacío y sin aviso. Nada se recalcula solo: una huella ya
guardada solo lo recoge si se vuelve a guardar su paso de electricidad.

En la edición anual en masa, vaciar los cuatro valores de un país elimina ese factor y dejar la
fila a medias da error. Al importar, la columna **País** espera el código ISO —ES, FR, PT—, no el
nombre. No se admiten factores de años posteriores al actual.
