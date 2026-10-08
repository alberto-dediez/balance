---
title: Buscador global
date: "2026-10-08 07:22"
category: feature
summary: Una lupa en la barra superior (o Ctrl+K) te lleva directamente a cualquier cuenta o usuario que tengas permiso para ver, sin pasar por el menú ni por los filtros de cada listado.
plan: 2026-10-07-buscador-global
manual: buscador-global.pdf
techManual: buscador-global.tech.md
published: true
---

## Qué cambia

La barra superior estrena una **lupa** que abre una ventana de búsqueda en el centro de la pantalla.
Escribes parte de un nombre y, un instante después de dejar de teclear, aparecen las coincidencias
agrupadas bajo **Cuentas** y **Usuarios**, con el mismo nombre e icono que en el menú lateral. Al
pulsar un resultado vas directamente a su pantalla de edición, como si hubieras llegado desde su
listado.

También puedes abrirla con el teclado: **Ctrl + K** (en Mac, **⌘ + K**). Dentro de la ventana, las
flechas **↑** y **↓** recorren los resultados e **Intro** abre el resaltado.

## A quién afecta

- **Administradores:** encuentran cuentas por su nombre y usuarios de cualquier cuenta por su nombre
  de usuario.
- **Propietarios:** encuentran solo los usuarios de su propia cuenta; no ven el grupo de cuentas.
- **Usuarios:** no ven la lupa, porque de momento no hay nada que puedan buscar con ella.
- **Administrador que suplanta una cuenta:** ve exactamente lo mismo que el propietario de esa
  cuenta. Al pulsar **Volver a mi cuenta** recupera la búsqueda en toda la plataforma.

El buscador nunca muestra nada que no puedas abrir.

## Dónde se encuentra

En la parte derecha de la barra superior, junto al botón del tema claro u oscuro, en todas las
pantallas de la zona privada. En pantallas estrechas, como la de un móvil, la lupa está dentro del
menú de tres puntos.

## Qué conviene saber

- Hay que escribir al menos **dos caracteres**; con menos, la ventana muestra
  **Escribe al menos 2 caracteres**. Si nada coincide, verás **Sin resultados**.
- Basta con una parte del nombre: «acme» encuentra «Acme» o «acme-propietario@ejemplo.com».
- Se muestran como máximo **cinco resultados por grupo**, por orden alfabético; si no ves lo que
  buscas, escribe más letras.
- Solo se busca por nombre de usuario y por nombre de cuenta, no en otros datos.
- Las cuentas inactivas aparecen; lo eliminado no aparece nunca.
- El botón **Borrar búsqueda**, a la derecha del campo, vacía lo escrito sin cerrar la ventana. Al
  cerrarla (con su aspa, **Esc** o pulsando fuera) se vacían el texto y los resultados.
- Buscar solo consulta: no modifica ni elimina ningún dato.
- Dentro de la plataforma, **Ctrl + K** abre el buscador en lugar del atajo propio del navegador.
