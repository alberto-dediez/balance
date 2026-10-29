# Texto de consentimiento adaptado a la calculadora de viajes — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-29 · **Bounded Contexts:** `CarbonFootprint` (plantillas públicas), `Shared` (componente de consentimiento)

## Qué hace y por qué

La casilla de aceptación del tratamiento de datos de los formularios públicos decía siempre «…la huella de carbono del evento». Es correcto en la encuesta de asistentes y en las aportaciones de proveedor y empleado de un evento, pero la calculadora de huéspedes de una propiedad y la calculadora de viajes de la cuenta no tienen evento: quien rellena calcula la huella de su propio viaje.

La solución es puramente de presentación: el componente compartido ya aceptaba un parámetro `consent_purpose`; ahora su valor por defecto es «de mi viaje» y las tres pantallas de evento pasan «del evento» de forma explícita. No hay cambios de dominio, aplicación, persistencia ni migración. Las decisiones (frase legal intacta, un único texto para cualquier alcance de la calculadora, solo ES/EN/TR) están en la tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `templates/shared/form/_consent_field.html.twig:1` | Default de `consent_purpose` pasa de `'del evento'` a `'de mi viaje'` | modificado |
| `templates/carbon_footprint/public/attendee_survey/step/city.html.twig:62` | Include con `consent_purpose: 'del evento' \| trans` | modificado |
| `templates/carbon_footprint/public/contribution/step/general.html.twig:71` | Ídem (aportación de proveedor de evento) | modificado |
| `templates/carbon_footprint/public/employee_contribution/step/assembly.html.twig:60` | Ídem (aportación de empleado de evento) | modificado |
| `translations/messages+intl-icu.{es,en,tr}.xlf` (unidad `vpCRUPo`, línea 10424 en es/en, 10340 en tr) | Clave nueva `de mi viaje` | modificado |
| `templates/carbon_footprint/public/property_trip/step/trip.html.twig:125` | Hereda el default; sin cambios | sin tocar |
| `templates/carbon_footprint/public/organizational_employee_contribution/commuting.html.twig:74`, `provider_contribution/general.html.twig:43` | Ya pasaban `'de la organización' \| trans`; sin cambios | sin tocar |

## Flujo de una operación

Solo renderizado. `_consent_field.html.twig` calcula `consent_purpose` (parámetro del include o `'de mi viaje' | trans`) y lo interpola en la frase legal, una única clave con placeholders `{company}`, `{purpose}` y `{url}` (línea 7 con enlace a política de privacidad, línea 9 sin él). En la variante con enlace el propósito pasa por `|e('html')` antes del `|raw`. La calculadora de propiedad y la de cuenta comparten ruta y plantilla (`TripController`, `property_trip/step/trip.html.twig`), por lo que ambas heredan el default.

El texto de la cláusula no se persiste: de la aceptación solo se guardan IP, User-Agent y Accept-Language (`src/CarbonFootprint/Infrastructure/UI/Web/Public/ConsentRequestReader.php`, `RegisterTripCalculationRequest.php`). Cambiarlo no afecta a registros existentes ni a la validación (el `input` sigue siendo `required`).

## Persistencia y migración

Ninguna.

## Cómo extenderlo

- Nueva pantalla pública con consentimiento: incluir `@shared/form/_consent_field.html.twig`. Sin `consent_purpose` dirá «de mi viaje»; si no es un viaje, pasar `consent_purpose: '<clave>' | trans`.
- Nuevo propósito: la clave es un sintagma que debe encajar tras «huella de carbono» (ES), tras «carbon footprint» (EN) y delante de «karbon ayak izini» (TR, genitivo). Añadirla con `make translation` y rellenar ES/EN/TR; no existen catálogos FR/IT/DE.
- Trampa: la clave `del evento` debe conservarse; la usan las tres pantallas de evento.
- Los temas (`templates/themes/`, solo `aurora`) no sobrescriben el componente, así que el cambio aplica a todos.

## Tests

Solo Behat (no hay cambios unitarios). Se comprueba el texto de la cláusula en:

- `tests/Acceptance/CarbonFootprint/account-trip-public.feature:22-23` (con «de mi viaje» y sin «del evento»)
- `property-trip-calculations.feature:23`
- `attendee-survey-public.feature:21`, `provider-contribution-public-general.feature:13`, `employee-contribution-public.feature:13` (con «del evento»)

Ejecución: `make test-this test=tests/Acceptance/CarbonFootprint/account-trip-public.feature` (y análogos); suite completa con `make test-parallel`. Las aserciones coinciden con ambas variantes de la frase (con y sin enlace a la política). Ninguna cubre las traducciones EN/TR ni las aportaciones organizacionales.

## Desviaciones y deuda

- Declaradas en `## Implementación` de `plan.md`: la clave se añadió con `make translation` (no a mano); la unidad quedó al final de los catálogos y no junto a `del evento`; la aserción de la encuesta está en el paso `city` (líneas 19-21).
- `review.md`: sin hallazgos abiertos (B1, aserciones de proveedor y empleado, corregido).
