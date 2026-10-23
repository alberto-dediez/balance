<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Geo;

use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
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

    public function testForwardsTheSearchAreaOfTheSuggestions(): void
    {
        $inner = new InMemoryPlaceFinder();
        $area = new PlaceSearchArea(regionCodes: ['es'], bounds: new GeoBounds(new GeoPoint(37.84, -1.53), new GeoPoint(40.79, 0.69)));

        (new CachedPlaceFinder($inner, new ArrayAdapter()))->suggest('Valencia', PlaceKind::City, Locale::Spanish, $area);

        $this->assertSame($area, $inner->lastArea());
    }

    public function testStoresThePlacesUnderTheVersionedKey(): void
    {
        $inner = new InMemoryPlaceFinder();
        $inner->add('madrid', 'Madrid, España', 40.4168, -3.7038);
        $cache = new ArrayAdapter();

        (new CachedPlaceFinder($inner, $cache))->resolve('madrid', Locale::Spanish);

        $this->assertTrue($cache->hasItem(sprintf('cf_geo_place_v2_%s_%s', Locale::Spanish->value, hash('xxh128', 'madrid'))));
        $this->assertFalse($cache->hasItem(sprintf('cf_geo_place_%s_%s', Locale::Spanish->value, hash('xxh128', 'madrid'))));
    }
}
