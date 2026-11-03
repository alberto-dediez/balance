---
title: "Importaciones sin filas duplicadas ante interrupciones"
date: "2026-11-03 07:37"
category: fix
summary: "Una fila de una importación ya no se registra dos veces aunque el proceso se interrumpa y se reintente."
plan: 2026-11-02-importation-row-idempotency
manual: importation-row-idempotency.pdf
techManual: importation-row-idempotency.tech.md
published: true
---

## Qué cambia

Cuando importas un fichero, cada fila se procesa por separado en segundo plano. Hasta ahora, si el proceso se interrumpía por un fallo puntual y la plataforma lo reintentaba, una misma fila podía cargarse dos veces y dejar un dato duplicado. Se ha detectado un caso real: un movimiento de tesorería que apareció repetido tras importar un fichero grande.

Desde ahora la plataforma garantiza que una fila se registra una sola vez, ocurra lo que ocurra durante el proceso.

## A quién afecta

A cualquier persona que importe ficheros. No hay pantallas ni botones nuevos: la importación se usa igual que siempre. También quedan protegidas las importaciones empezadas antes de esta mejora.

## Dónde se ve

En la pantalla **Resultado de la importación**, a la que llegas con el enlace **Ver resultado** del aviso «Importación de … finalizada». Cada fila muestra **OK**, **ERROR** o **---** si aún no se ha procesado.

## Qué conviene saber

- Si se reintenta una fila que ya se había cargado, no se vuelve a cargar y conserva su resultado. Si había terminado con error, mantiene ese mismo error.
- Si un intento se interrumpe sin llegar a confirmar el resultado, la fila no se repite por su cuenta: aparece como **ERROR** con el texto «Ejecución interrumpida en un intento anterior: comprueba si la fila se ha importado antes de repetirla». En ese caso, busca el dato en la plataforma; si ya existe, no hagas nada, y si no, impórtalo de nuevo, por ejemplo en un fichero con solo esas filas. La plataforma prefiere avisarte de más antes que arriesgarse a un duplicado silencioso.
- Una importación solo se inicia una vez: si pulsas «Finalizar» dos veces o recargas la página, no se vuelven a lanzar las filas. Al recargar esa pantalla puede aparecer un error de acceso; es normal, y los resultados se consultan desde el enlace del aviso.
- La importación se da por finalizada, y recibes el aviso, cuando se han procesado todas las filas, aunque la última haya sido un reintento.
- Los duplicados que ya existieran antes de esta mejora no se corrigen: hay que revisarlos a mano.
