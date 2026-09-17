---
title: Borrado y cierre de sesión protegidos frente a enlaces externos
date: "2026-09-17 13:31"
category: security
summary: Los enlaces Eliminar y Cerrar sesión solo funcionan si se pulsan desde dentro de la plataforma; una web ajena ya no puede provocarlos.
published: true
---

## Qué cambia

Las acciones **Eliminar** de los listados y el botón **Cerrar sesión** eran enlaces normales: una
página ajena podía incluir esa misma dirección escondida en una imagen o en un enlace y, si quien
la visitaba tenía la sesión abierta en la plataforma, el borrado o el cierre de sesión se ejecutaba
sin que se enterase. Es un ataque conocido como CSRF (falsificación de petición en sitio cruzado).

A partir de ahora esos enlaces llevan una marca de seguridad y la plataforma comprueba, además,
que la petición nace de una de sus propias pantallas. Si la petición llega desde otra web, o sin
la marca, se rechaza con un error de **acceso denegado** y no se borra nada ni se cierra la sesión.

## A quién afecta

A todo el mundo, aunque nadie tendrá que hacer nada distinto: se sigue pulsando **Eliminar**,
confirmando el aviso y listo. Quedan protegidos los borrados de usuarios, cuentas, solicitudes de
firma, archivos de la biblioteca y notificaciones, y el cierre de sesión desde el menú de perfil.

## Dónde se encuentra

En la acción **Eliminar** (icono rojo) de cualquier listado, en el botón **Eliminar** de la ficha
de una notificación y en **Cerrar sesión**, dentro del menú del avatar arriba a la derecha.

## Qué conviene saber

Si al pulsar **Eliminar** o **Cerrar sesión** aparece un **acceso denegado**, lo más probable es
que el enlace se haya abierto desde fuera de la plataforma: desde un marcador antiguo, un correo o
una pestaña que llevaba mucho tiempo abierta. Basta con volver al listado y repetir la acción desde
allí. Las direcciones de esos enlaces incluyen ahora un parámetro adicional, por lo que **los
marcadores o enlaces guardados a acciones de borrado dejan de servir**; nunca fueron una buena
idea. Las acciones masivas de los listados no cambian: ya estaban protegidas.
