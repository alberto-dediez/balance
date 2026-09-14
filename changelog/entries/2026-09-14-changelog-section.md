---
title: Sección de novedades para administradores
date: 2026-09-14
category: feature
summary: Los administradores ya pueden consultar dentro de la plataforma qué ha cambiado en cada actualización, con acceso directo al manual de cada funcionalidad.
plan: 2026-09-14-changelog-section
manual: changelog-section.pdf
techManual: changelog-section.tech.md
published: true
---

## Qué cambia

Hasta ahora, saber qué había cambiado en la plataforma exigía preguntarlo al equipo de desarrollo.
Con esta funcionalidad se añade una nueva sección, **Novedades**, donde cada actualización queda
registrada con su fecha, su categoría y una descripción de lo que cambia, y puede enlazar
directamente al manual de esa funcionalidad para consultarlo sin salir de la plataforma.

## A quién afecta

Solo a los usuarios con rol de administrador o superadministrador. El resto de usuarios de la
plataforma no ve el menú ni puede acceder a la sección.

## Dónde se encuentra

En el menú lateral, dentro del grupo **Novedades → Changelog**. Desde ahí se llega a un listado de
todas las novedades publicadas, ordenadas de la más reciente a la más antigua, con su fecha,
categoría (nueva funcionalidad, mejora, corrección o seguridad), título y un resumen. El listado se
puede filtrar por texto —busca en el título y en el resumen— y por categoría, y el filtro se
mantiene si se vuelve más tarde a la pantalla.

Al pulsar **Ver** en una novedad se abre su descripción completa. Si la novedad tiene manual de
usuario, aparecen los botones **Ver manual** (lo abre en una pestaña nueva) y **Descargar manual**
(lo guarda en el equipo). El manual técnico es distinto: solo está disponible para el
superadministrador, así que el botón **Manual técnico** únicamente aparece si se entra con ese
perfil.

## Qué conviene saber

Las novedades se publican junto con cada actualización: no hay pantalla para crear, editar ni
eliminar entradas desde la plataforma. Los botones de manual solo aparecen cuando la novedad tiene
ese manual asociado y el perfil con el que se entra puede consultarlo; si un enlace deja de estar disponible, la plataforma muestra que la página no
se ha encontrado en lugar de un enlace roto. No hay ningún aviso ni contador de novedades sin leer:
la sección se consulta cuando interesa.
