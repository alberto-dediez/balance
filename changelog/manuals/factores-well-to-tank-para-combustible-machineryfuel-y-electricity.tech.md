# Factores well-to-tank para combustibles, combustibles de maquinaria y electricidad — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-20 · **Revisado:** `review.md` (3 revisiones, 0 hallazgos abiertos) · **Bounded Context:** `CarbonFootprint`

## Qué hace y por qué

La categoría 3 del alcance 3 del GHG Protocol («Actividades relacionadas con combustibles y energía») necesita las emisiones *aguas arriba* de los combustibles y de la electricidad que ya se contabilizan en los alcances 1 y 2: extracción, refinado y transporte hasta el depósito (**well-to-tank**, WTT). Hasta ahora los maestros guardaban por año y grupo de coeficientes un único factor (combustión o generación).

La funcionalidad añade a cada fila de factor de cinco maestros — `Combustible`, `MachineryFuel`, `ElectricityMix`, `ElectricityMarketer` y `GdOFactor` — un **segundo `EmissionFactor` completo** (`co2eq`, `co2`, `ch4`, `n2o`, `source` propia), con el mismo año, grupo y unidad que el principal. Es el patrón de `MaterialEmissionFactor` con su `recycledFactor`. El WTT es **opcional**: «sin dato» se guarda como factor a cero (`EmissionFactor::zeroFor()`), porque un embebido de Doctrine no puede ser `NULL` (decisión «WTT opcional» del plan).

Las actividades `CombustibleActivity`, `MachineryFuelActivity` y `ElectricityActivity` guardan una instantánea del factor WTT y las emisiones WTT (cantidad × factor WTT). `ElectricityActivity` guarda además el WTT del resultado por localización (mix eléctrico). **Nada de esto entra en totales, resultados, informe, compensación ni cálculo AJAX**: queda persistido y expuesto en dominio para un desarrollo posterior de alcance 3 (decisión «Totales de huella»).

## Mapa de piezas

`<M>` ∈ {`Combustible`, `MachineryFuel`, `ElectricityMix`, `ElectricityMarketer`, `GdOFactor`}. Todos los ficheros son modificados salvo los marcados como nuevos.

| Capa | Fichero | Rol |
|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/EmissionFactor/EmissionFactor.php:47` | `zeroFor(year, group, unit)`: factor a cero sin fuente |
| Dominio | `src/CarbonFootprint/Domain/Model/<M>/<M>EmissionFactor.php` | Entidad hija con `wellToTankFactor`, `update()` de tres argumentos y `assertSameYearAndGroup()` (`:72`) |
| Dominio | `src/CarbonFootprint/Domain/Model/<M>/<M>.php` | `wellToTankFactorFor()`, `setEmissionFactor(EmissionFactor, ?EmissionFactor = null)`, `syncEmissionFactors()` con pares, `apply*` |
| Dominio | `src/CarbonFootprint/Domain/Model/<M>/<M>EmissionFactor{Added,Updated}.php` | Payload con claves `wellToTank*`; reconstrucción a cero si faltan |
| Dominio | `src/CarbonFootprint/Domain/Model/Activity/{Combustible,MachineryFuel,Electricity}Activity.php` | Factor y emisiones WTT; en electricidad, WTT de localización |
| Dominio | `src/CarbonFootprint/Domain/Model/Activity/{Combustible,MachineryFuel,Electricity}Activity{Registered,Updated}.php` | Payload y reconstrucción de los nuevos valores |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/<M>.<M>EmissionFactor.orm.xml` | Embebido `wellToTankFactor` con prefijo `well_to_tank_` |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/Activity.{Combustible,MachineryFuel,Electricity}Activity.orm.xml` | Embebidos `wellToTankEmissionFactor` / `wellToTankEmissions`; nueve campos `locationBasedWellToTank*` en electricidad |
| Persistencia | `migrations/Version20261020165447.php` (nuevo, creado por el usuario) | Migración de las ocho tablas; contenido idéntico a `migration.sql` |
| Aplicación | `src/CarbonFootprint/Application/Activity/{Combustible,MachineryFuel,Electricity}EmissionFactorSelector.php` | `selectWellToTank()` y método privado común `emissionFactorRowFor()` |
| Aplicación | `src/CarbonFootprint/Application/Activity/ElectricityMixEmissions.php`, `ElectricityMixEmissionsResolver.php` | WTT del mix y sus emisiones |
| Aplicación | `src/CarbonFootprint/Application/Activity/{Register,Update}/*{Combustible,MachineryFuel,Electricity}ActivityService.php` (6) | Seleccionan y calculan el WTT y lo pasan a la actividad |
| Aplicación | `src/CarbonFootprint/Application/<M>/EmissionFactor/EmissionFactorRow.php`, `Update/EmissionFactorData.php` | Cinco campos WTT; `wellToTankEmissionFactor()`; `isWellToTankEmpty()` |
| Aplicación | `src/CarbonFootprint/Application/<M>/EmissionFactor/Update/UpdateEmissionFactorsService.php`, `Update/Update<M>Service.php`, `EmissionFactor/CreateYear/CreateEmissionFactorsYearService.php` | Pasan o copian ambos factores |
| Aplicación | `src/CarbonFootprint/Application/<M>/<M>ImportationType.php`, `Import/Import<M>Request.php`, `Import/Import<M>Service.php` | Cinco columnas de importación y `buildWellToTankEmissionFactor()` |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/<M>EmissionFactorType.php`, `<M>EmissionFactorRowType.php` | Campos WTT y validación «todo o nada» |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/Admin/<M>/EditController.php`, `EmissionFactor/FactorEmissionController.php` | Construyen los DTO desde la entidad hija |
| Infraestructura | `src/CarbonFootprint/Infrastructure/DataExchange/{Combustibles,MachineryFuels,ElectricityMixes,ElectricityMarketers,GdOFactors}ExportationType.php` | Cinco columnas WTT tras `source` |
| UI | `templates/carbon_footprint/admin/emission_factors/_form.well_to_tank.html.twig` (nuevo) | Bloque de factores de la ficha con «Factor» + «Well-to-tank» |
| UI | `templates/carbon_footprint/admin/{combustibles,machinery_fuels,electricity_mixes,electricity_marketers,gdo_factors}/{edit,emission_factors}.html.twig` | Incluyen el parcial nuevo / cabecera de dos filas |
| i18n | `translations/fields+intl-icu.{es,en,tr}.xlf`, `translations/messages+intl-icu.{es,en,tr}.xlf` | Claves de columnas y etiquetas WTT |

## Modelo de dominio

### Factor WTT en los maestros

- `EmissionFactor::zeroFor()` (`EmissionFactor.php:47-50`) devuelve `new self('0','0','0','0', $year, $group, $unit, null)`. Ni el value object ni su embebible cambian.
- `<M>EmissionFactor` recibe `EmissionFactor $wellToTankFactor` tras `$emissionFactor`; el constructor y `update()` (`CombustibleEmissionFactor.php:63`) llaman a `assertSameYearAndGroup()` (`:72-81`), que lanza `\InvalidArgumentException` si difieren año o grupo. La unidad no se valida aquí: la mantienen alineada el agregado y los servicios.
- `<M>::setEmissionFactor(EmissionFactor $emissionFactor, ?EmissionFactor $wellToTankFactor = null)` (`Combustible.php:167`):
  1. Con `null` construye el WTT a cero con año, grupo y unidad del principal. Así siguen valiendo los llamadores que no conocen el WTT (pasos Behat, tests de Bioscore).
  2. Valida mismo año y grupo (`\InvalidArgumentException`).
  3. Sin fila previa publica `<M>EmissionFactorAdded`; con fila, solo publica `<M>EmissionFactorUpdated` si cambia **alguno** de los dos factores (`Combustible.php:194`, `equals()` sobre ambos). Por eso editar solo el WTT genera evento y se persiste.
- `syncEmissionFactors()` recibe `list<array{factor: EmissionFactor, wellToTank: EmissionFactor}>` (`Combustible.php:223`); la clave de duplicado sigue siendo año + grupo del `factor`.
- `restampEmissionFactorUnits()` (`Combustible.php:252`, `MachineryFuel.php:258`) reajusta la unidad de los dos factores al cambiar la unidad del maestro.
- `ElectricityMix`, `ElectricityMarketer` y `GdOFactor` fuerzan `withUnit(Unit::KWh)` también en el WTT en sus `apply*EmissionFactorAdded/Updated` (`ElectricityMix.php:212-229`).
- `wellToTankFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor` en los cinco agregados (`Combustible.php:100`, `MachineryFuel.php:106`, `ElectricityMix.php:79`, `ElectricityMarketer.php:74`, `GdOFactor.php:95`).

**Eventos de factor** (`<M>EmissionFactorAdded/Updated`): el payload añade `wellToTankCo2eq`, `wellToTankCo2`, `wellToTankCh4`, `wellToTankN2o`, `wellToTankSource`; año, unidad y grupo se comparten con el principal. `rebuildFromPayload()` usa `isset($payload['wellToTankCo2eq'])` y, si faltan las claves (eventos anteriores a la funcionalidad), reconstruye con `zeroFor()` (`CombustibleEmissionFactorAdded.php:78-94`). `<M>EmissionFactorRemoved` no cambia.

### WTT en las actividades

- `CombustibleActivity` y `MachineryFuelActivity`: propiedades `EmissionFactor $wellToTankEmissionFactor` y `Emissions $wellToTankEmissions`, no nulas. `register()`/`update()` reciben ambos como parámetros finales opcionales y, si llegan `null`, los rellenan con `zeroFor()` y `new Emissions()` (`CombustibleActivity.php:37,65`). Accesores en `:97` y `:102`.
- `ElectricityActivity`: lo mismo para el factor contractual (`:75`, `:124`, accesores `:228`, `:233`) y, para la localización, nueve escalares nulables `locationBasedWellToTankEmissionFactor{Co2eq,Co2,Ch4,N2o,Source}` y `locationBasedWellToTankEmissions{Co2eq,Co2,Ch4,N2o}`. `locationBasedWellToTankEmissionFactor()` (`:238`) reconstruye el value object con el año y el grupo del factor principal y `Unit::KWh`; `locationBasedWellToTankEmissions()` en `:256`.
- **Invariante de localización** (`ElectricityActivity.php:152-167`): mix, factor, emisiones, factor WTT y emisiones WTT de localización van **los cinco o ninguno** (`0 !== $provided && 5 !== $provided` → `\InvalidArgumentException`).
- `Activity::emissions()` no cambia: las emisiones WTT no se suman.

**Eventos de actividad**: `*ActivityRegistered/Updated` llevan `wellToTankEmissionFactor` y `wellToTankEmissions` obligatorios (tras `$source`, que deja de tener valor por defecto) y claves `wellToTankEmissionFactor*` / `wellToTankEmissions*` en el payload (`CombustibleActivityRegistered.php:109`). La reconstrucción usa `isset`/`?? '0'` y `zeroFor()` (`:135-146`). En electricidad, `$hasLocationBasedResult` (`ElectricityActivityRegistered.php:192`) decide si se reconstruye el WTT de localización; con resultado de localización pero sin claves WTT (payload antiguo) se reconstruye **a cero**, no a `null`, para respetar el invariante de cinco valores (`:207-220`; desviación declarada en `## Implementación`).

## Flujo de una operación

### Guardar factores de un maestro (pantalla de factores por año)

1. `GET|POST /admin/combustibles/emission-factors/{group}/{year}` → `Admin/Combustible/EmissionFactor/FactorEmissionController.php`. Construye cada `EmissionFactorRow::fromCombustible(..., $combustible->emissionFactors()->forYearAndGroup($year, $group->id()))` (`:73`) para que la fila lleve también el WTT.
2. `CombustibleEmissionFactorRowType::validateAllOrNothing()` (`:96-117`): si la fila tiene algún gas (principal o WTT), exige los cuatro gases principales y, si hay algún gas WTT, también los cuatro WTT. Una fila con WTT y sin principal se rechaza (desviación declarada: si no, `isEmpty()` la eliminaba en silencio). Violación con la clave `This value should not be blank.` (catálogo `validators`; en ES «Este valor no debería estar vacío.»).
3. `UpdateEmissionFactorsService` (`Application/Combustible/EmissionFactor/Update/UpdateEmissionFactorsService.php:43-49`): fila vacía → `removeEmissionFactor()`; si no, `setEmissionFactor($row->toEmissionFactor(...), $row->wellToTankEmissionFactor(...))`.
4. `EmissionFactorRow::wellToTankEmissionFactor()` sustituye cada gas vacío por `'0'`, fuente vacía por `null` y construye con `EmissionFactorGroup::buildEmissionFactor()` (`EmissionFactorGroup.php:170-177`). Igual que en el factor principal: si `co2eq` es 0 se calcula con el GWP del grupo, y si los tres gases son 0, `co2 = co2eq` (`EmissionFactor.php:30-34`).
5. En los maestros eléctricos los servicios pasan `Unit::KWh`.

### Editar la ficha del maestro

`Admin/<M>/EditController.php` crea `EmissionFactorData::fromEmissionFactor($combustibleEmissionFactor)` con la entidad hija (`Combustible/EditController.php:41`). El form `CombustibleEmissionFactorType` mantiene obligatorios año y los cuatro gases principales y añade los cinco WTT opcionales con `Callback validateWellToTankAllOrNothing` (`:114-134`). `UpdateCombustibleService` llama a `syncEmissionFactors()` con pares `['factor' => …, 'wellToTank' => $data->wellToTankEmissionFactor(...)]`.

### Copiar factores a otro año

`CreateEmissionFactorsYearService` (`Application/Combustible/EmissionFactor/CreateYear/CreateEmissionFactorsYearService.php:53-63`) lee la entidad hija y llama a `setEmissionFactor($source->emissionFactor()->withYear($t), $source->wellToTankFactor()->withYear($t))`.

### Importación y exportación

- `<M>ImportationType`: campos `wellToTankCo2eq…wellToTankSource` en `fields()`, `assignValueDefinitions()` (`TextType`) y extractor `stringOrNull` (`CombustibleImportationType.php:60,90,111`). Etiquetas desde el catálogo `fields` («CO2eq WTT», «CO2 WTT», «CH4 WTT», «N2O WTT», «Fuente WTT»).
- `Import<M>Service::buildWellToTankEmissionFactor()` (`ImportCombustibleService.php:108-117`) normaliza con `decimal()` (vacío → `'0'`, coma → punto) y llama a `setEmissionFactor($factor, $wellToTank)`. La importación **no** aplica «todo o nada»: los gases ausentes valen 0. Importar sobre un factor existente un fichero sin columnas WTT **sustituye su WTT por cero**.
- `<Plural>ExportationType`: columnas `wellToTank*` tras `source`, seleccionadas por defecto, rellenas desde `->wellToTankFactor()` (`CombustiblesExportationType.php:156-170`; fuente nula → `''`) y vacías en `emptyFactorRow()`.

### Registrar o actualizar una actividad

1. Los `*AnswerHandler` (respuestas del asistente, `PhaseAnswersSynchronizer`, carga de consumos de Bioscore) invocan los servicios de actividad sin cambios.
2. `RegisterCombustibleActivityService.php:40-52` (y los otros cinco): tras el factor principal, `$this->factorSelector->selectWellToTank(...)` y `$this->calculator->calculate($wellToTank, $request->quantity)`, con la misma conversión de unidades que las emisiones oficiales.
3. Los selectores comparten la búsqueda en `emissionFactorRowFor()` (`CombustibleEmissionFactorSelector.php:34`, `MachineryFuelEmissionFactorSelector.php:34`, `ElectricityEmissionFactorSelector.php:40-77`); `select()` devuelve `->emissionFactor()` y `selectWellToTank()` `->wellToTankFactor()`. En electricidad la prioridad es GdO → comercializadora. Si no hay fila lanzan la misma `\InvalidArgumentException` que `select()`.
4. Electricidad, localización: `ElectricityMixEmissionsResolver::resolve()` (`:19-52`) devuelve `ElectricityMixEmissions` con `wellToTankEmissionFactor` y `wellToTankEmissions`; devuelve `null` si no hay país, mix o factor (o WTT, caso que no se da cuando existe la fila). Los servicios pasan `$mix?->wellToTankEmissionFactor` y `$mix?->wellToTankEmissions` (`RegisterElectricityActivityService.php:48-73`).

## Autorización y ámbito

Sin cambios. Las pantallas de los cinco maestros siguen protegidas por sus `Authorizer` (p. ej. `Application/Combustible/CombustibleAuthorizer.php`: listar, editar, importar y exportar solo `Admin`/`SuperAdmin`). Los maestros son globales (sin ámbito de cuenta). El cálculo WTT de las actividades corre dentro de los servicios de actividad, con la autorización que ya tenían. Sin feature flags.

## Persistencia y migración

Migración: `migrations/Version20261020165447.php`, creada por el usuario. Tras el hallazgo C1 de `review.md`, su `up()` reproduce sentencia a sentencia `migration.sql`: añadir nulable → `UPDATE` de relleno → `MODIFY … NOT NULL`. Según `review.md` aún no se había ejecutado en la fecha de la revisión; este manual no lo ha comprobado.

| Tabla | Columnas nuevas | Relleno |
|---|---|---|
| `combustible_emission_factor`, `machinery_fuel_emission_factor`, `electricity_mix_emission_factor`, `electricity_marketer_emission_factor`, `gdo_factor_emission_factor` | `well_to_tank_{co2eq,co2,ch4,n2o}` `NUMERIC(20,8)`, `well_to_tank_year`, `well_to_tank_emission_factor_group_id`, `well_to_tank_unit` (`NOT NULL`), `well_to_tank_source` (nulable) | Gases a 0; año, grupo y unidad copiados de la misma fila |
| `combustible_activities`, `machinery_fuel_activities`, `electricity_activities` | `well_to_tank_emission_factor_{co2eq,co2,ch4,n2o,year,emission_factor_group_id,unit,source}` y `well_to_tank_emissions_{co2eq,co2,ch4,n2o}` | Valores `'0'`; año, grupo y unidad copiados desde `activities` por `id` |
| `electricity_activities` | Nueve `location_based_well_to_tank_*` `VARCHAR(255) DEFAULT NULL` | Sin relleno |

`Activity.CombustibleActivity.orm.xml`, `Activity.MachineryFuelActivity.orm.xml` y `Activity.ElectricityActivity.orm.xml` son los primeros subtipos JOINED con `<embedded>`: Doctrine los mapea en la tabla del subtipo. Las actividades existentes no se recalculan: quedan con WTT a cero hasta que se vuelva a guardar el paso de la huella. En producción, el `UPDATE … JOIN activities` conviene ejecutarlo en baja carga.

## Configuración y operación

Sin variables de entorno, colas, comandos ni cambios en `services.yaml`. Traducciones en `messages` («Well-to-tank», «Kg CO2eq (WTT)» … «Fuente (WTT)», «CO2eq WTT» … «Fuente WTT») y `fields` en los catálogos `es`, `en` y `tr` (los únicos existentes). Cabeceras de la UI: el bloque «Factor» abarca cinco columnas (cuatro gases + fuente) en `_form.well_to_tank.html.twig` (desviación declarada respecto al `colspan="4"` del plan).

## Cómo extenderlo

**Consumir el WTT en el alcance 3 (plan futuro).** Los datos están en `CombustibleActivity::wellToTankEmissions()`, `MachineryFuelActivity::wellToTankEmissions()`, `ElectricityActivity::wellToTankEmissions()` y `ElectricityActivity::locationBasedWellToTankEmissions()`. Las actividades guardadas antes de la migración tienen WTT a cero: hay que decidir si se recalculan.

**Añadir WTT a otro maestro** (p. ej. `Transport`):
1. Entidad hija: parámetro `wellToTankFactor`, `update()` de tres argumentos y `assertSameYearAndGroup()`, copiando `CombustibleEmissionFactor.php`. Mapping con `<embedded name="wellToTankFactor" … column-prefix="well_to_tank_"/>`.
2. Agregado: `wellToTankFactorFor()`, `setEmissionFactor(…, ?EmissionFactor = null)` comparando **ambos** factores, `syncEmissionFactors()` con pares, `restampEmissionFactorUnits()` sobre los dos si el maestro tiene unidad variable.
3. Eventos `Added`/`Updated`: claves `wellToTank*` y reconstrucción con `zeroFor()` si faltan.
4. Aplicación: `EmissionFactorRow`/`EmissionFactorData` con los cinco campos y `wellToTankEmissionFactor()`; `UpdateEmissionFactors`, `Update<M>`, `CreateEmissionFactorsYear`, `Import<M>` e `<M>ImportationType`; controladores pasando la entidad hija.
5. Form types con «todo o nada» y plantillas con `_form.well_to_tank.html.twig` y la cabecera de dos filas.
6. Actividad: embebidos `wellToTank*`, `selectWellToTank()` en el selector y cálculo en los servicios.
7. Migración con relleno antes del `NOT NULL` (embebidos no nulables; ver C1 en `review.md`).

**Trampas conocidas**
- Si `setEmissionFactor` compara solo el factor principal, editar únicamente el WTT no genera evento y se pierde.
- La entidad hija no valida la unidad: olvidar el WTT en `restampEmissionFactorUnits()` dejaría unidades distintas sin error.
- Cualquier llamador nuevo de `setEmissionFactor()` que no pase el WTT lo **sobrescribe a cero** si la fila ya tenía uno (el `null` se convierte en `zeroFor()` y la comparación detecta cambio).
- Una migración autogenerada con `ADD … NOT NULL` deja `well_to_tank_unit = ''` y rompe la hidratación del enum `Unit` (C1).

## Tests

- **Unitarios de dominio:** `tests/Unit/CarbonFootprint/Domain/Model/EmissionFactor/EmissionFactorTest.php` (`zeroFor`); `{Combustible,MachineryFuel (nuevo),ElectricityMix,ElectricityMarketer,GdOFactor}/<M>Test.php` (WTT a cero por defecto, año/grupo distinto lanza excepción, cambio solo del WTT publica `Updated`, `syncEmissionFactors` con pares, reajuste de unidad, `KWh`); cinco `<M>EmissionFactorAddedTest.php` nuevos (ida y vuelta, payload antiguo); `Activity/{CombustibleActivity,MachineryFuelActivity,CombustibleActivityRegistered,ElectricityActivityRegistered}Test.php` nuevos y `ElectricityActivityTest.php` (invariante de cinco valores, reconstrucción con `KWh`).
- **Unitarios de aplicación:** selectores y `ElectricityMixEmissionsResolverTest`; los seis servicios de actividad (WTT `0.61` × 1 000 → `'610'`); `UpdateEmissionFactors`, `CreateEmissionFactorsYear`, `Import<M>` (`'0,61'`/`DEFRA` y columnas ausentes) y `Update<M>` de los cinco maestros. Mothers: `ActivityMother`, `CombustibleActivityMother`, `MachineryFuelActivityMother`, `ElectricityActivityMother`.
- **Aceptación** (`tests/Acceptance/CarbonFootprint/`): en cada `*-emission-factors.feature`, tres escenarios (guardar con WTT, WTT parcial muestra «Este valor no debería estar vacío», ver el WTT guardado); «Well-to-tank» en los cinco `*-edit.feature`; «CO2eq WTT» en la pantalla de exportación de los cinco `*-export.feature`; dos escenarios en `property-carbon-footprint-scope-steps.feature` (alcance 1 con combustible y alcance 2 con comercializadora + mix, comprobando 610 kg CO2eq WTT contractual y de localización). Pasos nuevos en `CarbonFootprintContext.php`.
- **Ejecución:** `make test-unit options="tests/Unit/CarbonFootprint"`, `make test-acceptance options="tests/Acceptance/CarbonFootprint/combustible-emission-factors.feature"` o la batería completa con `make test-parallel`.
- **Huecos:** no hay paso de aceptación para las emisiones WTT de actividades de combustible de maquinaria, porque ningún flujo de alcances de propiedad o empresa las crea; las cubren los unitarios de servicio.

## Desviaciones y deuda

Ver `## Implementación` en `plan.md` y `review.md`:

- Cabecera «Factor» con `colspan="5"`; validación de fila que exige el factor principal cuando hay WTT; `$source` sin valor por defecto en los eventos de actividad; WTT de localización reconstruido a cero en payloads antiguos con localización; `ElectricityMixEmissionsResolver` devuelve `null` si faltase el WTT; comprobación de exportación en pantalla y no en el fichero.
- `review.md`: los 5 hallazgos (C1 migración sin relleno, A1 migración ausente, M1 duplicación en selectores, B1 mensaje sin traducir, B2 comentarios) están corregidos. B1 deja una diferencia con el texto del plan: el literal castellano se sustituyó por la clave `This value should not be blank.` (mismo resultado en ES). Los otros trece maestros y `Material` conservan el literal castellano (patrón heredado, fuera de alcance).
- Deuda que queda: la doble consulta al repositorio por actividad (una para `select()` y otra para `selectWellToTank()`) sigue ahí, porque los servicios hacen dos llamadas.
