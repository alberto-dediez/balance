---
title: "Dirección configurable para la administración"
date: "2026-11-09 14:00"
category: feature
summary: "La administración de la plataforma puede colgar de una dirección con prefijo (por ejemplo /management), dejando libre la dirección principal para una web pública."
plan: 2026-11-09-admin-prefix
manual: admin-prefix.pdf
techManual: admin-prefix.tech.md
published: true
---

## Qué cambia

La plataforma puede configurarse para que todas sus pantallas de gestión cuelguen de una dirección con un apartado intermedio, por ejemplo `/management`. Así la dirección principal del sitio queda libre para una web abierta al público, sin necesidad de iniciar sesión. Si tu plataforma no usa este apartado, nada cambia: las direcciones son las de siempre.

## A quién afecta

A Administradores, Propietarios y Usuarios: ven las mismas pantallas y menús, y solo cambia el principio de la dirección en el navegador y en los marcadores guardados. Un visitante sin cuenta solo puede ver la web pública, si existe; si entra en una dirección de gestión, se le pide iniciar sesión. El apartado intermedio lo fija el equipo técnico al configurar la plataforma y no se puede cambiar desde ninguna pantalla.

## Dónde se encuentra

Escribe en el navegador la dirección de la plataforma seguida del apartado (por ejemplo `/management`) y te llevará a la pantalla de inicio en tu idioma. Dentro de la plataforma no hay que hacer nada especial: menús, botones y enlaces ya apuntan a las direcciones correctas. Al iniciar sesión o cerrarla vuelves siempre a la pantalla de inicio de la administración, salvo que antes hubieras intentado abrir una pantalla concreta.

## Qué conviene saber

- Las direcciones antiguas sin el apartado dejan de abrir pantallas de gestión y no se redirigen: guarda de nuevo tus marcadores (por ejemplo `/management/es/profile`).
- Las demás direcciones fuera del apartado son públicas y no piden iniciar sesión, salvo los componentes interactivos de las pantallas, que siguen exigiéndola.
- El acceso con cuenta de Google o Microsoft, los avisos de servicios externos y la documentación de la interfaz de programación mantienen su dirección.
- Los enlaces públicos de firma y de archivos compartidos siguen abriéndose sin iniciar sesión, ahora con el apartado intermedio. Los enlaces recibidos antes de la configuración pueden dejar de abrir.
- Si abres una dirección que no existe, ves la pantalla de error habitual dentro de la administración y una pantalla de error sencilla fuera de ella.
