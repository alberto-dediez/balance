<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Geo;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Geo\CachedPlaceFinder;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CachedPlaceFinderTest extends TestCase
{
    public function testResolvesEachReferenceOnlyOnce(): void
    {
        $inner = new InMemoryPlaceFinder();
        $inner->add('madrid', 'Madrid, España', 40.4168, -3.7038);

        $sut = new CachedPlaceFinder($inner, new ArrayAdapter());

        $first = $sut->resolve('madrid', Locale::Spanish);
        $second = $sut->resolve('madrid', Locale::Spanish);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame('Madrid, España', $second->label);
        $this->assertSame(1, $inner->resolveCalls());
    }
}
