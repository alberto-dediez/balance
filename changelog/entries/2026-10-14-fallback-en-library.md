---
title: La biblioteca sigue funcionando aunque su almacenamiento falle
date: "2026-10-14 11:50"
category: improvement
summary: Si el almacenamiento de la biblioteca deja de responder, puedes seguir subiendo y abriendo archivos nuevos; se guardan temporalmente y se pasan solos a su sitio cuando el almacenamiento se recupera.
plan: 2026-10-14-fallback-en-library
manual: fallback-en-library.pdf
techManual: fallback-en-library.tech.md
published: true
---

## Qué cambia

Los archivos de la **Biblioteca** se guardan en un almacenamiento propio. Hasta ahora, si ese
almacenamiento dejaba de responder durante un rato, subir un archivo terminaba en error y la
biblioteca parecía rota aunque el resto de la plataforma funcionara.

A partir de esta mejora, mientras dura el problema la plataforma guarda los archivos nuevos
temporalmente en el servidor. La subida termina como siempre, con el mensaje «Archivo subido
correctamente», y el archivo se puede abrir y descargar enseguida. Cada cinco minutos la plataforma
vuelve a intentar pasarlos a su sitio habitual y, cuando lo consigue, todo queda como si no hubiera
pasado nada: mismo nombre, mismo enlace y mismos permisos.

## A quién afecta

- **Administradores**: reciben una notificación en la campana de la barra superior con el asunto
  «El almacenamiento de la biblioteca no está disponible» cuando empieza el problema, y otra con el
  asunto «El almacenamiento de la biblioteca se ha recuperado» cuando todos los archivos pendientes
  ya están en su sitio. Lo recomendable es avisar al equipo técnico; no hace falta pedir a nadie que
  deje de trabajar.
- **Propietarios y usuarios**: no reciben avisos y no tienen que hacer nada. No hay pantallas ni
  opciones nuevas.

## Qué conviene saber

- **Un aviso por incidencia.** Los archivos siguientes de la misma incidencia no generan más avisos,
  aunque en casos puntuales puede llegar un segundo.
- **Archivos anteriores al problema.** Los que ya estaban en la biblioteca no se pueden abrir hasta
  que el almacenamiento se recupere: aparece la página de contenido no encontrado. Vuelve a
  intentarlo pasados unos minutos.
- **Eliminar tiene límites.** Un archivo subido durante la incidencia se elimina con normalidad. Uno
  anterior no se puede eliminar mientras dure el problema y aparece el aviso «No se ha podido
  eliminar el archivo»; el archivo puede dejar de verse en la biblioteca igualmente.
- **Más lento, no roto.** Mientras el almacenamiento no responde, subir o abrir un archivo puede
  tardar algo más de lo normal.
- **Sin límite de reintentos.** La plataforma sigue intentándolo hasta conseguirlo.
- **Solo la biblioteca.** Las exportaciones, los documentos de las solicitudes de firma y los
  manuales de las novedades no cambian.
- **Sin correos.** Los dos avisos son solo notificaciones dentro de la plataforma.

Más detalles en el manual de usuario adjunto.
