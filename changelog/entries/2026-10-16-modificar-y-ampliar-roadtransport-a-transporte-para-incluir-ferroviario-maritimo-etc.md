---
title: Medios de transporte
date: "2026-10-16 20:14"
category: improvement
summary: El antiguo catálogo de transporte por carretera pasa a llamarse Medios de transporte, admite tren, barco y avión agrupados por modo, y permite calcular las emisiones de un envío a partir de su peso y su distancia.
plan: 2026-10-16-modificar-y-ampliar-roadtransport-a-transporte-para-incluir-ferroviario-maritimo-etc
manual: modificar-y-ampliar-roadtransport-a-transporte-para-incluir-ferroviario-maritimo-etc.pdf
techManual: modificar-y-ampliar-roadtransport-a-transporte-para-incluir-ferroviario-maritimo-etc.tech.md
published: true
---

## Qué cambia

El maestro que antes se llamaba **Transporte por carretera** se llama ahora **Medios de
transporte** y ya no se limita a la carretera. Trae tres novedades:

- **Más medios.** Además de turismos, furgonetas, camiones, ciclomotores, motocicletas y autobuses,
  se pueden registrar **Tren de mercancías**, **Buque portacontenedores**, **Barcaza** y
  **Carga aérea**.
- **Modo de transporte.** Cada medio pertenece a un modo (**Carretera**, **Ferrocarril**,
  **Marítimo y fluvial** o **Aéreo**) que la plataforma deduce sola del tipo de vehículo. El modo
  se ve en el listado, se puede filtrar por él y aparece como columna en la exportación.
- **Tonelada-kilómetro.** Un medio puede tener como unidad la **Tonelada-kilómetro (tkm)**, la
  unidad en la que se publican habitualmente los factores del transporte de mercancías. Al usar un
  medio así en una huella, en lugar de una cantidad se indican el **peso** de la carga (en
  kilogramos o toneladas) y la **distancia (km)**, y la plataforma calcula al momento las
  toneladas-kilómetro. Por ejemplo, 2.500 kg recorriendo 350 km son 875 tkm.

Lo que ya tenías sigue igual: los medios existentes conservan su unidad, sus factores y las
actividades guardadas en las huellas, y su modo aparece como «Carretera».

## A quién afecta

- **Administradores**: gestionan el maestro, registran medios en tonelada-kilómetro y filtran el
  listado por modo.
- **Propietarios**: al rellenar las tablas de transporte de la huella de un evento (transporte de
  mercancías en Montaje, Celebración y Desmontaje), de una empresa o de una propiedad
  (desplazamientos en vehículos del Alcance 1), la fila pide peso y distancia cuando el medio
  elegido está en tonelada-kilómetro.

## Dónde se encuentra

En el menú lateral, **Transporte → Medios de transporte** (la misma entrada que antes se llamaba
«Transporte por carretera»). Las importaciones y exportaciones se hacen con el nombre «Medios de
transporte».

## Qué conviene saber

- El modo no se elige: para cambiarlo, cambia el **Tipo de vehículo o medio**.
- En tonelada-kilómetro no se puede escribir la cantidad directamente: solo peso y distancia. Una
  fila con peso pero sin distancia (o al revés) no se guarda.
- Si un medio ya usado pasa a tonelada-kilómetro, sus filas conservan las emisiones, pero al
  reabrir el paso habrá que rellenar el peso y la distancia para volver a guardarlas.
- Los consumos que llegan de Bioscore se cargan como cantidad directa; si su unidad no es
  compatible con la del medio emparejado, se omiten.
- La exportación incluye la columna «Modo», pero no se puede filtrar por modo al exportar.
