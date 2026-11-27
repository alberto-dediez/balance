---
title: "Copia de seguridad de la biblioteca"
date: "2026-11-24 18:13"
category: feature
summary: "Cada día se copian a un segundo almacén los archivos de la biblioteca, para no perder nada aunque se eliminen por error o falle el almacenamiento principal."
plan: 2026-11-24-backup-de-ficheros
manual: backup-de-ficheros.pdf
techManual: backup-de-ficheros.tech.md
published: true
---

## Qué cambia

La plataforma hace ahora una copia diaria de todos los archivos de la biblioteca en un segundo lugar. Así, si alguien elimina un archivo por error, o el almacenamiento principal deja de estar disponible, el archivo se puede recuperar.

La copia funciona sola, de madrugada (04:00, hora UTC), y no tiene pantallas ni botones. Cada archivo se copia una sola vez; los que ya estaban copiados no se vuelven a tocar.

## Archivos eliminados

Cuando se elimina un archivo de la biblioteca, su copia no desaparece al momento: se conserva 30 días por si hay que recuperarlo y pasado ese plazo se borra definitivamente. El equipo técnico puede cambiar el plazo o hacer que esas copias no se borren nunca.

## A quién afecta

- **Propietarios y Usuarios**: no ven ningún cambio. Siguen subiendo, descargando y eliminando archivos como siempre.
- **Administradores (SuperAdmin)**: si una copia diaria termina con errores, reciben en sus notificaciones el aviso «La copia de seguridad de la biblioteca ha fallado», con el número de archivos sin copiar y el motivo del primer fallo. Como máximo llega un aviso al día.
- **Administradores**: no reciben el aviso; si detectan un problema, deben trasladarlo a un SuperAdmin o al equipo técnico.
- **Equipo técnico**: activa la copia, lanza la primera a mano y se encarga de recuperar archivos.

## Dónde se ve

La copia no tiene pantalla propia. Lo único visible es el aviso de fallo, que aparece en Notificaciones → Mis notificaciones y se anuncia en la campana de la barra superior. Si no llega ningún aviso, la copia ha terminado bien.

## Qué conviene saber

- La copia solo existe si el equipo técnico la ha activado.
- Un archivo nuevo no queda protegido hasta la siguiente copia diaria, y uno subido y eliminado antes de esa copia nunca llega a copiarse.
- Si el almacenamiento principal y el de la copia están en la misma cuenta del proveedor, la copia protege de borrados accidentales pero no de perder esa cuenta.
- En una biblioteca grande, la primera copia tarda mucho: la lanza el equipo técnico a mano antes de fiarse de la copia diaria.
- Recuperar un archivo no se hace desde la plataforma: hay que pedirlo al equipo técnico. Un archivo eliminado se entrega recuperado y hay que volver a subirlo con Subir archivo.
