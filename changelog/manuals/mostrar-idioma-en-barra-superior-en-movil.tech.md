# Selector de idioma visible en la barra superior de las páginas públicas — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-19 · **Bounded Contexts:** `Shared` (plantillas compartidas de layout)

## Qué hace y por qué

Las páginas públicas (encuestas de asistentes, cuestionarios de aportación de proveedores y empleados, calculadora de viajes de una propiedad, landings, verificación de certificados, firma de documentos) se visitan sobre todo desde el móvil. La barra superior es un `navbar-expand-lg` de Bootstrap: por debajo de 992 px escondía el selector de idioma dentro del `collapse` `#navbarNav`, detrás de un botón de tres puntos (`navbar-toggler`), aunque en público ese menú no contenía nada más. El visitante no encontraba cómo cambiar de idioma.

Ahora, cuando el componente `Topbar` se pinta con `public = true`, el selector de idioma se coloca directamente en la barra, a la derecha del logotipo y en todos los anchos, y no se pintan ni el botón de tres puntos ni el `collapse`. El backoffice (`public = false`) conserva exactamente el markup anterior. No hay CSS, JS, PHP ni traducciones nuevas: el cambio es solo de estructura Twig (ver la tabla «Decisiones tomadas» de `plan.md`).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Plantilla | `templates/components/Topbar.html.twig` | Barra superior; bifurca por la prop `public` | Modificado |
| Plantilla | `templates/components/Topbar/LanguageSwitch.html.twig` | Bandera del idioma actual y desplegable de idiomas | Sin cambios (reutilizado) |
| Plantilla | `templates/shared/layout/public.html.twig:13` | Invoca `<twig:Topbar :public="true">`; lo extienden `public-card`, `public-message`, `public-wizard` y `templates/themes/aurora/shared/layout/public-message.html.twig` | Sin cambios |
| Plantilla | `templates/shared/layout/app.html.twig:8` | Invoca `<twig:Topbar>` (backoffice) | Sin cambios |
| Estilos | `assets/styles/layouts/_header.scss` | Apertura por hover y posicionamiento del desplegable | Sin cambios |
| Tests | `tests/Acceptance/Security/branding-public.feature:53-72` | Dos escenarios nuevos | Modificado |

## Estructura resultante del componente

`templates/components/Topbar.html.twig`:

- Líneas 7-24, comunes y sin cambios: hamburguesa del sidebar (solo si `not public`), logotipo (`branding_logo_url()` en público, logos claro/oscuro en privado) y `<twig:Topbar:AccountSwitch>`.
- Líneas 25-30, rama `{% if public %}`: una `ul.navbar-nav.flex-row.ms-auto.align-items-center` con un único `li.nav-item.nav-icon-hover.dropdown` que envuelve `<twig:Topbar:LanguageSwitch>`.
- Líneas 31-69, rama `{% else %}`: el `a.navbar-toggler` (`data-bs-target="#navbarNav"`, icono `ph:dots-three`) y el `div.collapse.navbar-collapse#navbarNav` con impersonación de usuario (`IS_IMPERSONATOR`), `Topbar:Search`, conmutador claro/oscuro (`dark-layout`/`light-layout`), `Topbar:LanguageSwitch`, `Topbar:Notifications` y `Topbar:Profile`. Los antiguos `{% if not public %}` internos desaparecieron porque en esta rama siempre son ciertos.

Por qué cada clase de la rama pública es necesaria:

- `navbar-nav` dentro de `.topbar .navbar`: de ella cuelgan las reglas de `_header.scss:30-44` (apertura del desplegable con `&:hover .dropdown-menu { display: block }` y `dropdown-menu-end` alineado a la derecha) y las de `_header.scss:113-126` (bajo `md`, `.nav-item.dropdown { position: static }` y `.dropdown-menu { position: absolute; width: 100% }`, con lo que el desplegable ocupa el ancho de la barra).
- `flex-row`: por debajo de `lg` `.navbar-nav` es columna por defecto.
- `ms-auto`: empuja la bandera a la derecha; por encima de `lg` `.topbar-logo` tiene `width: 100%; justify-content: center` (`_header.scss:105-110`).
- `li.dropdown`: sin ella no hay apertura por hover. El enlace de la bandera (`LanguageSwitch.html.twig:4`) no lleva `data-bs-toggle="dropdown"`; en táctil el primer toque activa el estado hover.

## Flujo de una operación

1. Un layout público renderiza `<twig:Topbar :public="true">` (`public.html.twig:13`).
2. `Topbar.html.twig:25` entra en la rama pública y pinta `LanguageSwitch`.
3. `LanguageSwitch.html.twig:1-3` lee `_route_params` de la petición y solo pinta si existen `_locale` y el global Twig `locale_list` (`config/packages/twig.yaml`, alimentado por `app.locale_list: '%env(LANGUAGE_LIST)%'` en `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml:3`). Si falta alguno no pinta nada y la barra pública queda solo con el logotipo (antes quedaba un botón de tres puntos que abría un menú vacío).
4. Pinta la bandera `circle-flags:<locale>` y un desplegable con título «Idioma» y un enlace por idioma de `locale_list | split('|')` a `path(_route, _route_params | merge({'_locale': language}))` (`LanguageSwitch.html.twig:12-13`), con el nombre `language | locale_name | capitalize`.
5. Pulsar un idioma es una navegación GET a la misma ruta con otro `_locale`; no se envía ningún formulario.

Todas las rutas públicas cuelgan de prefijos `/{_locale}` en `config/routes.yaml` (`carbon-footprint-web`, `balance-web`, `security-public-sign`…), así que en la práctica el selector aparece siempre.

## Autorización y ámbito

Sin autorización nueva. `Topbar:AccountSwitch` sigue fuera de la bifurcación (`Topbar.html.twig:24`) y solo se pinta a un usuario con sesión que puede impersonar cuentas (`src/Security/Infrastructure/UI/Web/Component/Topbar/AccountSwitch.php`, método `enabled()`); en un enlace público visitado sin sesión no aparece. Los temas de marca blanca no redefinen `Topbar` (solo existe `templates/themes/aurora/shared/layout/public-message.html.twig`), así que todos heredan el cambio.

## Persistencia y migración

No aplica.

## Configuración y operación

- `LANGUAGE_LIST` (`.env:49`, `'es|en'`) decide los idiomas del desplegable y los aceptados en `_locale`.
- Sin assets que recompilar (no hay cambios en SCSS ni JS).

## Cómo extenderlo

- **Añadir otro elemento a la barra pública**: añadir un `li.nav-item` dentro de la `ul` de la rama `{% if public %}` (`Topbar.html.twig:26-30`). Si es un desplegable, el `li` lleva `dropdown` para heredar apertura por hover y ancho completo en móvil. No reintroducir el `collapse` en público salvo que haya varios elementos que agrupar.
- **Cambiar la barra del backoffice**: tocar solo la rama `{% else %}`; el escenario «The backoffice keeps the three-dots menu…» vigila que `#navbarNav` siga existiendo.
- **Trampa**: el escenario «Branded public pages are locked to the light theme and hide the theme switch» del mismo `.feature` comprueba que `dark-layout` no aparece en público; el conmutador claro/oscuro debe permanecer en la rama privada.

## Tests

`tests/Acceptance/Security/branding-public.feature`:

- «Public pages show the language switch in the top bar without the three-dots menu» (líneas 53-63): en `/es/carbon-footprint/attendee-survey/token-abc/intercity` no existen `data-bs-target="#navbarNav"` ni `id="navbarNav"`, existe el enlace a `/en/...` y el texto «Idioma».
- «The backoffice keeps the three-dots menu with the language switch inside» (líneas 65-72): como `ROLE_ADMIN` en `/es/`, existen el botón y el `collapse` y el enlace a `/en/`.

Los pasos (`I see a text`, `I do not see a text`, `I see a link to`) están en `tests/Acceptance/Shared/WebContext.php` y buscan en el HTML crudo. Ejecución:

```bash
make test-acceptance options="tests/Acceptance/Security/branding-public.feature"
```

No hay tests unitarios (no hay lógica PHP). La colocación visual (bandera a la derecha, desplegable a ancho completo en móvil) no la cubre ningún test automático.

## Desviaciones y deuda

- Según `## Implementación` de `plan.md`, el `Makefile` no tiene `test-this` y se usó `make test-acceptance options=…`; la comprobación visual se hizo sobre la página pública de firma a 390 px y 1400 px, sin capturar el backoffice en móvil.
- `review.md` (revisión 1): sin hallazgos.
