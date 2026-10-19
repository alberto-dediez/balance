---
title: La página de estado comprueba que la plataforma funciona de verdad
date: "2026-10-18 22:35"
category: improvement
summary: La página de estado ya no responde siempre «ok»: comprueba que la plataforma puede leer su información y que los trabajos en segundo plano avanzan, y avisa de qué parte falla.
plan: 2026-10-17-health-check-database
manual: health-check-database.pdf
techManual: health-check-database.tech.md
published: true
---

## Qué cambia

La plataforma tiene una página de estado que dice en una sola palabra si todo funciona: **ok** o
**error**. La consultan de forma automática los servicios de monitorización y también se puede
abrir en el navegador. Hasta ahora respondía siempre «ok» mientras la plataforma estuviera
encendida, aunque no pudiera leer ni guardar información o aunque las importaciones, las
exportaciones y los avisos se quedaran sin procesar.

A partir de ahora, cada vez que se consulta revisa dos partes esenciales y muestra una línea por
cada una:

- **database**: que la plataforma puede leer la información guardada (cuentas, usuarios, archivos,
  novedades…).
- **queue**: que los trabajos en segundo plano (importaciones, exportaciones, envío de
  notificaciones y publicación de novedades) se están procesando.

Si alguna falla, la palabra principal pasa a «error» y la línea afectada lo indica.

## A quién afecta

A los **administradores** y al equipo de soporte, que pueden abrirla para confirmar si un problema
es general, y a los servicios de monitorización, que ahora detectan también estos fallos.
Propietarios y usuarios no ven ningún cambio. No hay nada que activar.

## Dónde se encuentra

No está en ningún menú: se abre escribiendo la dirección de la plataforma seguida de
**/es/health-check** (o el código de otro idioma). No hace falta iniciar sesión y sigue disponible
durante el mantenimiento. Cada recarga hace una comprobación nueva.

## Qué conviene saber

- Basta con que una sola parte falle para que la página diga «error».
- Tener muchas tareas pendientes no es un fallo mientras se vayan procesando; lo que cuenta es que
  alguna lleve más de **15 minutos** esperando. Ese tiempo lo puede ajustar el equipo técnico en
  cada instalación.
- Las tareas programadas para más adelante, como una novedad para otro día, no cuentan hasta que
  llega su hora.
- La página es pública, así que solo dice qué parte falla, nunca el motivo exacto; el motivo queda
  registrado para el equipo técnico.
- Con «queue: error» se puede seguir trabajando, pero importaciones, exportaciones y notificaciones
  pueden retrasarse; al resolverse, los trabajos pendientes se procesan solos.
