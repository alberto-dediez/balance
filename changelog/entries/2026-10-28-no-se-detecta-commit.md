---
title: "Versión instalada en cada despliegue"
date: "2026-10-28 18:04"
category: fix
summary: "El aviso de resultado de cada despliegue indica ya la versión real instalada, también en los servidores gestionados con Plesk."
plan: 2026-10-28-no-se-detecta-commit
manual: no-se-detecta-commit.pdf
techManual: no-se-detecta-commit.tech.md
published: true
---

## Qué cambia

Cada vez que se instala una versión nueva de la plataforma en un servidor, se envía un aviso con el resultado. Hasta ahora, en los servidores gestionados con Plesk, ese aviso siempre indicaba la versión como «desconocido». A partir de ahora lleva el código corto de la versión realmente instalada, y el mismo código aparece entre paréntesis en la primera línea de cada despliegue del registro del servidor.

Gracias a ello se puede saber qué versión está en producción y relacionar un fallo con la versión que lo provocó.

## A quién afecta

Solo a los administradores que configuran los servidores. Propietarios y usuarios no ven ningún cambio, porque es una función de mantenimiento.

## Cómo se activa

Se configura una sola vez en cada servidor, en el fichero de ajustes locales del proyecto, junto a la dirección del aviso de resultado:

1. Localizar la carpeta donde Plesk guarda el repositorio del proyecto.
2. Añadir el ajuste `DEPLOY_GIT_DIR` con esa ruta.
3. Lanzar un despliegue con normalidad desde Plesk y comprobar que el aviso lleva ya la versión real.

Opcionalmente, el ajuste `DEPLOY_GIT_REF` permite indicar de qué rama se toma la versión, para el caso en que el repositorio de Plesk apunte a una rama distinta de la desplegada.

## Qué conviene saber

- Si no se configura nada, el despliegue funciona igual que antes y la versión se informa como «desconocido». Un despliegue nunca se detiene por no poder averiguar la versión.
- Si la versión no se puede determinar, justo antes de la primera línea del despliegue en el registro aparece un aviso que explica el motivo: falta el ajuste, la ruta no es un repositorio legible o la rama indicada no existe.
- Si el proyecto tiene su propio repositorio en la carpeta (por ejemplo, al desplegar a mano fuera de Plesk), se usa ese aunque el ajuste esté definido.
- La ruta hay que indicarla expresamente: no se detecta automáticamente.
- Si se relanza un despliegue a mano después de subir código que Plesk aún no ha instalado, se informará la versión más reciente subida, no la de los ficheros instalados.
