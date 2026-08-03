<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMediaStorage implements MediaStorage
{
    /**
     * @var array<string,string>
     */
    private array $contents = [];

    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string
    {
        $scope = $accountId instanceof AccountId ? (string) $accountId : 'shared';

        $relativePath = sprintf('%s/%s%s', $scope, (string) $mediaId, '' === $extension ? '' : '.' . $extension);

        $contents = is_file($localPath) ? file_get_contents($localPath) : '';

        $this->contents[$relativePath] = false === $contents ? '' : $contents;

        return $relativePath;
    }

    public function read(string $path): mixed
    {
        if (!$this->exists($path)) {
            throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path));
        }

        $stream = fopen('php://memory', 'r+b');

        if (false === $stream) {
            throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path));
        }

        fwrite($stream, $this->contents[$path]);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        unset($this->contents[$path]);
    }

    public function exists(string $path): bool
    {
        return isset($this->contents[$path]);
    }
}
