# Asistente IA — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-16 · **Bounded Contexts:** `Assistant` (nuevo), `Shared`, `Security`, `Changelog`, `Library`, `Notifications`

## Qué hace y por qué

El asistente es un chat en un panel lateral para `Admin` y `SuperAdmin`. Una pregunta la resuelve un modelo pequeño que solo puede **leer** (herramientas de lectura). Una petición de acción no se ejecuta: el modelo propone un **plan** de pasos que el administrador revisa y lanza con «Ejecutar»; solo entonces un modelo mayor ejecuta los pasos uno a uno, y cada llamada se verifica contra el plan aprobado.

El BC `Assistant` es una copia, ruta a ruta y byte a byte, del que ya existe en la rama `dediez-erp` (origen `6d6239b3a`, ver `plan.md` § Objetivo). El motivo es que `dediez-erp` fusiona `origin/master` periódicamente: ficheros idénticos fusionan sin conflicto. Las desviaciones inevitables están en [Guía de fusión](#guía-de-fusión-con-dediez-erp). El motor es genérico: cada contexto aporta herramientas implementando `AssistantTool`/`WriteAssistantTool` sin tocar `src/Assistant`. Hydra estrena el asistente con 22 herramientas propias (usuarios, cuentas, novedades, biblioteca, notificaciones, auditoría, buscador) y con tres piezas transversales traídas en la misma copia: capa de IA (`Shared/.../Ai`), actualización en vivo (`Shared/.../Live`) y atajos de teclado (`Shared/.../Shortcut`).

Decisiones clave (tabla de `plan.md` § Decisiones tomadas): copia idéntica; migraciones copiadas del ERP con su mismo número de versión; solo `Admin`/`SuperAdmin`; escritura limitada a registrar usuario, registrar cuenta y editar cuenta; ruta web bajo `%app.admin_prefix%`; `ASSISTANT_ENABLED=false` por defecto.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Assistant/Domain/Model/Conversation/` (`Conversation`, `Message`, `MessageAttachment`, `ToolCall`, eventos `ConversationStarted`, `ConversationMessageAdded/Completed/Failed`, `ConversationRenamed`, `ConversationRemoved`) | Conversación privada de un usuario, mensajes, adjuntos y llamadas a herramientas | nuevo |
| Dominio | `src/Assistant/Domain/Model/ActionPlan/` (`ActionPlan`, `PlanStep`, `PlanStepArgument`, `PlanStepOutput`, eventos `ActionPlanProposed/Approved/ExecutionStarted/StepExecuted/StepFailed/Executed/Failed/Discarded/Expired`) | Plan de acciones y su ciclo de vida | nuevo |
| Dominio | `src/Assistant/Domain/Model/AssistantUsage.php`, `AssistantUsageRow*.php` | Tokens y coste por respuesta; fila agregada del consumo | nuevo |
| Aplicación | `src/Assistant/Application/Conversation/{Start,SendMessage,Answer,ViewStatus,View,List,Rename,Remove,Stop,DownloadAttachment,ExpireStale,Purge}/` | Casos de uso de la conversación | nuevo |
| Aplicación | `src/Assistant/Application/ActionPlan/{Propose,Approve,Execute,Discard}/` | Casos de uso del plan | nuevo |
| Aplicación | `src/Assistant/Application/Usage/View/ViewAssistantUsageService.php` | Consumo por día, usuario y modelo | nuevo |
| Aplicación | `src/Assistant/Application/Engine/` (`Toolbox`, `ToolRunner`, `ToolSchemaBuilder`, `PlanStepVerifier`, `DailyBudget`, `ConversationTranscript`, `AssistantPrompts`) | Motor: catálogo de herramientas, ejecución, verificación, límite diario | nuevo |
| Aplicación | `src/Assistant/Application/AssistantSettings.php` | Parámetros `assistant.*` | nuevo |
| Contrato | `src/Shared/Application/Assistant/` (`AssistantTool`, `WriteAssistantTool`, `ToolParameter`, `ToolArguments`, `ToolResult`, `ToolPreview`, `ToolLink`, `ToolDocument`, `ToolContext`, `ToolMode`, `CurrentAssistantExecution`) | Contrato que implementa cada herramienta | nuevo |
| Herramientas | `src/Security/Application/{User,Account,ApiToken,EmailLog,RecordedEvent,SignatureRequest}/.../Assistant/*Tool.php`, `src/Changelog/Application/{Announcement,ChangelogEntry}/Assistant/`, `src/Library/Application/Media/Assistant/`, `src/Notifications/Application/Notification/Assistant/ListMyNotificationsTool.php`, `src/Shared/Application/Search/Assistant/GlobalSearchTool.php` | Catálogo de Hydra (ver tabla más abajo) | nuevo |
| Casos de uso nuevos de Security | `src/Security/Application/User/View/ViewUserService.php`, `src/Security/Application/Account/View/ViewAccountService.php` | Detalle que envuelven `view_user` / `view_account` | nuevo |
| Persistencia | `src/Assistant/Infrastructure/Persistence/Doctrine/{Mapping,Repository,Type}/`, `.../InMemory/Repository/` | Mappings XML, repositorios, tipos de id | nuevo |
| Almacén | `src/Assistant/Infrastructure/Storage/{Local,InMemory}ConversationAttachmentStorage.php` | Adjuntos en `%app.uploads_dir%/assistant/conversations` | nuevo |
| Web | `src/Assistant/Infrastructure/UI/Web/User/Assistant/*Controller.php` (10 controladores), `Admin/Usage/ViewController.php`, `AssistantAccess.php`, `Component/{Assistant/Panel,Assistant/UsageCard,Topbar/Assistant}.php`, `Shortcut/AssistantShortcutProvider.php` | Panel, JSON/HTML parciales y pantalla de consumo | nuevo |
| Plantillas | `templates/assistant/`, `templates/components/Assistant/`, `templates/components/Badge/Assistant*.html.twig`, `templates/components/Shortcuts/Modal.html.twig` | Vista del panel y del consumo | nuevo |
| Frontend | `assets/controllers/assistant_panel_controller.js`, `assets/js/live.js`, controlador de atajos | Panel, sondeo/Mercure, atajos | nuevo |
| IA | `src/Shared/Application/Ai/`, `src/Shared/Infrastructure/Ai/` (Anthropic, `ProviderAi*Client`, `InMemoryAi*Client`, `AiRequestLog`) | Cliente de modelos y su doble de test | nuevo |
| Live / atajos | `src/Shared/Application/Live/`, `src/Shared/Infrastructure/Live/`, `src/Shared/Application/Shortcut/` | Actualización en vivo (desactivada por defecto) y registro de atajos | nuevo |
| Compilación | `src/Shared/Infrastructure/Assistant/DisabledAssistantToolsPass.php`, registrado en `src/Shared/Infrastructure/Symfony/Kernel.php:61` | Apaga herramientas por proyecto | nuevo |
| Auditoría | `src/Security/Domain/Model/RecordedEvent/RecordedEvent.php` (+ `Filter`, repositorios, `RegisterRecordedEventService`, `RecordEventSubscriber`, `ListRecordedEventsType`), `templates/security/admin/events/list.html.twig`, `templates/shared/history/show.html.twig` | Marca de «hecho por el asistente» (`assistant_execution_id`) | modificado |
| Menú | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php:219-224` | «Consumo del asistente» en «Seguridad → Auditoría» si `assistant.enabled` | modificado |
| Tareas | `src/Shared/Application/CommandBus/CommandSchedule.php:44-53` | `assistant:purge-conversations` y `assistant:expire-stale-work` | modificado |
| Config | `config/routes.yaml:108`, `config/services.yaml`, `config/packages/{csrf,doctrine,twig,mercure}.yaml`, `Makefile:8`, `.env`, `.env.dev`, `.env.test`, `behat.yml.dist`, `phpstan.dist.neon` | Rutas, parámetros, tipos Doctrine, CSRF, entorno de test | modificado |
| Migraciones | `migrations/Version20261111111125.php`, `migrations/Version20261112122832.php` | Copias idénticas a las del ERP | copiadas |

## Modelo de dominio

**`Conversation`** (`Conversation.php`): privada de su usuario; máximo 4000 caracteres por mensaje (`MESSAGE_MAX_LENGTH`), título de 120 (`TITLE_MAX_LENGTH`, por defecto «Nueva conversación»), hasta 5 adjuntos por mensaje (`Message::ATTACHMENTS_MAX_COUNT`). Un único mensaje pendiente a la vez: `isBusy()`. Cada mensaje del asistente tiene un `MessageKind` (`Text`, `Plan`, `Execution`) y un `MessageStatus` (pendiente, completado, fallido). Invariantes violadas lanzan `InvalidMessageException` o `InvalidConversationTitleException`.

**`ActionPlan`** (`ActionPlan.php`): estados `proposed → approved → executing → executed | failed`, más `discarded` y `expired` (`ActionPlanStatus`; «activos» = `approved` y `executing`). Máximo 15 pasos (`MAX_STEPS`) y 3 llamadas rechazadas (`MAX_REJECTED_CALLS`) antes de interrumpir con «Demasiadas llamadas rechazadas». Cada `PlanStep` guarda herramienta, argumentos (`PlanStepArgument`: valor literal o «resultado del paso N» con `fromStep`) y salidas (`PlanStepOutput`) que alimentan a pasos posteriores. La escritura es optimista: un cambio concurrente lanza `ActionPlanConcurrentlyModifiedException`. `DiscardReason`: `User` o `Superseded`.

**Verificación literal** (`Engine/PlanStepVerifier.php`): la llamada del modelo de acción debe tener los mismos nombres de argumento y los mismos valores que el paso aprobado; un argumento diferido debe ser una de las `references` que devolvió el paso origen.

## Flujo de una operación

### Pregunta (solo lectura)

1. `POST /{prefijo}/{_locale}/assistant/messages` → `SendController` (`ruta assistant_message_send`). Comprueba `AssistantAccess::isGranted()` (`AssistantAccess.php:23-45`: flag, usuario conectado, **no suplantando**, `Action::Register` sobre `ConversationAuthorizer`). Valida `SendAssistantMessageType` (texto obligatorio, adjuntos `.pdf .txt .md .csv .json .html .png .jpg .jpeg .webp .gif`).
2. `SendMessageService` (`Application/Conversation/SendMessage/`): autoriza, `DailyBudget::assertAvailable` (línea 81), rechaza con `ConversationBusyException` si hay mensaje pendiente o plan activo (línea 94), añade el mensaje del usuario y uno pendiente del asistente, descarta los planes `proposed` previos (`DiscardReason::Superseded`) y despacha `DeferredCommand(AnswerMessageRequest, Priority::HIGH)` (línea 124).
3. Un worker de `async_high` ejecuta `AnswerMessageService`: bucle de hasta `ASSISTANT_MAX_TOOL_ROUNDS` rondas (línea 135) con el modelo del nivel de consulta; cada llamada a herramienta pasa por `ToolRunner::run(..., ToolMode::Read, ...)` (línea 224). La herramienta virtual `propose_plan` (`AssistantPrompts::proposePlanTool`) lleva a `ProposeActionPlanService`.
4. El panel sondea `GET .../assistant/conversations/{id}/status` cada 3 s (`assistant_panel_controller.js`, valor `interval`); con `LIVE_UPDATES_ENABLED=true` recibe además avisos por Mercure. Al cambiar la huella (`ConversationFingerprint`) pide `.../thread` y repinta.

### Acción (plan)

1. `ProposeActionPlanService` valida cada paso: debe ser una herramienta de escritura disponible para el usuario, llama a `preview()` para validar sin escribir y resolver nombres legibles, y exige que no haya otro plan vivo. Errores → `InvalidPlanException`.
2. El panel pinta `templates/assistant/_plan.html.twig` con «Ejecutar» y «Descartar» (POST con tokens `execute-plan` / `discard-plan`, stateless en `config/packages/csrf.yaml`).
3. `ExecutePlanController` es el **único** punto que aprueba: `ApproveActionPlanService` (`plan->approve(...)`, límite diario, conversación libre) y despacha `ExecuteActionPlanRequest` en `async_high` (línea 89). Errores de negocio → 422 con texto plano; no existente o sin permiso → 404.
4. `ExecuteActionPlanService`: marca `executing`, registra `CurrentAssistantExecution::begin(planId)` (de ahí sale `assistant_execution_id` en cada evento registrado), y en cada ronda verifica la llamada con `PlanStepVerifier` (línea 233) antes de `ToolRunner::run(..., ToolMode::Write, ...)` (línea 249). Fallo de paso → `failed`, el resto de pasos pasan a `skipped`.
5. Tareas programadas (`CommandSchedule.php:44-53`): `ExpireStaleAssistantWorkService` cada 5 min marca como interrumpido el trabajo sin progreso más de `ASSISTANT_STALE_MINUTES` y caduca planes `proposed` a los `ASSISTANT_PLAN_TTL_MINUTES`; `PurgeConversationsService` borra a las 03:20 conversaciones sin actividad desde hace `ASSISTANT_RETENTION_DAYS`, con sus planes y adjuntos.

### Consumo

`GET /{prefijo}/{_locale}/admin/assistant/usage` (`admin_assistant_usage`, `Admin/Usage/ViewController.php`) exige `ROLE_ADMIN`, flag activo y `Action::Admin` del `ConversationAuthorizer`; periodo `week`/`month`/`all` (`DailyPeriod`). Pinta `Assistant:UsageCard` (totales, y tablas por día, usuario y modelo). No comprueba suplantación (hallazgo B3 del review, descartado).

## Autorización y ámbito

- `ConversationAuthorizer.php`: todas las acciones exigen `Admin` o `SuperAdmin` vía `userHasRole`; ver/editar/eliminar además exigen ser el dueño; `History` siempre `false`. `ActionPlanAuthorizer` sigue el mismo criterio.
- Un `Owner`, un `User` o un administrador suplantando una cuenta o un usuario no ven botón ni panel (`AssistantAccess`) y reciben 403 en las rutas `assistant_*`.
- Cada herramienta decide su disponibilidad en `isAvailableTo($user)` consultando el `Authorizer` de su agregado; los servicios que envuelve la vuelven a comprobar. Excepción documentada: `ListApiTokensTool::isAvailableTo()` consulta `UserAuthorizer` (`Action::List`) y no `ApiTokenAuthorizer`; la autorización por token sigue en `ListApiTokensService`.
- `list_email_logs` solo es de `SuperAdmin` (`EmailLogAuthorizer`). `search_changelog_documents` incluye los manuales técnicos solo para `SuperAdmin`.
- Flag: `ASSISTANT_ENABLED` (`assistant.enabled`); con `false` desaparecen botón, panel, rutas efectivas, menú de consumo y atajo `A`.

## Catálogo de herramientas de Hydra

| Herramienta | Modo | Qué hace |
|---|---|---|
| `list_users`, `view_user` | lectura | Listar (nombre, rol, cuenta) y detalle |
| `list_accounts`, `view_account` | lectura | Listar (nombre, activa) y detalle |
| `register_account` | escritura | `name`, `active` obligatorios (sin valor por defecto) |
| `update_account` | escritura | Cambia nombre y/o estado; reenvía `apiTokensEnabled` actual (no lo expone) |
| `register_user` | escritura | `username` (+ `accountId` opcional); **sin parámetro de contraseña** (`RegisterUserTool.php:128` genera una aleatoria que nunca se devuelve ni se guarda en el plan); el email de bienvenida lo envía `WelcomeUserNotifier`, no la herramienta |
| `list_api_tokens` | lectura | Nombre, estado y fechas de los tokens de un usuario; nunca el token |
| `list_email_logs` | lectura | Sin cuerpo |
| `list_recorded_events` | lectura | Fecha, evento, contexto, agregado, usuario `{id, username}` y `byAssistant`; **nunca el contenido** |
| `list_signature_requests` | lectura | Por cuenta y estado |
| `list_announcements`, `view_announcement` | lectura | Novedades de administración (borrador/programada/publicada) |
| `list_changelog_entries`, `view_changelog_entry`, `search_changelog_documents`, `read_changelog_manual`, `read_changelog_technical_manual` | lectura | Changelog interno |
| `list_media`, `read_media` | lectura | Biblioteca y lectura de ficheros |
| `list_my_notifications` | lectura | Notificaciones del propio usuario |
| `search` | lectura | Buscador global |

Vetado por decisión (sin herramienta): roles, borrados, contraseñas, 2FA, tokens de API, suplantación, envío de notificaciones, firmas, importaciones y exportaciones.

Nota: las descripciones de `read_media` y `search` (copias idénticas del ERP) mencionan tareas, tickets y propuestas que Hydra no tiene.

## Persistencia y migración

- Tablas `assistant_conversation`, `assistant_message`, `assistant_message_attachment`, `assistant_tool_call`, `assistant_tool_call_argument`, `assistant_action_plan`, `assistant_plan_step`, `assistant_plan_step_argument`, `assistant_plan_step_output`, y la columna `recorded_events.assistant_execution_id` (`Version20261111111125`); `assistant_message_attachment` y `assistant_tool_call.result` (`Version20261112122832`).
- Las dos migraciones son **copias idénticas byte a byte** de las del ERP (verificado con `cmp` en el review). Una migración nueva fallaría en el ERP con «table already exists». Nunca ejecutar `make migration` para este módulo. Si algún entorno llegó a ejecutar la autogenerada `Version20261116101024`, hay que quitar su registro con `--delete` y marcar las dos copiadas con `--add` (review A1).
- Tipos Doctrine registrados en `config/packages/doctrine.yaml:27-32` (`assistant_*_id`).

## Configuración y operación

| Variable | Defecto (`.env`) | Efecto |
|---|---|---|
| `ASSISTANT_ENABLED` | `false` (`true` en `.env.dev` y `.env.test`) | Activa todo el asistente |
| `ASSISTANT_QUERY_AI_TIER` / `_EFFORT` | `low` / `medium` | Modelo de consulta |
| `ASSISTANT_ACTION_AI_TIER` / `_EFFORT` | `high` / `medium` | Modelo de ejecución de planes |
| `ASSISTANT_DAILY_COST_LIMIT_USD` | `5` | Límite por usuario y día; `0` lo desactiva |
| `ASSISTANT_RETENTION_DAYS` | `90` | Purga de conversaciones |
| `ASSISTANT_MAX_TOOL_ROUNDS` | `12` | Rondas de herramientas por respuesta |
| `ASSISTANT_PLAN_TTL_MINUTES` | `60` | Caducidad de un plan propuesto |
| `ASSISTANT_STALE_MINUTES` | `10` | Umbral de trabajo colgado |
| `ASSISTANT_DOCUMENT_MAX_BYTES` | `10485760` | Tamaño máximo por adjunto |
| `ASSISTANT_TRANSCRIPT_ATTACHMENTS_MAX_BYTES` | `12582912` | Total de adjuntos por mensaje |
| `AI_PROVIDER`, `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL_LOW/MEDIUM/HIGH`, `AI_LOG_ENABLED` | `anthropic`, vacío, `claude-haiku-5-5` / `claude-sonnet-5-5` / `claude-opus-5-5`, `false` | Proveedor y modelos por nivel; con `AI_LOG_ENABLED=true` cada llamada se vuelca a `var/ai-logs` |
| `LIVE_UPDATES_ENABLED` | `false` en `.env.test` | Mercure; sin hub el panel sondea cada 3 s |

Los parámetros `assistant.*` tienen valor por defecto en código (`src/Assistant/Infrastructure/Symfony/DependencyInjection/services.yaml`), de modo que la variable puede faltar. En `when@dev`/`when@prod` los servicios de IA son los de Anthropic; en `when@test` son dobles en memoria (`InMemoryAiAgentClient`).

Requisitos de despliegue: un worker de `async_high` (`make queues` o `make queues-high`) y `RunScheduledCommandsCommand` en marcha; sin ellos el asistente no responde ni caduca trabajo colgado. Un modelo ausente de `AnthropicPricing` cuenta con coste 0 y el límite diario no protege.

Extras de proyecto: `shared.assistant.suggestions` (lista de textos, defecto `[]`, global Twig `assistant_suggestions`) pinta las sugerencias del panel vacío; Hydra declara cuatro en `config/services.yaml`. `shared.assistant.disabled_tools` (lista de clases) apaga herramientas.

## Cómo extenderlo

**Añadir una herramienta de lectura:** 1) crear `XxxTool implements AssistantTool` en `src/<Ctx>/Application/<Agg>/Assistant/` con `name()`, `label()`, `description()`, `parameters()` (`ToolParameter::string/id/enum/boolean/date/limit`), `isAvailableTo()` y `execute()` que devuelva `ToolResult(summary, data, references, link)` envolviendo el servicio y su `Authorizer` (la etiqueta `shared.assistant.tool` se aplica por `#[AutoconfigureTag]`); 2) declarar la etiqueta de la herramienta en `templates/<ctx>/translations/assistant.html.twig` y ejecutar `make translation`; 3) unitario del estilo de `tests/Unit/Security/Application/Account/Assistant/`.

**Añadir una de escritura:** `implements WriteAssistantTool`, con `preview()` que valida sin escribir y devuelve `ToolPreview(displays)`; un id ausente en `preview()` es un argumento diferido y debe tolerarse. Capturar las excepciones de dominio esperadas y relanzarlas como `InvalidToolArgumentsException::because()` (patrón de `RegisterUserTool.php:116`).

**Apagar herramientas en un proyecto derivado:** añadir sus clases a `shared.assistant.disabled_tools` en su `config/services.yaml`. Se identifican por clase porque en compilación no hay instancias; una clase inexistente lanza `InvalidArgumentException` (`DisabledAssistantToolsPass.php`).

**Trampas:** dos herramientas con el mismo `name()` lanzan `DuplicatedToolNameException` al construir `Toolbox`; lo que devuelve una herramienta viaja al proveedor de IA, por eso no devuelven contenido de eventos, cuerpos de email ni tokens; las rutas con efectos van en `stateless_token_ids` de `config/packages/csrf.yaml` (hay cinco ids del asistente) y con `#[CsrfProtected('...')]`; los ficheros «copia idéntica» no se editan ni con `make cs-fix` (si lo cambia, revertir ese fichero).

## Atajos de teclado y modal

`PlatformShortcutProvider` registra `Ctrl+K` (buscar), `Ctrl+Enter` (enviar formulario) y `Ctrl+F1` (ayuda); `AssistantShortcutProvider` añade la tecla `A` (evento `assistant-toggle`) solo si `AssistantAccess` lo permite. `ShortcutLister` lanza `DuplicatedShortcutException` si dos proveedores repiten combinación. El buscador deja de escuchar el teclado por su cuenta y recibe el evento del sistema de atajos. `ActionBar:Button` gana el soporte de `Ctrl+Enter` (`data-submit-shortcut`) conservando los anchos de Hydra. El modal `templates/components/Shortcuts/Modal.html.twig` está en todas las páginas, y se abre desde «Atajos de teclado» en el menú del perfil.

## Tests

- **Unitarios** (`tests/Unit/Assistant/**`, `tests/Unit/Security/**/Assistant/`, `tests/Unit/Shared/**`): servicios de conversación y plan, motor (`ToolRunnerTest`, `PlanStepVerifierTest`, `DailyBudgetTest`, `ToolboxTest`, `ConversationTranscriptTest`), las herramientas de Hydra, `DisabledAssistantToolsPassTest`, atajos, live y reloj. `RegisterUserToolTest` usa `SpyPasswordHasher` para comprobar que la contraseña en claro nunca aparece en el resultado.
- **Aceptación** (`tests/Acceptance/Assistant/`): `assistant-access`, `-attachments`, `-conversations`, `-usage` (copiadas del ERP, salvo `assistant-access.feature:7`, que visita `/es/admin/users`) y los equivalentes de Hydra `assistant-base-{query,plan,plan-failure,documents,limits,memory,security-tools,stop}.feature`, más `tests/Acceptance/Shared/keyboard-shortcuts-base.feature`. Los nombres `*-base-*` evitan chocar con los del ERP.
- Ejecución: `make test-unit options="--filter Assistant"`, `make test-acceptance options="tests/Acceptance/Assistant/assistant-base-plan.feature"` y `make test-acceptance admin_prefix=/management` (la ruta del asistente respeta `ADMIN_PREFIX`). `Makefile:8` añade `-e ASSISTANT_ENABLED=true` a `TEST_CONT` porque `.env` lo trae a `false` y `env_file` ocultaría el `true` de `.env.test`.

## Guía de fusión con dediez-erp

Resumen de `plan.md` § Guía de fusión y de la «Ampliación de la Guía de fusión» de `review.md` (B6):

| Fichero | Resolución en el ERP |
|---|---|
| `tests/Unit/Assistant/Application/Engine/ToolRunnerTest.php`, `tests/Unit/Shared/Application/Live/PublishChangesOnDomainEventSubscriberTest.php`, `tests/Acceptance/Assistant/assistant-access.feature` | Versión de Hydra |
| `.env`, `.env.test`, `.env.dev` | Valores del ERP |
| `Makefile` (`TEST_CONT`) | Unir |
| `config/routes.yaml` | Una sola importación `assistant-web`, la de Hydra (con `%app.admin_prefix%`) |
| `config/services.yaml`, `config/packages/{doctrine,csrf,twig}.yaml`, `behat.yml.dist`, `importmap.php`, `config/bundles.php`, `src/Shared/.../services.yaml`, `assets/styles/app.scss`, `CommandSchedule.php` | Líneas duplicadas: una copia de cada una; en `config/services.yaml` los valores del ERP de `shared.assistant.suggestions` y `disabled_tools` |
| `composer.json` / `composer.lock` / `config/reference.php` | Unir / versión del ERP y `make composer c="update --lock"` / regenerar con `make cc` |
| Topbar, layout `app`, `Topbar/Profile`, `ActionBar/Button`, `markdown_editor_controller.js`, `WebContext.php`, `_cards.scss`, `UsageTable.html.twig` | Versión del ERP |
| `templates/assistant/_empty.html.twig` | Versión de Hydra (sugerencias desde parámetro) |
| `NodeLister.php` | Unir (menús del ERP + «Consumo del asistente») |
| `translations/*.xlf` | Regenerar con `make translation` |
| `migrations/` | Sin conflicto: copias idénticas |

Para no recibir las herramientas de usuarios y cuentas, el ERP añade a `config/services.yaml` `shared.assistant.disabled_tools` con `ListUsersTool`, `ViewUserTool`, `RegisterUserTool`, `ListAccountsTool`, `ViewAccountTool`, `RegisterAccountTool` y `UpdateAccountTool`, y elimina los `.feature` `assistant-base-{plan,plan-failure,query,security-tools}`.

## Desviaciones y deuda

Declaradas en `## Implementación` de [`plan.md`](plan.md) y en [`review.md`](review.md) (0 hallazgos abiertos: 8 corregidos, 2 descartados):

- `assistant-access.feature` y `ToolRunnerTest.php` no pueden ser idénticos a los del ERP.
- `Card.html.twig`, `_empty.html.twig`, `UsageTable.html.twig` y `_cards.scss` difieren del ERP (necesidades de Hydra; resueltos en la guía de fusión).
- `phpstan.dist.neon`: dos `ignoreErrors` acotados por ruta con `reportUnmatched: false` (Hydra usa PHPStan 2.1.40; el ERP, 2.2.16).
- Al registrar un usuario Hydra envía el email de bienvenida (`WelcomeUserNotifier`), de modo que el flujo no es «sin emails».
- `SignatureRequestAuthorizer.php:21,26` usa `$user->hasRole()` en vez de `userHasRole()` (deuda preexistente).
- Descartados: B3 (la pantalla de consumo sigue accesible al suplantar a un administrador; es de auditoría) y B4 (el test de `DisabledAssistantToolsPass` no cubre la etiqueta autoconfigurada real).
