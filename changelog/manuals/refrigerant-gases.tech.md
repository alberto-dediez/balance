# Gases refrigerantes (maestro de factores de emisión) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-15 · **Bounded Contexts:** `CarbonFootprint` (más el menú lateral de `Shared` y los tipos DBAL de `config/`)

## Qué hace y por qué

Los Administradores de la plataforma mantienen los maestros que alimentan el cálculo de huella de carbono. Cada maestro lleva sus factores de emisión por año y por grupo de coeficientes. Antes de esta funcionalidad no existía maestro para los **gases refrigerantes** (R-410A, R-134a, R-32…), cuyas fugas en instalaciones de climatización son una partida habitual de la huella de un evento o de una propiedad.

La funcionalidad añade el agregado `RefrigerantGas` con el ciclo de vida completo de un maestro de plataforma: alta, edición, baja lógica, listado con filtro por nombre, edición en masa de factores por año y grupo (con copia de un año a otro), importación y exportación de hoja de cálculo, acciones en bloque, entrada en el menú lateral y trazas en el Registro de auditoría. Es un maestro **global**: no se scopea por cuenta y la cuenta actual no interviene en ningún punto.

La decisión estructural (ver la tabla `## Decisiones tomadas` del plan) fue replicar 1:1 el maestro `ElectricityMarketer`, el único que ya tenía **unidad fija**, cambiando esa unidad de `Unit::KWh` a `Unit::Kilogram` (kg CO2eq por kg de gas fugado). Se descartó `Waste` como plantilla porque allí la unidad es elegible por elemento. El maestro solo tiene el campo `name`: el PCA del gas queda expresado en el `co2eq` por kilogramo del factor, así que no se añadieron código, fórmula ni PCA de referencia. El asistente de cálculo de huella (actividades) queda **fuera de alcance**: no existe `RefrigerantGasActivity` ni paso de wizard, y será un plan posterior que dependa de este maestro.

## Mapa de piezas

Todos los ficheros de las tablas son **nuevos** salvo donde se indica «modificado».

### Dominio — `src/CarbonFootprint/Domain/Model/RefrigerantGas/`

| Fichero | Rol |
|---|---|
| `RefrigerantGas.php` | Agregado raíz (trait `AggregateRoot`); impone `Unit::Kilogram` al aplicar los eventos de factor |
| `RefrigerantGasId.php` | Value object UUID del agregado |
| `RefrigerantGasEmissionFactor.php` | Subentidad: un factor del gas (`EmissionFactor` embebido + `createdAt`/`updatedAt`) |
| `RefrigerantGasEmissionFactorId.php` | Value object UUID de la subentidad |
| `RefrigerantGasEmissionFactors.php` | Colección con `add`, `forYearAndGroup`, `hasForYearAndGroup`, `removeForYearAndGroup`, `years()`, `groups()` |
| `RefrigerantGasRepository.php` | Puerto: `add`, `remove`, `find`, `findByFilter`, `totalResultsByFilter` |
| `RefrigerantGasFilter.php` | Criterios de búsqueda/paginación (trait `Filter`, campo `name`, `limit` 10) |
| `RefrigerantGasRegistered.php` · `RefrigerantGasUpdated.php` · `RefrigerantGasRemoved.php` | Eventos de ciclo de vida del gas |
| `RefrigerantGasEmissionFactorAdded.php` · `...Updated.php` · `...Removed.php` | Eventos de ciclo de vida del factor |

### Aplicación — `src/CarbonFootprint/Application/RefrigerantGas/`

| Fichero | Rol |
|---|---|
| `RefrigerantGasAuthorizer.php` | Autorización por acción; `SuperAdmin` y `Admin` para todo |
| `Register/RegisterRefrigerantGas{Service,Request,Response}.php` | Alta por nombre |
| `Update/UpdateRefrigerantGas{Service,Request,Response}.php` | Cambio de nombre + conciliación completa de factores |
| `Update/EmissionFactorData.php` | DTO de una fila de factor de la pantalla de edición del gas |
| `Remove/RemoveRefrigerantGas{Service,Request,Response}.php` | Baja lógica |
| `List/ListRefrigerantGases{Service,Request,Response}.php` | Listado paginado y filtrado (`Request` extiende el filtro de dominio) |
| `Import/ImportRefrigerantGas{Service,Request,Response}.php` | Una fila de hoja de cálculo → gas (existente o nuevo) + factor |
| `RefrigerantGasImportationType.php` | Tipo de importación del pipeline `DataExchange` (código `refrigerant_gases`) |
| `Export/RefrigerantGasesExportFilter.php` | Filtro de exportación (filtro de dominio + `emissionFactorGroup`) |
| `EmissionFactor/EmissionFactorRow.php` | DTO de una fila de la pantalla de edición en masa; `isEmpty()` decide el borrado |
| `EmissionFactor/List/ListEmissionFactors{Service,Request,Response}.php` | Resuelve año a mostrar y `canGoNext` |
| `EmissionFactor/Update/UpdateEmissionFactors{Service,Request,Response}.php` | Guardado en masa de un año |
| `EmissionFactor/CreateYear/CreateEmissionFactorsYear{Service,Request,Response}.php` | Copia de todos los factores de un año a otro |
| `EmissionFactor/CreateYear/EmissionFactorYearAlreadyExistsException.php` | El año destino ya tiene factores |

### Persistencia — `src/CarbonFootprint/Infrastructure/Persistence/`

| Fichero | Rol |
|---|---|
| `Doctrine/Mapping/RefrigerantGas.RefrigerantGas.orm.xml` | Tabla `refrigerant_gases`; `one-to-many` con `orphan-removal` y `fetch="EXTRA_LAZY"` |
| `Doctrine/Mapping/RefrigerantGas.RefrigerantGasEmissionFactor.orm.xml` | Tabla `refrigerant_gas_emission_factor`; índice único y FK `ON DELETE CASCADE` |
| `Doctrine/Type/RefrigerantGasIdType.php` | Tipo DBAL `refrigerant_gas_id` (`AggregateRootIdTypeTrait`) |
| `Doctrine/Type/RefrigerantGasEmissionFactorIdType.php` | Tipo DBAL `refrigerant_gas_emission_factor_id` |
| `Doctrine/Repository/DoctrineRefrigerantGasRepository.php` | Adaptador de producción (`#[When('dev')]`, `#[When('prod')]`) |
| `InMemory/Repository/InMemoryRefrigerantGasRepository.php` | Adaptador de test (`#[When('test')]`) |
| `config/packages/doctrine.yaml` | **Modificado**: los dos tipos DBAL bajo `dbal.types` |

### Infraestructura de UI — `src/CarbonFootprint/Infrastructure/`

| Fichero | Rol |
|---|---|
| `Symfony/Form/RegisterRefrigerantGasType.php` | Alta: `name` |
| `Symfony/Form/UpdateRefrigerantGasType.php` | Edición: `name` + `GroupedEmissionFactorsType` |
| `Symfony/Form/ListRefrigerantGasesType.php` | Filtro del listado (extiende `BaseFilterType`) |
| `Symfony/Form/AdminRefrigerantGasesExportType.php` | Filtro de exportación: `name` + `Grupo` |
| `Symfony/Form/BulkRefrigerantGasEmissionFactorsType.php` | Colección de filas de la edición en masa |
| `Symfony/Form/CreateRefrigerantGasEmissionFactorsYearType.php` | Copia de año: `targetYear` + grupo oculto |
| `Symfony/Form/Type/RefrigerantGasChoiceType.php` | Selector autocompletado (padre `AutocompleteType`), desambigua nombres repetidos |
| `Symfony/Form/Type/RefrigerantGasEmissionFactorRowType.php` | Fila de la edición en masa; validación «todo o nada» |
| `Symfony/Form/Type/RefrigerantGasEmissionFactorType.php` | Fila de factor en la edición del gas (incluye `year`) |
| `DataExchange/RefrigerantGasesExportationType.php` | Tipo de exportación (código `refrigerant_gases`), una fila por factor |
| `UI/Web/Admin/RefrigerantGas/ListController.php` | Listado (`FilterTrait` + `BulkTrait`) |
| `UI/Web/Admin/RefrigerantGas/RegisterController.php` | Alta; redirige a la edición |
| `UI/Web/Admin/RefrigerantGas/EditController.php` | Edición (`EditTrait`) |
| `UI/Web/Admin/RefrigerantGas/RemoveController.php` | Baja lógica |
| `UI/Web/Admin/RefrigerantGas/GetRefrigerantGasFromIdTrait.php` | Resolución por id con 404 «Gas refrigerante no encontrado» |
| `UI/Web/Admin/RefrigerantGas/Bulk/RemoveAction.php` | Acción en bloque de eliminación |
| `UI/Web/Admin/RefrigerantGas/EmissionFactor/FactorEmissionController.php` | Pantalla de factores por año y grupo + guardado en masa |
| `UI/Web/Admin/RefrigerantGas/EmissionFactor/CreateYearController.php` | Copia de año (POST) |

### Plantillas, menú y traducciones

| Fichero | Rol |
|---|---|
| `templates/carbon_footprint/admin/refrigerant_gases/list.html.twig` | Listado; columna «Unidad» fija con `'kg' \| trans({}, 'unitType')` |
| `templates/carbon_footprint/admin/refrigerant_gases/register.html.twig` | Alta |
| `templates/carbon_footprint/admin/refrigerant_gases/edit.html.twig` | Edición; incluye el parcial compartido `@carbon_footprint/admin/emission_factors/_form.simple.html.twig` |
| `templates/carbon_footprint/admin/refrigerant_gases/emission_factors.html.twig` | Edición en masa: navegación de años, selector de grupo, copia de año, tabla |
| `templates/security/admin/events/detail/carbon-footprint.refrigerantGas*.html.twig` (6) | Textos del Registro de auditoría, uno por evento de dominio |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:263-267` | **Modificado**: nodo «Gases refrigerantes» (icono `solar:snowflake-line-duotone`) en el grupo «Instalaciones», entre «Factores GdO» y «Residuos» |
| `translations/fields+intl-icu.{es,en,tr}.xlf` | **Modificados**: clave `refrigerantGas` («Gas refrigerante» / «Refrigerant gas» / «Soğutucu gaz») |
| `translations/messages+intl-icu.{es,en,tr}.xlf` | **Modificados**: 14 claves de interfaz y auditoría por `make translation` |

### Tests

22 ficheros nuevos: 5 en `tests/Unit/CarbonFootprint/Domain/Model/RefrigerantGas/`, 9 en `tests/Unit/CarbonFootprint/Application/RefrigerantGas/`, 7 `.feature` en `tests/Acceptance/CarbonFootprint/` y **modificado** `tests/Acceptance/CarbonFootprint/CarbonFootprintContext.php` (getter del repositorio en `:158-161` y dos pasos `Given` en `:372-400`). Detalle en § Tests.

## Modelo de dominio

### Agregado

`RefrigerantGas` (`src/CarbonFootprint/Domain/Model/RefrigerantGas/RefrigerantGas.php:13-32`) usa el trait `AggregateRoot` y promociona en el constructor `id`, `name`, `removed`, `registeredAt`, `updatedAt`, `removedAt`, más la colección `emissionFactors` (`iterable`/`Collection` de Doctrine). Implementa `\Stringable` devolviendo el nombre (`:213-216`), lo que alimenta las etiquetas del selector autocompletado.

Métodos de negocio:

- `register(string $name)` / `update(string $name)` / `remove()` (`:74-101`) — publican `RefrigerantGasRegistered` / `Updated` / `Removed`. La baja es **lógica**: `applyRefrigerantGasRemoved` pone `removed = true` y `removedAt` (`:183-187`).
- `setEmissionFactor(EmissionFactor)` (`:103-129`) — si no hay factor para `(año, grupo)` publica `...EmissionFactorAdded` con un id nuevo; si hay y es igual (`EmissionFactor::equals`) **no publica nada**; si cambia publica `...EmissionFactorUpdated`.
- `removeEmissionFactor(int $year, EmissionFactorGroupId)` (`:131-144`) — publica `...EmissionFactorRemoved` solo si el factor existe.
- `syncEmissionFactors(EmissionFactor[])` (`:147-169`) — concilia la lista completa: añade/actualiza los recibidos y elimina los que ya no vienen. Es el método que usa la pantalla de edición del gas.
- `emissionFactorFor(int $year, EmissionFactorGroupId): ?EmissionFactor` (`:69-72`) — lectura puntual.

### Invariantes

| Invariante | Dónde se protege |
|---|---|
| Como máximo un factor por `(gas, año, grupo)` | `RefrigerantGasEmissionFactors::add` ignora el duplicado (`RefrigerantGasEmissionFactors.php:29-36`); `setEmissionFactor` decide añadir o actualizar; en base de datos, el índice único `uq_refrigerant_gas_emission_factor_year` |
| Ningún factor duplicado en una misma sincronización | `syncEmissionFactors` lanza `\InvalidArgumentException` ante dos entradas con la misma clave `año\|grupo` (`RefrigerantGas.php:154-156`) |
| **`unit` de todo factor = `kg`** | Se impone al **aplicar** los eventos: `withUnit(Unit::Kilogram)` en `applyRefrigerantGasEmissionFactorAdded` (`:195`) y `applyRefrigerantGasEmissionFactorUpdated` (`:205`). Es una invariante del agregado, no del formulario: da igual con qué unidad se construya el `EmissionFactor` que entra |
| Año destino de la copia `<= año actual` y `!= año origen` | `CreateEmissionFactorsYearService.php:39` y `:43` (`\InvalidArgumentException`); además el formulario lo acota con `Range(1900, año actual)` |
| Ningún gas tiene ya factor en `(año destino, grupo)` antes de copiar | `CreateEmissionFactorsYearService.php:47-51` lanza `EmissionFactorYearAlreadyExistsException` |
| Fila de la edición en masa: los cuatro gases, o ninguno | `RefrigerantGasEmissionFactorRowType::validateAllOrNothing`; fila vacía (`EmissionFactorRow::isEmpty()`, `EmissionFactorRow.php:44`) significa **eliminar** el factor |

Además, la unidad se fija **también** en los tres servicios que construyen factores, de forma redundante con el agregado: `UpdateRefrigerantGasService.php:47`, `UpdateEmissionFactorsService.php:47` e `ImportRefrigerantGasService.php:94`. Son cinco puntos en total; si se añade un cuarto camino de escritura, el agregado ya garantiza la invariante, pero conviene mantener la coherencia.

### Value object compartido `EmissionFactor`

No se tocó. Dos comportamientos heredados que condicionan lo que ve el usuario:

- `EmissionFactor::__construct` copia `co2eq` en `co2` cuando `co2`, `ch4` y `n2o` son 0 (`src/CarbonFootprint/Domain/Model/EmissionFactor/EmissionFactor.php:31-35`). Para un gas refrigerante basta rellenar `co2eq`.
- `EmissionFactorGroup::buildEmissionFactor` calcula `co2eq` a partir de los tres gases y el GWP del grupo cuando `co2eq` llega a 0 (`src/CarbonFootprint/Domain/Model/EmissionFactorGroup/EmissionFactorGroup.php:170-177`). Es la vía por la que todos los servicios construyen factores, así que el camino inverso también funciona.

### Eventos de dominio

Los seis extienden `App\CarbonFootprint\Domain\DomainEvent`, declaran `$eventName` en camelCase y definen `payload()` / `rebuildFromPayload()`.

| Evento | `$eventName` | Payload |
|---|---|---|
| `RefrigerantGasRegistered` | `refrigerantGasRegistered` | `refrigerantGasId`, `name` |
| `RefrigerantGasUpdated` | `refrigerantGasUpdated` | `refrigerantGasId`, `name` |
| `RefrigerantGasRemoved` | `refrigerantGasRemoved` | `refrigerantGasId` |
| `RefrigerantGasEmissionFactorAdded` | `refrigerantGasEmissionFactorAdded` | `refrigerantGasId`, `emissionFactorId`, `co2eq`, `co2`, `ch4`, `n2o`, `year`, `unit`, `source`, `emissionFactorGroupId` |
| `RefrigerantGasEmissionFactorUpdated` | `refrigerantGasEmissionFactorUpdated` | igual, sin `emissionFactorId` |
| `RefrigerantGasEmissionFactorRemoved` | `refrigerantGasEmissionFactorRemoved` | `refrigerantGasId`, `year`, `emissionFactorGroupId` |

Los tres de factor serializan `unit` y la reconstruyen con `Unit::from($payload['unit'])` (`RefrigerantGasEmissionFactorAdded.php:128`). `RefrigerantGasEmissionFactorAdded::rebuildFromPayload` tolera payloads antiguos sin `emissionFactorId` generando uno nuevo (`:132-134`).

Cada `$eventName` tiene su plantilla de auditoría en `templates/security/admin/events/detail/carbon-footprint.<eventName>.html.twig`, que es cómo `templates/security/admin/events/list.html.twig` resuelve el texto legible del Registro.

## Flujo de una operación

### Alta

`GET/POST /{_locale}/admin/refrigerant-gases/register` → `RegisterController` (`RegisterController.php:21`).

1. `denyAccessUnlessGranted(Action::Register, new RefrigerantGas(...))` antes de construir el formulario (`:28`).
2. `RegisterRefrigerantGasType` valida `name` (`NotBlank`, `Length(max: 255)`).
3. `RegisterRefrigerantGasService` genera el id, autoriza con `SecurityAction::List` (`RegisterRefrigerantGasService.php:32` — convención heredada de todos los maestros; el `Authorizer` hace `canRegister` → `canList`, así que es equivalente), instancia el agregado, llama `register()` y persiste.
4. Flash `success` «Gas refrigerante creado correctamente: {name}» y **redirección a la edición** del gas recién creado (`RegisterController.php:49`), para que el Administrador continúe con los factores.

### Edición del gas (nombre + factores por grupo)

`GET/POST /{_locale}/admin/refrigerant-gases/edit/{refrigerantGasId}` → `EditController` (`EditController.php:22`).

1. `GetRefrigerantGasFromIdTrait` resuelve el agregado o lanza 404.
2. `denyAccessUnlessGranted(Action::Edit, $refrigerantGas)` (`:37`).
3. Se hidrata un `UpdateRefrigerantGasRequest` con un `EmissionFactorData` por factor existente.
4. Al enviar, `UpdateRefrigerantGasService`: autoriza `Edit` (`:40`), `update()` del nombre y `syncEmissionFactors()` con los factores del formulario, cada uno construido con `Unit::Kilogram` y con su grupo resuelto vía `EmissionFactorGroupRepository` (`\InvalidArgumentException('Emission factor group not found')` si no existe, `:69`).
5. Flash «Gas refrigerante actualizado con éxito»; «Guardar y salir» redirige al listado (`EditController.php:62`), «Guardar» recarga.

### Edición en masa por año y grupo

`GET/POST /{_locale}/admin/refrigerant-gases/emission-factors/{emissionFactorGroup}/{year}` → `FactorEmissionController` (`FactorEmissionController.php:29-34`). Ambos parámetros son **opcionales** (`defaults`) y `year` está restringido a `\d+`.

1. Sin grupo en la URL se usa el grupo por defecto (`emissionFactorGroupRepository->findDefault()`); si no hay ninguno, `NoDefaultEmissionFactorGroupException` (`:60-62`).
2. `ListEmissionFactorsService` autoriza `List` (`ListEmissionFactorsService.php:25`), carga **todos** los gases (`RefrigerantGasFilter(limit: 0)`) y resuelve el año: el pedido, o el **último con datos para ese grupo**, o el año actual (`:31`). `canGoNext = year < currentYear` (`:37`).
3. Se construye una `EmissionFactorRow` por gas con el factor de ese año y grupo (o vacía) y se monta `BulkRefrigerantGasEmissionFactorsType`.
4. Al enviar, `UpdateEmissionFactorsService` recorre fila a fila: busca el gas (si no existe, **salta** la fila, `:36-38`), autoriza `Edit` (`:40`), y si la fila está vacía elimina el factor, o lo fija con `Unit::Kilogram` (`:42-48`). Persiste por fila.
5. Flash «Factores de emisión actualizados con éxito».

La navegación de años son enlaces `year ± 1` a la misma ruta; el selector de grupo es el componente compartido `twig:EmissionFactorGroup:Selector`.

### Copia de un año a otro

`POST /{_locale}/admin/refrigerant-gases/emission-factors/{emissionFactorGroup}/{sourceYear}/create-year` → `CreateYearController` (`CreateYearController.php:19-25`).

1. Formulario inválido → flash «No se ha podido crear el nuevo año.» y vuelta al año origen (`:41-43`).
2. `CreateEmissionFactorsYearService`: autoriza `Edit` (`:32`), valida año destino no futuro (`:39`) y distinto del origen (`:43`); **primer bucle** comprueba que ningún gas tenga ya factor en `(destino, grupo)` (`:47-51`); **segundo bucle** copia con `$sourceFactor->withYear($targetYear)` los gases que tengan factor en el origen, saltando los que no (`:53-62`).
3. Éxito → flash «Nuevo año creado con éxito» y redirección al **año destino** (`CreateYearController.php:51`). `EmissionFactorYearAlreadyExistsException` → «El año destino ya tiene factores de emisión.» (`:55`); `\InvalidArgumentException` → «El año destino no es válido.» (`:57`).

### Importación

Pipeline genérico de `DataExchange` en `/{_locale}/refrigerant_gases/import` (pasos: subida → emparejar campos → traducciones → valores por defecto → finalizar → resultados). `RefrigerantGasImportationType` se registra solo, por el `#[AutoconfigureTag('exchange.importation.type')]` de la interfaz `ImportationType`.

- `code()` `refrigerant_gases`, `returnRoute()` `admin_refrigerant_gases`.
- Campos: `refrigerantGas`, `name`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source`, `emissionFactorGroup`.
- `refrigerantGas` y `emissionFactorGroup` usan `MapExtractor` (valores del fichero traducidos a entidades); el resto `DirectExtractor`; los campos sin columna emparejada caen en `AssignValueExtractor` con el valor por defecto de la importación.
- `isAuthorized` con `Action::Import`.

Por fila, `ImportRefrigerantGasService`:

1. Resuelve el año; `<= 0` → `\InvalidArgumentException('A valid year is required')` (`:117`).
2. Si viene `refrigerantGas`, lo busca (no existe → excepción, `:57`) y autoriza `Edit` (`:60`). Si no viene, usa `name` recortado; vacío → `\InvalidArgumentException('A refrigerant gas or a name is required')` (`:68`); si hay nombre, **registra un gas nuevo** autorizando `List` (`:78`).
3. Resuelve el grupo de coeficientes (obligatorio, `:100` y `:106`).
4. Construye el factor con `Unit::Kilogram` (`:94`), normalizando decimales con coma a punto y los vacíos a `'0'` (`:123-128`), y lo fija con `setEmissionFactor` (añade o actualiza).

Nota: la importación **no** deduplica por nombre. Dos filas con el mismo nombre y sin `refrigerantGas` crean dos gases distintos; `RefrigerantGasChoiceType` desambigua después las etiquetas repetidas con un sufijo `(1)`, `(2)`.

### Exportación

`/{_locale}/refrigerant_gases/export`, resuelto por `RefrigerantGasesExportationType` (registrado por el tag `exchange.exportation.type`).

- Columnas: `refrigerantGas` («Identificador», **no seleccionada por defecto**), `name`, `year`, `co2eq`, `co2`, `ch4`, `n2o`, `source`, `emissionFactorGroup`.
- `rows()` fuerza `limit = 0` y emite **una fila por factor**; un gas sin factores emite una fila con las columnas de factor vacías (`RefrigerantGasesExportationType.php:109-113`). El filtro por grupo descarta factores de otros grupos (`:116-120`).
- Los nombres de grupo se resuelven con caché por id (`:178-188`).
- `isAuthorized` con `Action::Export`.

### Baja

- Individual: `GET /{_locale}/admin/refrigerant-gases/remove/{refrigerantGasId}` → `RemoveController`. Baja lógica vía `RemoveRefrigerantGasService` (autoriza `Remove`, `:31`); flash de éxito o, ante cualquier `\Exception`, «No se ha podido eliminar el gas refrigerante {name}» (`RemoveController.php:37`). Siempre redirige al listado.
- En bloque: `Bulk/RemoveAction` construye un `RemoveRefrigerantGasRequest` por elemento marcado, con mensajes por id.

Tras la baja el gas desaparece de listados, selectores y exportaciones, porque todas las consultas filtran `removed` (`DoctrineRefrigerantGasRepository.php:55-61`).

## Autorización y ámbito

`RefrigerantGasAuthorizer` extiende `AbstractAuthorizer` y `supports()` por `instanceof RefrigerantGas`.

| Acción | Resolución | Quién puede |
|---|---|---|
| `List` | `userHasRole(SuperAdmin) \|\| userHasRole(Admin)` (`:26`) | SuperAdmin, Admin |
| `Edit` | idéntico (`:31`) | SuperAdmin, Admin |
| `Register` | → `canList` (`:16`) | SuperAdmin, Admin |
| `View` | → `canEdit` (`:21`) | SuperAdmin, Admin |
| `Remove` | → `canEdit` (`:36`) | SuperAdmin, Admin |
| `Import` | → `canRegister` → `canList` (`:41`) | SuperAdmin, Admin |
| `Export` | → `canList` (`:46`) | SuperAdmin, Admin |

`Owner`, `User` y `Provider` reciben **403** en todas las pantallas (cubierto en los `.feature`). No hay `Action::History` ni `Autocomplete` propios.

Detalles de ámbito:

- Es un **maestro global**: no se usa `CurrentAccountIdProvider` en ningún punto, y los roles se consultan siempre con `userHasRole` del `AbstractAuthorizer`, que ya respeta la impersonación.
- No hay feature flags ni variables de entorno propias.
- La autorización se repite en la capa UI (`denyAccessUnlessGranted` en `RegisterController:28` y `EditController:37`, y el filtro `is_authorized` de los botones del listado) y en la de aplicación (`denyUnlessCanDoThat` al inicio de cada servicio), que es la que manda.

## Persistencia y migración

### `refrigerant_gases`

`id` (VARCHAR(36), `DC2Type:refrigerant_gas_id`, PK), `name` (VARCHAR(255) NOT NULL), `removed` (TINYINT(1) NOT NULL), `registered_at` (DATETIME NOT NULL), `updated_at` / `removed_at` (DATETIME NULL). Relación `one-to-many` a la subentidad con `orphan-removal="true"`, `fetch="EXTRA_LAZY"` y cascade persist + remove.

### `refrigerant_gas_emission_factor`

`id` (VARCHAR(36), `DC2Type:refrigerant_gas_emission_factor_id`, PK), `refrigerant_gas_id`, `created_at` (NOT NULL), `updated_at` (NULL), y los seis campos que entran por el `<embedded ... use-column-prefix="false">` de `EmissionFactor`: `co2eq`, `co2`, `ch4`, `n2o` (NUMERIC(20,8)), `year` (INT), `emission_factor_group_id`, `unit` (VARCHAR(255)), `source` (VARCHAR(255) NULL).

Índices: `INDEX idx_refrigerant_gas_emission_factor_gas (refrigerant_gas_id)`, `UNIQUE INDEX uq_refrigerant_gas_emission_factor_year (refrigerant_gas_id, year, emission_factor_group_id)`, y FK a `refrigerant_gases(id)` con `ON DELETE CASCADE`.

### Migración: **pendiente**

⚠️ **Las dos tablas no existen todavía en `migrations/`.** Es el hallazgo A1 de `review.md`, confirmado en `review-fix.md` y delegado al usuario por el propio plan: la migración se crea **a mano** desde `migration.sql` (up y down), nunca con `make migration`.

- Sin backfill: no hay datos previos.
- Comprobar antes de aplicar: `SHOW TABLES LIKE 'refrigerant_gas%';` no debe devolver nada.
- `review-fix.md` verificó que `migration.sql` es coherente columna a columna con los dos mappings XML.
- Los tests pasan porque el entorno de test reconstruye el esquema desde el mapping (`doc:sch:drop` + `doc:sch:upd`); dev y prod aplican migraciones, así que sin ella el maestro no es desplegable.

## Configuración y operación

- **Tipos DBAL**: `config/packages/doctrine.yaml` declara `refrigerant_gas_id` y `refrigerant_gas_emission_factor_id` bajo `dbal.types`. Es el único cambio de configuración.
- **Servicios**: nada que declarar. `src/CarbonFootprint/Infrastructure/Symfony/DependencyInjection/services.yaml` carga todo `App\CarbonFootprint\` por `resource`, y los tipos de importación/exportación se autoconfiguran por tag.
- **Rutas**: atributos `#[Route]` en los controladores; `config/routes.yaml` ya carga las rutas de `CarbonFootprint`.
- **Sin colas, comandos de consola, tareas programadas, correos ni notificaciones propios.** La importación y la exportación usan la infraestructura asíncrona genérica de `DataExchange`, sin nada específico de este maestro.
- **Menú**: el nodo vive en `NodeLister.php:263-267`. El icono `solar:snowflake-line-duotone` se resuelve bajo demanda por UX Icons; `config/packages/ux_icons.yaml` fija `ignore_not_found: false` fuera de producción, así que un icono inexistente rompería cualquier pantalla con menú. `review-fix.md` [Q1] confirmó con la caché y el log de descarga que el icono existe.
- **Traducciones**: `make translation` extrae ES, EN y TR. La clave `refrigerantGas` del dominio `fields` no la genera con valor: sus tres targets están escritos a mano.

## Cómo extenderlo

**Añadir un campo al maestro** (p. ej. una fórmula química): campo promocionado en `RefrigerantGas` + payload de `RefrigerantGasRegistered`/`Updated` y sus `apply*` → `<field>` en el mapping XML → migración a mano → `RegisterRefrigerantGasRequest`/`UpdateRefrigerantGasRequest` y sus servicios → `RegisterRefrigerantGasType`/`UpdateRefrigerantGasType` → columna en `list.html.twig` → campos de importación y exportación si procede → `RefrigerantGasMother` y tests.

**Añadir un camino nuevo de escritura de factores**: construir el `EmissionFactor` con `EmissionFactorGroup::buildEmissionFactor(..., Unit::Kilogram, ...)` y llamar `setEmissionFactor`. La unidad la garantiza el agregado al aplicar el evento, así que no se puede colar un factor en otra unidad; aun así, pasar `Unit::Kilogram` explícitamente mantiene la coherencia con los otros tres servicios.

**Enganchar el maestro al asistente de huella** (el plan posterior previsto): hace falta un agregado de actividad (`RefrigerantGasActivity`) siguiendo el patrón de las otras `*Activity` de `src/CarbonFootprint/Domain/Model/Activity/`, su paso de wizard, la entrada de catálogo, la resolución del nombre en el informe y el selector de factor. Este maestro no necesita cambios: ya expone `emissionFactorFor(año, grupo)`.

**Trampas conocidas**

- `RefrigerantGasEmissionFactors::__construct` recibe la colección **por referencia** (`iterable &$items`) y la convierte en `ArrayCollection` si no lo es. `RefrigerantGas::emissionFactors()` devuelve un envoltorio nuevo en cada llamada sobre la **misma** colección subyacente: las mutaciones sí se propagan, pero no conviene cachear el envoltorio.
- `setEmissionFactor` no publica nada si el factor es igual (`equals`). Un test que espere un evento tras «guardar lo mismo» fallará por diseño.
- La fila vacía en la edición en masa **elimina** el factor; es la única vía de borrado de un factor desde esa pantalla.
- `UpdateEmissionFactorsService` salta silenciosamente las filas cuyo gas ya no existe (`:36-38`), lo que hace idempotente el reenvío de un formulario obsoleto.
- Los mensajes de interfaz se escriben en español en el código y se extraen con `make translation`; revisar el diff, porque el target regenera ficheros grandes (durante la implementación arrastró claves de validadores ajenas al plan, que se revirtieron).

## Tests

**Unitarios** (`make test-unit`, o dirigidos):

```bash
make test-this test=tests/Unit/CarbonFootprint/Domain/Model/RefrigerantGas
make test-this test=tests/Unit/CarbonFootprint/Application/RefrigerantGas
```

- `RefrigerantGasTest` (10 casos) — los tres eventos de ciclo de vida con `SpyDomainEventSubscriber`, `testSetEmissionFactorAddsIt`, `testSetEmissionFactorForSameYearReplacesIt`, `testEmissionFactorForReturnsNullWhenMissing`, `testSetEmissionFactorUsesKilogramUnit` (construye el factor con otra unidad y comprueba que queda `Kilogram`), `testSyncEmissionFactorsAddsUpdatesAndRemoves`, `testSyncEmissionFactorsThrowsOnDuplicateYear`, `testRemoveEmissionFactor`.
- `RefrigerantGasRepositoryTest` (abstracto, 5 casos) + `InMemoryRefrigerantGasRepositoryTest` — add, remove, `findByFilter` por nombre y paginación, `limit: 0` devuelve todo, `find` no recupera eliminados.
- `RefrigerantGasMother` (`registeredRefrigerantGas`, `fromName`, `fromNameAndId`, `from`, `random`) y `RefrigerantGasRepositoryMother::inMemory()`.
- Aplicación (30 casos): `RefrigerantGasAuthorizerTest` (6: admin puede List/Register/Edit/Remove; owner y user no pueden List), `Register` (2), `Update` (2, incluido `testUpdateSyncsEmissionFactors`), `Remove` (2), `List` (2, con `testFilterByName`), `Import` (6, incluidos los cuatro caminos de error), `EmissionFactor/List` (3: último año con datos, año actual sin datos, año pedido con `canGoNext`), `EmissionFactor/Update` (3: añade, elimina con fila vacía, `isHandled`), `EmissionFactor/CreateYear` (4: copia, año ya existente, año futuro, `isHandled`).

**Aceptación** (`make test-acceptance`, o `make test-this test=tests/Acceptance/CarbonFootprint/refrigerant-gas-list.feature`): siete `.feature` — `list` (tabla + 403 de `ROLE_USER`), `register` (acceso, 403, alta con flash, redirección a `admin_refrigerant_gases_edit`), `edit` (guardado con flash, presencia de «Factores de emisión», 403 de `ROLE_OWNER`), `remove` (flash, 403), `emission-factors` (último año con datos, navegación a un año concreto con «Copiar factores», guardado en masa, 403 de owner), `import` (los 8 pasos del pipeline genérico con `type "refrigerant_gases"`), `export` (pantalla 200/403 y descarga de una exportación completada).

Pasos añadidos en `CarbonFootprintContext.php`: `getRefrigerantGasRepository()` (`:158-161`), `Given refrigerant gas :name with id :id exists` (`:372-380`) y `Given refrigerant gas :name with id :id and emission factor for year :year exists` (`:382-400`, factor `1.5/1.0/0.2/0.3` con fuente `IPCC` y grupo por defecto).

**Huecos de cobertura conocidos**: no hay test de la copia de año que cubra la carrera concurrente descrita en `review.md` [B2] (asumida), ni tests de los form types (`validateAllOrNothing` se ejercita solo indirectamente por el escenario de guardado en masa). Tampoco hay cobertura del `RefrigerantGasesExportationType` más allá del escenario de pantalla y descarga.

## Desviaciones y deuda

Del plan (`plan.md` § Implementación):

- `make translation` genera también los targets turcos: se rellenaron los 14 nuevos en los tres idiomas (ES, EN, TR) en vez de solo ES y EN.
- `make translation --force` arrastró claves de validadores de Symfony ajenas al plan a `translations/validators.{es,en,tr}.xlf`; se revirtieron esos tres ficheros.
- `.claude/CONCEPTS.md` es un symlink fuera del repo (`ai/events/CONCEPTS.md`): la fila de glosario se añadió pero no aparece en `git status`.
- Deuda técnica declarada por el implementador: ninguna.

De la revisión (`review.md` y su triaje en `review-fix.md`):

- **[A1] 🟠 Abierto — migración pendiente.** Confirmado, sin corrección de código: la crea el usuario desde `migration.sql`. Ver § Persistencia y migración.
- **[B1] 🔵 Asumido.** El mensaje de `\InvalidArgumentException` en `syncEmissionFactors` (`RefrigerantGas.php:155`) nombra solo el año, aunque la clave es `año|grupo`. Descartado por el usuario: el mensaje no llega a la interfaz, el duplicado no es alcanzable desde la UI y es idéntico en los 12 agregados de factores del contexto; corregirlo solo aquí divergiría de la familia.
- **[B2] 🔵 Asumido.** La comprobación de año destino en la copia no es atómica: dos POST concurrentes pueden acabar en un 500 por violación del índice único en vez del flash amigable. Descartado por el usuario: sin corrupción de datos (el índice protege), ventana mínima, acción admin-only, y el patrón es idéntico en los 12 controladores análogos.
- **[Q1] ✅ Cerrado.** El icono `solar:snowflake-line-duotone` existe (verificado en la caché de iconos y el log de descarga).
