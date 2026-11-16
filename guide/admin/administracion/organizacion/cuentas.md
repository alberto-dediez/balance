---
title: "Cuentas"
summary: "Alta, edición, funcionalidades, personalización y baja de las cuentas de clientes."
profile: admin
menu: "Administración › Organización › Cuentas"
routes: [admin_accounts, admin_accounts_register, admin_accounts_edit, admin_accounts_remove, admin_accounts_branding_edit, branding_preview, admin_accounts_impersonate, admin_accounts_impersonate_exit]
updated: "2026-11-14 00:19"
---

# Cuentas

Cada cliente de la plataforma es una cuenta. Aquí das de alta las cuentas, decides qué funcionalidades tienen, si son consultoras o distribuidoras, y personalizas su imagen.

> **Dónde está:** menú **Administración › Organización › Cuentas**.

## Qué ves en la pantalla

| Columna | Qué significa |
|---|---|
| **Nombre** | Nombre de la cuenta. |
| **Activa** | Si la cuenta está activa. |
| **Es consultora** | Si es una cuenta consultora, que asesora a otras. |
| **Consultora** | Cuenta consultora asignada a esta cuenta, si tiene. |
| **Es distribuidora** | Si es una cuenta distribuidora. |
| **Tokens de API** | Si sus usuarios pueden crear tokens de API. |
| **Fecha de registro** | Cuándo se dio de alta. |

Pulsa **Filtrar** para buscar por **Nombre**, **Activa**, **Es consultora**, **Consultora** o **Es distribuidora**.

## Registrar una cuenta

1. Pulsa **Registrar**.
2. Rellena el formulario:

   | Campo | Obligatorio | Qué poner |
   |---|---|---|
   | **Nombre** | Sí | Nombre del cliente. |
   | **Activa** | Sí | Si la cuenta puede usarse ya. |
   | **Grupo de coeficientes por defecto** | No | Conjunto de factores de emisión que usarán sus cálculos. |
   | **Es consultora** | Sí | Márcalo si esta cuenta asesorará a otras. |
   | **Es distribuidora** | Sí | Márcalo si es una cuenta distribuidora. |

3. Pulsa **Registrar**. Verás **Cuenta creada correctamente** con su nombre.

## Editar una cuenta

Pulsa **Editar** en la fila. Además de los datos del alta encontrarás:

- **Consultora**: la cuenta consultora que la asesora (**Sin consultora** si ninguna). El propietario de esa consultora podrá entrar en esta cuenta y trabajar en ella.
- **Permitir tokens de API**: deja que sus usuarios creen tokens de API.
- Bloque **Informe de huella de carbono**:
  - **Puede editar los textos del informe de huella**.
  - **Autorrevisión del informe de huella**: los informes no necesitan que un administrador los revise.
- Bloque **Funcionalidades**, que decide qué menús y pantallas ve la cuenta:
  - **Calculadora de viajes de la cuenta**
  - **Calculadoras de viajes de las propiedades**
  - **Eventos**
  - **Compensación (proyectos, créditos, aportaciones y saldo)**
  - **Huella de carbono de propiedades y empresas (alcances 1, 2, 3 y materialidad)**

Pulsa **Guardar** o **Guardar y salir**. Verás **Cuenta actualizada con éxito**.

## Personalizar la imagen de la cuenta

Pulsa **Personalización** en la fila. En esta pantalla defines cómo se ven las páginas públicas de la cuenta (encuestas, calculadoras, certificados):

- **Identidad**: **Tema visual** y **Logotipo** (de la biblioteca).
- **Gama de colores**: **Color principal**, **Color secundario**, de éxito, informativo, de aviso, de error, claro, oscuro, del texto y de fondo. Deja un color vacío para heredarlo del tema. Si dos colores no contrastan lo suficiente verás **Contraste insuficiente**.
- **Avanzado: tonos derivados**: tonos al pasar el ratón y atenuados. Si los dejas vacíos se calculan solos.
- **Tipografía web** (una familia de Google Fonts, por ejemplo «Poppins»), **Grosores de la tipografía web** (por ejemplo «400;600;700») y **Tipografía del certificado PDF**.

Pulsa **Vista previa** para ver botones, avisos, textos y formularios con esos colores, y **Guardar** para aplicarlos.

## Eliminar cuentas

- **Una:** pulsa **Eliminar** en la fila y confirma. Verás **Se ha eliminado la cuenta** con su nombre.
- **Varias:** marca sus casillas, elige **Eliminar** en **Acción a realizar** y pulsa **Realizar**.

## Asignar una consultora a varias cuentas

Marca las cuentas, elige **Asignar consultora** en **Acción a realizar**, escoge la consultora (o **Sin consultora** para quitarla) y pulsa **Realizar**. Verás **Se ha asignado la consultora seleccionada a la cuenta** por cada una.

## Adherir cuentas a un distribuidor

Si tu plataforma lo tiene activado, marca las cuentas, elige **Asignar distribuidor** en **Acción a realizar**, escoge el **Distribuidor** y pulsa **Realizar**. Verás **Se ha asignado el distribuidor seleccionado a la cuenta** por cada una. Todas las propiedades de esas cuentas, presentes y futuras, quedan adheridas al distribuidor.

## Conviene saber

- Para trabajar dentro de una cuenta usa el selector **Cambiar de cuenta** de la barra superior, si tu plataforma lo tiene activado.
- Si un formulario tiene errores verás **El formulario contiene errores, por favor, reviselos antes de continuar.**

## Relacionado

- [Usuarios](usuarios.md)
- [Barra superior](../../general/barra-superior.md)
- [Grupos de coeficientes](../../maestros/grupos-de-coeficientes.md)
- [Distribuidores](../../propiedades/distribuidores.md)
