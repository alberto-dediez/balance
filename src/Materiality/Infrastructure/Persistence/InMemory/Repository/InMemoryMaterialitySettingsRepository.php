<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\InMemory\Repository;

use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMaterialitySettingsRepository implements MaterialitySettingsRepository
{
    /** @param array<string, MaterialitySettings> $materialitySettings */
    public function __construct(
        private array $materialitySettings = [],
    ) {}

    public function add(MaterialitySettings $materialitySettings): void
    {
        $this->materialitySettings[(string) $materialitySettings->id()] = $materialitySettings;
    }

    public function findCurrent(): ?MaterialitySettings
    {
        $first = reset($this->materialitySettings);

        return false === $first ? null : $first;
    }
}
