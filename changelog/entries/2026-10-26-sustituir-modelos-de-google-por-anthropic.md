---
title: Elección entre inteligencia artificial de Google o de Anthropic
date: "2026-10-26 11:21"
category: improvement
summary: Las funciones que generan textos con inteligencia artificial pueden usar ahora un servicio de Anthropic además del de Google, sin cambios en lo que ves ni en cómo trabajas.
plan: 2026-10-26-sustituir-modelos-de-google-por-anthropic
manual: sustituir-modelos-de-google-por-anthropic.pdf
techManual: sustituir-modelos-de-google-por-anthropic.tech.md
published: true
---

## Qué cambia

Algunas funciones de la plataforma redactan informes, traducen textos o hacen sugerencias con
inteligencia artificial. Hasta ahora lo hacía siempre un servicio de Google. Con esta mejora la
plataforma puede apoyarse también en un servicio de Anthropic, y el equipo técnico decide cuál de
los dos se usa al preparar la instalación.

## A quién afecta

A los Administradores, que pueden pedir al equipo técnico que la instalación pase de un servicio a
otro. Para los Propietarios y los Usuarios el cambio es invisible: no hay pantallas ni botones
nuevos, y las funciones de generación de textos se usan igual que antes. Como mucho, notarán
diferencias de estilo o de duración en los textos generados.

## Dónde se encuentra

No hay una pantalla propia ni ninguna opción en los menús: la elección del servicio se hace al
preparar la instalación y no puede cambiarla cada persona. Las funciones que generan textos las
ofrece cada plataforma por separado, y esta mejora solo las deja preparadas para elegir proveedor.

## Qué conviene saber

- Se usa un solo servicio a la vez para toda la instalación y para todas las cuentas. No se alterna
  entre ellos ni se prueba el otro si el primero falla.
- Si el servicio rechaza una petición por motivos de seguridad, la generación falla en lugar de
  reintentarse con otro modelo, para que la incidencia se pueda revisar.
- Los textos largos o que requieren más razonamiento pueden tardar varios minutos. Si la respuesta
  es demasiado extensa o tarda demasiado, la generación puede quedar incompleta y fallar.
- Cada servicio redacta a su manera, así que los textos no serán idénticos con uno y con otro.
  Conviene revisarlos siempre antes de darlos por buenos.
- Cambiar de servicio no afecta a lo ya generado, solo a los textos que se creen a partir de ese
  momento.
- Si una generación falla, vuelve a intentarlo pasados unos minutos y, si se repite, avisa a un
  Administrador.
