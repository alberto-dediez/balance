---
title: Roles protegidos al editar un usuario
date: 2026-09-15
category: security
summary: Editar a un usuario ya no le retira, sin querer, los roles que quien edita no puede gestionar.
plan: 2026-09-15-protect-super-admin-role
manual: protect-super-admin-role.pdf
techManual: protect-super-admin-role.tech.md
published: true
---

## Qué cambia

Al editar a un usuario, el campo **Rol de usuario** solo ofrece los roles que quien edita puede
conceder o retirar. A partir de ahora, los roles que no aparecen en ese campo **se conservan tal
cual** al guardar. La regla es sencilla: lo que no ves, no lo tocas.

Antes ocurría lo contrario: como esos roles no se mostraban, la plataforma los interpretaba como
casillas desmarcadas. Un Administrador que solo quería cambiar el idioma de un Super administrador
le retiraba el rol sin enterarse, y un Propietario que editaba a un Administrador de su cuenta veía
un mensaje rojo de error en cada guardado.

## A quién afecta

A quien edita usuarios: Administradores y Propietarios. Un Administrador gestiona los roles
Usuario, Propietario y Administrador, pero no Super administrador. Un Propietario gestiona Usuario
y Propietario, y solo sobre personas de su propia cuenta. Al suplantar una cuenta se trabaja con
los permisos de Propietario de esa cuenta, aunque el usuario sea Administrador. Las personas
editadas no notan nada distinto: simplemente conservan los roles que tenían.

## Dónde se encuentra

En **Seguridad › Usuarios**, con la acción **Editar** de la fila de cada persona. La columna
**Roles** del listado permite comprobar, antes y después de guardar, qué roles tiene cada una.

## Qué conviene saber

El rol **Super administrador** no se concede ni se retira desde esta pantalla: no se ofrece a
nadie. Hay que solicitarlo al equipo que administra la plataforma. El rol **Usuario** lo tiene todo
el mundo por definición: aunque se desmarque y se guarde, la persona lo conserva y ya no aparece
ningún aviso de retirada. Si se desmarcan **todos** los roles, no se retira ninguno; para retirar
uno concreto hay que desmarcarlo dejando al menos otro marcado.

Tras guardar solo se muestra un mensaje por cada rol que realmente ha cambiado. El mensaje rojo
"No se ha podido retirar acceso como…" ya no aparece al editar a alguien con roles por encima de
los propios; si se ve, se trata de un problema real que conviene comunicar. Las acciones masivas
**Asignar rol** y **Eliminar rol** siguen la misma regla y solo ofrecen los roles gestionables.
