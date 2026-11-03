---
title: "Asistente de importación más rápido y aviso de finalización fiable"
date: "2026-11-02 14:10"
category: improvement
summary: "El asistente de importación responde con rapidez aunque el fichero sea grande y el aviso de «finalizada» llega solo cuando se han procesado todas las filas."
plan: 2026-11-02-importation-wizard-performance
manual: importation-wizard-performance.pdf
techManual: importation-wizard-performance.tech.md
published: true
---

## Qué cambia

Hemos mejorado la importación de datos desde ficheros CSV o Excel en dos aspectos.

**Un asistente más ágil.** Los pasos del asistente (subir el fichero, emparejar campos, traducir valores y asignar valores) ahora responden con rapidez aunque el fichero tenga cientos o miles de filas. Antes, con ficheros de tamaño normal, cada paso podía hacerse muy lento.

**Un aviso que dice la verdad.** El aviso «Importación de «nombre del fichero» finalizada» llegaba casi nada más empezar, cuando la mayoría de las filas aún estaban pendientes. Ahora solo se envía cuando todas las filas se han procesado, ya sea con éxito o con error. Se envía una única vez, tanto en la plataforma como por correo, aunque varias filas terminen a la vez.

## A quién afecta

A los usuarios con permiso para importar en un listado, y a quienes consultan después los resultados desde el aviso.

## Dónde se encuentra

Desde el listado que admita importación, pulsa su botón de importar para abrir la pantalla **Importar**. Al lanzar la importación verás **Importación en curso**: el proceso continúa en segundo plano y puedes seguir trabajando o cerrar la pantalla. Al terminar recibirás el aviso con el enlace a **Resultado de la importación**.

## Qué conviene saber

- «Finalizada» significa que se ha procesado todo, no que no haya errores. Los errores de cada fila se ven en la pantalla de resultados.
- Si abres los resultados antes de recibir el aviso, las filas que aún se procesan aparecen marcadas con «---». No es un fallo: espera al aviso y vuelve a abrir la pantalla.
- Una importación sin filas se da por terminada de inmediato y también genera su aviso.
- Si tuviste un aviso prematuro antes de este cambio, las filas pendientes se procesaron igualmente y no se envió un segundo aviso.
