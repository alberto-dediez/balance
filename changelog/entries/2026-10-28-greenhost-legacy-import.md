---
title: "Traspaso de la plataforma Greenhost"
date: "2026-10-28 12:58"
category: feature
summary: "Los hoteles de Greenhost pasan a esta plataforma agrupados por grupo hotelero, con una propiedad por hotel y HOSBEC como consultora de sus cuentas."
plan: 2026-10-28-greenhost-legacy-import
manual: greenhost-legacy-import.pdf
techManual: greenhost-legacy-import.tech.md
published: true
---

## Qué cambia

Greenhost, la plataforma de la asociación hotelera HOSBEC, se traspasa a esta plataforma. Cada hotel pasa a ser una propiedad y HOSBEC queda como consultora de las cuentas de sus hoteles, de modo que sus Propietarios pueden entrar en ellas y la calculadora de viajes de HOSBEC puede limitarse a «Todas mis propiedades».

El traspaso reutiliza la orden guiada con la que ya se pasaron las plataformas Balance antiguas y añade tres novedades:

- **Una cuenta por grupo hotelero.** Los hoteles de un mismo grupo (por ejemplo RH Hoteles o SH Hoteles) acaban en una sola cuenta, con una propiedad por hotel. Un fichero de grupos indica a qué grupo pertenece cada hotel y aporta su identificador de Bioscore, coordenadas, dirección y código postal.
- **Creación de propiedades.** Se crea una propiedad por hotel, y se reconoce la que ya exista por su identificador de Bioscore o por su nombre.
- **HOSBEC como consultora.** Se marca como consultora y se asigna a las cuentas importadas. Si una cuenta ya tiene otra consultora, se respeta.

## A quién afecta

A los Administradores, que lanzan el traspaso desde la consola de la plataforma. Los Propietarios y Usuarios de cada hotel encuentran su cuenta ya creada, con su hotel como propiedad, y entran con «Generar nueva contraseña»; no reciben ningún correo.

## Dónde se ve el resultado

Las cuentas aparecen en el listado de cuentas, las propiedades en el de propiedades y los cálculos en la calculadora de viajes: los de cada hotel en la de su propiedad y los de HOSBEC en la de su cuenta.

## Qué conviene saber

- Conviene hacer siempre una simulación antes de la ejecución real: se hacen las mismas preguntas pero no se crea nada.
- Antes de las preguntas sobre cuentas aparece un aviso de «Grupos sin cuenta», con las cuentas parecidas ya existentes, para evitar duplicar una cuenta por una simple diferencia de nombre.
- El fichero de grupos se valida antes de escribir nada y el traspaso se detiene si tiene errores.
- Si dos registros corresponden al mismo hotel, comparten una única propiedad.
- Las personas de HOSBEC cuyas únicas pertenencias son a organizaciones que no están en los datos se crean a mano.
- El traspaso se puede repetir sin duplicar lo ya creado.
