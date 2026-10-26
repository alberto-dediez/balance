<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final class RedirectRulesWriter
{
    /** @param list<RedirectRule> $rules */
    public function write(string $path, string $legacyUrl, array $rules): void
    {
        $host = (string) parse_url($legacyUrl, PHP_URL_HOST);

        $lines = [
            sprintf('# Redirecciones generadas por balance:legacy:import para %s', $legacyUrl),
            sprintf('# Copiar dentro del bloque <IfModule mod_rewrite.c> del .htaccess del servidor que atiende %s, antes de las reglas del front controller', $host),
        ];

        foreach ($rules as $rule) {
            $lines[] = '';

            if ($rule->skipped) {
                $lines[] = sprintf('# %s: %s', $rule->slug, $rule->comment);

                continue;
            }

            $lines[] = sprintf('# %s (%s) -> %s', $rule->slug, $rule->type, $rule->comment);
            $lines[] = sprintf('RewriteCond %%{HTTP_HOST} ^%s$ [NC]', preg_quote($host));
            $lines[] = sprintf('RewriteCond %%{QUERY_STRING} (?:^|&)organization=%s(?:&|$) [NC]', $rule->slug);
            $lines[] = sprintf('RewriteRule ^emissions/?$ %s? [R=301,L]', $rule->targetUrl ?? sprintf('{{PROPERTY_TRIP_URL:%s}}', $rule->slug));
        }

        if (false === file_put_contents($path, implode("\n", $lines) . "\n")) {
            throw new \RuntimeException(sprintf('No se ha podido escribir el fichero "%s".', $path));
        }
    }
}
