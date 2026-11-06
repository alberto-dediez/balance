# Menús de tres niveles en la barra lateral — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-06 · **Bounded Contexts:** `Shared`

## Qué hace y por qué

El panel secundario de la barra lateral solo admitía dos niveles (icono raíz en la columna mini → lista plana de enlaces). Ahora admite un tercero: dentro del panel de un raíz, un **grupo** agrupa hojas bajo una cabecera propia, separada del grupo anterior por un divisor. Se estrena en el menú «Seguridad» de los administradores, dividido en **Organización** (Cuentas, Usuarios) y **Auditoría** (Firma de documentos, Emails enviados solo para `SuperAdmin`, Registro).

Decisiones clave (tabla «Decisiones tomadas» de `plan.md`): se reutiliza `Node` sin clases nuevas (un grupo es un `Node` sin `target` con `items`; una hoja, un `Node` con `target` y sin `items`); los grupos se pintan en el mismo `<ul>` plano del panel, de modo que ni `assets/js/theme.js` ni los SCSS cambian (`.nav-small-cap` y `.sidebar-divider.lg` ya existían); no hay cuarto nivel plegable.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Infraestructura UI | `src/Shared/Infrastructure/UI/Web/Navigation/Node.php` | `$icon` pasa a `?string $icon = null` (l.18); los grupos no llevan icono | modificado |
| Infraestructura UI | `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | `roots()`, `select()`, `leaves()`; «Seguridad» dividido en dos grupos en `toAdmins()` | modificado |
| Infraestructura UI | `src/Shared/Infrastructure/UI/Web/Navigation/NavigationSearchProvider.php` | `collect()` recursivo con camino `Raíz > Grupo` | modificado |
| Plantilla | `templates/components/Sidebar/Nav.html.twig` | bifurca grupo / hoja por `item.items` | modificado |
| Plantilla | `templates/components/Sidebar/Group.html.twig` | componente anónimo: divisor, cabecera y hojas | nuevo |
| Traducciones | `translations/messages+intl-icu.{es,en}.xlf` | «Organización» / «Auditoría» (EN: Organization / Audit) | modificado |
| Tests | `tests/Unit/.../Navigation/NodeListerTest.php`, `NavigationSearchProviderTest.php`, `ThreeLevelNodeLister.php` | unitarios del árbol de tres niveles | modificado / nuevo |
| Tests | `tests/Acceptance/Shared/sidebar-menu.feature`, `global-search.feature` | Behat del menú agrupado y del buscador | nuevo / modificado |
| Docs | `.claude/ARCHITECTURE.md` (fila «UI Web transversal») | describe el árbol de tres niveles | modificado |

Sin cambios (intencionado): `Sidebar/Link`, `Sidebar/Mini`, `Sidebar/Left`, `NavigationEntry`, `theme.js`, SCSS.

## Modelo de la navegación

No hay dominio ni persistencia. El árbol de `Node` tiene hasta tres niveles:

| Nivel | `icon` | `target` | `items` |
|---|---|---|---|
| Raíz | obligatorio en la práctica (`Sidebar:Mini` lo pinta) | opcional (raíz hoja) | hojas o grupos |
| Grupo | `null` | `null` | solo hojas |
| Hoja | obligatorio en la práctica (`Sidebar:Link`) | sí | vacío |

Reglas: se decide siempre por `items` (como ya hacía el buscador), no hay `isGroup()`. Un nodo con `items` nunca es enlace, ni resultado del buscador, ni se selecciona por su propio `target` (`ThreeLevelNodeLister` declara «Grupo A» con `target` precisamente para probarlo). No se valida la profundidad en ejecución: un hijo de grupo con `items` se pintaría como hoja.

## Flujo de una petición

1. `Sidebar:Left` obtiene `NodeLister::nodes()` (`NodeLister.php:30`) = `markSelected(roots())`. `roots()` (l.38) elige `toAdmins()`/`toOwners()`/`toUsers()` por rol efectivo (`hasRole`, l.51, que respeta la impersonación).
2. `markSelected()` (l.67) calcula `bestMatchingTarget()` sobre `leaves()` (l.107, l.131: todas las hojas de cualquier nivel, incluidas raíces sin `items`) y llama a `select()` por raíz.
3. `select()` (l.85): una hoja queda `selected` si su `target` es el mejor; un nodo con `items` se marca si alguno de sus hijos lo está. Se visitan todos los hijos sin cortocircuito (`$this->select(...) || $selected`, el orden evita la evaluación perezosa).
4. `Nav.html.twig` recorre `node.items`: si `item.items is not empty` renderiza `Sidebar:Group` con `first = loop.first`; si no, `Sidebar:Link`.
5. `Group.html.twig` pinta `<li><span class="sidebar-divider lg">` solo si `first` es falso, luego `<li class="nav-small-cap">` con la etiqueta traducida, y un `Sidebar:Link` por hoja. La cabecera del grupo no tiene estado seleccionado en el tema.
6. Buscador (`Ctrl+K`): `NavigationSearchProvider::entries()` llama a `collect()` (l.80) por raíz. Un nodo con `items` se recorre con el camino ampliado y no genera entrada; una hoja sin `target` se ignora; el subtítulo es `implode(' > ', $path)` (o `null` para una raíz hoja) y el icono `$node->icon ?? ''` (`NavigationEntry::$icon` sigue siendo `string`). El `haystack` es `normalize(subtitle . ' ' . title)` (`entry()`, l.99), así que contiene raíz, grupo y hoja.

El resultado se escapa en Twig: en HTML el subtítulo sale como `Seguridad &gt; Organización` (relevante para los pasos Behat `I see a text`).

## Autorización y ámbito

Sin cambios: no hay `Authorizer` propio; el menú depende del rol efectivo. Solo `toAdmins()` usa grupos. Dentro de «Auditoría», «Emails enviados» solo se añade si `hasRole(Role::SuperAdmin)` (`NodeLister.php`, bloque de `$auditItems`). Los menús de propietario, usuario, y los demás de administrador (Biblioteca, Notificaciones, Novedades) generan el mismo HTML que antes. La impersonación sigue decidiendo el menú por rol efectivo.

## Persistencia y migración

Ninguna (`Migración: no`).

## Configuración y operación

Sin variables, colas ni comandos nuevos. `NodeLister` sigue declarado en `src/Notifications/Infrastructure/Symfony/DependencyInjection/services.yaml` solo para inyectar `$internalNotificationsEnabled`. Las traducciones nuevas están en ambos `.xlf` (ids `F7jg8Ld` y `qth1N0B`); el extractor las detecta desde los `new TranslatableMessage(...)` de `NodeLister.php`.

## Cómo extenderlo

Para agrupar otro menú (o en un proyecto derivado): en el `toXxx()` correspondiente, sustituir `items` del raíz por `new Node(label: new TranslatableMessage('Grupo'), items: [hojas...])`.

Trampas:
- Declarar las hojas sueltas **antes** de los grupos: grupos y hojas comparten el mismo `<ul>` y un grupo no se «cierra», así que una hoja suelta tras un grupo se vería como suya mientras el buscador no le da ese subtítulo (documentado en `.claude/ARCHITECTURE.md`).
- No hay aplanado automático de un raíz con un solo grupo (como en bioscore): no lo agrupes si no hace falta.
- El primer grupo del raíz no lleva divisor (`loop.first`); los siguientes, sí. Una hoja suelta antes del primer grupo hace que ese grupo tenga divisor.
- Las hojas exigen icono; un grupo no lo pinta aunque se informe.
- Para probar un árbol propio sin depender del menú real, extender `NodeLister` y sobrescribir `roots()` (`tests/Unit/.../Navigation/ThreeLevelNodeLister.php`).

## Tests

- `NodeListerTest.php`: «Seguridad» dividido para `Admin` y `SuperAdmin` (`testAdminsSeeSecurityGroupedInOrganizationAndAudit`), propietarios y usuarios sin grupos, selección de hoja-grupo-raíz, hoja más específica entre niveles, un grupo nunca se selecciona por su `target`, raíz hoja sigue seleccionándose.
- `NavigationSearchProviderTest.php`: subtítulo con camino, «Auditoría» solo encuentra sus hojas, grupos y raíces no son resultados, raíz hoja sin subtítulo.
- `tests/Acceptance/Shared/sidebar-menu.feature`: menú agrupado, ausencia de divisor ante el primer grupo, «Emails enviados» para `SuperAdmin` dentro de «Auditoría», propietario sin grupos. `global-search.feature`: subtítulos `Seguridad > Organización/Auditoría`, búsqueda por grupo, en inglés.
- Ejecución: `make test-unit options="--filter Navigation"`, `make test-acceptance options="tests/Acceptance/Shared/sidebar-menu.feature"` y `make test-acceptance options="tests/Acceptance/Shared/global-search.feature"`.

## Desviaciones y deuda

- Desviaciones declaradas en `## Implementación` de `plan.md`: stub de `AuthorizationCheckerInterface` para `SuperAdmin` en el test, helper `labels()`, y números de línea del plan desfasados. Sin impacto.
- Deuda no corregida (previa): doble espacio en `$nodes =  []` de `toAdmins()`/`toOwners()`; el raíz «Novedades» repite a mano `announcementsNode()`.
- `review.md`: 2 hallazgos bajos (B1, B2), ambos corregidos; sin hallazgos abiertos.
