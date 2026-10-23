<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
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

    private bool $available = true;

    public function markUnavailable(): void
    {
        $this->available = false;
    }

    public function markAvailable(): void
    {
        $this->available = true;
    }

    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string
    {
        $relativePath = MediaPath::from($mediaId, $accountId, $extension);

        $this->write($relativePath, $localPath);

        return $relativePath;
    }

    public function write(string $path, string $localPath): void
    {
        $this->assertAvailable($path);

        $contents = is_file($localPath) ? file_get_contents($localPath) : '';

        $this->contents[$path] = false === $contents ? '' : $contents;
    }

    public function read(string $path): mixed
    {
        $this->assertAvailable($path);

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
        $this->assertAvailable($path);

        unset($this->contents[$path]);
    }

    public function exists(string $path): bool
    {
        $this->assertAvailable($path);

        return isset($this->contents[$path]);
    }

    private function assertAvailable(string $path): void
    {
        if (!$this->available) {
            throw new NotStoredMediaException(sprintf('Media storage unavailable for "%s"', $path));
        }
    }
}
