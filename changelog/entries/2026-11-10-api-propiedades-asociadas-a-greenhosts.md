---
title: "Consulta automática de las propiedades de un distribuidor"
date: "2026-11-10 12:55"
category: feature
summary: "Una cuenta distribuidora, como Greenhost, puede obtener desde otro sistema el listado completo de sus propiedades propias y adheridas, sin entrar en la plataforma."
plan: 2026-11-10-api-propiedades-asociadas-a-greenhosts
manual: api-propiedades-asociadas-a-greenhosts.pdf
techManual: api-propiedades-asociadas-a-greenhosts.tech.md
published: true
---

## Qué cambia

Una cuenta distribuidora, como Greenhost, ya puede pedir desde otro sistema el listado de todas las propiedades que tiene a su cargo, sin necesidad de entrar en la plataforma. El resultado es exactamente el mismo conjunto que su Propietario ve en la pantalla **Propiedades**: las propiedades propias de la distribuidora y las propiedades adheridas que un Administrador le asignó con **Asignar distribuidor**, ya sea por cuenta completa (presentes y futuras) o propiedad a propiedad. Una propiedad nueva de una cuenta adherida aparece sola, sin ningún paso adicional.

## A quién afecta

- Los **Administradores** pueden consultar las propiedades de cualquier cuenta distribuidora.
- El **Propietario de la distribuidora** puede consultar únicamente las de su propia cuenta.
- Los Propietarios de otras cuentas y los Usuarios no tienen acceso.

## Dónde se encuentra

Esta función no añade ningún menú ni botón: la usa el equipo técnico que conecta otro sistema con la plataforma, identificándose con un usuario de la plataforma y facilitando el identificador de la cuenta distribuidora. Para una consulta manual, el mismo listado sigue estando en **Propiedades** con el usuario Propietario de la distribuidora. Solo está disponible si la plataforma tiene activada la gestión de propiedades.

## Qué conviene saber

- El listado se entrega de una sola vez, ordenado por nombre, sin páginas ni límite de cantidad.
- De cada propiedad se reciben su identificador, nombre, cuenta, tipo, dirección, código postal, destino, país, régimen de explotación y contacto del operador. La cuenta permite distinguir las propias de las adheridas.
- Una propiedad adherida solo aparece si su cuenta tiene activa la funcionalidad «Calculadoras de viajes de las propiedades»; las propias de la distribuidora aparecen siempre.
- Las propiedades eliminadas y las de cuentas no adheridas nunca se incluyen.
- La cuenta debe estar marcada como «Es distribuidora», activa y no eliminada; si no, la consulta responde que no se ha encontrado. Si no hay permiso o no se ha identificado, se rechaza como prohibida.
- Si la distribuidora no tiene propiedades ni asignaciones, se recibe un listado vacío.
- No hay filtros por nombre, país ni destino, y la consulta solo sirve para leer: las asignaciones se siguen gestionando desde la Administración.
