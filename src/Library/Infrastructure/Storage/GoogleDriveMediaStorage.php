<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveClient;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveException;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveFileNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class GoogleDriveMediaStorage implements MediaStorage
{
    /**
     * @var array<string, string>
     */
    private array $fileIds = [];

    public function __construct(
        private readonly GoogleDriveClient $client,
        private readonly string $folderId,
    ) {}

    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string
    {
        $path = MediaPath::from($mediaId, $accountId, $extension);

        $this->write($path, $localPath);

        return $path;
    }

    public function write(string $path, string $localPath): void
    {
        if (!is_file($localPath) || !is_readable($localPath)) {
            throw new NotStoredMediaException(sprintf('Unable to store media file at "%s"', $path));
        }

        $ids = $this->find($path, 'store');

        try {
            $this->fileIds[$path] = $this->client->upload($this->folderId, $this->name($path), $localPath, $this->mimeType($localPath), $ids[0] ?? null);
        } catch (GoogleDriveException $exception) {
            unset($this->fileIds[$path]);

            throw new NotStoredMediaException(sprintf('Unable to store media file at "%s"', $path), 0, $exception);
        }
    }

    public function read(string $path): mixed
    {
        if (isset($this->fileIds[$path])) {
            try {
                return $this->client->download($this->fileIds[$path]);
            } catch (GoogleDriveFileNotFoundException) {
                unset($this->fileIds[$path]);
            } catch (GoogleDriveException $exception) {
                throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path), 0, $exception);
            }
        }

        $id = $this->find($path, 'read')[0] ?? null;

        if (null === $id) {
            throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path));
        }

        try {
            return $this->client->download($id);
        } catch (GoogleDriveException $exception) {
            throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path), 0, $exception);
        }
    }

    public function delete(string $path): void
    {
        if ('' === $path) {
            return;
        }

        $ids = $this->find($path, 'delete');

        unset($this->fileIds[$path]);

        foreach ($ids as $id) {
            try {
                $this->client->trash($id);
            } catch (GoogleDriveFileNotFoundException) {
            } catch (GoogleDriveException $exception) {
                throw new NotStoredMediaException(sprintf('Unable to delete media file "%s"', $path), 0, $exception);
            }
        }
    }

    public function exists(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        $ids = $this->find($path, 'check');

        if ([] === $ids) {
            unset($this->fileIds[$path]);

            return false;
        }

        $this->fileIds[$path] = $ids[0];

        return true;
    }

    /**
     * @return list<string>
     */
    private function find(string $path, string $operation): array
    {
        if ('' === $this->folderId) {
            throw new \InvalidArgumentException('Google Drive storage is enabled but GOOGLE_DRIVE_* variables are not configured');
        }

        try {
            return $this->client->findFileIds($this->folderId, $this->name($path));
        } catch (GoogleDriveException $exception) {
            $message = 'store' === $operation ? 'Unable to store media file at "%s"' : sprintf('Unable to %s media file "%%s"', $operation);

            throw new NotStoredMediaException(sprintf($message, $path), 0, $exception);
        }
    }

    private function name(string $path): string
    {
        return ltrim($path, '/');
    }

    private function mimeType(string $localPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mimeType = false === $finfo ? false : @finfo_file($finfo, $localPath);

        return is_string($mimeType) && '' !== $mimeType ? $mimeType : 'application/octet-stream';
    }
}
