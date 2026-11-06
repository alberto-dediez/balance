# Reordenar menús del administrador: un único raíz «Maestros» — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-11-06 · **Bounded Contexts:** `Shared` (navegación transversal)

## Qué hace y por qué

La columna de iconos del menú lateral del administrador tenía quince raíces; cinco eran catálogos que alimentan las
actividades de huella (Materiales, Productos vendidos, Inversiones, Instalaciones, Transporte). Se reúnen bajo un único
raíz **«Maestros»** (icono `solar:database-line-duotone`). Cada antigua raíz pasa a ser un **grupo** (nodo sin `icon` ni
`target`, con `items`) dentro del panel de «Maestros»; las 18 hojas conservan etiqueta, icono y ruta.

Es un cambio puramente de navegación: reutiliza el árbol de tres niveles de `Node` (raíz → grupo → hoja) de la
funcionalidad «Menús de tres niveles». No toca dominio, aplicación, persistencia, controladores ni rutas, y solo afecta
al menú de `Admin`/`SuperAdmin`; `toOwners()` y `toUsers()` no cambian. Decisiones en la tabla de `plan.md`.

Durante la review se corrigió un defecto latente que el panel largo hizo aflorar: `data-simplebar` no tenía efecto
porque la librería JS no se cargaba (ver «Scroll de los paneles»).

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Shared/Infrastructure/UI/Web/Navigation/NodeLister.php` | `mastersNode()` (l.220) construye el raíz; `toAdmins()` lo añade en l.445 y pierde las cinco raíces absorbidas | Modificado |
| `translations/messages+intl-icu.es.xlf` / `.en.xlf` | Clave `Maestros` (id `..ezF4G`): ES «Maestros», EN «Master data» (l.12328 de ambos) | Modificado |
| `translations/messages+intl-icu.tr.xlf` | 32 líneas de claves previas pendientes que añadió el extractor `make translation` (sin relación funcional) | Modificado |
| `assets/app.js` (l.11), `importmap.php` (l.120-139) | Carga de SimpleBar (corrección A1 de la review) | Modificado |
| `templates/components/Sidebar/Nav.html.twig` (l.1) | Se quitó la clase estática `simplebar-scrollable-y`; SimpleBar la gestiona | Modificado |
| `tests/Unit/.../Navigation/NodeListerTest.php`, `NavigationSearchProviderTest.php` | Casos nuevos y orden de raíces actualizado | Modificado |
| `tests/Acceptance/Shared/sidebar-menu.feature`, `global-search.feature` | Escenarios nuevos | Modificado |
| `.claude/ARCHITECTURE.md` (l.422), `.claude/CONCEPTS.md` (l.71-78) | Nota de navegación y rutas de menú con prefijo «Maestros → » | Modificado |
| `Node.php`, `NavigationSearchProvider.php`, `Sidebar/Group`, `Link`, `Left`, `Mini` | Sin cambios: ya soportan raíz → grupo → hoja | Sin tocar |

Los paquetes de SimpleBar se descargan en `assets/vendor/` (ignorado por git); la review indica que lo instala el
auto-script `importmap:install` de `composer.json`.

## Modelo de dominio

No hay dominio. El modelo es el árbol de `Node` (`Node(label, ?icon, ?target, items, selected)`):

- Raíz «Maestros»: `icon` `solar:database-line-duotone`, sin `target`, cinco grupos en `items`.
- Grupo: `label` traducible, `icon` y `target` nulos (`NodeListerTest::testAdminsSeeMastersGroupedByActivity`, l.155).
- Hoja: `label`, `icon` y `target = $this->router->generate('<ruta>')`.

Contenido de `mastersNode()` (`NodeLister.php:220-345`), en orden:

| Grupo | Hojas (ruta) |
|---|---|
| Materiales | Materiales (`admin_materials`), Artículos de merchandising (`admin_merchandising_items`), Elementos de catering (`admin_catering_items`), Bienes y servicios comprados (`admin_purchase_services`), Bienes de capital (`admin_capital_goods`) |
| Productos vendidos | Procesos (`admin_processes`), Perfiles de uso (`admin_use_profiles`) |
| Inversiones | Sectores económicos (`admin_economic_sectors`) |
| Instalaciones | Combustibles (`admin_combustibles`), Comercializadoras eléctricas (`admin_electricity_marketers`), Factores GdO (`admin_gdo_factors`), Mix eléctrico (`admin_electricity_mixes`), Gases refrigerantes (`admin_refrigerant_gases`), Residuos (`admin_wastes`) |
| Transporte | Medios de transporte (`admin_transports`), Combustible de maquinaria (`admin_machinery_fuels`), Movilidad (`admin_mobilities`), Pernoctaciones (`admin_overnight_stays`) |

Se eliminaron las anclas `'#productos-vendidos'`, `'#inversiones'`, `'#instalaciones'` y `'#transporte'`: eran inertes
porque `select()` y `collect()` ignoran el `target` de cualquier nodo con `items`.

Orden actual de raíces del administrador (`NodeListerTest::testAdminRootsKeepTheirOrderAroundMasters`, l.206):
Propiedades (flag), Proyectos, Tesorería, **Maestros**, Huella de carbono, Administración, Biblioteca, Notificaciones
(flag), Novedades.

## Flujo de una operación

Pintado del menú (no hay petición propia):

1. `NodeLister::nodes()` → `roots()` elige `toAdmins()` si el rol efectivo es `Admin` (incluye `SuperAdmin`; `hasRole()`
   usa `CurrentRolesProvider`, respeta la impersonación).
2. `toAdmins()` llama a `mastersNode()` (`NodeLister.php:445`), que genera las 18 URLs con el router.
3. `markSelected()` (l.99) calcula `bestMatchingTarget()` (l.139, hoja con el `target` más largo que sea prefijo de la
   ruta actual, recorriendo `leaves()` de forma recursiva) y `select()` (l.117) propaga `selected` hoja → grupo → raíz a
   cualquier profundidad. Visitar `/es/admin/combustibles` deja seleccionados Combustibles, Instalaciones y «Maestros».
4. `templates/components/Sidebar/Nav.html.twig` pinta el panel del raíz: para cada `item` con `items` usa
   `Sidebar:Group` (cabecera `nav-small-cap`, y divisor `sidebar-divider lg` salvo si `first`), si no `Sidebar:Link`.
   `Mini.html.twig` pinta el icono de la columna con la clase `selected`.

Búsqueda global (`Ctrl+K`): `NavigationSearchProvider::collect()` (l.80) recorre el árbol; un nodo con `items` solo
aporta su etiqueta al camino y no genera resultado; una hoja genera una entrada con subtítulo `implode(' > ', $path)`.
Efectos: cada maestro aparece como `Maestros > <Grupo>`; buscar «Maestros» devuelve las 18 hojas; buscar un nombre de
grupo devuelve solo las suyas.

## Autorización y ámbito

Sin Authorizer propio. El raíz solo se construye en `toAdmins()`, es decir, para `Admin` y `SuperAdmin` (y un `Admin`
que impersona una cuenta recibe el menú de propietario, sin «Maestros»). Los destinos conservan su autorización en los
controladores, que no cambian. Los flags de cuenta no afectan al administrador.

## Persistencia y migración

Sin cambios de esquema. No hay migración.

## Configuración y operación

- Traducción: la clave `Maestros` se extrajo con `make translation`; el `target` EN («Master data») se rellenó a mano.
- Scroll de los paneles: `Nav.html.twig` y `Left.html.twig` (`ul.mini-nav-ul`) llevan `data-simplebar`. Antes de este
  plan la librería no se cargaba y el panel no desplazaba; ahora `assets/app.js:11` hace `import 'simplebar'` (inicializa
  los `[data-simplebar]`, también los que se muestran después). Entornos nuevos: `make asset-install` (o el
  auto-script de `composer install`). La hoja de estilos de SimpleBar ya se importa desde
  `assets/styles/vendors/_simplebar.scss`; las entradas CSS del importmap no se usan.
- No hay variables de entorno, colas ni comandos nuevos.

## Cómo extenderlo

Añadir un maestro de actividades nuevo:

1. Añadir una hoja `Node(label, icon, target: $this->router->generate('<ruta>'))` al grupo que corresponda dentro de
   `mastersNode()`, no como raíz. Mantener el orden.
2. Para un grupo nuevo: `Node` con `label` y `items`, **sin** `icon` ni `target`.
3. Si la etiqueta es nueva, ejecutar `make translation` y rellenar EN a mano.
4. Ajustar las listas esperadas de `testAdminsSeeMastersGroupedByActivity` y `testEveryMasterTargetIsStillReachable`.

Trampas: las hojas reutilizan el nombre del grupo («Materiales» es grupo y hoja) y «Combustibles de instalaciones»
de Bioscore también coincide con la búsqueda «Instalaciones»; los tests lo asumen. El `id` del `Node` es
`md5(label . icon)`: solo debe ser único entre raíces.

## Tests

- Unitarios (`NodeListerTest`): `testAdminsSeeMastersGroupedByActivity` (l.155), `testAbsorbedMastersAreNoLongerRoots`
  (l.178), `testAdminRootsKeepTheirOrderAroundMasters` (l.206), `testAMasterLeafSelectsItsGroupAndTheMastersRoot`
  (l.214), `testEveryMasterTargetIsStillReachable` (l.230). `NavigationSearchProviderTest`:
  `testAMasterGroupLabelFindsOnlyItsLeaves` (l.49), `testTheMastersRootFindsEveryMaster` (l.60).
- Behat: `tests/Acceptance/Shared/sidebar-menu.feature` (agrupado en un único raíz, primer grupo sin divisor, selección
  del grupo, Medidas de mitigación dentro de «Huella de carbono», el propietario no ve «Maestros») y
  `tests/Acceptance/Shared/global-search.feature` (búsqueda por raíz, por grupo y en inglés, «Master data»).
- Ejecución (objetivos del `Makefile`): `make test-unit options="tests/Unit/Shared/Infrastructure/UI/Web/Navigation"`,
  `make test-acceptance options="tests/Acceptance/Shared/sidebar-menu.feature"` (ídem `global-search.feature`),
  `make test-parallel`. No existe `test-this` en el Makefile.
- Hueco: el desplazamiento del panel (CSS/JS) no es automatizable con Behat; la review lo verificó a mano.

## Desviaciones y deuda

- `plan.md` da por hecho que `data-simplebar` hacía scroll; era falso y se corrigió cargando SimpleBar (hallazgo A1 de
  `review.md`).
- El código actual difiere del plan en el resto del menú: «Medidas de mitigación», «Materialidad» y «Bioscore» ya no
  son raíces propias. Están dentro de «Huella de carbono» (Medidas de mitigación, Materialidad) y de «Administración»
  (Bioscore, junto a Tipos de cambio, Organización y Auditoría) por cambios posteriores (`NodeLister.php:447-640`).
- El manual de administradores (`docs/manual-administradores/`) se actualizó con el prefijo «Maestros → » (B2).
- `review.md`: sin hallazgos abiertos (A1, B1 y B2 corregidos). Ver `plan.md` (`## Implementación`) y `review.md`.
