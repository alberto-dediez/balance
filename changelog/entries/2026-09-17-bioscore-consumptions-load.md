---
title: Cargar consumos de Bioscore en la huella de propiedades y empresas
date: "2026-09-17 13:37"
category: feature
summary: Los pasos Alcance 1 y Alcance 2 de las huellas de propiedad y de empresa se rellenan con un clic con los consumos anuales registrados en Bioscore, sin teclearlos a mano.
plan: 2026-09-17-bioscore-consumptions-load
manual: bioscore-consumptions-load.pdf
techManual: bioscore-consumptions-load.tech.md
published: true
---

## Qué cambia

Las propiedades hoteleras registran en Bioscore, a lo largo del año, su electricidad por
comercializadora, sus combustibles de instalaciones y de vehículos y sus recargas de gas
refrigerante. Hasta ahora había que volver a teclear esos datos como actividades de la huella. La
nueva acción **Cargar consumos de Bioscore** descarga los consumos del año de la huella y los
convierte en actividades del alcance en el que estás: **Instalaciones fijas**, **Desplazamientos
en vehículos** y **Fugas de equipos de refrigeración** en el **Alcance 1**; **Consumo eléctrico**
(una fila por comercializadora) en el **Alcance 2**. Cada fila creada lleva el texto «Bioscore» en
su columna **Fuente**, para distinguirla de las introducidas a mano.

## Dónde se encuentra

Abre la huella desde **Propiedades › Huellas de Carbono** o **Empresas › Huellas de Carbono** y
entra en el paso **Alcance 1** o **Alcance 2**. En el desplegable **Acciones** de la cabecera,
debajo de **Volver**, aparece **Cargar consumos de Bioscore**. La acción pide confirmación,
porque sustituye las filas del alcance, y al terminar vuelve al mismo paso con las actividades ya
visibles y un resumen de lo cargado, de las fuentes que se han vuelto a incluir y de los consumos
que no se han podido convertir. No existe en el paso **Resultados** ni en las huellas de eventos.

## A quién afecta

La ven y la ejecutan los **Propietarios** de la cuenta (también un Administrador que la
impersona). Depende del emparejamiento de los maestros de Bioscore que mantienen los
Administradores: un consumo cuyo elemento no está mapeado, cuyo maestro ya no existe o cuya unidad
es de otra naturaleza que la del maestro se omite y se enumera en un aviso, mientras el resto se
carga con normalidad.

## Qué conviene saber

Para cada propiedad que Bioscore devuelve se sustituye el alcance completo, también en los
bloques para los que Bioscore no trae nada ese año; las propiedades no vinculadas a Bioscore y el
bloque **Sin asignar a propiedad** de una huella de empresa no cambian. Si una fuente estaba
excluida y Bioscore trae datos de ella, vuelve a incluirse; si no trae nada, sigue excluida. Los
combustibles válidos para instalaciones y vehículos a la vez van siempre a **Instalaciones
fijas**. Los consumos a cero no generan filas, y las facturas, importes y ocupación de Bioscore no
se cargan. La carga no avanza el paso ni cambia el estado de la huella, y solo afecta al alcance
en el que se lanza: para rellenar los dos hay que repetirla en cada paso. Los datos no se
refrescan solos; vuelve a cargar cuando cambien en Bioscore. Si Bioscore no responde, si ninguna
propiedad de la huella está vinculada o si la huella ya está completada, no se modifica nada y se
muestra el aviso correspondiente.
