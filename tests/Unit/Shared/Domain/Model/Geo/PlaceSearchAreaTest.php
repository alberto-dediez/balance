<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\PlaceSearchArea;
use PHPUnit\Framework\TestCase;

class PlaceSearchAreaTest extends TestCase
{
    public function testAdmitsUpToFifteenRegionCodes(): void
    {
        $area = new PlaceSearchArea(array_map(static fn(int $i): string => 'c' . $i, range(1, 15)));

        $this->assertCount(15, $area->regionCodes);
    }

    public function testRejectsMoreThanFifteenRegionCodes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlaceSearchArea(array_map(static fn(int $i): string => 'c' . $i, range(1, 16)));
    }
}
