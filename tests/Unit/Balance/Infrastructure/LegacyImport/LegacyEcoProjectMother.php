<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyEcoProject;

class LegacyEcoProjectMother
{
    public static function from(string $id, string $displayName): LegacyEcoProject
    {
        return new LegacyEcoProject(
            id: $id,
            displayName: $displayName,
            slug: strtolower(str_replace(' ', '-', $displayName)),
        );
    }
}
