# Sincronización y mapeo de maestros de Bioscore — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-17 · **Bounded Contexts:** `Bioscore` (nuevo), `Shared` (menú y `CommandSchedule`), `Security` (plantillas del Registro), `config/`

> Este manual describe el código final, que difiere del plan en la capa de interfaz: la revisión manual del usuario (`review-fix.md`, hallazgo D1) sustituyó la pantalla de edición en masa por un listado estándar con paginación, acciones masivas y una pantalla de edición individual. Las secciones *UI Web*, *Formularios* y *Aceptación* de `plan.md` describen la interfaz anterior.

## Qué hace y por qué

Bioscore mantiene cuatro catálogos que Events necesita interpretar para, en un plan posterior, convertir los consumos anuales de las propiedades en actividades de alcance 1 y 2: combustibles de instalaciones, combustibles de vehículos, gases refrigerantes y comercializadoras eléctricas. Este BC construye la tabla de equivalencias entre cada elemento de esos catálogos y el maestro de `CarbonFootprint` que le corresponde (`Combustible`, `RoadTransport`, `RefrigerantGas`, `ElectricityMarketer`).

Un único agregado, `BioscoreCatalogItem`, representa cada elemento con su catálogo, su identificador en Bioscore, su nombre y unidad tal como los publica Bioscore, y el estado de su mapeo. Una sincronización diaria descarga los catálogos por la API de Bioscore, da de alta los elementos nuevos, actualiza nombre y unidad de los existentes, retira los que dejan de ofrecerse, intenta mapear automáticamente por nombre normalizado y unidad compatible, y avisa por notificación interna a los Administradores cuando hay elementos nuevos sin mapear o mapeos que han dejado de ser válidos. El Administrador completa o corrige el mapeo desde cuatro listados independientes, uno por catálogo.

Las decisiones de diseño (BC propio, un solo agregado, doble elemento para un combustible con dos usos, regla de auto-mapeo con exactamente un candidato, mapeos manuales intocables por la sincronización, retirada en lugar de borrado, notificación solo con novedades de esa ejecución) están justificadas en la tabla *Decisiones tomadas* de `plan.md`.

## Mapa de piezas

### Dominio (`src/Bioscore/Domain/`)

| Fichero | Rol | Estado |
|---|---|---|
| `DomainEvent.php` | Base de los eventos del BC, `$context = 'bioscore'` | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogItem.php` | Agregado raíz | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogItemId.php` | UUID del agregado | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalog.php` | Enum de los cuatro catálogos | nuevo |
| `Model/BioscoreCatalogItem/BioscoreMappingStatus.php` | Enum de estado del mapeo, con `isMapped()` y `style()` | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogItemFilter.php` | Criterio de búsqueda y paginación | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogItemRepository.php` | Puerto de persistencia | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogItemNotFoundException.php` | Elemento inexistente | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogEntry.php` | VO con lo que devuelve la API (`bioscoreId`, `name`, `unit`) | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogGateway.php` | Puerto hacia la API de Bioscore | nuevo |
| `Model/BioscoreCatalogItem/BioscoreUnavailableException.php` | Fallo de descarga o de autenticación | nuevo |
| `Model/BioscoreCatalogItem/NameNormalizer.php` | Normalización de nombres para comparar | nuevo |
| `Model/BioscoreCatalogItem/BioscoreUnitParser.php` | Interpretación de la unidad textual de Bioscore y compatibilidad con `Unit` | nuevo |
| `Model/BioscoreCatalogItem/BioscoreCatalogItem{Registered,Updated,Mapped,Unmapped,Ignored,Withdrawn,Restored}.php` | Eventos del agregado | nuevos |
| `Model/BioscoreCatalogItem/BioscoreCatalogSyncCompleted.php` | Evento de fin de sincronización, sin agregado | nuevo |

### Aplicación (`src/Bioscore/Application/BioscoreCatalogItem/`)

| Fichero | Rol | Estado |
|---|---|---|
| `BioscoreCatalogItemAuthorizer.php` | Autorización por acción | nuevo |
| `CatalogTarget.php` | VO del maestro destino (`id`, `name`, `names`, `unit`, `vehicle`) | nuevo |
| `CatalogTargetFinder.php` | Única correspondencia catálogo → repositorio de `CarbonFootprint`; `all`, `find`, `matching` | nuevo |
| `CatalogTargetNotFoundException.php` | Destino inexistente al mapear | nuevo |
| `Register/*` | Alta de un elemento (único punto que instancia el agregado) | nuevos |
| `Sync/*` | Sincronización completa o de un catálogo | nuevos |
| `Map/*`, `Unmap/*`, `Ignore/*` | Acciones unitarias sobre un elemento | nuevos |
| `List/*` | Listado paginado de un catálogo con contador de pendientes y destinos indexados | nuevos |
| `UpdateMapping/*` | Caso de uso de la pantalla de edición: decide entre mapear, desmapear o ignorar | nuevos |

### Infraestructura (`src/Bioscore/Infrastructure/`)

| Fichero | Rol | Estado |
|---|---|---|
| `Persistence/Doctrine/Mapping/BioscoreCatalogItem.BioscoreCatalogItem.orm.xml` | Mapping de la tabla `bioscore_catalog_items` | nuevo |
| `Persistence/Doctrine/Type/BioscoreCatalogItemIdType.php` | Tipo DBAL `bioscore_catalog_item_id` | nuevo |
| `Persistence/Doctrine/Repository/DoctrineBioscoreCatalogItemRepository.php` | Repositorio dev/prod | nuevo |
| `Persistence/InMemory/Repository/InMemoryBioscoreCatalogItemRepository.php` | Repositorio test | nuevo |
| `Gateway/HttpBioscoreCatalogGateway.php` | Cliente HTTP de la API de Bioscore (dev/prod) | nuevo |
| `Gateway/InMemoryBioscoreCatalogGateway.php` | Doble configurable (test) | nuevo |
| `Notification/BioscoreCatalogSyncCompletedNotifier.php` | Notificación interna a Administradores | nuevo |
| `UI/Console/SyncBioscoreCatalogsCommand.php` | Comando `bioscore:catalogs:sync` | nuevo |
| `UI/Web/Admin/BioscoreCatalogItem/BioscoreCatalogRoutes.php` | Correspondencia catálogo → nombre de ruta | nuevo |
| `UI/Web/Admin/BioscoreCatalogItem/ListController.php` | Cuatro rutas de listado sobre un controlador | nuevo |
| `UI/Web/Admin/BioscoreCatalogItem/EditController.php` | Pantalla de edición de un elemento | nuevo |
| `UI/Web/Admin/BioscoreCatalogItem/SyncController.php` | Encola la sincronización desde la web | nuevo |
| `UI/Web/Admin/BioscoreCatalogItem/GetBioscoreCatalogItemFromIdTrait.php` | Carga o 404 | nuevo |
| `UI/Web/Admin/BioscoreCatalogItem/Bulk/{IgnoreAction,UnmapAction}.php` | Acciones masivas del listado | nuevos |
| `Symfony/Form/ListBioscoreCatalogItemsType.php` | Formulario de filtro | nuevo |
| `Symfony/Form/UpdateBioscoreCatalogItemMappingType.php` | Formulario de edición | nuevo |
| `Symfony/DependencyInjection/services.yaml` | Wiring del BC y credenciales del gateway | nuevo |

### Plantillas, traducciones y configuración

| Fichero | Rol | Estado |
|---|---|---|
| `templates/bioscore/admin/catalogs/list.html.twig` | Listado (común a los cuatro catálogos) | nuevo |
| `templates/bioscore/admin/catalogs/edit.html.twig` | Edición de un elemento | nuevo |
| `templates/bioscore/notification/catalog-sync-completed.notification.html.twig` | Cuerpo de la notificación | nuevo |
| `templates/bioscore/translations/{bioscoreCatalog,bioscoreMappingStatus}.html.twig` | Plantillas de extracción de claves dinámicas (review M1) | nuevos |
| `templates/components/Link/Sync.html.twig` | Componente de enlace "Sincronizar ahora" | nuevo |
| `templates/security/admin/events/detail/bioscore.bioscoreCatalogItem{Registered,Mapped,Unmapped,Ignored}.html.twig` | Texto legible en el Registro | nuevos |
| `translations/bioscoreCatalog+intl-icu.{es,en,tr}.xlf`, `translations/bioscoreMappingStatus+intl-icu.{es,en,tr}.xlf` | Dominios de traducción de los enums | nuevos |
| `translations/messages+intl-icu.{es,en,tr}.xlf` | Claves de interfaz nuevas | modificados |
| `migrations/Version20260917074340.php` | Tabla `bioscore_catalog_items` | nuevo (creado por el usuario) |
| `config/services.yaml:14`, `config/routes.yaml:174-177`, `config/packages/twig.yaml:28`, `config/packages/doctrine.yaml:95,178-183`, `behat.yml.dist:25` | Wiring del BC | modificados |
| `.env:122-125`, `.env.test` | `BIOSCORE_API_USERNAME`, `BIOSCORE_API_PASSWORD` | modificados |
| `src/Shared/Application/CommandBus/CommandSchedule.php:59-63` | Comando diario `bioscore:sync-catalogs` a las 06:00 | modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:309-335` | Grupo de menú "Bioscore" con cuatro entradas | modificado |

## Modelo de dominio

### `BioscoreCatalogItem`

Constructor promovido (`BioscoreCatalogItem.php:14-31`): `id`, `catalog` (`BioscoreCatalog`), `bioscoreId` (int), `name`, `unit` (texto libre de Bioscore), `targetId` (`?string`, UUID plano del maestro), `status` (`BioscoreMappingStatus`), `withdrawn` (bool), y las fechas `firstSeenAt`, `lastSeenAt`, `mappedAt`, `withdrawnAt`, `updatedAt`.

`targetId` es un `string` sin relación Doctrine porque la entidad destino depende del catálogo; la resolución vive en `CatalogTargetFinder`.

Comportamiento, todo con `recordApplyAndPublish` salvo donde se indica:

| Método | Guarda | Evento | Efecto en `apply*` |
|---|---|---|---|
| `register(catalog, bioscoreId, name, unit, seenAt)` `:113` | — | `BioscoreCatalogItemRegistered` | Fija catálogo, id de Bioscore, nombre, unidad; `firstSeenAt = lastSeenAt = seenAt` |
| `refresh(name, unit, seenAt)` `:127` | Si estaba retirado, llama antes a `restore(seenAt)`. Si nombre y unidad no cambian, asigna `lastSeenAt` directamente y **no publica** (`:133-137`) | `BioscoreCatalogItemUpdated` | Nombre, unidad, `lastSeenAt`, `updatedAt` |
| `map(targetId, automatic)` `:149` | Sin evento si ya está mapeado al mismo destino con el mismo carácter (`:153`) | `BioscoreCatalogItemMapped` | `targetId`, estado `AutoMapped` o `ManuallyMapped`, `mappedAt`, `updatedAt` |
| `unmap()` `:166` | Sin evento si ya está `Unmapped` sin destino | `BioscoreCatalogItemUnmapped` (`previousTargetId`) | `targetId = null`, `Unmapped`, `mappedAt = null` |
| `ignore()` `:180` | Sin evento si ya está `Ignored` | `BioscoreCatalogItemIgnored` (`previousTargetId`) | `targetId = null`, `Ignored`, `mappedAt = null` |
| `withdraw(at)` `:194` | Idempotente | `BioscoreCatalogItemWithdrawn` | `withdrawn = true`, `withdrawnAt` |
| `restore(at)` `:208` | Idempotente | `BioscoreCatalogItemRestored` | `withdrawn = false`, `withdrawnAt = null`, `lastSeenAt` |

Accesores derivados: `isMapped()` (delegado a `BioscoreMappingStatus::isMapped()`), `isIgnored()`, `isWithdrawn()`, `isPending()` = `Unmapped && !withdrawn` (`:83-86`).

Invariante que se cumple por construcción: `targetId !== null ⇔ status ∈ {AutoMapped, ManuallyMapped}`; `unmap()` e `ignore()` siempre vacían el destino.

### Enums

- `BioscoreCatalog` (`BioscoreCatalog.php`): `FacilityFuel = 'facility_fuel'`, `TransportFuel = 'transport_fuel'`, `RefrigerantGas = 'refrigerant_gas'`, `ElectricityMarketer = 'electricity_marketer'`. Sin métodos de presentación: las etiquetas están en el dominio de traducción `bioscoreCatalog` y las rutas en `BioscoreCatalogRoutes`.
- `BioscoreMappingStatus` (`BioscoreMappingStatus.php`): `Unmapped`, `AutoMapped`, `ManuallyMapped`, `Ignored`; `isMapped()` (`:14`) y `style()` (`:19-27`) con la clase Bootstrap del badge (`bg-warning text-dark`, `bg-info`, `bg-success`, `bg-secondary`). Etiquetas en el dominio `bioscoreMappingStatus`.

### Correspondencia catálogo → maestro

| `BioscoreCatalog` | Endpoint de Bioscore (`HttpBioscoreCatalogGateway.php:80-84`) | Filtro (`:101-107`) | Maestro | Unidad del destino (`CatalogTargetFinder.php`) |
|---|---|---|---|---|
| `facility_fuel` | `/platform/combustibles` | `usable_for_facilities === true` | `Combustible` | `Combustible::unit()` `:166` |
| `transport_fuel` | `/platform/combustibles` | `usable_for_transportation === true` | `RoadTransport` | `RoadTransport::unit()` `:188` |
| `refrigerant_gas` | `/platform/refrigerant-gases` | — | `RefrigerantGas` | `RefrigerantGas::unit()` `:203` |
| `electricity_marketer` | `/platform/electricity-marketers` | — | `ElectricityMarketer` | `Unit::KWh` fijo `:217` |

Un combustible de Bioscore con ambos flags genera dos elementos, uno por catálogo, con el mismo `bioscoreId`; la clave única es `(catalog, bioscore_id)`.

### Regla de auto-mapeo

`CatalogTargetFinder::matching(catalog, name, rawUnit)` (`CatalogTargetFinder.php:78-98`): recorre `all(catalog)` y conserva los destinos con unidad compatible y cuyo nombre base **o cualquiera de sus traducciones** (`CatalogTarget::names`, construido en `:154-160` y `:176-182`) coincide con el nombre de Bioscore tras normalizar ambos. El servicio de sincronización solo mapea si hay **exactamente un** candidato (`SyncBioscoreCatalogsService.php:159`).

- `NameNormalizer::normalize` (`NameNormalizer.php:9-22`): `trim` + `mb_strtolower`, descomposición Unicode `FORM_D` con `\Normalizer` (extensión `intl`), eliminación de marcas diacríticas (`\p{Mn}`), colapso de espacios. `"Gasóleo C"` → `"gasoleo c"`.
- `BioscoreUnitParser::parse` (`BioscoreUnitParser.php:11-25`): tras `trim`, minúsculas y quitar puntos finales, reconoce `kg/kgs/kilogramo(s)` → `Kilogram`, `g/gramo(s)` → `Gram`, `t/tn/tonelada(s)` → `Tonne`, `l/lt/lts/litro(s)` → `Liter`, `m3/m³/metro(s) cubico(s)` → `CubicMeter`, `kwh` → `KWh`, `kwhpcs/kwh pcs` → `KWhPCS`; cualquier otro texto → `null`.
- `BioscoreUnitParser::isCompatible(rawUnit, targetUnit)` (`:27-40`): unidad vacía → compatible; unidad no reconocida → **no** compatible; reconocida → misma `Magnitude` que la del destino.

`CatalogTargetFinder::all()` (`:41-61`) carga con `limit: 0` y ordena por nombre (`strcasecmp`) y, a igual nombre, por `vehicle->value`. `find()` (`:63-75`) devuelve `null` si el id no es un UUID válido o el maestro no existe (los repositorios de `CarbonFootprint` excluyen los eliminados).

### `BioscoreCatalogSyncCompleted`

Evento sin agregado (`aggregateRootId()` devuelve `null`, `BioscoreCatalogSyncCompleted.php:78-81`). Payload (`:83-95`): `newPending` y `invalidated` como `list<array{itemId, catalog, bioscoreId, name}>`, `failedCatalogs` (`list<string>`) y los contadores `registered`, `updated`, `autoMapped`, `withdrawn`, `restored`. `RecordEventSubscriber` lo guarda en el Registro con `aggregateRootId = null` y lo muestra con `default.html.twig`.

## Flujo de una operación

### Sincronización (`SyncBioscoreCatalogsService`)

Entrada: `SyncBioscoreCatalogsRequest(?catalog, now)` (`SyncBioscoreCatalogsRequest.php`). Tres puntos de entrada la invocan:

1. **Programada**: `CommandSchedule.php:59-63` — `ScheduledCommand::dailyAt(id: 'bioscore:sync-catalogs', command: new SyncBioscoreCatalogsRequest(now: ...), time: '06:00')`; la ejecuta `RunScheduledCommandsCommand` con el usuario que recibe por argumento.
2. **Web**: `SyncController.php:24-62` — `GET /admin/bioscore/{catalog}/sync?_token=…` (`admin_bioscore_catalogs_sync`). Autoriza `Admin` sobre un agregado vacío (`:41-45`), valida el token CSRF `bioscore_sync` (`:50-52`), despacha `new DeferredCommand(new SyncBioscoreCatalogsRequest(now: …), Priority::MEDIUM)` (`:54-57`) — siempre los cuatro catálogos —, añade el flash "Sincronización de maestros de Bioscore encolada" y redirige al listado del catálogo de origen (`:61`).
3. **Consola**: `SyncBioscoreCatalogsCommand.php` — `bioscore:catalogs:sync <username> [--catalog=<valor>]`; invoca el servicio directamente, sin bus (`:69-72`).

Secuencia del servicio (`SyncBioscoreCatalogsService.php`):

1. `denyUnlessCanDoThat(..., Action::Admin, $user)` (`:57-61`) y `reset()` de contadores (`:63`): el servicio es una instancia única y acumula estado por ejecución.
2. `catalogs = request->catalog ? [catalog] : BioscoreCatalog::cases()` (`:66`).
3. Por catálogo: `gateway->fetch(catalog)`; si lanza `BioscoreUnavailableException` → `logger->warning('Bioscore catalog sync failed')`, se anota en `failedCatalogs` y se pasa al siguiente (`:69-77`). **Un catálogo fallido no retira nada.**
4. Por entrada, `synchronize()` (`:115-175`):
   - `findByCatalogAndBioscoreId`; si no existe, alta vía `RegisterBioscoreCatalogItemService` (`:121-132`); si existe, `refresh()` y contadores `updated`/`restored` (`:134-145`).
   - Si está mapeado y `targetFinder->find()` no encuentra el destino → `unmap()` y se marca `wasInvalidated` (`:150-154`). Se aplica también a `ManuallyMapped`.
   - Si está `Unmapped` → `matching()`; con un único candidato, `map(id, automatic: true)` (`:156-164`). `ManuallyMapped` e `Ignored` nunca entran aquí.
   - Si sigue `Unmapped`: nuevo → `newPending`; invalidado → `invalidated` (`:166-172`). Un mapeo invalidado que se re-mapea automáticamente **no** se notifica.
   - `repository->add(item)`.
5. `withdrawMissing()` (`:178-195`): todos los no retirados del catálogo cuyo `bioscoreId` no venga en la descarga → `withdraw(now)`.
6. `publishCompletion()` (`:197-213`): publica `BioscoreCatalogSyncCompleted` **solo** si `response->hasNews()` (`SyncBioscoreCatalogsResponse.php:29-32`: `newPending` o `invalidated` no vacíos). Los pendientes de ejecuciones anteriores no se repiten.
7. `logger->info('Bioscore catalogs synchronized', [...])` y devuelve `SyncBioscoreCatalogsResponse`.

Descarga (`HttpBioscoreCatalogGateway.php`): `token()` (`:118-145`) hace `POST {apiBaseUrl}/login` con `{username, password}` y memoriza el `token` en la instancia (no se persiste). `request()` (`:53-76`) hace `GET` con `auth_bearer`; ante `401` invalida el token, vuelve a autenticar y reintenta **una vez**; cualquier otro estado distinto de `200` o un cuerpo no JSON → `BioscoreUnavailableException::fromCatalog`. `toEntry()` (`:90-116`) descarta filas sin `id` numérico o sin `name` con `logger->warning`, aplica el filtro por flags de combustible y tolera `unit` ausente (`''`).

Notificación: `BioscoreCatalogSyncCompletedNotifier.php` se suscribe a `BioscoreCatalogSyncCompleted` (`:72-75`), obtiene todos los `SuperAdmin` y `Admin` (`:64-70`) y envía a cada uno una `SystemNotification` por canal `Internal` (`:52-59`) con asunto "Maestros de Bioscore pendientes de mapear" y la plantilla `catalog-sync-completed.notification.html.twig`, que agrupa por catálogo los nuevos pendientes y los invalidados con enlace a la pantalla de cada catálogo (`routes` = `BioscoreCatalogRoutes::all()`) y lista los catálogos que no se pudieron descargar. Sin correo.

### Listado (`ListController`)

`ListController.php:24-27` declara cuatro `#[Route]` sobre la misma clase invocable, cada una con su `name` y `defaults.catalog`; el cargador de atributos de Symfony crea una ruta por atributo. Como el nombre de ruta es distinto, `FilterTrait` guarda un filtro de sesión distinto por catálogo.

1. `BioscoreCatalog::from($catalog)` (`:39`); `filter()` con `ListBioscoreCatalogItemsType` sobre `ListBioscoreCatalogItemsRequest(catalog: …)` (`:42-46`); `$filter->catalog` se fuerza al de la ruta porque el formulario no lo expone (`:50`).
2. `ListBioscoreCatalogItemsService` (`ListBioscoreCatalogItemsService.php:23-47`): autoriza `List`, exige catálogo (`:31-33`), devuelve `items`, `totalResults`, `pendingCount` (`countPending(catalog)`) y `targetsById` (`CatalogTargetFinder::all` indexado por id, para pintar la columna de destino).
3. `BulkType` con `IgnoreAction` y `UnmapAction` (`:57-63`), procesado por `BulkTrait::bulkActions()` (`:65`); si hubo acción, redirige a la misma URI.
4. Render de `@bioscore/admin/catalogs/list.html.twig`: aviso `Alert` con los pendientes (`list.html.twig:6-10`), `List:Table` con columnas ordenables `name`, `status`, `lastSeenAt` (`:15-35`), badge de estado vía `status.style` y badge "Retirado" (`:43-46`), destino con etiqueta `"{name} - {vehículo}"` para `RoadTransport` (`:49-54`), acción de fila `List:Action:Edit` (`:58-60`) y, en `list_actions`, el componente `Link:Sync` con token CSRF y confirmación (`:66-73`).

Orden y paginación en `DoctrineBioscoreCatalogItemRepository.php:131-135` (`status`, `lastSeenAt`, `firstSeenAt`, por defecto `name`); límite por defecto 10 (`BioscoreCatalogItemFilter.php:19`).

### Edición (`EditController`)

`GET|POST /admin/bioscore/catalog-items/edit/{itemId}` (`admin_bioscore_catalog_items_edit`, `EditController.php:22`).

1. Carga o 404 (`GetBioscoreCatalogItemFromIdTrait`); `denyAccessUnlessGranted('edit', $item)` (`:38`).
2. `targetsById` con `CatalogTargetFinder::all($item->catalog())` (`:40-44`): el selector solo ofrece maestros del tipo correcto.
3. `UpdateBioscoreCatalogItemMappingType` (`UpdateBioscoreCatalogItemMappingType.php`): `targetId` (`AutocompleteType`, opcional, placeholder "Sin mapear", `choices` etiqueta → id; etiqueta `"{name} - {vehículo traducido}"` con el dominio `roadTransportVehicleType` y desambiguación `(n)` para nombres repetidos, `:74-88`), `ignore` (`CheckboxType` "Ignorar este elemento"), botones `saveAndReturn` ("Guardar y salir") y `save` ("Guardar").
4. `EditTrait::edit()` invoca `UpdateBioscoreCatalogItemMappingService` y añade el flash "Equivalencia de Bioscore actualizada con éxito" (`:57-61`); con `saveAndReturn` redirige al listado del catálogo (`:63-65`).

`UpdateBioscoreCatalogItemMappingService.php:38-57` decide en este orden: `ignore` marcado → `IgnoreBioscoreCatalogItemService` (salvo que ya esté ignorado); `targetId` no vacío → `MapBioscoreCatalogItemService` (salvo que ya esté `ManuallyMapped` al mismo destino, `:70-72`; un `AutoMapped` con el mismo destino **sí** se re-mapea y pasa a manual); en otro caso → `UnmapBioscoreCatalogItemService` si estaba mapeado o ignorado (`:79-81`), lo que devuelve un ignorado a `Unmapped`.

`MapBioscoreCatalogItemService.php:40-42` comprueba con `CatalogTargetFinder::find($item->catalog(), $targetId)` que el destino existe y es del catálogo del elemento; si no, `CatalogTargetNotFoundException`.

### Acciones masivas

`Bulk/IgnoreAction.php` y `Bulk/UnmapAction.php` implementan `BulkAction`: generan un `IgnoreBioscoreCatalogItemRequest` / `UnmapBioscoreCatalogItemRequest` por elemento seleccionado (`:26-39`) y devuelven los mensajes de éxito/fallo (`:41-53`), que interpolan el `itemId` (UUID) del elemento.

## Autorización y ámbito

`BioscoreCatalogItemAuthorizer` (`BioscoreCatalogItemAuthorizer.php`): `canList` y `canEdit` → `SuperAdmin` o `Admin` (`:24-32`); `canView` → `canEdit`; `canRegister` → `canAdmin` (por defecto de `AbstractAuthorizer`, `Admin`/`SuperAdmin`); `canRemove`, `canImport`, `canExport`, `canAutocomplete`, `canHistory`, `canImpersonate` → `false`. `supports` → `instanceof BioscoreCatalogItem`.

| Servicio / controlador | Acción |
|---|---|
| `RegisterBioscoreCatalogItemService` | `Register` (`:32`) |
| `SyncBioscoreCatalogsService`, `SyncController` | `Admin` |
| `ListBioscoreCatalogItemsService` | `List` |
| `Map`, `Unmap`, `Ignore`, `UpdateMapping`, `EditController` | `Edit` |

Los elementos son maestros globales de plataforma: no hay scoping por cuenta ni impersonación. `Owner`, `User` y `Provider` reciben 403 en las pantallas y `UnauthorizedActionException` en los servicios. No hay feature flag.

## Persistencia y migración

Tabla `bioscore_catalog_items` (`BioscoreCatalogItem.BioscoreCatalogItem.orm.xml`):

| Columna | Tipo | Nulable |
|---|---|---|
| `id` | `bioscore_catalog_item_id` (VARCHAR 36) | no |
| `catalog` | VARCHAR 32, `enum-type` `BioscoreCatalog` | no |
| `bioscore_id` | INT | no |
| `name` | VARCHAR 255 | no |
| `unit` | VARCHAR 50 | no |
| `target_id` | VARCHAR 36 | sí |
| `status` | VARCHAR 32, `enum-type` `BioscoreMappingStatus` | no |
| `withdrawn` | BOOLEAN | no |
| `first_seen_at`, `last_seen_at` | DATETIME (immutable) | no |
| `mapped_at`, `withdrawn_at`, `updated_at` | DATETIME (immutable) | sí |

Índices: único `uq_bioscore_catalog_items_catalog_bioscore_id (catalog, bioscore_id)`; `idx_bioscore_catalog_items_status (catalog, status, withdrawn)` (usado por `countPending`, `DoctrineBioscoreCatalogItemRepository.php:79`); `idx_bioscore_catalog_items_target (target_id)`.

Migración **ya creada** por el usuario: `migrations/Version20260917074340.php` (`up()` crea la tabla con todos los índices; `down()` la elimina). Coincide con `migration.sql` del plan. No hay datos previos ni backfill. No hay `remove` en el repositorio: los elementos se retiran (`withdrawn`), nunca se borran.

## Configuración y operación

- **Variables de entorno** (`.env:122-125`): `BIOSCORE_API_USERNAME` y `BIOSCORE_API_PASSWORD`, credenciales de un `ApiUser` de Bioscore con `ROLE_API_PLATFORM`. Vacías por defecto: hasta rellenarlas, cada sincronización falla los cuatro catálogos, escribe `warning` en el log y no notifica. La URL base reutiliza `%sso_api_url%` (`SSO_API_URL`). Inyección en `src/Bioscore/Infrastructure/Symfony/DependencyInjection/services.yaml:11-33` bajo `when@dev` y `when@prod`; en `test` manda `InMemoryBioscoreCatalogGateway`.
- **Tarea programada**: `bioscore:sync-catalogs`, diaria a las 06:00 UTC, prioridad `LOW` por defecto de `ScheduledCommand::dailyAt` (`CommandSchedule.php:59-63`).
- **Botón web**: `DeferredCommand` con `Priority::MEDIUM` → cola `async` (`SyncController.php:54-57`). Requiere un worker (`make queues` o `make queues-medium`).
- **Consola**: `make sf c="bioscore:catalogs:sync admin@dediez.software"` o `make sf c="bioscore:catalogs:sync admin@dediez.software --catalog=refrigerant_gas"`. Pinta una tabla con los contadores (`Registered`, `Updated`, `Auto-mapped`, `Withdrawn`, `Restored`), las secciones `New unmapped items` e `Invalidated mappings` (`catalog · #bioscoreId · name`) y un `warning` con los catálogos fallidos. Devuelve `SUCCESS` aunque fallen catálogos; `FAILURE` solo con usuario inexistente o `--catalog` desconocido (`SyncBioscoreCatalogsCommand.php:45-67`).
- **Notificaciones**: solo canal `Internal`, a todos los `SuperAdmin` y `Admin`, únicamente cuando la ejecución deja nuevos pendientes o mapeos invalidados.
- **Registro**: todos los eventos del BC quedan en el Registro por `RecordEventSubscriber`; hay plantilla propia para `Registered`, `Mapped`, `Unmapped` e `Ignored`; el resto usa `default.html.twig`.
- **Menú**: grupo "Bioscore" (`NodeLister.php:309-335`) con "Combustibles de instalaciones", "Combustibles de vehículos", "Gases refrigerantes" y "Comercializadoras eléctricas", solo para Administradores.

## Cómo extenderlo

**Añadir un catálogo de Bioscore** (p. ej. tipos de residuo):

1. Caso nuevo en `BioscoreCatalog` y claves en `translations/bioscoreCatalog+intl-icu.{es,en,tr}.xlf` y en `templates/bioscore/translations/bioscoreCatalog.html.twig`.
2. Endpoint y filtro en `HttpBioscoreCatalogGateway::url()` / `toEntry()`.
3. Repositorio destino, `all()`, `find()` y método `fromX()` en `CatalogTargetFinder` (el `match` de `all()` y `find()` es exhaustivo y PHPStan avisará).
4. Nombre de ruta en `BioscoreCatalogRoutes::routeName()`, `#[Route]` adicional en `ListController`, `requirements` de `SyncController` y nodo en `NodeLister`.
5. Escenario en `bioscore-catalog-list.feature` y caso en `CatalogTargetFinderTest`.

**Añadir una unidad reconocida**: caso en el `match` de `BioscoreUnitParser::parse()` y fila en el `DataProvider` de `BioscoreUnitParserTest`.

**Añadir una acción masiva**: clase que implemente `BulkAction` en `UI/Web/Admin/BioscoreCatalogItem/Bulk/` y registrarla en `ListController.php:59-62`.

Trampas conocidas:

- `SyncBioscoreCatalogsService` acumula contadores en propiedades; cualquier nuevo punto de entrada debe pasar por `__invoke`, que llama a `reset()`.
- `CatalogTargetFinder::all()` itera `translations()` de entidades Doctrine; con `limit: 0` son decenas de consultas por catálogo. Aceptable en el proceso diario y en la pantalla; si creciera, memorizar por petición.
- `NameNormalizer` exige la extensión `intl` (`\Normalizer`).
- En `test`, los `DeferredCommand` van a colas `in-memory://`: el botón web solo se prueba por flash y redirección; la lógica se prueba por consola.
- Un elemento de `transport_fuel` con varios `RoadTransport` del mismo combustible (distintos vehículos) queda siempre pendiente para el Administrador: es el comportamiento previsto.

## Tests

### Unitarios (`tests/Unit/Bioscore/`, 90 métodos)

- `Domain/Model/BioscoreCatalogItem/BioscoreCatalogItemTest.php` — eventos y fechas de `register`, `refresh` con/sin cambios y sobre retirado, `map` automático/manual/repetido, `unmap`, `ignore`, `withdraw`/`restore` idempotentes.
- `NameNormalizerTest.php`, `BioscoreUnitParserTest.php` — tabla completa de normalización y de unidades, `isCompatible` (vacía, desconocida, misma y distinta magnitud).
- `BioscoreCatalogItemRepositoryTest.php` (abstracto) + `InMemoryBioscoreCatalogItemRepositoryTest.php` — `add`/`find`, `findByCatalogAndBioscoreId`, filtros, `limit: 0`, `countPending`, mismo `bioscoreId` en dos catálogos.
- `Application/BioscoreCatalogItem/BioscoreCatalogItemAuthorizerTest.php` — Admin y SuperAdmin; Owner, User y Provider denegados; `Remove` denegado a todos.
- `CatalogTargetFinderTest.php` — `all` por catálogo, `find` con eliminado y con id no UUID, `matching` por traducción, acentos, unidad vacía/incompatible, dos candidatos, `vehicle` solo en `transport_fuel`.
- `Sync/SyncBioscoreCatalogsServiceTest.php` — alta y auto-mapeo, dos candidatos, unidad incompatible, unidad vacía, manual/ignorado intactos, destino desaparecido (con y sin re-mapeo), retirada y restauración, catálogo fallido, evento solo con novedades, un solo catálogo, Owner denegado.
- `Register`, `Map`, `Unmap`, `Ignore`, `List`, `UpdateMapping` — caso feliz, inexistente, destino inexistente, Owner denegado; `UpdateMapping` cubre además confirmar un auto-mapeo (pasa a manual), destino vacío desmapea, desmarcar "ignorar" devuelve a `Unmapped`, reenviar el mismo destino manual no publica.
- `Infrastructure/Notification/BioscoreCatalogSyncCompletedNotifierTest.php` — canal `Internal` a todos los Administradores, `params` con conteos y rutas, sin `Email`.
- `Infrastructure/Gateway/HttpBioscoreCatalogGatewayTest.php` — `MockHttpClient`: login + Bearer, separación por flags, reintento tras 401, 500, login fallido, fila sin id.

### Aceptación (`tests/Acceptance/Bioscore/`, 21 escenarios)

`BioscoreContext.php` configura `InMemoryBioscoreCatalogGateway` (`:48-61`), crea elementos con `BioscoreCatalogItemMother` (`:63-102`) y comprueba estados (`:104-136`); `reset()` del gateway en `#[AfterScenario]`.

- `bioscore-catalog-list.feature` — cada pantalla solo muestra su catálogo, las cuatro rutas responden, filtros independientes por pantalla, ignorar y quitar mapeo en masa, 403 para User y Owner.
- `bioscore-catalog-map.feature` — acceso a la edición desde la fila, mapear, ignorar, desmapear vaciando el destino, 404 y 403.
- `bioscore-catalog-sync-button.feature` — enlace "Sincronizar ahora" encola y vuelve a la misma pantalla; sin token → 403; Owner → 403.
- `bioscore-catalog-sync.feature` — por consola: auto-mapeo, dos candidatos pendientes, catálogo no disponible con código 0, retirada, notificación visible en "Mis notificaciones".

Ejecución:

```bash
make test-this test=tests/Unit/Bioscore
make test-this test=tests/Acceptance/Bioscore
```

Sin huecos de cobertura conocidos (`review.md`, sección *Cobertura de tests*).

## Desviaciones y deuda

Declarado en `## Implementación` de `plan.md`:

- `ListBioscoreCatalogItemsType`: el filtro `withdrawn` es un `BooleanType` ("Retirado": Sí/No) en lugar del `ChoiceType` del plan.
- `CatalogTargetFinder::all()`: desempate por vehículo con comparaciones explícitas (PHPStan).
- Escenario Behat de notificación: petición web previa al comando para registrar los suscriptores de eventos (limitación de `CommandTester`).
- `services.yaml` del BC repite `_defaults` bajo `when@dev`/`when@prod`.
- `make translation` catalogó además la clave preexistente `Huellas de Carbono`.

Revisión (`review.md`, `review-fix.md`): A1 (migración) y M1 (plantillas de extracción) corregidos; Q1 (texto de la notificación cuando solo hay invalidaciones) descartado por el usuario; D1 (interfaz fuera de convenciones) corregido y es el origen de la interfaz actual. **Deuda documental**: las secciones de UI de `plan.md` describen la pantalla anterior; conviene actualizarlas vía `feature-planner`.

Pendiente de operación: dar de alta el `ApiUser` en Bioscore y rellenar `BIOSCORE_API_USERNAME` / `BIOSCORE_API_PASSWORD` antes del despliegue; ejecutar la migración `Version20260917074340`.
