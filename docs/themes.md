# Marca blanca: branding por cuenta y temas visuales

La plataforma se puede vender como marca blanca en dos niveles.

- **Nivel 1 — branding por cuenta, sin tocar código.** Cada cuenta configura su logotipo, su
  gama de colores y su tipografía desde `/branding` (Admin/Owner) o desde
  `/admin/accounts/{accountId}/branding` (Admin). Afecta a las páginas públicas, a los emails
  transaccionales dirigidos al público y al certificado PDF de aportación/compensación.
  **El proceso de firma de documentos queda fuera:** sus páginas y sus emails mantienen siempre la
  marca de la plataforma.
- **Nivel 2 — temas visuales, tocando código.** Un tema aporta valores por defecto de los
  tokens, opcionalmente una hoja SCSS propia, opcionalmente plantillas Twig que sustituyen a las
  públicas y opcionalmente un renderizador de certificado distinto. Solo un SuperAdmin puede
  asignar un tema a una cuenta.

## Cómo funciona el nivel 1

`ResolveBrandingService` recibe un `AccountId` y devuelve siempre un `BrandingTokens`: nunca
falla ni devuelve `null`. Para cada token el orden de prioridad es

1. el valor explícito del `Branding` de la cuenta,
2. el valor por defecto del tema asignado a la cuenta,
3. un tono derivado calculado con `HexColor::shade()` / `HexColor::tint()` (mismos pesos que
   `shade-color` / `tint-color` de Bootstrap).

Los controladores públicos declaran la cuenta activa con `$this->brandFor($accountId)`
(`BrandedPublicControllerTrait`). A partir de ahí:

- **Web.** `templates/shared/layout/_branding.html.twig` emite un `<style>` con los tokens
  acotado a `body.branded-page`. La capa de mapeo vive en `assets/styles/_branding.scss`, cuyas
  reglas están **todas** dentro de `body.branded-page`, de modo que el backoffice nunca puede
  verse afectado. `tests/Unit/Shared/Infrastructure/Styles/BrandingStylesheetTest` falla si
  alguna regla se sale de ese bloque o menciona `:root`.
- **Email.** `inline_css` no entiende `var()`, así que `branding_email_css()` genera una hoja con
  los hexes ya resueltos. Los emails se renderizan fuera del ciclo de request, así que no pueden
  usar `BrandingContext`: quien los envía inyecta `BrandingResolver`, resuelve los tokens desde el
  `AccountId` de su agregado y los pasa como variable `brandingTokens`. Un email sin
  `brandingTokens` cae a la paleta de la plataforma.
- **PDF.** `PdfBranding::fromTokens()` convierte los tokens a RGB y elige la familia tipográfica.
  Una cuenta sin branding recibe `PdfBranding::bioscore()`, es decir, el certificado sale
  exactamente igual que antes.

**El branding queda congelado en los PDF ya emitidos.** Cambiar los colores no regenera los
certificados emitidos; es el comportamiento correcto para un documento emitido, pero conviene
decírselo al cliente.

## Cómo crear un tema nuevo

Tomando `aurora` como ejemplo (`src/Shared/Infrastructure/Theme/AuroraTheme.php`):

### 1. Crear la clase del tema

```php
// src/Shared/Infrastructure/Theme/AuroraTheme.php
final class AuroraTheme implements Theme
{
    public const string SLUG = 'aurora';

    public function slug(): string { return self::SLUG; }
    public function name(): string { return 'Aurora'; }
    public function defaults(): ThemeDefaults { /* los 10 colores + tipografías */ }
    public function stylesheet(): string { return 'styles/themes/aurora.css'; }
}
```

### 2. Registrarlo

No hay nada que hacer: `_instanceof` en
`src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` etiqueta con `app.theme`
toda clase que implemente `Theme`, y `TaggedThemeRegistry` las recoge. Un slug desconocido cae al
tema por defecto y deja un warning en el log, así que retirar un tema nunca rompe una cuenta.

### 3. Crear las plantillas que quieras cambiar

En `templates/themes/<slug>/`, **replicando la ruta de la plantilla original sin la `@`**:

```
@shared/layout/public-message.html.twig
  → templates/themes/aurora/shared/layout/public-message.html.twig
```

Un tema no está obligado a sobrescribir nada. Las plantillas públicas usan
`{% extends theme_template('@...') %}` y `{% include theme_template('@...') %}`, que devuelven el
array `[ruta en el tema, ruta original]`: si el tema no tiene la plantilla, se usa la del núcleo.
Añadir plantillas nuevas al núcleo nunca rompe un tema existente.

### 4. Crear la hoja de estilos

`assets/styles/themes/<slug>.scss`, añadida a `root_sass` en
`config/packages/symfonycasts_sass.yaml`. **Todas sus reglas deben estar dentro de
`body.branded-page`**: el test de aislamiento cubre también `assets/styles/themes/*.scss`.

Orden de carga en la página pública: `app.css` → hoja del tema → `<style>` de tokens de la
cuenta. Así el branding de la cuenta gana siempre sobre el tema.

### 5. (Opcional) Un certificado propio

Implementa `ContributionCertificatePdfGenerator` y etiqueta el servicio con
`app.theme.certificate_generator` indexado por el slug:

```yaml
App\Balance\Infrastructure\Tcpdf\AuroraContributionCertificatePdfGenerator:
    tags:
        - { name: 'app.theme.certificate_generator', theme: 'aurora' }
```

`ThemeAwareContributionCertificatePdfGenerator` lo elegirá cuando el branding de la cuenta use ese
tema, y le pasará el `PdfBranding` ya resuelto: el generador hereda gratis los colores, la
tipografía y el logotipo de la cuenta.

## Fuera de alcance

Dominio propio por cuenta (las URLs públicas siguen siendo las de la plataforma y la cuenta se
resuelve desde el token o la entidad), backoffice y área privada, y los informes PDF de
DataExchange y CarbonFootprint: todos mantienen la marca de la plataforma.
