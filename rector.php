<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true, // Vital para Niveles 0-6: Añade tipos de retorno, parámetros y propiedades
        codeQuality: true,         // Vital para Nivel 4: Elimina código inalcanzable y variables no usadas
        typeDeclarations: true,      // Vital general: Mejora la estructura lógica para que PHPStan no se confunda
        privatization: true,      // Vital para Nivel 8: Reduce la anidación (if/else), facilitando que PHPStan infiera cuándo algo ya no es null
        earlyReturn: true,    // Convierte métodos/propiedades a private si no se usan fuera. Ayuda a inferir tipos más estrictos.
        strictBooleans: true    // Vital para altos niveles: Cambia comprobaciones sueltas (if $var) por estrictas (if $var !== null)
    )
    // Limpia los "use" en la cabecera del archivo para que los PHPDocs generados sean limpios (ej: @var Command en vez de @var \App\...\Command)
    ->withImportNames(importNames: true, importDocBlockNames: true, importShortClasses: false, removeUnusedImports: true);
;
