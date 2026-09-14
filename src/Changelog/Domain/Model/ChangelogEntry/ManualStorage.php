<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

interface ManualStorage
{
    public function exists(string $fileName): bool;

    /** @return resource a readable stream of the stored contents */
    public function read(string $fileName): mixed;

    public function readContents(string $fileName): string;

    public function size(string $fileName): int;
}
