---
title: "Google Drive como almacén de la biblioteca"
date: "2026-11-24 19:00"
category: feature
summary: "Los archivos de la biblioteca, y sobre todo su copia de seguridad diaria, pueden guardarse ahora en Google Drive, fuera del servidor y del almacén principal."
plan: 2026-11-24-nuevo-media-storage-google-drive
manual: nuevo-media-storage-google-drive.pdf
techManual: nuevo-media-storage-google-drive.tech.md
published: true
---

## Qué cambia

La plataforma puede guardar los archivos de la biblioteca en Google Drive, además de en el servidor o en el almacén en la nube que ya existían. Su uso habitual es guardar ahí la copia de seguridad diaria, de modo que una copia de todos los archivos quede fuera del servidor y fuera del almacén principal. En algunos proyectos, Drive también puede ser el lugar donde se guardan los archivos del día a día.

## A quién afecta

La elección y la puesta en marcha las hace el equipo técnico. No hay ninguna pantalla nueva ni ningún botón que pulsar. Los administradores siguen subiendo, viendo, descargando y eliminando archivos desde **Biblioteca** igual que antes, y propietarios y usuarios no notan ninguna diferencia.

## Qué conviene saber

- **No toques la carpeta de Drive a mano.** La plataforma espera encontrar los archivos con los nombres que ella misma les ha puesto. Si se renombran, se mueven o se eliminan desde Drive, dejará de encontrarlos.
- **Eliminar no es borrar del todo.** Un archivo eliminado en la plataforma pasa a la papelera de Drive, que se vacía sola a los 30 días. Hasta entonces sigue ocupando espacio y puede recuperarse a mano; para volver a verlo en la biblioteca hay que subirlo de nuevo.
- **Conservación de la copia.** La copia de un archivo eliminado se conserva los días que haya configurado el equipo técnico (30 por defecto) y después pasa también a la papelera de Drive.
- **Carpetas distintas.** La copia y el almacén principal no pueden usar la misma carpeta.
- **Cuenta de Google.** Hace falta una unidad compartida de Google Workspace; no sirve una cuenta personal.
- **Algo más de espera.** Si Drive es el almacén principal, abrir o descargar archivos grandes puede tardar más que desde el servidor.

## Qué ocurre después

Cada día se copian a Drive los archivos que aún no tienen copia; la primera vez tarda más porque se copian todos. Si Drive es el almacén principal y no responde al subir un archivo, este se guarda temporalmente en el servidor y pasa a Drive cuando vuelve a funcionar; los administradores reciben los avisos correspondientes. Si la copia falla, los usuarios con rol SuperAdmin reciben el aviso «La copia de seguridad de la biblioteca ha fallado» y el proceso continúa en la siguiente pasada diaria.
