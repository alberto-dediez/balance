---
title: "Importar y exportar"
summary: "Cómo funcionan los botones Importar y Exportar de los listados: el asistente de importación y la descarga de ficheros."
profile: admin
menu: "General"
routes: [import, import_pair_fields, import_translations, import_assign_values, import_finish, import_view, export, export_download, job_progress]
updated: "2026-11-14 00:19"
---

# Importar y exportar

Muchos listados tienen los botones **Importar** y **Exportar**. Los dos funcionan igual en todas las pantallas: este artículo explica el proceso común.

## Exportar un listado

1. En el listado, pulsa **Exportar**.
2. En **Filtrar datos**, elige qué registros quieres sacar (los filtros son los mismos del listado).
3. En **Columnas y formato**, marca las **Columnas a exportar** y elige el **Formato**: **XLSX**, **CSV** o **PDF** (según la pantalla).
4. Pulsa **Exportar**.

Verás **Exportación en curso** y el aviso **La exportación se está generando. Recibirás un email con el enlace de descarga cuando esté lista.** Cuando termine te llegará un aviso con el enlace **Descargar fichero**. El enlace caduca pasado un tiempo.

Si algo falla al empezar verás **No se ha podido iniciar la exportación.**

## Importar desde una hoja de cálculo

La importación es un asistente de varios pasos:

1. **Subir el fichero.** Pulsa **Importar**, elige el **Fichero a importar** (CSV o Excel) y, si tu fichero tiene filas de cabecera o títulos que no son datos, indica cuántas en **Líneas a ignorar del inicio del fichero**. Pulsa **Importar**.
2. **Emparejar campos.** Para cada **Columna** de tu fichero verás unos **Ejemplos** de su contenido; elige en **Campo** a qué dato de la plataforma corresponde, o **Ignorar** si no quieres usarla. Pulsa **Continuar**.
3. **Traducir valores.** Si alguna columna contiene valores que deben coincidir con una lista de la plataforma (por ejemplo, un tipo o una unidad), verás cada **Valor original** y elegirás su equivalente en **Asignar valor**.
4. **Asignar valores.** Para los campos que no has emparejado con ninguna columna (**Campo no emparejado**) puedes fijar un valor para todas las filas en **Asignar valor**.
5. **En curso.** Verás **Importación en curso**: el proceso sigue en segundo plano y puedes seguir trabajando. Te avisaremos por correo al terminar, con el enlace **Ver resultado**.
6. **Resultados.** En **Resultado de la importación** ves qué filas se han importado y cuáles han dado error.

Los pasos que no hacen falta para un fichero concreto se saltan solos. La plataforma recuerda cómo emparejaste las columnas para proponértelo la próxima vez.

## Conviene saber

- Si el fichero no es válido verás **Por favor, sube un fichero CSV o Excel válido.** o **El fichero no es válido.**
- Si un paso tiene errores verás **Hay errores en el emparejamiento. Por favor, revise el formulario.** o **Hay errores en la configuración de los distintos campos. Por favor, revise el formulario.**
- Si la importación termina mal verás **No se ha importado el fichero correctamente. Revise la configuración o contacte con un administrador.**

## Relacionado

- [Usuarios](../administracion/organizacion/usuarios.md)
- [Materiales](../maestros/materiales/materiales.md)
