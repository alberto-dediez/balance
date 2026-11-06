---
title: "Menú lateral con grupos"
date: "2026-11-06 14:03"
category: improvement
summary: "El menú lateral agrupa las pantallas bajo cabeceras propias, empezando por Seguridad, que ahora se divide en Organización y Auditoría."
plan: 2026-11-06-menus-de-tres-niveles
manual: menus-de-tres-niveles.pdf
techManual: menus-de-tres-niveles.tech.md
published: true
---

## Qué cambia

El panel que se abre al pulsar un icono del menú lateral ya no tiene por fuerza una lista plana de enlaces. Ahora puede organizar las pantallas en **grupos**: cada grupo lleva su propia cabecera y se separa del anterior con una línea divisoria. Así, las áreas con muchas pantallas resultan más fáciles de recorrer de un vistazo.

## Dónde se estrena

El menú **Seguridad** de los administradores queda dividido en dos grupos:

- **Organización**: Cuentas y Usuarios.
- **Auditoría**: Firma de documentos, Emails enviados (solo para super administradores) y Registro.

Entre la cabecera «Seguridad» y la primera cabecera de grupo no se añade línea divisoria, porque ya quedan visualmente separadas.

## A quién afecta

A los administradores y super administradores. Los menús de propietarios y usuarios, y el resto de menús de administrador (Biblioteca, Notificaciones y Novedades), se ven exactamente igual que antes.

## Qué sigue funcionando igual

- La pantalla en la que estás sigue resaltada en el menú, y el icono del área correspondiente sigue marcado en la columna de iconos.
- Cuando dos enlaces podrían coincidir con la dirección actual, se resalta solo el más específico.
- El buscador de acciones (<kbd>Ctrl</kbd>+<kbd>K</kbd>) encuentra las mismas pantallas que antes. Ahora indica el grupo en el subtítulo de cada resultado, por ejemplo «Seguridad > Auditoría». Buscar «Seguridad» devuelve todas las pantallas del área, y buscar «Auditoría» devuelve solo las de ese grupo.
- La suplantación de cuentas sigue mostrando el menú que corresponde al rol con el que se actúa.

## Qué conviene saber

- Los grupos tienen un solo nivel de profundidad: icono del área, grupo y enlace. No hay submenús plegables dentro de un grupo.
- Las cabeceras de grupo son solo un rótulo: no se pueden pulsar ni aparecen como resultado en el buscador.
- No hay que hacer nada para activarlo: el nuevo menú aparece automáticamente.
