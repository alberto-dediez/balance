<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\RedirectRule;
use App\Balance\Infrastructure\LegacyImport\RedirectRulesWriter;
use PHPUnit\Framework\TestCase;

class RedirectRulesWriterTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/redirects-' . bin2hex(random_bytes(4)) . '.txt';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testWritesTheRulesInTheExactFormat(): void
    {
        (new RedirectRulesWriter())->write(
            $this->path,
            'https://balance.bioscore.info',
            [
                new RedirectRule('sao', 'ota', 'https://events.example.com/es/carbon-footprint/trip/token-sao', 'calculadora de viajes de la cuenta account-sao'),
                new RedirectRule('coolrooms', 'hotel', null, 'sin propiedad en la cuenta account-coolrooms: sustituir el marcador por la URL de la calculadora de la propiedad'),
                new RedirectRule('darkside', 'hotel', null, 'omitida', skipped: true),
            ],
        );

        $expected = <<<'TXT'
            # Redirecciones generadas por balance:legacy:import para https://balance.bioscore.info
            # Copiar dentro del bloque <IfModule mod_rewrite.c> del .htaccess del servidor que atiende balance.bioscore.info, antes de las reglas del front controller

            # sao (ota) -> calculadora de viajes de la cuenta account-sao
            RewriteCond %{HTTP_HOST} ^balance\.bioscore\.info$ [NC]
            RewriteCond %{QUERY_STRING} (?:^|&)organization=sao(?:&|$) [NC]
            RewriteRule ^emissions/?$ https://events.example.com/es/carbon-footprint/trip/token-sao? [R=301,L]

            # coolrooms (hotel) -> sin propiedad en la cuenta account-coolrooms: sustituir el marcador por la URL de la calculadora de la propiedad
            RewriteCond %{HTTP_HOST} ^balance\.bioscore\.info$ [NC]
            RewriteCond %{QUERY_STRING} (?:^|&)organization=coolrooms(?:&|$) [NC]
            RewriteRule ^emissions/?$ {{PROPERTY_TRIP_URL:coolrooms}}? [R=301,L]

            # darkside: omitida

            TXT;

        $this->assertSame($expected, file_get_contents($this->path));
    }
}
