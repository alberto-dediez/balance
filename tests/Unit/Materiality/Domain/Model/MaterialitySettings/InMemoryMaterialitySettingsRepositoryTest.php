<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySettings;

use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySettingsRepository;
use PHPUnit\Framework\TestCase;

class InMemoryMaterialitySettingsRepositoryTest extends TestCase
{
    public function testFindCurrentWithoutSettingsReturnsNull(): void
    {
        $this->assertNull((new InMemoryMaterialitySettingsRepository())->findCurrent());
    }

    public function testFindCurrentReturnsTheStoredSettings(): void
    {
        $repository = new InMemoryMaterialitySettingsRepository();
        $materialitySettings = MaterialitySettingsMother::withThreshold(30);

        $repository->add($materialitySettings);
        $repository->add($materialitySettings);

        $this->assertSame($materialitySettings, $repository->findCurrent());
    }
}
