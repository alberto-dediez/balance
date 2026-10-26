---
title: Nombre de usuario sin caracteres < ni >
date: "2026-10-26 09:34"
category: security
summary: Al registrar un usuario ya no se admiten los caracteres < y > en su nombre, y los nombres existentes se muestran siempre como texto seguro.
plan: 2026-10-26-username-html-validation
manual: username-html-validation.pdf
techManual: username-html-validation.tech.md
published: true
---

## Qué cambia

Al registrar un usuario nuevo, su nombre de usuario ya no puede contener los caracteres `<` ni `>`.
Esos caracteres sirven para escribir código, y hasta ahora podían usarse para colar contenido malicioso
que se ejecutaría en el navegador de quien consultara después los listados, el historial o el registro
de eventos. Con esta regla, el nombre se muestra siempre como texto normal.

## A quién afecta

- **Administradores**: al registrar usuarios de cualquier cuenta desde la zona de administración.
- **Propietarios**: al registrar usuarios de su propia cuenta.

Los usuarios que ya existen no se modifican ni se bloquean: siguen funcionando igual. Si alguno tuviera
esos caracteres en su nombre, a partir de ahora se verá como texto, sin efectos extraños, en el
registro de eventos, los historiales, las notificaciones y los filtros.

## Dónde se encuentra

En **Usuarios → Registrar**, en el campo **Nombre de usuario**. La regla se aplica igual en el alta
desde la plataforma, desde la API y desde la línea de comandos.

## Qué conviene saber

- Solo se prohíben `<` y `>`. Un correo electrónico sigue siendo un nombre válido, y también lo son
  los apóstrofos, las comillas y el símbolo &.
- Si el nombre no es válido, el formulario se vuelve a mostrar con el aviso «El nombre de usuario no
  puede contener los caracteres < ni >» bajo el campo, no se crea el usuario y el resto de datos
  escritos se conservan. Basta con quitar esos caracteres y pulsar de nuevo **Registrar**.
- El nombre de usuario sigue siendo único: si ya existe otro igual, también se rechaza.
- Los usuarios borrados siguen mostrándose tachados en el registro de eventos.
