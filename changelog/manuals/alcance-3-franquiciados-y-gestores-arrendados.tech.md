# Alcance 3 · Franquiciados y gestores arrendados — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-22 · **Bounded Contexts:** `Property`, `CarbonFootprint`, `Bioscore`

## Qué hace y por qué

Cada propiedad tiene ahora un **régimen de explotación** (`OperatingRegime`: `owned`, `leased`, `managed`, `franchised`, `leased_out`). Con control operacional (GHG Protocol), `owned`, `leased` y `managed` se **consolidan** (alcances 1 y 2 de la empresa, como antes); `franchised` y `leased_out` no. Las propiedades no consolidadas dejan de ser bloques de los pasos Alcance 1, 2 y 3 de la huella de empresa y pasan a aportar dos categorías nuevas del alcance 3, fuentes **derivadas** (no tecleadas): `Source::Franchises` (cat. 14) y `Source::DownstreamLeasedAssets` (cat. 13).

Cada propiedad no consolidada genera una fila con los alcances 1 + 2 de su huella de propiedad **completada** del mismo año y cuenta (o 0 si no existe). El usuario puede sustituir la cifra por un valor manual. Las filas son una **foto fija** guardada en la huella de empresa (entidad hija, sin JSON): resultados, informe y plan de mitigación leen la foto, no la huella de propiedad en vivo. Decisiones y justificación: tabla «Decisiones tomadas» de `plan.md`.

## Mapa de piezas

### Property

| Fichero | Rol | Estado |
|---|---|---|
| `src/Property/Domain/Model/Property/OperatingRegime.php` | Enum + `isConsolidated()` | nuevo |
| `src/Property/Domain/Model/Property/Property.php` | Campo `operatingRegime` (`register()` línea 105, `update()` línea 122, `applyPropertyMatched()` línea 189) | modificado |
| `Property/Domain/Model/Property/{PropertyRegistered,PropertyUpdated,PropertyFilter}.php` | Payload y filtro con el régimen | modificado |
| `Property/Application/Property/{Register,Update}/*` | Request/Response/Service con el régimen | modificado |
| `Property/Infrastructure/Persistence/Doctrine/Mapping/Property.Property.orm.xml`, `DoctrinePropertyRepository`, `InMemoryPropertyRepository` | Columna `operating_regime` y filtro | modificado |
| `Property/Infrastructure/Symfony/Form/Type/OperatingRegimeType.php` | `ChoiceType` con `choice_value` (valor del enum) y dominio `operatingRegime` | nuevo |
| `Property/Infrastructure/Symfony/Form/{RegisterPropertyType,UpdatePropertyType,ListPropertiesType}.php`, `templates/property/{properties,admin/properties}/{_form,list}.html.twig` | Campo, columna y filtro | modificado |
| `Property/Infrastructure/UI/Api/PropertyRegister/*`, `UI/Api/Property/PropertyResponse*` | `operatingRegime` opcional en el alta (`Assert\Choice`), presente en la respuesta | modificado |
| `Property/Infrastructure/UI/Web/Admin/Property/Bulk/AssignAccount.php`, `UI/Api/PropertyMatch/PropertyMatchController.php`, `UI/Api/PropertyUnlinkAccount/PropertyUnlinkAccountController.php`, `Property/Domain/Event/Company/UnlinkCompanyOnCompanyRemovedSubscriber.php` | Pasan régimen, empleados y clientes actuales a `update()` | modificado (review M1) |
| `templates/components/List/FilterBar.html.twig` | Rama `operatingRegime` para mostrar el filtro activo traducido | modificado (review B1) |
| `templates/property/translations/operatingRegime.html.twig`, `translations/operatingRegime+intl-icu.{es,en,tr}.xlf` | Traducciones | nuevo |

### CarbonFootprint y Bioscore

| Fichero | Rol | Estado |
|---|---|---|
| `CarbonFootprint/Domain/Model/CarbonFootprint/Source.php` | `DownstreamLeasedAssets`, `Franchises` entre `SoldProductsEndOfLife` e `Investments` | modificado |
| `CarbonFootprint/Domain/Model/OrganizationalCarbonFootprint/Scope.php` | `derivedSources()` (55), `companyOnlySources()` (64), `sourcesFor()` (73), `sourceForOperatingRegime()` (87), `downstreamSources()` (133) | modificado |
| `CarbonFootprint/Domain/Model/CarbonFootprint/NonConsolidatedPropertyLine.php` | VO de lectura de una fila | nuevo |
| `CarbonFootprint/Domain/Model/CompanyCarbonFootprint/CompanyCarbonFootprintNonConsolidatedProperty{,Id,Recorded,Removed}.php` | Entidad hija, id y eventos | nuevo |
| `CarbonFootprint/Domain/Model/CompanyCarbonFootprint/CompanyCarbonFootprint.php` | `recordNonConsolidatedProperty` (274), `retainNonConsolidatedProperties` (319), `nonConsolidatedPropertyLines` (340), `apply…Recorded` (538), `apply…Removed` (566); `excludeSource` (152) y `excludeStage` (218) borran filas | modificado |
| `CarbonFootprint/Domain/Model/{CompanyCarbonFootprint,PropertyCarbonFootprint}/*Exclusion.php`, `PropertyCarbonFootprint.php` | `update()` en la exclusión (review A1); `excludeSource` ignora las fuentes solo de empresa | modificado |
| `CarbonFootprint/Application/CompanyCarbonFootprint/CompanyProperties.php` | `all()`, `consolidated()`, `nonConsolidated()` | nuevo |
| `CarbonFootprint/Application/CompanyCarbonFootprint/NonConsolidatedProperty/{NonConsolidatedPropertyEmissions,…EmissionsResolver,NonConsolidatedPropertyAnswer,NonConsolidatedPropertiesSynchronizer}.php` | Cálculo y sincronización | nuevo |
| `CarbonFootprint/Application/CarbonFootprint/{DerivedSourceEmissionsCalculator,EmissionsBreakdownBuilder}.php` | Suman las filas (calculator:50, builder:105) | modificado |
| `CarbonFootprint/Application/CompanyCarbonFootprint/{AnswerScope,GetScope,ReplaceScopeAnswers,GetResults,Complete}/*` | Bloques solo consolidados; sincronización al guardar (AnswerScope:94) y al completar (Complete:41) | modificado |
| `CarbonFootprint/Application/CarbonFootprintReport/Data/CompanyCarbonFootprintReportDataCollector.php` | `nonConsolidatedPropertyLines` (105) | modificado |
| `CarbonFootprint/Application/MitigationAction/MitigationEmissionSourcesBuilder.php` | Una fuente reducible por fila con emisiones > 0 (88) | modificado |
| `Bioscore/Application/BioscoreConsumption/LoadCompanyCarbonFootprintConsumptions/LoadCompanyCarbonFootprintConsumptionsService.php` | Solo propiedades consolidadas | modificado |
| `CarbonFootprint/Infrastructure/Persistence/Doctrine/Mapping/CompanyCarbonFootprint.CompanyCarbonFootprintNonConsolidatedProperty.orm.xml`, `Type/CompanyCarbonFootprintNonConsolidatedPropertyIdType.php`, `config/packages/doctrine.yaml` | Mapping, tipo de id y registro | nuevo / modificado |
| `CarbonFootprint/Infrastructure/UI/Web/CompanyCarbonFootprint/NonConsolidatedPropertyHttpMapper.php`, `…/OrganizationalCarbonFootprint/View/NonConsolidatedPropertiesView.php`, `…/Step/Scope3Controller.php` (empresa y propiedad), `_scope_3_sections.html.twig`, `MitigationSourceType.php` | UI del paso Alcance 3 y de medidas | nuevo / modificado |
| `CarbonFootprint/Infrastructure/Tcpdf/{CarbonFootprintReportFiguresTrait,TcpdfCarbonFootprintReportPdfGenerator,ClassicTcpdfCarbonFootprintReportPdfGenerator}.php` | Tabla de filas (`nonConsolidatedPropertyTable`, trait:444) | modificado |
| `migrations/Version20261022134504.php` | Migración aplicada (creada por el usuario) | nuevo |

## Modelo de dominio

- **`OperatingRegime`** (`Property`): `isConsolidated()` es `true` para `owned`, `leased`, `managed`. Va como campo plano de `Property`, no dentro de `PropertyInfo` (que es lo casable con fuentes externas). `PropertyRegistered`/`PropertyUpdated` lo llevan en el payload; `rebuildFromPayload` cae a `Owned` si falta.
- **`Property::update()`** recibe `?OperatingRegime` y `null` conserva el actual (`Property.php:122`); `register()` usa `Owned` por defecto. `applyPropertyMatched()` (189) construye el `PropertyUpdated` con todos los campos actuales, de modo que casar ya no pierde empresa, empleados, clientes ni régimen.
- **`Scope`**: la correspondencia régimen → fuente vive en CarbonFootprint (`sourceForOperatingRegime`: `franchised` → `Franchises`, `leased_out` → `DownstreamLeasedAssets`, resto `null`). `companyOnlySources()` es `[]` en alcances 1 y 2 y las dos fuentes nuevas en el 3. `sourcesFor(CarbonFootprintReportSubjectType)` devuelve `allSources()` sin esas fuentes salvo para `Company`.
- **`CompanyCarbonFootprintNonConsolidatedProperty`**: `propertyId`, `source`, `propertyCarbonFootprintId` (nullable, origen), `calculatedEmissions` (embebido `Emissions`), `manualCo2eq` (string nullable), `recordedAt`. `emissions()` = manual si existe, si no calculado. `update()` reasigna sin cambiar el id.
- **Invariantes** en `CompanyCarbonFootprint::recordNonConsolidatedProperty` (274): huella editable; fuente en `Scope3->companyOnlySources()` (si no, `\InvalidArgumentException`); manual numérico y >= 0 (`'Manual emissions must be a non-negative number'`); con alcance 3 o fuente excluidos no registra y retira la fila existente; idempotente si nada cambia; como mucho una fila por (huella, propiedad). `retainNonConsolidatedProperties` publica `Removed` de las filas ausentes de la lista. `excludeSource` (fuente de empresa) y `excludeStage` borran filas.
- **Eventos**: `CompanyCarbonFootprintNonConsolidatedPropertyRecorded` (propertyId, source, propertyCarbonFootprintId, cifras calculadas CO2eq/CO2/CH4/N2O, manualCo2eq) y `…Removed` (propertyId). `applyRecorded` actualiza la fila existente con `update()` o añade una nueva; así Doctrine no ejecuta DELETE + INSERT del mismo par bajo `UNIQ_ccf_ncp_property` (review A1). El mismo cambio se aplicó a las exclusiones de empresa y propiedad.
- **`PropertyCarbonFootprint::excludeSource`** ignora las fuentes de `companyOnlySources()`: la huella de propiedad no tiene estas categorías.

## Flujo de una operación

### Cambiar el régimen
`properties/{id}/edit` (o la ruta de admin) → `UI/Web/Property/EditController` / `UI/Web/Admin/Property/EditController` (monta `UpdatePropertyRequest` con el régimen actual) → `UpdatePropertyType` (campo `operatingRegime`, obligatorio) → `UpdatePropertyService` (Authorizer, `Action::Edit`) → `Property::update()` → `PropertyUpdated`. API: `POST /api/{_locale}/properties` con `operatingRegime` opcional (`PropertyRegisterController`: `tryFrom(...) ?? Owned`); valor desconocido → 422 por `Assert\Choice`.

### Guardar el paso Alcance 3 de la huella de empresa
`Step/Scope3Controller` (CSRF `scope_3_{id}`) → `NonConsolidatedPropertyHttpMapper::map()` (`nonConsolidated[<propertyId>][co2eq]`; vacío → `null`, coma decimal admitida, negativo o texto → `\InvalidArgumentException`, que el controlador muestra como flash) → `AnswerCompanyCarbonFootprintScopeService`:
1. Autoriza (`Action::Edit`).
2. Valida bloques contra `CompanyProperties::consolidated()`; un bloque de no consolidada → `'Property does not belong to company'`.
3. Sincroniza con respuestas vacías los bloques de `nonConsolidated()` (línea 89): retira actividades de ese alcance. Ocurre al volver a guardar **cada** paso, no al completar.
4. Alcance 3 sin exclusión de etapa: `NonConsolidatedPropertiesSynchronizer::sync($fp, $request->nonConsolidatedProperties)` (línea 94): el resolver calcula, se valida que cada respuesta sea de una propiedad no consolidada, se llama a `recordNonConsolidatedProperty()` por propiedad y a `retainNonConsolidatedProperties()`.

### Cálculo de la cifra (`NonConsolidatedPropertyEmissionsResolver`)
Por cada propiedad de `CompanyProperties::nonConsolidated()`: fuente según régimen; huella = `PropertyCarbonFootprintRepository::findByPropertyIdAndYear(propiedad, año)` si existe, no está borrada, es de la misma cuenta y `isCompleted()`; cifra = `byStage['scope_1'] + byStage['scope_2']` de `EmissionsBreakdownBuilder::build($huella, [Scope1, Scope2])` (nunca `total`, que suma todas las etapas). Sin huella válida: 0 y origen `null`.

### Lectura y totales
`DerivedSourceEmissionsCalculator::nonConsolidatedPropertyLines()` devuelve las filas guardadas (solo empresa y alcance 3, respetando exclusiones de etapa y fuente). `EmissionsBreakdownBuilder` (línea 105) las suma a `total`, `bySource`, `byStage`, `byStageAndSource` y `byPropertyId`; de ahí salen resultados y gráficas. `lines()` sigue siendo solo la categoría 3. El calculador lee el agregado y no debe depender del resolver (evita ciclo).

### Completar
`CompleteCompanyCarbonFootprintService` (línea 41): si el alcance 3 no está excluido, `sync($fp, null)` refresca las cifras calculadas conservando los manuales y después calcula el total. La huella completada ya no cambia aunque cambien la huella de propiedad o el régimen. `GetCompanyCarbonFootprintScopeService` muestra las filas en vivo si la huella es editable y desde la foto si está completada.

### Informe y mitigación
`CompanyCarbonFootprintReportDataCollector:105` rellena `OrganizationalCarbonFootprintReportData::$nonConsolidatedPropertyLines`; los generadores TCPDF pintan `nonConsolidatedPropertyTable` (Propiedad, Origen, Emisiones) y `derivedSourceTable` queda filtrado a `FuelAndEnergyRelated`. `MitigationEmissionSourcesBuilder:88` crea una `MitigationEmissionSource` de `Scope3` por fila con emisiones > 0 (`itemId` = propiedad, sin `originKey`, por tanto reducible); `MitigationSourceType` añade las dos fuentes a las opciones.

## Autorización y ámbito

- Régimen: `PropertyAuthorizer`, `Action::Register` / `Action::Edit` (Propietario de la cuenta y Administradores); no hay permiso nuevo.
- Filas 13/14: mismo permiso que el resto del paso (`CompanyCarbonFootprintAuthorizer`, `Action::Edit`, al inicio de `AnswerCompanyCarbonFootprintScopeService`). Las propiedades se obtienen de `CompanyProperties` por la empresa de la huella; la cuenta de la huella de propiedad origen se compara con la de la huella de empresa.
- No hay feature flag nuevo; el menú de propiedades y de huellas depende de los flags existentes (`NodeLister`).

## Persistencia y migración

- `properties.operating_regime VARCHAR(255) NOT NULL DEFAULT 'owned'`.
- Tabla `company_carbon_footprint_non_consolidated_properties`: FK a `company_carbon_footprints`, `UNIQ_ccf_ncp_property (company_carbon_footprint_id, property_id)`, `IDX_3F2FD39E1AA6FD85`/`FK_3F2FD39E1AA6FD85`, columnas `calculated_emissions_*`, `manual_co2eq`, `recorded_at`. Relación `one-to-many` con `orphan-removal` y `cascade-persist` en `CompanyCarbonFootprint.CompanyCarbonFootprint.orm.xml`; tipo de id `company_carbon_footprint_non_consolidated_property_id` en `config/packages/doctrine.yaml`.
- Migración aplicada: `migrations/Version20261022134504.php` (coherente con `migration.sql`). Sin backfill.

## Configuración y operación

Sin variables de entorno, colas, comandos ni tareas programadas nuevas. Hay que ejecutar `make migrate`. La importación de propiedades por consola no lee el régimen: las propiedades que registra quedan como `owned`. Si cambian las traducciones: `make translation`.

## Cómo extenderlo

Para otro régimen no consolidado con su categoría: 1) caso en `OperatingRegime` (con `isConsolidated()` falso) y su traducción en `operatingRegime`; 2) caso en `Source` (respetando el orden GHG) y traducción en `source` (y en `templates/carbon_footprint/translations/source.html.twig`); 3) añadirlo a `Scope::derivedSources()`, `downstreamSources()` y `companyOnlySources()`; 4) mapearlo en `Scope::sourceForOperatingRegime()`; 5) añadir la rama en `_scope_3_sections.html.twig` y en el `match` de los generadores TCPDF; 6) `NonConsolidatedPropertiesView` ya agrupa por fuente. Trampas: `Source::cases()` ordena tablas e informe (los tests que cuentan fuentes pasaron de 24 a 26); el régimen se lee en el momento de guardar o completar (sin histórico por año); cualquier llamante nuevo de `Property::update()` debe pasar los campos actuales (el régimen `null` lo conserva, pero empleados, clientes y `companyId` no).

## Tests

- Unitarios: `tests/Unit/Property/Domain/Model/Property/{OperatingRegimeTest,PropertyTest}.php`, `tests/Unit/Property/Domain/Event/Company/UnlinkCompanyOnCompanyRemovedSubscriberTest.php`, servicios de Register/Update/Match/List, `ScopeTest`, `CompanyCarbonFootprintTest`, `PropertyCarbonFootprintTest`, `CompanyPropertiesTest`, `NonConsolidatedProperty/*Test`, `DerivedSourceEmissionsCalculatorTest`, `EmissionsBreakdownBuilderTest`, servicios AnswerScope/GetScope/Complete/ReplaceScopeAnswers, `MitigationEmissionSourcesBuilderTest`, colector del informe, generadores TCPDF, `NonConsolidatedPropertyHttpMapperTest`, `NonConsolidatedPropertiesViewTest`.
- Behat: `tests/Acceptance/CarbonFootprint/company-carbon-footprint-non-consolidated-properties.feature` (11 escenarios: bloque oculto, cifra de huella completada, «Sin datos», valor manual, exclusión, retirada de actividades, huella completada estable, plan de mitigación, reguardado, completar con refresco, cambio de motivo de exclusión) y los de `tests/Acceptance/Property/{property-edit,property-register,property-list,property-match,property-unlink-account}.feature`.
- Ejecución: `make test-this test=tests/Acceptance/CarbonFootprint/company-carbon-footprint-non-consolidated-properties.feature`, `make test-unit`, `make test-acceptance`.
- Hueco: en el entorno de test el repositorio de huellas de empresa es `InMemoryCompanyCarbonFootprintRepository`, así que Behat y los unitarios no reproducen el índice único de Doctrine (A1): la protección se apoya en el código.

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`) y `review.md`: la revisión 2 no deja hallazgos abiertos (A1, A2, M1, B1, B2 corregidos). Desviaciones declaradas: `choice_value` en `OperatingRegimeType`, filtrado por `subjectType` también en la página de emisiones incluidas del tema clásico, `PropertyContext` conserva campos al asignar empresa. Pendiente fuera de este plan: la huella de evento tiene el mismo patrón DELETE + INSERT en sus exclusiones (`.claude/plans/planning/2026-10-22-event-carbon-footprint-exclusion-update/plan.md`). Observación: el mensaje `Invalid emissions for property "%s"` del mapper HTTP no tiene traducción en `translations/messages`, por lo que el aviso de un importe no válido se muestra en inglés.
