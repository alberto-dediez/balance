<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Domain\Model\Account\AccountId;
use AsyncAws\Core\Exception\Exception;
use AsyncAws\Core\Waiter;
use AsyncAws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class OvhMediaStorage implements MediaStorage
{
    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly string $prefix,
    ) {}

    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string
    {
        $path = MediaPath::from($mediaId, $accountId, $extension);

        $this->write($path, $localPath);

        return $path;
    }

    public function write(string $path, string $localPath): void
    {
        $stream = @fopen($localPath, 'rb');

        if (false === $stream) {
            throw new NotStoredMediaException(sprintf('Unable to store media file at "%s"', $path));
        }

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
                'Body' => $stream,
                'ContentLength' => (int) filesize($localPath),
                'ContentType' => $this->mimeType($localPath),
            ])->resolve();
        } catch (Exception $exception) {
            throw new NotStoredMediaException(sprintf('Unable to store media file at "%s"', $path), 0, $exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function read(string $path): mixed
    {
        try {
            return $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
            ])->getBody()->getContentAsResource();
        } catch (Exception $exception) {
            throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path), 0, $exception);
        }
    }

    public function delete(string $path): void
    {
        if ('' === $path) {
            return;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
            ])->resolve();
        } catch (Exception $exception) {
            throw new NotStoredMediaException(sprintf('Unable to delete media file "%s"', $path), 0, $exception);
        }
    }

    public function exists(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        try {
            $state = $this->client->objectExists([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
            ])->getState();
        } catch (Exception $exception) {
            throw new NotStoredMediaException(sprintf('Unable to check media file "%s"', $path), 0, $exception);
        }

        if (Waiter::STATE_FAILURE === $state) {
            throw new NotStoredMediaException(sprintf('Unable to check media file "%s"', $path));
        }

        return Waiter::STATE_SUCCESS === $state;
    }

    private function key(string $path): string
    {
        $prefix = trim($this->prefix, '/');

        return '' === $prefix ? ltrim($path, '/') : $prefix . '/' . ltrim($path, '/');
    }

    private function mimeType(string $localPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mimeType = false === $finfo ? false : @finfo_file($finfo, $localPath);

        return is_string($mimeType) && '' !== $mimeType ? $mimeType : 'application/octet-stream';
    }
}
