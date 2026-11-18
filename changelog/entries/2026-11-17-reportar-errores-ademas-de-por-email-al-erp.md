---
title: "Aviso de errores al ERP"
date: "2026-11-17 07:52"
category: feature
summary: "Cuando la plataforma falla, el error puede llegar al ERP como un ticket automático, además de (o en lugar de) por correo."
plan: 2026-11-16-reportar-errores-ademas-de-por-email-al-erp
manual: reportar-errores-ademas-de-por-email-al-erp.pdf
techManual: reportar-errores-ademas-de-por-email-al-erp.tech.md
published: true
---

## Qué cambia

Cuando la plataforma sufre un error grave, avisa a quien la mantiene. Hasta ahora ese aviso era un correo con los detalles del fallo. A partir de ahora, cada plataforma puede además crear automáticamente un ticket en el ERP, en el proyecto que se indique.

Cada plataforma decide a dónde van sus avisos: solo por correo (como siempre), solo al ERP, a los dos o a ninguno. Si el mismo error se repite mientras su ticket sigue abierto, el ERP lo acumula como una repetición de ese ticket en lugar de crear uno nuevo cada vez.

Se avisa de los mismos fallos que antes: las páginas que terminan en un error interno y cualquier tarea automática que falle. El aviso lleva los mismos datos que el correo, con las contraseñas y datos similares ocultos.

## A quién afecta

- **Responsables de la plataforma:** deciden si los avisos van por correo, al ERP, a ambos o a ninguno, y configuran la conexión con el ERP.
- **Equipo que atiende el proyecto en el ERP:** recibe un ticket automático por cada error nuevo, con los detalles necesarios para investigarlo.
- **Administradores, Propietarios y Usuarios:** no notan ningún cambio; ven la misma página de error de siempre.

## Dónde se encuentra

Es un ajuste de configuración del servidor, sin ninguna pantalla en la plataforma. Lo cambia el equipo técnico; los detalles están en el manual técnico adjunto.

## Qué conviene saber

- Las plataformas existentes no cambian: el aviso al ERP queda apagado y los errores siguen llegando solo por correo hasta que se configure.
- Hacen falta cuatro datos: dirección del ERP, usuario, clave de acceso (API token) y proyecto. Si falta alguno, no se envía nada al ERP y se anota en el registro interno.
- Cada canal tiene su propio límite de repeticiones (15 minutos por defecto) y un fallo de uno no afecta al otro.
- No hay segundo intento: si el ERP no responde, ese aviso se pierde y queda anotado en el registro. Si además el correo está desactivado, el error solo queda en el registro.
- El envío es inmediato, así que si el ERP está caído o lento la página de error puede tardar algo más en mostrarse (hasta unos 15 segundos en el peor caso).
