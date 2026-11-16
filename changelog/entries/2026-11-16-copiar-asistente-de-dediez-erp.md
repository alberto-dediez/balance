---
title: "Asistente IA para administradores"
date: "2026-11-16 12:30"
category: feature
summary: "Un chat en panel lateral con el que los administradores consultan los datos de la plataforma y piden acciones que se ejecutan solo tras aprobar un plan."
plan: 2026-11-16-copiar-asistente-de-dediez-erp
manual: copiar-asistente-de-dediez-erp.pdf
techManual: copiar-asistente-de-dediez-erp.tech.md
published: true
---

## Qué cambia

Los administradores disponen ahora de un **Asistente IA**: un chat que se abre en un panel lateral, disponible en cualquier pantalla, con el que pueden preguntar por los datos de la plataforma y pedir que se hagan cosas por ellos.

Las preguntas se responden al momento y el asistente solo mira, nunca cambia nada. Cuando se le pide una acción, como registrar una cuenta, no la hace por su cuenta: propone un plan con los pasos y no se ejecuta nada hasta que se pulsa **Ejecutar**. Cada paso se comprueba contra el plan aprobado.

## A quién afecta

Solo a los usuarios con rol Administrador o Super administrador, y siempre que la instalación lo tenga activado. Propietarios y Usuarios no ven el asistente. Si se está suplantando una cuenta o a otro usuario, el asistente desaparece.

## Dónde se encuentra

- El icono de burbuja de chat de la barra superior, o la tecla **A** fuera de un campo de texto.
- **Seguridad → Auditoría → Consumo del asistente**, con el gasto, los mensajes y los tokens por día, usuario y modelo.

## Qué puede hacer

- Consultar usuarios, cuentas, novedades, archivos de la biblioteca, notificaciones propias, registro de actividad y solicitudes de firma, además de buscar un nombre en toda la plataforma.
- Con plan previo: registrar usuarios, registrar cuentas y cambiar el nombre o el estado de una cuenta.
- Leer ficheros adjuntos (hasta 5 por mensaje).

## Qué conviene saber

- Nunca elimina nada ni cambia roles, contraseñas, segundo factor, tokens de API o suplantación.
- Un plan tiene hasta 15 pasos y caduca si no se ejecuta a tiempo. Si un paso falla, el plan se detiene y lo ya hecho no se deshace.
- Cada administrador tiene un límite de gasto diario.
- Lo que lee el asistente viaja a un proveedor de inteligencia artificial, por lo que no accede al contenido de los eventos, al cuerpo de los emails ni a los tokens de API.
- Lo ejecutado queda en el registro de actividad con la etiqueta **Asistente IA**.
- Las conversaciones son privadas y se eliminan tras 90 días sin actividad.

También se estrenan los atajos de teclado: **Ctrl+F1** abre su ayuda, **Ctrl+K** busca y **Ctrl+Enter** envía el formulario activo.
