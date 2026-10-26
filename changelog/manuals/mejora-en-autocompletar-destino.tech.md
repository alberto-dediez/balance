# Mejora en autocompletar destino — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-26 · **Bounded Contexts:** `CarbonFootprint`

## Qué hace y por qué

La calculadora de viajes de cuenta pide el destino con un autocompletado Tom Select. Cuando el enlace restringe el destino a propiedades (`TripDestinationMode::AllProperties` o `SelectedProperties`) las opciones son un conjunto cerrado, pero antes el campo parecía vacío hasta teclear 3 letras.

Ahora, en esos dos modos: (1) al enfocar el campo se precarga y despliega la lista de propiedades permitidas; (2) se filtra por nombre desde la primera letra; (3) el tope pasa de 10 a 50. Los modos que consultan Google (`Free`, `Countries`, `Region`) no cambian: mínimo de 3 caracteres y sin precarga. No hay migración ni textos nuevos.

La decisión de precargar se toma en servidor (plantilla) y el servicio sigue siendo la barrera final contra consultas a Google (ver tabla de decisiones en `plan.md`).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Application | `src/CarbonFootprint/Application/TripCalculationLink/SuggestDestinations/SuggestTripDestinationsService.php` | Comprueba el modo antes del mínimo de longitud; acepta consulta vacía en modos de propiedades; tope 50 | Modificado |
| Infrastructure/UI | `src/CarbonFootprint/Infrastructure/UI/Web/Public/PropertyTrip/Step/TripController.php` | Pasa `destinationPreload` a la plantilla | Modificado |
| Plantilla | `templates/carbon_footprint/public/property_trip/step/trip.html.twig` | Atributo `data-autocomplete-preload-value` en `#trip-destination` | Modificado |
| JS | `assets/controllers/autocomplete_controller.js` | Valor Stimulus `preload` -> opción Tom Select `preload: 'focus'` | Modificado |
| Tests | `tests/Unit/CarbonFootprint/.../SuggestTripDestinationsServiceTest.php`, `tests/Acceptance/CarbonFootprint/account-trip-public.feature`, `tests/Unit/Shared/Domain/Model/Geo/InMemoryPlaceFinder.php` | Casos nuevos; contador `suggestCalls()` | Modificados |

## Flujo de una operación

1. `GET .../trip/{token}/trip` -> `TripController` calcula `destinationPreload = $asksForDestination && destinationMode()->usesProperties()` (`TripController.php:107`) y lo pasa a `trip.html.twig`.
2. La plantilla escribe `data-autocomplete-preload-value="true|false"` en `#trip-destination` (`trip.html.twig:40`).
3. `autocomplete_controller.js:41-43`: si `preloadValue`, `options.preload = 'focus'`. Tom Select, al recibir el foco, llama a `load('')` una sola vez por instancia (no pasa por `shouldLoad`) y, con `openOnFocus` por defecto, abre la lista al llegar los datos.
4. `fetch(<ruta _destinations>?query=)` -> `DestinationSuggestionsController` (sin cambios): valida token, aplica `PublicFormRateLimiter::checkPlaces()` y llama al servicio; responde `PlaceSuggestionsPresenter::toChoices()` (`[{value, label}]`).
5. `SuggestTripDestinationsService::__invoke()` (`:41-47`): recorta la consulta; si `usesProperties()` devuelve `propertySuggestions()` antes de comprobar longitud; si no, aplica `MINIMUM_QUERY_LENGTH = 3` y consulta `PlaceFinder`.
6. `propertySuggestions()` (`:57-75`): toma `TripDestinationCandidates::allowedFor()` (orden alfabético con `strcasecmp`), filtra por `mb_stripos` solo si la consulta no es `''`, y corta a `MAX_PROPERTY_SUGGESTIONS = 50`. Referencias `property:<id>` (`TripDestinationResolver::PROPERTY_REFERENCE_PREFIX`).

## Autorización y ámbito

Sin cambios: endpoint público protegido por token del enlace (token inválido o de propiedad -> `TripCalculationLinkTokenInvalidException`) y por el limitador `property_trip_places`. Con consulta vacía solo se exponen las propiedades que `allowedFor()` ya permitía descubrir por nombre, con tope de 50.

## Persistencia y migración

Ninguna.

## Configuración y operación

- El limitador `property_trip_places` (`config/packages/rate_limiter.yaml`, 30/min por IP) recibe una petición extra por carga de página en los modos de propiedades.
- Nada más: sin variables de entorno, colas ni servicios nuevos.

## Cómo extenderlo

- Precargar en otro autocompletado: añadir `data-autocomplete-preload-value="true"` al campo que use `data-controller="autocomplete"` con `data-autocomplete-path-value`; el valor es opcional y por defecto falso. Asegura que el endpoint responde a `query=` vacío sin coste externo.
- Cambiar el tope: constante `MAX_PROPERTY_SUGGESTIONS` (un único tope para consulta vacía y con texto).
- Trampa: no mover la comprobación de `MINIMUM_QUERY_LENGTH` por delante de `usesProperties()`; los modos Google dependen de ella para no gastar cuota.
- Trampa: el origen y las paradas (`_places`, `PlaceSuggestionsController`) usan el mismo controlador JS pero no definen `preload`.

## Tests

- Unitarios (`SuggestTripDestinationsServiceTest.php`): consulta vacía en `AllProperties` y `SelectedProperties`, propiedades sin coordenadas, filtro desde la primera letra, tope de 50, y modos Google sin consultar al buscador (`InMemoryPlaceFinder::suggestCalls()`).
- Behat (`account-trip-public.feature`): `?query=` en ambos modos de propiedades, `?query=s`, `?query=` en modo libre (`[]`) y atributo `data-autocomplete-preload-value` `true`/`false` en la pantalla del viaje.
- Ejecución: `make test-unit options=<ruta>` y `make test-acceptance options=<ruta>` (el Makefile no define `test-this` con ese uso en el plan; objetivos comprobados: `test`, `test-unit`, `test-acceptance`, `test-parallel`).
- Hueco: no hay test automático del comportamiento JS (foco -> lista abierta).

## Desviaciones y deuda

- Contador `suggestCalls()` añadido a `InMemoryPlaceFinder` (condicional prevista en el plan) y helper `addLocatedProperty()` en el test; ver `plan.md`, sección «Implementación».
- Pendiente del usuario: comprobación manual en navegador (paso 5 del plan). `review.md`: sin hallazgos abiertos (B1 corregido).
