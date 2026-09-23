<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Shared\Domain\Model\Locale\Locale;

interface AnnouncementManualStorage
{
    public function store(AnnouncementId $id, Locale $locale, string $localPath): string;

    /** @return resource */
    public function read(string $path): mixed;

    public function exists(string $path): bool;

    public function size(string $path): int;

    public function delete(string $path): void;
}
