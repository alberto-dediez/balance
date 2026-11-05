# Descargar el listado de propiedades — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-05 · **Bounded Contexts:** `Property` (conectado al pipeline genérico de `DataExchange`, sin modificarlo)

## Qué hace y por qué

El listado de cuenta `/properties` (ruta `properties`) tiene un botón «Exportar» que lleva a la pantalla genérica `/{code}/export` (ruta `export`, `src/DataExchange/Infrastructure/UI/Web/Export/ExportController.php`) con `code = properties` y los filtros del listado como parámetros de query. El usuario elige columnas y formato (CSV o XLSX, por defecto XLSX según `ExportOptionsRequest`), la exportación se registra y se genera en segundo plano, y recibe un email con el enlace de descarga (válido 7 días: `RegisterExportationService.php:31`, purga diaria a la 01:00 en `CommandSchedule`).

No se ha tocado `DataExchange`: basta con un nuevo `ExportationType` que el framework autodescubre. La única lógica compartida con el listado, la ampliación del ámbito para cuentas distribuidoras, se ha extraído de `ListPropertiesService` a `DistributorPropertiesScope`. Las decisiones (pipeline genérico en vez de descarga síncrona, solo `/properties` y no `/admin/properties`, filas sin paginar, `Action::List` como permiso) están en la tabla de decisiones de `plan.md`.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/Property/Application/Property/Export/PropertiesExportFilter.php` | Filtro de exportación (`extends PropertyFilter`), tipo propio para el formulario | Nuevo |
| Aplicación | `src/Property/Application/Property/List/DistributorPropertiesScope.php` | `expand(PropertyFilter)`: si la cuenta del filtro es distribuidora activa, sustituye `accountId` por un `PropertyScope` (propias + distribuidas con `PropertyTripCalculators`) | Nuevo |
| Aplicación | `src/Property/Application/Property/List/ListPropertiesService.php` | Delega en `DistributorPropertiesScope` (dependencia opcional) | Modificado |
| Infraestructura | `src/Property/Infrastructure/DataExchange/PropertiesExportationType.php` | El `ExportationType` (`FilterAuthorizedExportationType`) | Nuevo |
| Infraestructura | `src/Property/Infrastructure/Symfony/Form/PropertiesExportType.php` | Formulario de filtros de la pantalla de exportación | Nuevo |
| Infraestructura | `src/Property/Infrastructure/UI/Web/Property/ListController.php` | Construye `exportParameters` a partir de los filtros de sesión (`exportParameters()`, l. 85) | Modificado |
| Plantilla | `templates/property/properties/list.html.twig` | `<twig:Link:Export>` en `list_actions`, antes de «Registrar» | Modificado |
| Tests | `tests/Unit/Property/Infrastructure/DataExchange/PropertiesExportationTypeTest.php`, `tests/Unit/Property/Application/Property/List/DistributorPropertiesScopeTest.php`, `ListPropertiesServiceTest.php`, `tests/Acceptance/Property/property-export.feature` | Cobertura | Nuevos / modificado |

No hay configuración de servicios nueva: el tipo se autodescubre por la etiqueta `exchange.exportation.type`.

## Flujo de una operación

1. **Listado.** `ListController::__invoke` convierte los filtros del listado (viven en sesión vía `FilterTrait`, no en la URL) en `exportParameters` (`name`, `destinationId`, `countryId`, `operatingRegime`, `orderBy`, `orderWay`; se descartan nulos y vacíos) y la plantilla genera `path('export', {code: 'properties'} | merge(exportParameters))`.
2. **Pantalla de opciones.** `ExportController` resuelve el tipo por código, llama a `isAuthorized($user)` (403 si falla) y construye el formulario con `createFilter()`, que lee la query del `RequestStack` (`PropertiesExportationType.php:86`). Solo acepta `orderBy` en `name|registeredAt` y `orderWay` en `ASC|DESC`; valores vacíos o inválidos se ignoran. `accountId` siempre es la cuenta actual (`CurrentAccountIdProvider`), nunca un parámetro de la URL. Las columnas se marcan por defecto todas, salvo «Cuenta» que solo lo está si la cuenta actual es distribuidora activa (`columnDefinitions()`, l. 68).
3. **Registro.** `RegisterExportationService` comprueba `isAuthorized` y `isAuthorizedForFilter` (l. 41-49) y guarda `dehydrateFilter()` (`:122`) como criterios de la exportación.
4. **Generación (worker).** `ExecuteExportationService::generate` (l. 89-93) rehidrata con `hydrateFilter()` (`:139`, `limit: 0`, `orderBy` por defecto `default`), vuelve a comprobar `isAuthorizedForFilter` y consume `rows()` (`:161`): clona el filtro, fuerza `limit = 0`, aplica `DistributorPropertiesScope::expand` y recorre `PropertyRepository::findByFilter`. Nombres de cuenta, destino, país y empresa se resuelven con una caché local por identificador; el régimen se traduce con el dominio `operatingRegime` (`:209`) y la fecha sale como `d/m/Y`.
5. **Entrega.** El email y la descarga (`DownloadController`, ruta `export_download`) son del framework.

Columnas: `name` (Nombre), `account` (Cuenta), `destination` (Destino), `country` (País), `company` (Empresa), `operatingRegime` (Régimen de explotación), `registeredAt` (Fecha de registro): las mismas que el listado.

## Autorización y ámbito

- `isAuthorized` (`:215`) y `isAuthorizedForFilter` (`:225`) usan `PropertyAuthorizer` con `Action::List` sobre una `Property` temporal de la cuenta actual / del filtro, no `Action::Export` (decisión del plan). `canList` (`PropertyAuthorizer.php:64`) admite: Propietario de la cuenta con alguna funcionalidad de propiedades (`canRegister`), Administrador, y Propietario de una cuenta distribuidora activa aunque no tenga esas funcionalidades.
- `isAuthorizedForFilter` devuelve `false` si el filtro no es un `PropertiesExportFilter` o no trae `accountId`. Consecuencia: un Administrador sin cuenta actual (sin impersonar) pasa la pantalla pero el registro falla y ve «No se ha podido iniciar la exportación.». Con impersonación sí exporta.
- La cuenta se resuelve siempre con `CurrentAccountIdProvider` (impersonación respetada).
- Rol `User`: 403 en la pantalla (cubierto en Behat).

## Persistencia y migración

Sin cambios de esquema ni migración. Se reutiliza el agregado `Exportation` de `DataExchange` con los criterios en `dehydrateFilter()`.

## Configuración y operación

Sin variables de entorno ni comandos nuevos. La generación va por la cola de `DeferredCommand` del framework; la purga de ficheros caducados es la tarea programada `data-exchange:purge-expired-exports` (`CommandSchedule.php`, 01:00).

## Cómo extenderlo

- **Añadir una columna:** una `ExportColumnDefinition` en `columnDefinitions()` y su clave en el `yield` de `rows()`, y la columna en `list.html.twig` para mantener la paridad.
- **Añadir un filtro:** campo en `PropertiesExportType`, lectura en `createFilter()`, par en `dehydrateFilter()`/`hydrateFilter()` y parámetro en `ListController::exportParameters()`.
- **Reutilizar el ámbito de distribuidor:** inyectar `DistributorPropertiesScope` y llamar a `expand($filter)`; no copiar la lógica.
- Trampa: la rehidratación ocurre en el worker; todo lo que `rows()` necesite debe poder derivarse de los criterios guardados (no del `RequestStack`).

## Tests

- Unitarios: `PropertiesExportationTypeTest` (filtro desde query, valores inválidos, hidratación, filas sin paginar, formato, distribuidor con cuenta, autorización por rol y por filtro), `DistributorPropertiesScopeTest` y `ListPropertiesServiceTest` (regresión del listado). Ejecutar: `make test-unit options="tests/Unit/Property"`.
- Behat: `tests/Acceptance/Property/property-export.feature` (botón visible, botón con filtros, pantalla con filtro precargado y envío, distribuidor sin funcionalidades, 403 a `User`). Ejecutar: `make test-acceptance options="tests/Acceptance/Property/property-export.feature"`.
- `make test-this` no existe en el `Makefile`.

## Desviaciones y deuda

Ver `plan.md` (sección `## Implementación`) y `review.md`. Corregidos M1, B1 y B2. Abiertas, y ajenas a esta funcionalidad:

- **Q1:** el worker no aplica el locale del usuario (`RegisterExportationService` despacha un `DeferredCommand` simple), así que cabeceras y «Régimen de explotación» salen en el locale por defecto. Afecta a todas las exportaciones.
- **Q2:** `AccountExtension`, `CompanyExtension`, `CountryExtension` y `DestinationExtension` (`src/Property/Infrastructure/Symfony/Twig/`) guardan una caché en memoria sin reinicio; esta exportación ya no las usa.
