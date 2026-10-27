---
title: "Permisos del informe al trabajar en cuentas gestionadas"
date: "2026-10-27 09:35"
category: security
summary: "Al impersonar una cuenta, el informe de huella se tramita con los permisos de tu propia cuenta (consultora o Administrador) y no con los de la cuenta impersonada."
plan: 2026-10-26-alcance-1-y-2-editar-informe-en-base-a-los-permisos-de-la-empresa-principal
manual: alcance-1-y-2-editar-informe-en-base-a-los-permisos-de-la-empresa-principal.pdf
techManual: alcance-1-y-2-editar-informe-en-base-a-los-permisos-de-la-empresa-principal.tech.md
published: true
---

## Qué cambia

Hasta ahora, cuando una consultora o un Administrador entraba en otra cuenta mediante la impersonación y completaba una huella, el informe se tramitaba con los permisos de la cuenta en la que estaba trabajando. Una consultora de confianza acababa pasando por el circuito completo de revisión de un Administrador en las cuentas que gestiona.

Desde ahora se aplican los permisos de informe de **tu propia cuenta**: **Puede editar los textos del informe de huella** y **Autorrevisión del informe de huella**. Sustituyen por completo a los de la cuenta impersonada, en los dos permisos; no se combinan ni se elige el más permisivo. Vale para las huellas de evento, de propiedad y de empresa.

## A quién afecta

- **Propietarios de una consultora**: al impersonar una cuenta que tienen asignada, se aplican los permisos de la consultora.
- **Administradores**: al impersonar una cuenta, se aplican los permisos de la cuenta del propio Administrador. Si no tiene cuenta, los de la cuenta impersonada.
- **Propietarios de una cuenta que no impersonan**: no cambia nada, siguen con los permisos de su cuenta.

## Dónde se encuentra

Los permisos los configura un Administrador en **Administración → Cuentas**, al editar la cuenta. El informe se revisa desde la pantalla **Resultados** de la huella, con el botón **Revisar textos del informe**.

Además, ese botón ahora solo aparece a quien realmente puede editar los textos. El aviso «Los textos del informe están listos para revisar.» se sigue mostrando a todos.

## Qué conviene saber

- Con permiso para editar textos, el informe queda en borrador. Sin él y con autorrevisión, se aprueba automáticamente. Sin ninguno de los dos, se envía a revisión de la plataforma.
- Si la consultora completa la huella y la cuenta gestionada no puede editar textos, solo la consultora puede cerrar el borrador.
- El aviso de textos listos le llega a quien completó la huella.
- Una consultora solo influye en las cuentas que tiene asignadas y mientras está activa.
- Aprobar o rechazar informes desde **Informes de huella** sigue siendo solo de Administradores.
