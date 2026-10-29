---
title: "Transferir crédito a varias cuentas a la vez"
date: "2026-10-29 15:19"
category: improvement
summary: "Reparte el crédito de un lote a partes iguales entre varias cuentas en una sola operación, sin repetir la transferencia cuenta por cuenta."
plan: 2026-10-29-credit-transfer-multiple-accounts
manual: credit-transfer-multiple-accounts.pdf
techManual: credit-transfer-multiple-accounts.tech.md
published: true
---

## Qué cambia

La pantalla **Transferir crédito** permite ahora elegir varias cuentas de destino a la vez. Las toneladas se reparten a partes iguales, con hasta seis decimales, y cada cuenta recibe su propio lote del mismo número de serie, con su número de lote y su parte del importe. Es útil, por ejemplo, para repartir en un solo paso el crédito de un lote pagado por la plataforma entre todas las propiedades de un grupo hotelero.

La operación es de todo o nada: si algo falla, no se transfiere nada a ninguna cuenta.

## A quién afecta

A los administradores, que son quienes pueden transferir crédito. Los propietarios y usuarios no tienen acceso a la pantalla, pero ven el crédito recibido en su cuenta como un lote nuevo.

## Dónde se encuentra

En **Proyectos → Créditos de CO2**, con el icono **Transferir crédito** de la fila del lote, o desde la ficha del lote con el botón **Transferir crédito**. En **Cuentas de destino** se marcan una o varias cuentas (la cuenta del lote de origen no aparece en la lista) y, si se desea, se indica en **Toneladas a transferir** el total a repartir. Si se deja vacío, se reparte todo el crédito disponible.

## Qué conviene saber

- Si se indican más toneladas de las disponibles, se transfieren solo las disponibles; el mensaje de éxito muestra lo realmente transferido.
- Si el reparto no es exacto, el pequeño resto se queda en el lote de origen cuando se han indicado menos toneladas que las disponibles. Si se reparte todo, se suma a la última cuenta de la lista.
- Cada cuenta debe recibir al menos una millonésima de tonelada.
- Las cuentas que no estaban adheridas al proyecto se adhieren automáticamente, y sus aportaciones pendientes usan el crédito recibido.
- Una transferencia no se puede deshacer; para revertirla hay que transferir de nuevo.
- Solo se pueden transferir lotes validados, pagados por la plataforma, con número de serie y con crédito libre.

## Corrección incluida

Cuando un mismo lote transfería más de una vez, las cuentas posteriores podían aparecer con más toneladas de las transferidas, y sus alertas de crédito se calculaban sobre esa cifra. Ahora cada lote recibido corresponde exactamente a lo transferido, y su barra de crédito muestra como «Otros lotes del serial» la parte que pertenece a otras cuentas. Se incluye además una herramienta para detectar y reparar los lotes ya afectados.
