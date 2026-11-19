---
title: "Comunicación entre plataformas"
date: "2026-11-19 16:29"
category: feature
summary: "Las plataformas de la familia pueden avisarse entre sí de forma automática y fiable, sin copiar datos a mano."
plan: 2026-11-19-integration-events-broker
manual: integration-events-broker.pdf
techManual: integration-events-broker.tech.md
published: true
---

## Qué cambia

Las plataformas de la familia pueden enviarse avisos entre sí cuando ocurre algo relevante, de forma automática y sin que nadie tenga que copiar datos a mano. Los avisos se entregan aunque la plataforma receptora esté parada en ese momento: esperan su turno y se procesan una sola vez y en el orden correcto.

Esta versión incorpora la base de esa comunicación y un aviso de prueba. Todavía no hay avisos de negocio (por ejemplo, de propiedades o cuentas): irán llegando en próximas versiones.

## A quién afecta y dónde se ve

No hay pantallas nuevas, ni menús ni botones. La función viene desactivada y la activa el equipo técnico plataforma a plataforma.

Los administradores podrán ver en **Registro** los cambios aplicados a partir de avisos recibidos, atribuidos a un usuario administrador reservado para esta tarea, no a una persona. Propietarios y usuarios no notan ningún cambio en su forma de trabajar: un dato modificado por un aviso de otra plataforma aparece, sencillamente, ya modificado.

## Qué conviene saber

- **Orden.** Si llegan varios avisos sobre el mismo elemento, prevalece el más reciente; uno más antiguo que llegue tarde se descarta.
- **Sin bucles.** Una cadena de avisos que rebotan entre plataformas se corta tras un número máximo de saltos (cinco por defecto).
- **Usuario administrador.** Solo funciona si existe el usuario administrador reservado. Si falta o deja de serlo, los avisos no se aplican y quedan pendientes de revisión por el equipo técnico.
- **Datos mínimos.** Los avisos llevan identificadores y lo imprescindible, sin datos personales innecesarios.
- **Conservación.** Los avisos ya enviados y recibidos se borran automáticamente cada noche pasados 30 días.
- **Plataforma desactivada.** Una plataforma con la función desactivada no recibe avisos; los que lleguen mientras tanto se pierden.

Si el sistema de envío se cae, las plataformas siguen funcionando con normalidad: los avisos se acumulan y se envían al volver. Pasados diez minutos con avisos sin enviar, el control de estado de la plataforma lo indica para que el equipo técnico actúe. Un aviso que no se pueda aplicar se reintenta varias veces y, si sigue fallando, queda apartado para su revisión.
