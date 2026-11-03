<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySettings;

interface MaterialitySettingsRepository
{
    public function add(MaterialitySettings $materialitySettings): void;

    public function findCurrent(): ?MaterialitySettings;
}
