---
title: Propiedades y Empleados, opcionales en cada instalación
date: "2026-10-15 16:31"
category: improvement
summary: Las instalaciones que no trabajan con establecimientos ni con personal pueden prescindir de Propiedades y Empleados, y su menú solo muestra lo que se usa.
plan: 2026-10-15-integrar-reporting
manual: integrar-reporting.pdf
techManual: integrar-reporting.tech.md
published: true
---

## Qué cambia

Los grupos **Propiedades** y **Empleados** pasan a ser opcionales en cada instalación de la
plataforma. Una instalación que no trabaja con establecimientos ni con personal deja de mostrar
pantallas que nadie va a usar. Las dos piezas son independientes: una instalación puede tener solo
Propiedades, solo Empleados, las dos o ninguna.

Lo habitual es que estén activadas. En ese caso la plataforma funciona igual que siempre.

## A quién afecta

- **Administradores.** Sin Propiedades, desaparece el grupo **Propiedades** completo (Propiedades,
  Empresas, Categorías, Marcas, Países y Destinos). Sin Empleados, desaparece **Empleados** dentro
  de «Administración».
- **Propietarios.** Sin Propiedades, desaparece el grupo **Propiedades** (Propiedades y Empresas).
  Sin Empleados, en «Administración» solo queda **Usuarios**.
- **Usuarios.** Nada cambia: su menú nunca ha incluido estas opciones.

## Dónde se nota

- **Menú lateral.** Las opciones desactivadas no aparecen; el resto del menú y su orden no cambian.
- **Buscador de la barra superior (Ctrl+K).** El grupo «Acciones» tampoco ofrece esas pantallas.
- **Enlaces y marcadores guardados.** Si apuntan a una de esas pantallas, llevan a la página «Esta
  página no existe». No es un fallo ni un problema de permisos.
- **Alta de usuarios.** Con Empleados activado, al registrar un usuario en una cuenta se vincula con
  el empleado de su mismo email o se crea uno nuevo. Con Empleados desactivado, esto no ocurre.

## Qué conviene saber

- **No se activa desde la plataforma.** Ninguna pantalla permite encender o apagar Propiedades o
  Empleados: lo decide el equipo técnico que mantiene la instalación.
- **No se borra nada.** Desactivarlos solo oculta las pantallas; si se vuelven a activar, las
  propiedades, empresas y empleados que hubiera aparecen de nuevo.
- **Los usuarios registrados mientras tanto no tienen empleado.** Si se activan los empleados más
  tarde, regístralo desde **Administración → Empleados** con el mismo email del usuario y se
  vincularán.
- **El resto de la plataforma funciona igual.** Cuentas, usuarios, biblioteca, notificaciones y
  novedades no cambian.
