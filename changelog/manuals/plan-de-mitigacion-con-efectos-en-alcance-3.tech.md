# Plan de mitigación con efectos en alcance 3 — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-01 · **Bounded Contexts:** `CarbonFootprint`

## Qué hace y por qué

El plan de mitigación (`MitigationAction` con sus `MitigationActionEffect`, y el catálogo `MitigationMeasure` con `MitigationMeasureEffect`) ya aceptaba cualquier fuente con alcance, incluido el alcance 3: `MitigationAction::assertSupportedSource()` solo exige que `Scope::ofSource()` no sea nulo, y `MitigationEmissionSourcesBuilder` ya construía las fuentes de las 14 categorías del alcance 3, las emisiones comunicadas por proveedor, las propiedades no consolidadas y la categoría 3 derivada (well-to-tank). Este cambio **no toca el modelo ni el esquema** (sin migración): verifica que el alcance 3 funciona de extremo a extremo y cierra cuatro huecos.

1. El alta de una medida a partir de una medida sugerida descartaba las fuentes derivadas en la edición y en el servicio de actualización, pero no en el servicio de alta.
2. El catálogo de medidas sugeridas no ofrecía la categoría «Actividades relacionadas con combustibles y energía» (`Source::FuelAndEnergyRelated`), que en una huella «ya calculada» sí es una fuente directa reducible.
3. El selector de fuentes de las medidas manuales era una lista plana, ilegible con el alcance 3.
4. Los efectos no indicaban su alcance, y el mismo maestro (p. ej. un medio de transporte) existe en alcance 1 y en alcance 3.

Decisiones (tabla «Decisiones tomadas» de `plan.md`): las actividades aportadas por proveedores y por empleados se siguen agrupando con las del Propietario por `source|itemId` (solo las emisiones comunicadas forman un elemento propio por proveedor); en una huella calculada una medida sugerida sobre la categoría 3 no genera efectos.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Aplicación | `src/CarbonFootprint/Application/MitigationAction/Register/RegisterMitigationActionService.php` | Al generar efectos desde una medida sugerida salta las fuentes derivadas (`:92`) | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/MitigationEmissionSource.php` | Nuevo comparador estático `compare()` (alcance, posición en `Source::cases()`, nombre) (`:36`) | Modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/MitigationProjector.php` | `sorted()` usa `MitigationEmissionSource::compare(...)` (`:118-120`) | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/Symfony/Form/Type/MitigationSourceType.php` | Opciones del catálogo desde `Scope::allSources()`, ordenadas por `Source::cases()` (`:24-33`) | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/MitigationAction/EditController.php` | `emissionSourceChoices()` agrupa por alcance y desambigua etiquetas repetidas (`:149-168`); `scope` en `effectRows()` (`:198`) | Modificado |
| Infraestructura | `src/CarbonFootprint/Infrastructure/UI/Web/OrganizationalCarbonFootprint/View/MitigationPlanView.php` | `scope` en cada efecto de la fila (`:58`) | Modificado |
| Plantilla | `templates/carbon_footprint/organizational_carbon_footprints/_mitigation_plan.html.twig` | Columna «Efectos»: «{alcance} · {fuente} — {elemento}: {x} %» (`:37`) | Modificado |
| Plantilla | `templates/carbon_footprint/mitigation_actions/edit.html.twig` | Misma etiqueta en la tabla de medidas sugeridas (`:72`) | Modificado |
| Tests | `RegisterMitigationActionServiceTest`, `MitigationEmissionSourcesBuilderTest`, `MitigationProjectorTest`, `MitigationPlanViewTest`, `MitigationPlanReportBuilderTest`, `MitigationEmissionSourceTest` (nuevo), `MobilityActivityMother`, `CarbonFootprintContext`, y los `.feature` de propiedad, empresa y `mitigation-measure-edit` | Cobertura del alcance 3 | Modificado / nuevo |

## Modelo de dominio

Sin cambios de agregados, eventos ni mappings. Reglas que sostienen el alcance 3:

- Una fuente de emisión del plan se identifica por `source|itemId` (`MitigationEmissionSource::key()`). En alcance 3 el `itemId` es el maestro de la actividad, el proveedor (emisiones comunicadas; cadena vacía si no hay proveedor, caso de la categoría 3 de una huella «ya calculada») o la propiedad no consolidada (Franquicias, Activos arrendados aguas abajo).
- Derivadas (`isDerived()`): well-to-tank de la categoría 3 en huellas calculadas, con `originKey` apuntando a su actividad de origen. `MitigationProjector` solo trata como reducibles las no derivadas con emisiones (`:17-20`), ignora efectos sobre claves no reducibles (`:37`) y reduce las derivadas con las tasas de su origen (`reductionKey()`).
- **Invariante explícita nueva:** ningún efecto generado desde una medida sugerida apunta a una fuente derivada (`RegisterMitigationActionService.php:92`). Los efectos de medidas manuales ya la cumplían (`UpdateMitigationActionService.php:56-60`, `EditController.php:59-66`).
- `MitigationEmissionSource::compare()` es el único criterio de orden: lo comparten la proyección y el selector del controlador.

## Flujo de una operación

**Alta desde una medida sugerida.** `RegisterController` (`/mitigation-actions/{subjectType}/{carbonFootprintId}/register`, `mitigation_actions_register`) → `RegisterMitigationActionService` (autoriza `Edit` sobre la huella) → `registerFromMeasure()` → `MitigationEmissionSourcesBuilder::build()` → para cada efecto de la medida y cada fuente con la misma `Source`, **salvo las derivadas**, `setEffect(..., reducción mínima del rango, min, max)` (`:88-105`). Consecuencias:

- Fuente del alcance 3 con varios elementos: un efecto por elemento con emisiones (incluidos proveedores y propiedades no consolidadas).
- `FuelAndEnergyRelated` en huella calculada: cero efectos (todas sus fuentes son derivadas).
- `FuelAndEnergyRelated` en huella «ya calculada»: un efecto con `itemId` vacío sobre las emisiones comunicadas.

**Edición de una medida manual.** `EditController` construye las fuentes no derivadas indexadas por `key()` (`:56-66`), llama a `emissionSourceChoices()` y pasa el resultado como `emission_sources` a `MitigationActionEffectType`, cuyo `ChoiceType` acepta opciones anidadas (`<optgroup>`) sin cambios. `emissionSourceChoices()` (`:149-168`): ordena con `compare()`, agrupa por la etiqueta traducida del alcance (dominio `scope`), etiqueta cada opción «{Fuente} — {Elemento}» (dominio `source`) y, si la etiqueta ya existe en el grupo, añade « (2)», « (3)»… para no perder opciones (dos propiedades franquiciadas homónimas, dos proveedores homónimos).

**Visualización.** `MitigationPlanView::actionRow()` añade `scope` (`$effect->scope()->value`) a cada efecto; las dos plantillas lo anteponen con `trans({}, 'scope')`. En medidas de catálogo, `EditController::effectRows()` aporta el mismo `scope`.

**Catálogo.** `MitigationSourceType` recorre `Scope::cases()`, toma `allSources()` (secciones + derivadas, `Scope.php:96-113`) y las ordena por posición en `Source::cases()`, de modo que la categoría 3 queda entre «Bienes de capital» y «Transporte y distribución aguas arriba». `group_by` agrupa por alcance. Las pantallas de alta y edición del catálogo (`templates/carbon_footprint/admin/mitigation_measures/{register,edit}.html.twig`) heredan el cambio sin tocar plantilla.

## Autorización y ámbito

Sin cambios. Alta, importación y edición de efectos: `Edit` sobre la huella (`MitigationActionAuthorizer`: rol Owner de la cuenta actual, o Admin/SuperAdmin). Catálogo: solo Administradores (menú **Medidas de mitigación → Medidas**, `NodeLister.php:347-360`). Una huella completada no admite cambios (`MitigationPlanNotEditableException`).

## Persistencia y migración

Sin cambios de esquema ni migración. Las medidas y efectos ya guardados no se tocan. Una medida antigua con un efecto sobre una fuente derivada (solo posible con datos previos) sigue sin reducir nada y se muestra con el aviso «Sin emisiones en esta huella»; no se migra.

## Configuración y operación

Sin variables de entorno, colas ni comandos nuevos. Traducciones: se reutilizan los dominios `scope` (`translations/scope+intl-icu.*.xlf`) y `source` (`translations/source+intl-icu.*.xlf`); no hay claves nuevas.

## Cómo extenderlo

- **Nueva fuente de alcance 3:** basta añadirla a `Source` y a `Scope` (secciones o derivadas). `MitigationSourceType`, el constructor de fuentes y los selectores la recogen por `allSources()` / `Scope::ofSource()`; hay que traducirla en el dominio `source`. Su posición en `Source::cases()` fija el orden en catálogo, selector y proyección.
- **Cambiar el orden** de fuentes: solo en `MitigationEmissionSource::compare()` (`:36`).
- **Separar actividades de proveedores o empleados** del elemento del Propietario: cambia la clave `source|itemId` y la compatibilidad de los efectos importados de años anteriores; fuera de alcance (plan propio).
- Trampa: el catálogo ofrece `FuelAndEnergyRelated`, pero en huellas calculadas esa medida no produce efectos. No filtrarla del catálogo: es válida en huellas «ya calculadas».
- Trampa: las claves de las opciones del selector son las etiquetas visibles; cualquier cambio de formato debe conservar la desambiguación.

## Tests

- Unitarios: `RegisterMitigationActionServiceTest` (alcance 3 con `MobilityActivity` en `BusinessTravel`, categoría 3 derivada, categoría 3 de huella precalculada, franquicias de empresa), `MitigationEmissionSourcesBuilderTest` (fuente directa de huella precalculada, actividades de proveedor y de empleado sumadas al elemento del Propietario), `MitigationProjectorTest` (efecto sobre fuente de alcance 3 comunicada), `MitigationEmissionSourceTest::testCompareOrdersByScopeThenSourceThenItemName`, `MitigationPlanViewTest` (`scope` del efecto), `MitigationPlanReportBuilderTest` (efecto de alcance 3 en el informe).
- Aceptación (Behat): `property-carbon-footprint-mitigation-plan.feature` (medida manual sobre alcance 3 con orden de grupos, proyección con «Alcance 3 · Bienes y servicios adquiridos», categoría 3 sin efectos en huella calculada y con efecto en precalculada), `company-carbon-footprint-mitigation-plan.feature` (franquicia; dos franquicias homónimas), `mitigation-measure-edit.feature` (catálogo con la categoría 3 y orden).
- Ejecución: el `Makefile` no define `test-this`. Usar `make test-unit options=<ruta>` y `make test-acceptance options=<ruta>`, o `make test-parallel` para todo.
- Los escenarios de medida manual crean la medida con `Given mitigation action … exists` y `… reduces source …` y envían la fuente con `I fill the unprefixed field`, porque `I submit a form with` no puede añadir filas a la colección y separa argumentos por `|`.

## Desviaciones y deuda

- `MobilityActivityMother::registered()` acepta un `MobilityId` opcional (desviación declarada en `## Implementación` de `plan.md`).
- La `review.md` no deja hallazgos abiertos (B1 etiquetas homónimas, B2 tests de orden/agrupación y B3 comparador duplicado, corregidos). B3 contradice la sección «Sin cambios» de `plan.md` (se tocan `MitigationEmissionSource` y `MitigationProjector`); el plan no se ha actualizado.
- `CLAUDE.md` documenta `make test-this`, que el `Makefile` no define.
