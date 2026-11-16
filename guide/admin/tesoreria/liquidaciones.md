---
title: "Liquidaciones"
summary: "Remesa, paga o cancela las liquidaciones a beneficiarios y descarga sus justificantes."
profile: admin
menu: "Tesorería › Liquidaciones"
routes: [admin_payouts, admin_payouts_view, admin_payouts_remit, admin_payouts_remittance, admin_payouts_pay, admin_payouts_cancel, admin_payouts_statement]
updated: "2026-11-14 00:19"
---

# Liquidaciones

Una liquidación agrupa los asientos pendientes de un beneficiario para pagárselos de una vez. Nace como borrador, se remesa al banco y se marca como pagada cuando se hace la transferencia.

> **Dónde está:** menú **Tesorería › Liquidaciones**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Número** | Número de la liquidación. |
| **Beneficiario** | A quién se paga. |
| **Importe** | |
| **Asientos** | Cuántos asientos agrupa. |
| **Periodo** | Fechas que cubre. |
| **Estado** | **Borrador**, **Enviada**, **Pagada**, **Fallida** o **Cancelada**. |
| **Creada** | |

Pulsa **Filtrar** para elegir **Estado** o buscar por **Número** o **Referencia**.

## Preparar la remesa del banco

En el desplegable **Acciones** de arriba:

- **Descargar remesa SEPA** o **Descargar remesa CSV**: fichero con las liquidaciones en borrador para subir al banco.
- **Marcar como remesadas**: pasa a enviadas todas las liquidaciones en borrador, tras confirmar. Si no hay ninguna verás **No hay liquidaciones pendientes de remesar**.

También puedes marcar una sola con **Marcar como remesada** en su fila.

## Marcar una liquidación como pagada

1. Pulsa **Pagar** en la fila.
2. Revisa el **Importe a transferir** y escribe la **Referencia de la transferencia**. Si quieres, adjunta el **Justificante**.
3. Pulsa **Marcar como pagada**. Verás **Liquidación marcada como pagada**.

## Ver una liquidación

Pulsa **Detalles**. Verás **Número**, **Beneficiario**, **IBAN**, **Importe devengado**, **Importe a transferir**, **Estado**, **Referencia de la transferencia** y el **Detalle de asientos** (**Fecha**, **Concepto**, **Proyecto**, **Lote**, **Toneladas**, **Importe**). Pulsa **Descargar justificante** para obtener el PDF.

## Cancelar una liquidación

Pulsa **Eliminar** en la fila y confirma. Verás **Liquidación cancelada**; sus asientos vuelven a quedar pendientes.

## Relacionado

- [Beneficiarios](beneficiarios.md)
- [Libro mayor](libro-mayor.md)
