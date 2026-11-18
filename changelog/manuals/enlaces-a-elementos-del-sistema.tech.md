# Enlaces a elementos del sistema en las respuestas del asistente — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-17 · **Bounded Contexts:** `Assistant`, `Shared`, `Security`, `Library`, `Changelog`, `Notifications`

## Qué hace y por qué

Las herramientas del asistente devolvían datos sin forma de abrir el elemento mencionado. Ahora cada herramienta entrega al modelo **referencias de ruta** (`route:<nombre>?<param>=<valor>`), el modelo las copia en un enlace Markdown (`[Acme](route:admin_accounts_edit?accountId=…)`) y es el servidor, **al pintar el hilo**, quien las convierte en la URL real con el router. Así el enlace sigue el idioma de la URL actual y el `ADMIN_PREFIX` sin guardar nada en el mensaje, y una ruta inventada degrada a texto, nunca a enlace roto (decisiones «Formato que maneja el modelo» y «Dónde se resuelve» del plan).

Además, toda dirección interna escrita a mano por el modelo (relativa, ancla, `//host` o absoluta con el host propio) deja de pintarse como enlace, y la herramienta nueva `list_screens` permite enlazar pantallas del menú.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Shared/Application/Assistant/ToolLink.php` | `SCHEME`, `href()` (construye la referencia) y `hrefFrom()` (la saca de las `references` del resultado) | modificado |
| `src/Assistant/Application/Engine/ToolResultContent.php` | JSON enviado al modelo y guardado en la llamada; añade `_link` | nuevo |
| `src/Assistant/Application/Conversation/Answer/AnswerMessageService.php` (~l.242) | usa `ToolResultContent::of()` en vez de `json_encode(data)` | modificado |
| `src/Assistant/Application/Engine/AssistantPrompts.php` (~l.43-44) | reglas de «href» y de `list_screens` en `querySystem()` | modificado |
| `src/Assistant/Infrastructure/Symfony/Twig/AssistantLinkResolver.php` | `isReference()`, `isInternal()`, `resolve()` con `UrlGeneratorInterface` | nuevo |
| `src/Assistant/Infrastructure/Symfony/Twig/AssistantMarkdownExtension.php` | listener `DocumentParsedEvent` (`resolveLinks`), filtro `assistant_copy_text` | modificado |
| `templates/assistant/_thread.html.twig:32` | `data-copy-text="{{ message.text \| assistant_copy_text }}"` | modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | `nodesFor(User)` y rama de `hasRole()` por roles del usuario con `RoleHierarchyInterface` | modificado |
| `src/Shared/Infrastructure/UI/Web/Navigation/ListScreensTool.php` | herramienta `list_screens` | nuevo |
| Herramientas con `href` por elemento | `ListUsersTool`, `ViewUserTool`, `ListAccountsTool`, `ListMediaTool`, `ListAnnouncementsTool`, `ListChangelogEntriesTool`, `SearchChangelogDocumentsTool`, `ListSignatureRequestsTool`, `ListEmailLogsTool`, `ListRecordedEventsTool`, `GlobalSearchTool` | modificadas |
| Herramientas con `ToolLink` nuevo | `ViewAnnouncementTool` («Ver anuncio»), `ListApiTokensTool` («Ver tokens», `userId`), `ListMyNotificationsTool` («Ver notificaciones») | modificadas |
| `templates/{shared,security,notifications}/translations/assistant.html.twig` + `translations/*` | etiquetas «Ver tokens», «Ver notificaciones», «Listar pantallas» | modificados |
| `tests/Acceptance/Shared/WebContext.php` | paso `Then I see a link :name to :path` (usa `AcceptanceClient::resolvePath()`) | modificado |
| `tests/Acceptance/Assistant/assistant-base-links.feature` | 4 escenarios | nuevo |

Sin cambios de contrato: `AssistantTool` y `ToolResult` no cambian de firma (las herramientas de `dediez-erp` siguen compilando).

## Modelo de dominio

No hay agregados ni eventos nuevos. La referencia de ruta es solo texto:

`route:` + nombre de ruta + (opcional) `?` + parámetros codificados RFC 3986 (`ToolLink::href()`, `ToolLink.php:19-22`).

Invariantes (plan, «Modelo conceptual»): todo `<a>` interno del hilo procede de `UrlGeneratorInterface::generate()`; ningún `href` interno lleva `?`; resolver nunca lanza.

## Flujo de una operación

**Ida al modelo.** `AnswerMessageService` ejecuta la herramienta y calcula el contenido con `ToolResultContent::of($result)`. Esa función serializa `data` con los flags de siempre y, si el `ToolResult` trae `link` y `link->hrefFrom($references)` no es `null`, añade `_link: {label, href}` (`ToolResultContent.php:13-21`). Cada herramienta aporta además su `href` por elemento dentro de `data` con `ToolLink::href()`. El mismo contenido se guarda en la llamada (truncado a 2000 caracteres por `ToolCall`). Las reglas del prompt (`AssistantPrompts.php`) instruyen a copiar el `href` tal cual en `[nombre](href)`.

**Vuelta al pintar.** `_thread.html.twig:31` aplica `assistant_markdown`. `AssistantMarkdownExtension::resolveLinks()` (listener de `DocumentParsedEvent`, prioridad por defecto 0, anterior al `ExternalLinkProcessor` que va a -50) recorre los nodos `Link`:

1. URL que empieza por `route:` → `AssistantLinkResolver::resolve()`; si devuelve URL, `setUrl()`; si no, `unwrap()` (queda el texto).
2. URL que no es `http(s):`/`mailto:` (relativa, ancla, `//host`) o cuyo host es el del contexto del router (`isInternal()`) → `unwrap()`.
3. Resto (externos) → sin tocar: `ExternalLinkExtension` los abre en pestaña nueva con `nofollow noopener noreferrer`.

`resolve()` (`AssistantLinkResolver.php:32-71`): nombre vacío o que empieza por `_` → `null`; `parse_str` de la query y `null` si algún valor no es `string`; `generate()` capturando `Symfony\Component\Routing\Exception\ExceptionInterface`; devuelve la URL cortada en el primer `?`, de modo que solo se rellenan variables del path y nunca se arrastra un `_token` a un GET con `#[CsrfProtected]`.

**Copiar.** `toCopyText()` reemplaza con regex cada `[texto](route:…)` por `[texto](URL absoluta)` (`resolve($href, true)`) o por `texto` si no resuelve.

**`list_screens`.** `ListScreensTool::execute()` recorre las hojas de `NodeLister::nodesFor($user)`, traduce etiquetas, filtra por `term` (todas las palabras, sin mayúsculas ni acentos) y obtiene la ruta de cada `target` con `RouterInterface::match()` forzando `GET` en el `RequestContext` y restaurándolo en `finally` (`ListScreensTool.php:117-127`). El `href` usa los parámetros del match que no empiezan por `_`. Hojas que no casan se omiten sin error. Devuelve `screens: [{title, location, href}]`.

## Autorización y ámbito

- Sin acciones ni `Authorizer` nuevos. El enlace solo lleva a una pantalla, que aplica su propia autorización.
- `list_screens` es `ToolMode::Read`; `isAvailableTo()` exige que `nodesFor($user)` no sea vacío.
- `nodesFor()` decide los roles con `$user->roles()` y `RoleHierarchyInterface` (misma jerarquía que `isGranted()`), no con la sesión web; guarda los roles en una propiedad temporal y la limpia en `finally` (los workers reutilizan el servicio). Con impersonación decide con los roles del `Admin` autenticado, no los de la cuenta impersonada: dudas Q1 de `review.md`, aceptado por el usuario. Un `SuperAdmin` ve además «Emails»; un `Admin` no.
- El asistente solo existe para `Admin`/`SuperAdmin` (ver `ARCHITECTURE.md`).

## Persistencia y migración

Sin cambios de esquema ni migración.

## Configuración y operación

Ninguna variable ni comando nuevo. El resolver se autoconecta (`UrlGeneratorInterface`); el `services.yaml` del BC no cambia. Las etiquetas nuevas se extraen con `make translation`.

Notas operativas:
- El idioma y el `ADMIN_PREFIX` de los enlaces son los de la petición que pinta el hilo; un cambio de prefijo exige `make cc` como siempre.
- Las etiquetas del menú que ve el modelo en `list_screens` salen en el idioma por defecto (el worker no tiene idioma de petición).
- Si `BASE_URL` llevara subdirectorio, `generate()` lo antepondría y `match()` fallaría: la pantalla se omite sin error.

## Cómo extenderlo

Para que una herramienta nueva sea enlazable:
1. Elemento de listado o detalle: añade `'href' => ToolLink::href('<ruta>', ['<param>' => (string) $id])` al array del elemento. La ruta debe existir y los parámetros deben ser variables de su path.
2. Enlace único del resultado: devuelve `link: new ToolLink('<etiqueta>', '<ruta>', ['<nombreReferencia>'])` y `references: ['<nombreReferencia>' => …]`; el motor añade `_link` solo si están todas las referencias.
3. Declara la etiqueta nueva en `templates/<ctx>/translations/assistant.html.twig` y ejecuta `make translation`.
4. Añade una aserción del `href` en el test de la herramienta.

Trampas: no uses parámetros que no sean variables del path (se descartan, y las rutas con query o `_token` no se enlazan); las rutas cuyo nombre empieza por `_` nunca se resuelven; no cambies la firma de `AssistantTool`/`ToolResult` (fusión con `dediez-erp`); `nodesFor()` y `NodeLister::__construct` (último argumento `?RoleHierarchyInterface`) se fusionan «uniendo» con el ERP.

## Tests

- Unitarios: `tests/Unit/Shared/Application/Assistant/ToolLinkTest.php`, `tests/Unit/Assistant/Application/Engine/ToolResultContentTest.php` y `AssistantPromptsTest.php`, `AnswerMessageServiceTest.php`, `tests/Unit/Assistant/Infrastructure/Symfony/Twig/AssistantLinkResolverTest.php` y `AssistantMarkdownExtensionTest.php` (referencias válidas e inválidas, enlaces internos a mano incluidos `//host` y URL con host propio, externos, copia), `tests/Unit/Shared/Infrastructure/UI/Web/Navigation/NodeListerTest.php` y `ListScreensToolTest.php` (jerarquía de roles, ruta solo-GET con contexto POST), y una aserción de `href` por herramienta.
- Aceptación: `tests/Acceptance/Assistant/assistant-base-links.feature` (cuenta enlazada, ruta inexistente, dirección interna escrita a mano, `list_screens`).
- Ejecución: `make test-unit options="--filter 'AssistantLinkResolverTest'"`, `make test-acceptance options="tests/Acceptance/Assistant/assistant-base-links.feature"` y, con prefijo, `make test-acceptance admin_prefix=/management options="tests/Acceptance/Assistant/assistant-base-links.feature"`.
- El proyecto usa PHPUnit 9.6: los proveedores de datos van con `@dataProvider`, no con atributos.

## Desviaciones y deuda

Ver `## Implementación` en `plan.md` y `review.md` (sin hallazgos abiertos; A1, B1, B2 y B3 corregidos).

- El plan decía «externos como hoy» y «jerarquía `SuperAdmin > Admin > Owner > User` por orden del enum»: el código ya no lo hace así (A1: las URL absolutas del host propio se desenlazan; B1: se usa `RoleHierarchyInterface`). Manda el código; el plan no se ha reescrito.
- La deuda del `match()` con método `POST` declarada en `plan.md` está corregida (B2).
- Un alias de dominio distinto del host del contexto del router no se detecta como interno (aceptado en A1).
