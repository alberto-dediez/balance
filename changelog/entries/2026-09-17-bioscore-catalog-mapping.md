---
title: Equivalencias de los maestros de Bioscore
date: "2026-09-17 10:44"
category: feature
summary: La plataforma sincroniza a diario los catálogos de Bioscore y los empareja con sus propios maestros, con cuatro pantallas para completar o corregir las equivalencias pendientes.
plan: 2026-09-17-bioscore-catalog-mapping
manual: bioscore-catalog-mapping.pdf
techManual: bioscore-catalog-mapping.tech.md
published: true
---

## Qué cambia

Bioscore registra los consumos anuales de las propiedades: la electricidad por comercializadora,
los combustibles de las instalaciones y de los vehículos, y las fugas de gas refrigerante. Para
poder convertir esos consumos en emisiones, la plataforma necesita saber a qué combustible,
transporte, gas o comercializadora de sus propios maestros corresponde cada elemento del catálogo
de Bioscore. Esta funcionalidad construye y mantiene esa tabla de equivalencias.

Todos los días, a las seis de la mañana, la plataforma descarga los cuatro catálogos de Bioscore
(**Combustibles de instalaciones**, **Combustibles de vehículos**, **Gases refrigerantes** y
**Comercializadoras eléctricas**), da de alta lo nuevo, actualiza lo que ya conocía y marca como
**Retirado** lo que Bioscore ha dejado de ofrecer, sin perder su equivalencia. Cuando un elemento
tiene un único maestro con el mismo nombre (sin distinguir mayúsculas, acentos ni traducciones) y
una unidad compatible, se empareja solo y pasa a **Mapeado automáticamente**. Con ninguno o varios
candidatos queda **Sin mapear** para que lo resuelvas tú.

## Dónde se encuentra

En el menú lateral aparece el grupo **Bioscore**, entre **Transporte** y **Administración**, con
una entrada por catálogo. Cada listado muestra el estado de cada elemento, su destino en la
plataforma y la fecha en que Bioscore lo ofreció por última vez, con un aviso con el número de
**Elementos pendientes de mapear** y un filtro propio por **Nombre**, **Estado** y **Retirado**.
Desde el icono **Editar** de cada fila eliges el **Destino en la plataforma** o marcas **Ignorar
este elemento**; desde el listado puedes **Ignorar** o **Quitar mapeo** a varios elementos de una
vez. El botón **Sincronizar ahora** lanza en segundo plano la sincronización completa de los cuatro
catálogos sin esperar a la mañana siguiente.

## Qué conviene saber

El acceso es exclusivo de los **Administradores**; las equivalencias son comunes a toda la
plataforma y no cambian al impersonar. La sincronización nunca toca lo que decidas a mano: un
elemento **Mapeado manualmente** o **Ignorado** se respeta en las siguientes ejecuciones, salvo
que su destino se haya eliminado de la plataforma, en cuyo caso vuelve a **Sin mapear**. Un
combustible de vehículos siempre queda pendiente cuando existen varios transportes con ese
combustible, para que elijas el vehículo.

Tras cada sincronización con elementos nuevos sin emparejar o equivalencias invalidadas, los
Administradores reciben la notificación interna **Maestros de Bioscore pendientes de mapear** en
**Notificaciones › Mis notificaciones**; no se envía correo y no se repiten los pendientes de
días anteriores. Todos los cambios quedan anotados en **Administración › Registro**. Esta versión
solo mantiene las equivalencias: la descarga de los consumos y su conversión en actividades de
huella llegará en una fase posterior. Para que la sincronización funcione, el equipo técnico debe
configurar las credenciales de acceso a Bioscore.
