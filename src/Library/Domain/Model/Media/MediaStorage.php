<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Security\Domain\Model\Account\AccountId;

interface MediaStorage
{
    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string;

    public function write(string $path, string $localPath): void;

    /**
     * @return resource
     */
    public function read(string $path): mixed;

    public function delete(string $path): void;

    public function exists(string $path): bool;
}
