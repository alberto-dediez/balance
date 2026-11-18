---
title: "Aviso de mantenimiento durante las actualizaciones"
date: "2026-11-12 17:16"
category: improvement
summary: "Mientras se publica una nueva versión, la plataforma muestra una página clara de mantenimiento en lugar de errores confusos."
plan: 2026-11-12-deploy-maintenance-gate
manual: deploy-maintenance-gate.pdf
techManual: deploy-maintenance-gate.tech.md
published: true
---

## Qué cambia

Cuando se publica una nueva versión de la plataforma, esta se pone en mantenimiento automáticamente durante unos minutos. Hasta ahora, en ese intervalo podían aparecer errores confusos al abrir cualquier pantalla. A partir de ahora se muestra una página de aviso con el número 503, el título «Estamos en mantenimiento» y el mensaje «Estamos realizando tareas de mantenimiento para mejorar el servicio. Vuelve a intentarlo en unos minutos.».

El aviso aparece solo al empezar la actualización y desaparece también solo cuando todo está listo. Nadie tiene que activarlo ni quitarlo.

## A quién afecta

A todas las personas que usan la plataforma, sea cual sea su rol: Administradores, Propietarios y Usuarios ven la misma página de aviso. También a los programas conectados a la plataforma, que reciben una respuesta indicando que el servicio está en mantenimiento y que deben reintentar pasados unos segundos.

## Dónde se encuentra

No hay ninguna opción de menú ni botón: el aviso aparece al abrir cualquier dirección de la plataforma mientras dura la actualización.

## Qué conviene saber

- Es temporal: dura lo que tarde la actualización, normalmente unos minutos.
- Afecta a toda la plataforma y no se puede saltar, ni siquiera con permisos de Administrador.
- La página de aviso solo está en castellano, con independencia del idioma elegido.
- No hace falta cerrar sesión. Basta con esperar unos minutos y recargar la página.
- Si estabas rellenando un formulario, es posible que no se haya guardado: revisa tus datos al volver.
- Si el aviso sigue apareciendo pasado un buen rato, es posible que la actualización haya tenido un problema; avisa al equipo que gestiona la plataforma.
