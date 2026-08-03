<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Security\Domain\Model\Account\AccountId;

interface MediaStorage
{
    /**
     * Persists the contents located at $localPath and returns an opaque reference to them.
     */
    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string;

    /**
     * @return resource a readable stream of the stored contents
     */
    public function read(string $path): mixed;

    public function delete(string $path): void;

    public function exists(string $path): bool;
}
