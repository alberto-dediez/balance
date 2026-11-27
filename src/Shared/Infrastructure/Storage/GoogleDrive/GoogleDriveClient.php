<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Storage\GoogleDrive;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GoogleDriveClient
{
    private const string FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    private const string UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';

    private const int PAGE_SIZE = 10;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly GoogleDriveAccessTokenProvider $tokens,
    ) {}

    /**
     * @return list<string>
     */
    public function findFileIds(string $folderId, string $name): array
    {
        $response = $this->request('GET', self::FILES_URL, [
            'query' => [
                'q' => sprintf("name = '%s' and '%s' in parents and trashed = false", self::escape($name), self::escape($folderId)),
                'fields' => 'incompleteSearch,files(id)',
                'orderBy' => 'createdTime',
                'pageSize' => self::PAGE_SIZE,
                'corpora' => 'allDrives',
                'includeItemsFromAllDrives' => 'true',
            ],
        ]);

        $data = $this->expect($response, 'GET', 'find', [200]);

        if (true === ($data['incompleteSearch'] ?? null)) {
            throw new GoogleDriveException('Google Drive GET find request returned an incomplete search');
        }

        $ids = [];

        foreach (is_array($data['files'] ?? null) ? $data['files'] : [] as $file) {
            if (is_array($file) && isset($file['id']) && is_string($file['id']) && '' !== $file['id']) {
                $ids[] = $file['id'];
            }
        }

        return $ids;
    }

    public function upload(string $folderId, string $name, string $localPath, string $mimeType, ?string $fileId = null): string
    {
        $size = (int) @filesize($localPath);
        $method = null === $fileId ? 'POST' : 'PATCH';

        $session = $this->request(
            $method,
            null === $fileId ? self::UPLOAD_URL : self::UPLOAD_URL . '/' . rawurlencode($fileId),
            [
                'query' => ['uploadType' => 'resumable'],
                'headers' => [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'X-Upload-Content-Type' => $mimeType,
                    'X-Upload-Content-Length' => (string) $size,
                ],
                'body' => null === $fileId ? (string) json_encode(['name' => $name, 'parents' => [$folderId]]) : '{}',
            ],
        );

        $this->expect($session, $method, 'upload', [200], null !== $fileId);

        $location = $this->header($session, 'location');

        if (null === $location) {
            throw new GoogleDriveException(sprintf('Google Drive %s upload request returned no upload location', $method));
        }

        $stream = @fopen($localPath, 'rb');

        if (false === $stream) {
            throw new GoogleDriveException(sprintf('Google Drive upload could not open the local file "%s"', $localPath));
        }

        try {
            $response = $this->request('PUT', $location, [
                'headers' => [
                    'Content-Type' => $mimeType,
                    'Content-Length' => (string) $size,
                ],
                'body' => $stream,
            ]);

            $data = $this->expect($response, 'PUT', 'upload', [200, 201]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $id = $data['id'] ?? null;

        if (!is_string($id) || '' === $id) {
            throw new GoogleDriveException('Google Drive PUT upload request returned no file id');
        }

        return $id;
    }

    /**
     * @return resource
     */
    public function download(string $fileId): mixed
    {
        $response = $this->request('GET', self::FILES_URL . '/' . rawurlencode($fileId), [
            'query' => ['alt' => 'media'],
        ]);

        $this->status($response, 'GET', 'download', [200], true);

        $stream = fopen('php://temp/maxmemory:2097152', 'w+b');

        if (false === $stream) {
            throw new GoogleDriveException('Google Drive GET download could not open a temporary stream');
        }

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($stream, $chunk->getContent());
            }
        } catch (ExceptionInterface $exception) {
            fclose($stream);

            throw new GoogleDriveException('Google Drive GET download request failed', 0, $exception);
        }

        rewind($stream);

        return $stream;
    }

    public function trash(string $fileId): void
    {
        $response = $this->request('PATCH', self::FILES_URL . '/' . rawurlencode($fileId), [
            'json' => ['trashed' => true],
        ]);

        $this->expect($response, 'PATCH', 'trash', [200], true);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function request(string $method, string $url, array $options): ResponseInterface
    {
        $token = $this->tokens->token();

        $query = is_array($options['query'] ?? null) ? $options['query'] : [];

        try {
            return $this->httpClient->request($method, $url, [
                ...$options,
                'auth_bearer' => $token,
                'query' => [...$query, 'supportsAllDrives' => 'true'],
            ]);
        } catch (ExceptionInterface $exception) {
            throw new GoogleDriveException(sprintf('Google Drive %s request failed', $method), 0, $exception);
        }
    }

    /**
     * @param list<int> $expected
     *
     * @return array<array-key, mixed>
     */
    private function expect(ResponseInterface $response, string $method, string $operation, array $expected, bool $notFoundIsMissingFile = false): array
    {
        $this->status($response, $method, $operation, $expected, $notFoundIsMissingFile);

        try {
            $content = $response->getContent(false);
        } catch (ExceptionInterface $exception) {
            throw new GoogleDriveException(sprintf('Google Drive %s %s request failed', $method, $operation), 0, $exception);
        }

        if ('' === $content) {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param list<int> $expected
     */
    private function status(ResponseInterface $response, string $method, string $operation, array $expected, bool $notFoundIsMissingFile): void
    {
        try {
            $status = $response->getStatusCode();
        } catch (ExceptionInterface $exception) {
            throw new GoogleDriveException(sprintf('Google Drive %s %s request failed', $method, $operation), 0, $exception);
        }

        if (404 === $status && $notFoundIsMissingFile) {
            throw new GoogleDriveFileNotFoundException(sprintf('Google Drive %s %s request failed with status 404', $method, $operation));
        }

        if (!in_array($status, $expected, true)) {
            throw new GoogleDriveException(sprintf('Google Drive %s %s request failed with status %d', $method, $operation, $status));
        }
    }

    private function header(ResponseInterface $response, string $name): ?string
    {
        try {
            $value = $response->getHeaders(false)[$name][0] ?? null;
        } catch (ExceptionInterface) {
            return null;
        }

        return is_string($value) && '' !== $value ? $value : null;
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
