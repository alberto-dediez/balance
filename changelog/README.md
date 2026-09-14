# Changelog

Cada fichero de `entries/` es una entrada de la sección "Novedades" de la plataforma (solo visible
para administradores). El nombre del fichero debe seguir el patrón `YYYY-MM-DD-<slug>.md`; el
`<slug>` es la identidad de la entrada y debe casar con `^[a-z0-9]+(?:-[a-z0-9]+)*$`.

## Formato

```markdown
---
title: Firma electrónica de documentos
date: 2026-09-14
version: 1.8.0
category: feature
summary: Envío de documentos PDF para firma con verificación por código de un solo uso.
plan: 2026-08-20-signature-request
manual: signature-request.pdf
techManual: signature-request.tech.md
published: true
---

Texto en Markdown…
```

- `title`, `date`, `category` y `summary` son obligatorios. `category` debe ser una de: `feature`,
  `improvement`, `fix`, `security`.
- `manual` y `techManual` son opcionales y deben nombrar un fichero dentro de `manuals/`, sin rutas.
- `published: false` oculta la entrada de la plataforma sin borrarla.

Un fichero con el frontmatter inválido **no rompe la sección**: simplemente no aparece en el
listado ni en el detalle. Revisa los logs de la aplicación si una entrada "desaparece".

Los manuales viven en `manuals/`: normalmente los publica la skill `feature-changelog` a partir de
los manuales generados por `feature-manual` para un plan cerrado.
