# Medidas de reducción y mitigación (plan de mitigación) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-09-29 · **Revisado:** 2026-09-29 (`review.md`, `review-fix.md`) · **Bounded Contexts:** `CarbonFootprint` (principal), `Shared` (menú y filtro `md_to_html`), `Security` (plantillas de auditoría)

## Qué hace y por qué

Las huellas de **propiedad** y de **empresa** tienen ahora un paso 3 en el asistente, «Medidas de Reducción y Mitigación», entre Alcance 2 y Resultados. En él el Propietario define el plan de mitigación de la huella: una lista de medidas (manuales o creadas a partir de una medida sugerida del catálogo), cada una con año base, año objetivo y la reducción total que espera conseguir sobre cada fuente de emisión concreta de la huella (fuente + elemento: un combustible, un gas refrigerante, una comercializadora). Con esas medidas se calcula una proyección de emisiones año a año que se muestra en el paso y se añade al informe PDF como bloque «Plan de mejora {año}», con un texto de IA que enumera las medidas.

El cambio de flujo más relevante: **la huella ya no se completa al guardar el Alcance 2**, sino al pulsar «Siguiente» en el paso de medidas. Se eliminaron los suscriptores `Complete{Property,Company}CarbonFootprintOn…ScopeAnsweredSubscriber`, y el cierre lo invoca el controlador del paso nuevo. El plan es opcional: se puede completar sin medidas.

Un único agregado, `MitigationAction`, sirve a los dos tipos de huella identificándola por `CarbonFootprintReportSubjectType` + `carbonFootprintId`, igual que `CarbonFootprintReport` (decisión «Un agregado por medida» de `plan.md`). El catálogo global (`MitigationMeasure` y `MitigationMeasureCategory`) lo mantienen `Admin`/`SuperAdmin`. Las emisiones base y los valores por año **no se persisten**: se recalculan en cada lectura desde las actividades de la huella.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/MitigationAction.php` | Agregado traducible: medida del plan con efectos | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/{MitigationActionId,MitigationActionTranslation,MitigationActionEffect,MitigationActionEffectId,MitigationActionEffects,MitigationActionFilter,MitigationActionRepository}.php` | Id, traducción, efecto hijo, colección, filtro y puerto | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/MitigationAction{Registered,Updated,Translated,Removed,EffectAdded,EffectUpdated,EffectRemoved}.php` | Eventos | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/MitigationAction{InvalidYears,ReductionOutOfRange,SourceNotSupported}Exception.php` | Invariantes | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationAction/{MitigationEmissionSource,MitigationProjection,MitigationProjectionSource,MitigationProjector}.php` | Proyección pura | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationMeasure/*` | Medida sugerida traducible con efectos por fuente (rango mín–máx) | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/MitigationMeasureCategory/*` | Categoría traducible de medidas sugeridas | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/{Property,Company}CarbonFootprint/{Property,Company}CarbonFootprintMitigationPlanStarted.php` | Evento de plan iniciado | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Model/{Property,Company}CarbonFootprint/{Property,Company}CarbonFootprint.php` | Campo `mitigationPlanStarted`, publica el evento en `answerScope()` | modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintTrait.php` | `isMitigationPlanStarted()` | modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/OrganizationalCarbonFootprintStatus.php` | Paso 3 = `mitigation-plan`, paso 4 = `results` | modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/Scope.php` | `Scope::ofSource(Source): ?Scope` | modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/CarbonFootprintReportSectionKey.php` | Clave opcional `MitigationPlan`, `isOptional()` | modificado |
| Dominio | `src/CarbonFootprint/Domain/Model/CarbonFootprintReport/OrganizationalCarbonFootprintReport{Data,MitigationPlan,MitigationAction}.php` | Datos del informe con plan | nuevo / modificado |
| Dominio | `src/CarbonFootprint/Domain/Event/{Property,Company}CarbonFootprint/ImportMitigationActionsOn…MitigationPlanStartedSubscriber.php` | Importación automática | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Event/{Property,Company}CarbonFootprint/RemoveMitigationActionsOn…RemovedSubscriber.php` | Borrado en cascada | nuevo |
| Dominio | `src/CarbonFootprint/Domain/Event/{Property,Company}CarbonFootprint/Complete…OnScopeAnsweredSubscriber.php` | Cierre al guardar el alcance 2 | **eliminado** |
| Aplicación | `src/CarbonFootprint/Application/MitigationAction/{MitigationActionAuthorizer,MitigationPlanFootprints,MitigationEmissionSourcesBuilder,MitigationActionEffectData,MitigationPlanNotEditableException}.php` | Autorización, colaborador de huellas, fuentes de emisión | nuevo |
| Aplicación | `src/CarbonFootprint/Application/MitigationAction/{Register,Update,Remove,RemoveOfCarbonFootprint,Import,GetPlan}/*` | Casos de uso | nuevo |
| Aplicación | `src/CarbonFootprint/Application/MitigationMeasure/**`, `src/CarbonFootprint/Application/MitigationMeasureCategory/**` | Authorizer y CRUD del catálogo | nuevo |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/MitigationPlanReportBuilder.php` | Plan para el informe | nuevo |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/{Property,Company}CarbonFootprintReportDataCollector.php` | Pasan `mitigationPlan:` | modificado |
| Aplicación | `src/CarbonFootprint/Application/CarbonFootprintReport/Data/ActivityItemNameResolver.php` | `itemId()` pasa a público | modificado |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/Mitigation{Action,Measure,MeasureCategory}.*.orm.xml` (8) | Mappings | nuevo |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/{Property,Company}CarbonFootprint.{Property,Company}CarbonFootprint.orm.xml` | Columna `mitigation_plan_started` | modificado |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/Doctrine/Type/Mitigation*IdType.php` (5), `config/packages/doctrine.yaml:76-80` | Tipos DBAL | nuevo / modificado |
| Persistencia | `src/CarbonFootprint/Infrastructure/Persistence/{Doctrine,InMemory}/Repository/*Mitigation*Repository.php` (6) | Repositorios | nuevo |
| Persistencia | `migrations/Version20260929094558.php` (a partir de `migration.sql`) | Migración | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/UI/Web/{Property,Company}CarbonFootprint/Step/MitigationPlanController.php` | Paso del asistente (GET muestra, POST completa) | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/UI/Web/MitigationAction/{Register,Edit,Remove,Import}Controller.php`, `MitigationPlanStepRoute.php`, `GetMitigationActionFromIdTrait.php` | Alta, edición, baja e importación de medidas | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/UI/Web/OrganizationalCarbonFootprint/View/MitigationPlanView.php` | Filas y gráfica del paso | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/UI/Web/Admin/MitigationMeasure/**`, `Admin/MitigationMeasureCategory/**` | CRUD Admin del catálogo | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/UI/Web/{Property,Company}CarbonFootprint/Step/Scope2Controller.php`, `…/EditController.php` | Redirección al paso de medidas; rama `mitigation-plan` | modificado |
| UI | `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update}MitigationActionType.php`, `Type/MitigationActionEffectType.php`, `Type/MitigationMeasureChoiceType.php` | Formularios del Propietario | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/Symfony/Form/{Register,Update,List}MitigationMeasure{,Category,Categories,s}Type.php`, `Type/{MitigationMeasureEffectType,MitigationSourceType,MitigationMeasureCategoryChoiceType}.php` | Formularios del catálogo | nuevo |
| UI | `src/CarbonFootprint/Infrastructure/Symfony/Form/{UpdateCarbonFootprintReportTextsType,ReviewCarbonFootprintReportType}.php` | Obligatoriedad con `isOptional()` | modificado |
| UI | `src/CarbonFootprint/Infrastructure/Symfony/Twig/MitigationExtension.php` | Filtros `mitigation_measure_name`, `mitigation_measure_category_name` | nuevo |
| UI | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:299-315` | Grupo de menú Admin «Medidas de mitigación» | modificado |
| UI | `src/Shared/Infrastructure/Symfony/Twig/MarkdownExtension.php` | `md_to_html` escapa HTML crudo (corrección Q2 de `review-fix.md`) | modificado |
| Informe | `src/CarbonFootprint/Infrastructure/AI/Gemini/GenerateOrganizationalReportTextsPrompt.php` | Sección 5 `mitigationPlan` si hay plan | modificado |
| Informe | `src/CarbonFootprint/Infrastructure/Chart/ApexChartCarbonFootprintReportChartsGenerator.php:72-74,209-225` | Gráfica `mitigationProjection` | modificado |
| Informe | `src/CarbonFootprint/Infrastructure/Tcpdf/CarbonFootprintReportFiguresTrait.php:608-727` | Tablas por año | modificado |
| Informe | `src/CarbonFootprint/Infrastructure/Tcpdf/{Tcpdf,ClassicTcpdf}CarbonFootprintReportPdfGenerator.php` | Bloque «Plan de mejora» | modificado |
| Informe | `assets/images/carbon-footprint-report/classic/measures.jpg` | Foto de la página de texto clásica | nuevo |
| Plantillas | `templates/carbon_footprint/{property,company}_carbon_footprints/step/mitigation-plan.html.twig`, `organizational_carbon_footprints/_mitigation_plan.html.twig` | Paso | nuevo |
| Plantillas | `templates/carbon_footprint/mitigation_actions/{register,edit,_breadcrumb}.html.twig` | Alta y edición de medida | nuevo |
| Plantillas | `templates/components/TopAction/ImportMitigationActions.html.twig` | Acción «Importar acciones previas» con confirmación | nuevo |
| Plantillas | `templates/carbon_footprint/admin/mitigation_measure{s,_categories}/{list,register,edit}.html.twig` | Catálogo | nuevo |
| Plantillas | `templates/carbon_footprint/{property,company}_carbon_footprints/{wizard-layout,step/results}.html.twig` | Cuarto paso; «Anterior» apunta a medidas | modificado |
| Plantillas | `templates/components/List/FilterBar.html.twig` | Rama `mitigation_measure_category_choice` | modificado |
| Plantillas | `templates/security/admin/events/detail/carbon-footprint.*Mitigation*.html.twig` (20) | Detalle de auditoría | nuevo |
| Traducciones | `templates/carbon_footprint/translations/{mitigation,source}.html.twig`, `translations/*.{es,en,tr}.xlf` | Extracción y textos | nuevo / modificado |

## Modelo de dominio

### `MitigationAction`

`src/CarbonFootprint/Domain/Model/MitigationAction/MitigationAction.php`. Campos: `subjectType`, `carbonFootprintId`, `accountId` (el de la huella), `measureId` (nulo = manual, `isManual()` `:74`), `name`/`description` base, `baseYear`, `targetYear`, `removed`, `translations`, `effects`.

- `register(...)` (`:151`) y `update(name, description, targetYear, ?Locale)` (`:183`) validan `targetYear > baseYear` (`assertValidYears`, `:330`, lanza `MitigationActionInvalidYearsException`). Con `locale` no nulo, `update` conserva los textos base y guarda una traducción (`translate`, `:201`).
- `name()`/`description()` (`:79-93`) devuelven la traducción del idioma activo o el texto base; `defaultName()`/`defaultDescription()` siempre el base.
- `setEffect(source, itemId, itemName, reduction, min = 0, max = 1)` (`:222`): valida que la fuente sea de alcance 1 o 2 (`Scope::ofSource`, `:337`) y `0 ≤ min ≤ reduction ≤ max ≤ 1` (`:344`). Publica `EffectAdded` o `EffectUpdated`; nada si no cambia.
- `changeReduction(source, itemId, reduction)` (`:272`) conserva el rango del efecto; ignora efectos inexistentes.
- `syncEffects(list<{source,itemId,itemName,reduction}>)` (`:306`) usa rango 0–1, rechaza duplicados (`\InvalidArgumentException`) y elimina los no recibidos.
- Un efecto (`MitigationActionEffect`) se identifica por `source` + `itemId`; `itemName` es el nombre del elemento en el momento de crearlo. En pantalla y en el informe se usa el nombre resuelto de la huella, y el guardado solo cuando la fuente ya no tiene emisiones.

### Catálogo

- `MitigationMeasure` (`src/CarbonFootprint/Domain/Model/MitigationMeasure/MitigationMeasure.php`): nombre y descripción traducibles, `categoryId`, efectos `MitigationMeasureEffect` **por fuente** (sin elemento) con `minimumReduction`/`maximumReduction`. `setEffect` (`:175`) valida fuente soportada y `0 ≤ min ≤ max ≤ 1`; `syncEffects` (`:230`) rechaza fuentes repetidas.
- `MitigationMeasureCategory`: nombre traducible.
- Los tres agregados usan baja lógica (`removed`).

### Inicio del plan en la huella

`PropertyCarbonFootprint::answerScope()` (`src/CarbonFootprint/Domain/Model/PropertyCarbonFootprint/PropertyCarbonFootprint.php:92-98`) publica `PropertyCarbonFootprintMitigationPlanStarted` la primera vez que se responde el Alcance 2 (`mitigationPlanStarted` es falso); `apply…` (`:238`) lo pone a verdadero. Reabrir no lo restablece. `CompanyCarbonFootprint.php:92-98,248` es idéntico.

`OrganizationalCarbonFootprintStatus`: `Scope2Answered` → paso 3 / `mitigation-plan`; `Completed` → paso 4 / `results`.

### Proyección

`MitigationProjector::project(footprintYear, emissionSources, actions)` (`src/CarbonFootprint/Domain/Model/MitigationAction/MitigationProjector.php:16`):

```
n(a)      = t(a) - b(a)                                   (medidas con n ≤ 0 se ignoran, :33)
r(a,s)    = 1 - (1 - R(a,s)) ^ (1 / n(a))                 (:46)
T         = max t(a) de medidas con efecto sobre fuentes con emisiones (:44)
años      = Y0 + 1 .. T                                   (null si no hay T o T ≤ Y0, :53)
tasa(s,y) = min(1, Σ r(a,s) con b(a) < y ≤ t(a))          (:71-77)
E(s,y)    = E(s,y-1) · (1 - tasa(s,y))                    (encadenada, :78)
I(y)      = (TE(y-1) - TE(y)) / TE(y-1), 0 si TE(y-1) = 0 (:103)
```

Las fuentes se ordenan por alcance, posición en `Source::cases()` y nombre del elemento (`:121`). Los efectos sobre fuentes sin emisiones no aportan. El ejemplo numérico de `plan.md` (*Modelo conceptual → Ejemplo*) está cubierto por `MitigationProjectorTest`.

### Fuentes de emisión

`MitigationEmissionSourcesBuilder::build()` (`src/CarbonFootprint/Application/MitigationAction/MitigationEmissionSourcesBuilder.php:166`) recorre `activityReferences()` de la huella, descarta fuentes sin alcance, agrupa por `source|itemId` (`ActivityItemNameResolver::itemId`, `''` para electricidad sin comercializadora) sumando `co2eq`, y devuelve solo grupos con emisiones > 0. En empresa suma todas las propiedades y el bloque sin asignar: no hay reducciones por propiedad.

## Flujo de una operación

### Guardar el Alcance 2 (importación automática)

`Scope2Controller` → `AnswerPropertyCarbonFootprintScopeService` → `answerScope(Scope2)` publica `…MitigationPlanStarted` → `ImportMitigationActionsOnPropertyCarbonFootprintMitigationPlanStartedSubscriber` invoca `ImportMitigationActionsService` con `onlyIfEmpty: true` y `LoggedUser` → el controlador redirige a `property_carbon_footprints_mitigation_plan` (`src/CarbonFootprint/Infrastructure/UI/Web/PropertyCarbonFootprint/Step/Scope2Controller.php:81`; empresa `:83`).

### `ImportMitigationActionsService`

`src/CarbonFootprint/Application/MitigationAction/Import/ImportMitigationActionsService.php`:

1. `MitigationPlanFootprints::find` (rechaza `Event` y huellas inexistentes), autoriza `Edit` sobre la huella, `ensureIsEditable` (`:34-38`).
2. Con `onlyIfEmpty` y medidas existentes, devuelve 0 sin tocar nada (`:46`).
3. Da de baja **todas** las medidas actuales (`:50-53`), también cuando no hay huella anterior.
4. `MitigationPlanFootprints::previous()` (`src/CarbonFootprint/Application/MitigationAction/MitigationPlanFootprints.php:122`): la huella no eliminada del mismo sujeto con el mayor año inferior al actual.
5. Copia las medidas de la anterior con `targetYear > año de la huella actual` (`:70`) a través de `RegisterMitigationActionService` con `translations` y `effects` explícitos (`:82-113`): conserva medida de origen, textos base y traducciones, años y rango de cada efecto.

La importación manual (`ImportController`, `onlyIfEmpty: false`) muestra «Se han importado {count} medidas.». La acción solo se pinta si `hasPreviousFootprint`.

### Alta de medida (`RegisterMitigationActionService`)

`src/CarbonFootprint/Application/MitigationAction/Register/RegisterMitigationActionService.php:38`. Carga la huella, autoriza `Edit` sobre ella, `ensureIsEditable`. Tres ramas:

- **Importación** (`translations !== null`, `:177`): textos base recibidos y una traducción por entrada; no carga la medida sugerida, para que importar una medida cuya sugerida se eliminó no falle (`:48`).
- **Desde sugerida** (`:133`): textos base de la sugerida y todas sus traducciones; un nombre o descripción tecleados sustituyen a los de la sugerida solo en el idioma activo. Efectos: por cada efecto de la sugerida y cada fuente de la huella con esa `Source`, `setEffect` con reducción = mínimo y el rango de la sugerida (`:87-105`).
- **Manual** (`:116`): nombre obligatorio (`:56`); idioma resuelto como `UpdateMaterialService` (en el idioma por defecto texto base, en otro traducción). Nace sin efectos.

`baseYear` = `baseYear ?? calculationYear()`. Tras registrar, `RegisterController` redirige a la edición.

### Edición (`UpdateMitigationActionService`)

`src/CarbonFootprint/Application/MitigationAction/Update/UpdateMitigationActionService.php:31`. Autoriza `Edit` sobre la medida (`MitigationActionAuthorizer`), `ensureIsEditable` sobre su huella, `update()` con el idioma activo. Medida manual: cada efecto debe corresponder a una fuente con emisiones de la huella (`\InvalidArgumentException` si no, `:64`) y se sincroniza. Medida desde sugerida: solo `changeReduction` (no se añaden ni quitan efectos).

`EditController` (`src/CarbonFootprint/Infrastructure/UI/Web/MitigationAction/EditController.php`) codifica cada fila como `emissionSource = "fuente|elemento"` (`:205`), y pasa `ranges` al formulario para que `MitigationActionEffectType` añada en `PRE_SET_DATA` el `Range` y la ayuda «Rango establecido entre {min} % y {max} %». `targetYear` se valida `> base_year` y `≤ año de la huella + 50` (`UpdateMitigationActionType`); en el alta, `> año` y `≤ año + 50`, con valor inicial año + 5 (`RegisterMitigationActionType`).

### Paso y cierre

`src/CarbonFootprint/Infrastructure/UI/Web/PropertyCarbonFootprint/Step/MitigationPlanController.php`:

- Exige `Edit` sobre la huella (`:43`) y estado `Scope2Answered` o posterior; si no, redirige al alcance pendiente (`:45-50`).
- POST con la huella completada → aviso «La huella está completada. Reábrela para modificarla.» y resultados (`:52-56`).
- POST: token `mitigation_plan_<id>`, `CompletePropertyCarbonFootprintService` y redirección a resultados; `OrganizationalCarbonFootprintNotReadyToCompleteException` → aviso «No se ha podido completar la huella de carbono.» (`:58-73`). Completar dispara el registro del informe (suscriptor existente).
- GET: `GetMitigationPlanService` + `MitigationPlanView::build()`; `readOnly` si está completada; `report` para la tarjeta `ReportStatus` si está rechazado.

`MitigationPlanView` (`src/CarbonFootprint/Infrastructure/UI/Web/OrganizationalCarbonFootprint/View/MitigationPlanView.php`) calcula la «Reducción esperada (kg CO₂eq)» de cada medida como Σ emisiones × reducción sobre efectos con emisiones (`:57-59`) y marca «Sin emisiones en esta huella» los efectos cuya fuente ya no emite.

### Informe

`MitigationPlanReportBuilder::build()` (`src/CarbonFootprint/Application/CarbonFootprintReport/Data/MitigationPlanReportBuilder.php`) devuelve `null` sin medidas o sin proyección; si no, las medidas con nombre y descripción en `$report->baseLocale()` (o texto base) y la proyección. No autoriza (corre en los comandos diferidos del informe).

- Prompt: con plan añade el bloque «Mitigation plan», la instrucción 5 `mitigationPlan` (enumerar **todas** las medidas con su nombre exacto) y la clave en `properties`, no en `required` (`GenerateOrganizationalReportTextsPrompt.php:81-114,168-170`).
- Gráfica `mitigationProjection`: barras de totales por año, `COLOR_TEAL`.
- Tablas (`CarbonFootprintReportFiguresTrait.php:608-727`): tasa %, reducción kg y emisiones esperadas (con año base, «Total» y «Reducción interanual %»). Con más de seis años: cuatro primeros, «…» y el último (`:609-618`).
- Estándar (`TcpdfCarbonFootprintReportPdfGenerator.php:114,277`): página nueva con título «Plan de mejora {año}», texto de la sección si existe, las tres tablas y la gráfica, entre resultados y conclusión.
- Clásico (`ClassicTcpdfCarbonFootprintReportPdfGenerator.php:185,190`): página de texto «Medidas de reducción y mitigación» con `measures.jpg` si hay texto, tres páginas de datos «Plan de mejora {año}» y la gráfica (`embedClassicChart`) en la última si cabe. `buildClassicTableHtml` recibe `boldRows` para las dos filas finales.
- En los formularios de textos y de revisión, `mitigationPlan` aparece siempre para propiedad y empresa y no es obligatorio. Si la huella no tiene plan, el PDF lo ignora aunque tenga texto.

## Autorización y ámbito

- **Paso y medidas**: todo pasa por la autorización de la huella (`PropertyCarbonFootprintAuthorizer` / `CompanyCarbonFootprintAuthorizer`, elegidos en `MitigationPlanFootprints::denyUnlessCanDoThat`, `:102`): `Edit`/`View` solo para `Owner` de la cuenta actual. Las operaciones sobre una medida existente usan además `MitigationActionAuthorizer` (`src/CarbonFootprint/Application/MitigationAction/MitigationActionAuthorizer.php`): `Owner` (roles vigentes, `userHasRole`) y `currentAccountId` igual al `accountId` de la medida.
- `canRemove` admite también `Admin`/`SuperAdmin` (`:47-52`) para el borrado en cascada cuando un Administrador elimina la huella o la propiedad (desviación declarada en `plan.md`).
- **Impersonación**: `CurrentRoles::currentRoles()` devuelve `[Owner, User]` al impersonar (`src/Security/Infrastructure/Symfony/Security/CurrentRoles.php:24`), así que un Administrador que impersona la cuenta actúa como su Propietario. Un `User` recibe 403.
- **Huella completada**: `MitigationPlanFootprints::ensureIsEditable` lanza `MitigationPlanNotEditableException`; los controladores la traducen al aviso de huella completada. La plantilla oculta añadir, importar, editar, eliminar y «Siguiente».
- **Catálogo**: `MitigationMeasureAuthorizer` y `MitigationMeasureCategoryAuthorizer` solo `Admin`/`SuperAdmin`. El Propietario lee las sugeridas a través de `MitigationMeasureChoiceType` (form type de Infrastructure), sin permiso `List`.
- **CSRF**: `mitigation_actions_remove` con `#[CsrfProtected('remove')]`; `mitigation_actions_import` con `#[CsrfProtected('mitigation_actions_import')]` (token de sesión, no stateless); POST del paso con token `mitigation_plan_<id>`.
- **Markdown**: la descripción de la medida se pinta con `md_to_html`, que desde la corrección Q2 escapa el HTML crudo y retira enlaces inseguros (`src/Shared/Infrastructure/Symfony/Twig/MarkdownExtension.php`). Afecta a todos los usos del filtro.

## Persistencia y migración

Migración `migrations/Version20260929094558.php` (creada por el usuario desde `migration.sql`):

| Tabla | Contenido |
|---|---|
| `mitigation_measure_categories` / `mitigation_measure_category_translation` | Categoría y su nombre por idioma |
| `mitigation_measures` / `mitigation_measure_translation` | Medida sugerida (`category_id`, `name`, `description`) y traducción |
| `mitigation_measure_effect` | `source`, `minimum_reduction`, `maximum_reduction`; único (`mitigation_measure_id`, `source`) |
| `mitigation_actions` | `subject_type`, `carbon_footprint_id`, `account_id`, `mitigation_measure_id` nullable, textos, `base_year`, `target_year`; índice `idx_mitigation_actions_subject` |
| `mitigation_action_translation` | Nombre y descripción por idioma |
| `mitigation_action_effect` | `source`, `item_id`, `item_name`, `reduction`, `minimum_reduction`, `maximum_reduction`; único (`mitigation_action_id`, `source`, `item_id`) |
| `property_carbon_footprints`, `company_carbon_footprints` | Columna `mitigation_plan_started TINYINT(1) NOT NULL DEFAULT 0` |

Los porcentajes son fracciones `DOUBLE PRECISION` entre 0 y 1. Hijos y traducciones con `ON DELETE CASCADE`. Sin backfill: las huellas existentes quedan con el plan no iniciado. Las completadas no cambian, y las que estaban en `Scope2Answered` pasan a tener el paso de medidas como paso activo.

## Configuración y operación

- Sin variables de entorno, comandos de consola ni colas nuevas. Rutas, servicios y mappings se registran por directorio. Solo los cinco tipos DBAL se dan de alta a mano en `config/packages/doctrine.yaml:76-80`.
- La generación de textos y PDF sigue en los comandos diferidos existentes (`Priority::LOW`).
- Menú Admin: grupo «Medidas de mitigación» con «Medidas» (`admin_mitigation_measures`) y «Categorías de medidas» (`admin_mitigation_measure_categories`). El catálogo nace vacío.
- Los iconos `solar:*` nuevos no están bloqueados en `assets/icons/` (`make icon-lock`, deuda declarada).

## Cómo extenderlo

- **Nueva fuente reducible**: añadirla a `Scope::sections()`. `Scope::ofSource` la acepta automáticamente en `MitigationAction`, `MitigationMeasure` y `MitigationSourceType`. Si su elemento no lo resuelve `ActivityItemNameResolver::itemId`, añadir el caso ahí; si no, todas sus actividades comparten `itemId = ''`.
- **Nuevo tipo de huella con plan**: ampliar `MitigationPlanFootprints` (`find`, `denyUnlessCanDoThat`, `previous`), `MitigationPlanStepRoute`, el requisito de ruta `property|company` de `Register/ImportController`, un controlador de paso y un suscriptor de inicio y otro de borrado. El recolector de datos del informe debe pasar `mitigationPlan:`.
- **Cambiar la fórmula**: solo `MitigationProjector`. Paso, informe y prompt consumen `MitigationProjection`.
- **Trampas**:
  - La importación automática corre dentro de `answerScope()`, antes de persistir la huella; si falla, el plan queda iniciado y no hay reintento automático (queda el botón).
  - `RemoveMitigationActionsOfCarbonFootprintService` no comprueba `ensureIsEditable` a propósito.
  - `Collection/Table` dentro de un `Card` con `title` rompe el formulario, por eso «Efectos» es un `<h6>`.

## Tests

- Unitarios: `tests/Unit/CarbonFootprint/Domain/Model/MitigationAction/` (`MitigationProjectorTest`, `MitigationActionTest`, repositorios), `Domain/Model/MitigationMeasure*/`, `Application/MitigationAction/` (authorizer, builder de fuentes, un test por caso de uso), `Application/MitigationMeasure*/` (`…ServicesTest`), `Application/CarbonFootprintReport/Data/MitigationPlanReportBuilderTest.php`, suscriptores en `Domain/Event/{Property,Company}CarbonFootprint/`, `Infrastructure/UI/Web/OrganizationalCarbonFootprint/View/MitigationPlanViewTest.php`, prompt, generadores PDF (con plan, ambos temas y más de seis años), repositorios Doctrine y `tests/Unit/Shared/Infrastructure/Symfony/Twig/MarkdownExtensionTest.php`.
- Aceptación: `tests/Acceptance/CarbonFootprint/property-carbon-footprint-mitigation-plan.feature` (20 escenarios: importación automática y manual, alta manual y desde sugerida, traducción, rangos, años, solo lectura, 403), `company-carbon-footprint-mitigation-plan.feature` (agregación por elemento, importación, cierre), `mitigation-measure{,-category}-{list,register,edit,remove}.feature`. Además se adaptaron `*-carbon-footprint-complete.feature`, `carbon-footprint-report-status.feature` y `carbon-footprint-report-review.feature`.
- Ejecución:
  - `make test-this test=tests/Unit/CarbonFootprint/Domain/Model/MitigationAction`
  - `make test-acceptance options="tests/Acceptance/CarbonFootprint/property-carbon-footprint-mitigation-plan.feature"`
- Sin tests unitarios de los controladores del paso (cubiertos por Behat).

## Desviaciones y deuda

Ver `## Implementación` de `plan.md`, `review.md` y `review-fix.md`:

- Desviaciones declaradas: `canRemove` ampliado a Admin; importación sin cargar la sugerida; `base_year` en la edición; `boldRows` en la tabla clásica; formularios inválidos con 200; token de importación de sesión.
- `mitigation.jpg` se retiró en la corrección Q1 (el plan pedía copiarlo; no se usaba).
- Deuda asumida (B2): `RegisterTrait`/`EditTrait` capturan `\Exception` y muestran «Error inesperado»; en el catálogo, un error del servicio (p. ej. fuente repetida en los efectos) llega como aviso genérico.
- Deuda declarada sin corregir: tokens CSRF de enlaces GET dependientes de sesión (`config/packages/csrf.yaml`), iconos sin bloquear y `onsubmit="return confirm(...)"` en `step/results.html.twig`.
- Hallazgos abiertos: ninguno. B1 y Q2 están aplicados y B2 está asumido.
