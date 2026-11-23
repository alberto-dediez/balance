<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Infrastructure\Integration\IntegrationSettings;
use PHPUnit\Framework\TestCase;

class IntegrationSettingsTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function validPlatforms(): iterable
    {
        yield 'single word' => ['biohydra'];
        yield 'with hyphens' => ['dont-waste-food'];
        yield 'with digits' => ['platform2'];
    }

    /** @dataProvider validPlatforms */
    public function testAcceptsValidPlatform(string $platform): void
    {
        $settings = new IntegrationSettings(true, $platform, 5);

        $this->assertSame($platform, $settings->platform);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPlatforms(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Waste'];
        yield 'with spaces' => ['my platform'];
        yield 'with dots' => ['integration.waste'];
        yield 'with underscore' => ['data_platform'];
    }

    /** @dataProvider invalidPlatforms */
    public function testRejectsInvalidPlatform(string $platform): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IntegrationSettings(true, $platform, 5);
    }
}
