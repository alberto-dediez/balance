---
title: "Créditos de CO2"
summary: "Valida o rechaza las solicitudes de créditos de las cuentas, asigna créditos y transfiere remanentes."
profile: admin
menu: "Proyectos › Créditos de CO2"
routes: [admin_credit_purchases, admin_credit_purchases_register, admin_credit_purchases_view, admin_credit_purchases_validate, admin_credit_purchases_reject, admin_credit_purchases_transfer]
updated: "2026-11-14 00:19"
---

# Créditos de CO2

Un crédito son las toneladas de compensación compradas para una cuenta en un proyecto. Las cuentas con compensación solicitan créditos; tú los validas (indicando lo que realmente se ha comprado) o los rechazas. También puedes asignar créditos directamente y transferir lo que sobre de un crédito a otras cuentas.

> **Dónde está:** menú **Proyectos › Créditos de CO2**. La pantalla se titula **Compras de créditos de CO2**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Cuenta** | Cuenta del crédito. |
| **Proyecto** | Proyecto en el que se compra. |
| **Solicitadas** | Toneladas pedidas. |
| **Compradas** | Toneladas realmente compradas. Si son menos de las pedidas verás **Parcial**. |
| **Importe** | Coste del lote. |
| **Tipo de pago** | **Pagado por el cliente** o **Pagado por Bioscore**. |
| **Identificador** | Identificador de la compra y número de lote. |
| **Estado** | **Pendiente**, **Validada**, **Rechazada** o **Cancelada**. |
| **Origen** | **Solicitado**, **Asignado por el administrador** o **Recibido por transferencia**. |
| **Fecha** | |

Pulsa **Filtrar** para elegir **Proyecto**, **Estado**, **Origen**, **Identificador** o **Con toneladas disponibles** (por defecto se muestran los que aún tienen toneladas).

## Validar una solicitud

1. Pulsa **Editar** en una solicitud **Pendiente**. Verás la **Solicitud** (**Cuenta**, **Proyecto**, **Código Registro Miteco**, **Toneladas solicitadas**, **Coste por tonelada**).
2. En **Validación** rellena:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
   | **Toneladas compradas** | Sí | Pueden ser menos que las solicitadas. |
   | **Coste del lote** | No | |
   | **Tipo de pago** | Sí | |
   | **Identificador de la compra** | Sí | Referencia o número de serie del certificado. |
   | **Certificado** | No | Documento de la compra. |

3. Pulsa **Validar compra**. Verás **Compra validada correctamente** o **Compra validada parcialmente**.

## Rechazar una solicitud

Pulsa **Eliminar** en una solicitud pendiente, escribe el **Motivo del rechazo** y pulsa **Rechazar solicitud**. Verás **Solicitud rechazada correctamente**.

## Asignar créditos sin solicitud

1. Pulsa **Asignar créditos**.
2. Elige la **Cuenta** y el **Proyecto**, y rellena **Toneladas asignadas**, **Tipo de pago**, **Identificador de la compra** y, si quieres, **Coste del lote** y **Certificado**.
3. Pulsa **Asignar créditos**. Verás **Créditos asignados correctamente**.

Si la cuenta tenía una solicitud pendiente para ese proyecto, en lugar de crear un crédito nuevo se valida esa solicitud con estos datos.

## Ver un crédito y su reparto

Pulsa **Detalles**. Verás las toneladas compradas, compensadas, transferidas y libres, y el **Reparto del crédito** entre las aportaciones que lo han consumido.

## Transferir un crédito

Pulsa **Transferir crédito** (en la fila o en el detalle) en un crédito validado con toneladas libres.

1. Revisa el **Lote de origen**: cuenta, identificador, **Toneladas del lote** y **Disponibles para transferir**.
2. Elige las **Cuentas de destino** (una o varias).
3. Escribe las **Toneladas a transferir** o déjalo vacío para repartir todo el remanente. Las toneladas se reparten a partes iguales entre las cuentas.
4. Pulsa **Transferir crédito**.

Cada cuenta recibe un lote nuevo con el mismo identificador. Si no queda nada que transferir verás **Esta compra no tiene crédito disponible para transferir**.

## Relacionado

- [Proyectos](proyectos.md)
- [Aportaciones](aportaciones.md)
