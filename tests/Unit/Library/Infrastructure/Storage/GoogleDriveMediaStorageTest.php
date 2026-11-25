<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Storage\GoogleDriveMediaStorage;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveClient;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GoogleDriveMediaStorageTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private const string FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    private const string SESSION_URL = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session';

    /**
     * @var list<array{method: string, path: string, query: array<array-key, mixed>, headers: array<string, string>, body: string}>
     */
    private array $requests = [];

    /**
     * @var list<MockResponse>
     */
    private array $responses = [];

    protected function setUp(): void
    {
        $this->requests = [];
        $this->responses = [];
    }

    public function testStore(): void
    {
        $this->responses[] = $this->found();
        $this->responses[] = $this->session();
        $this->responses[] = $this->json(['id' => 'file-1']);

        $mediaId = MediaId::generate();
        $contents = (string) base64_decode(self::PNG);

        $path = $this->createSUT()->store($mediaId, null, 'png', MediaStorageMother::localFile($contents));

        $this->assertSame(sprintf('shared/%s.png', (string) $mediaId), $path);
        $this->assertCount(3, $this->requests);
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame(sprintf("name = 'shared/%s.png' and 'folder-1' in parents and trashed = false", (string) $mediaId), $this->requests[0]['query']['q']);
        $this->assertSame('POST', $this->requests[1]['method']);
        $this->assertSame(['name' => $path, 'parents' => ['folder-1']], json_decode($this->requests[1]['body'], true));
        $this->assertSame('PUT', $this->requests[2]['method']);
        $this->assertSame('image/png', $this->requests[2]['headers']['content-type']);
        $this->assertSame($contents, $this->requests[2]['body']);
    }

    public function testWriteOverAnExistingPathUpdatesTheFile(): void
    {
        $this->responses[] = $this->found('file-1');
        $this->responses[] = $this->session();
        $this->responses[] = $this->json(['id' => 'file-1']);

        $this->createSUT()->write('shared/file.txt', MediaStorageMother::localFile('hola'));

        $this->assertSame('PATCH', $this->requests[1]['method']);
        $this->assertSame('https://www.googleapis.com/upload/drive/v3/files/file-1', $this->requests[1]['path']);
        $this->assertNotContains('POST', array_column($this->requests, 'method'));
    }

    public function testRead(): void
    {
        $this->responses[] = $this->found('file-1', 'file-2');
        $this->responses[] = new MockResponse('hola', ['http_code' => 200]);

        $stream = $this->createSUT()->read('shared/file.txt');

        $this->assertSame('stream', get_resource_type($stream));
        $this->assertSame('hola', stream_get_contents($stream));
        $this->assertSame(self::FILES_URL . '/file-1', $this->requests[1]['path']);
        $this->assertSame('media', $this->requests[1]['query']['alt']);

        fclose($stream);
    }

    public function testReadInterruptedMidBody(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = $this->found('file-1');
        $this->responses[] = new MockResponse(['ho', new TransportException('Connection reset by peer')], ['http_code' => 200]);

        $this->createSUT()->read('shared/file.txt');
    }

    public function testReadNotExistingPath(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = $this->found();

        $this->createSUT()->read('shared/file.txt');
    }

    public function testReadWithARememberedIdThatNoLongerExistsSearchesAgain(): void
    {
        $this->responses[] = $this->found('file-1');
        $this->responses[] = new MockResponse('', ['http_code' => 404]);
        $this->responses[] = $this->found('file-2');
        $this->responses[] = new MockResponse('hola', ['http_code' => 200]);

        $sut = $this->createSUT();

        $this->assertTrue($sut->exists('shared/file.txt'));

        $stream = $sut->read('shared/file.txt');

        $this->assertSame('hola', stream_get_contents($stream));
        $this->assertSame(self::FILES_URL . '/file-1', $this->requests[1]['path']);
        $this->assertSame(self::FILES_URL, $this->requests[2]['path']);
        $this->assertSame(self::FILES_URL . '/file-2', $this->requests[3]['path']);

        fclose($stream);
    }

    public function testExists(): void
    {
        $this->responses[] = $this->found('file-1');

        $this->assertTrue($this->createSUT()->exists('shared/file.txt'));
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame(self::FILES_URL, $this->requests[0]['path']);
    }

    public function testNotExists(): void
    {
        $this->responses[] = $this->found();

        $this->assertFalse($this->createSUT()->exists('shared/file.txt'));
    }

    public function testExistsFailure(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 403]);

        $this->createSUT()->exists('shared/file.txt');
    }

    public function testExistsNetworkFailure(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = new MockResponse('', ['error' => 'Connection refused']);

        $this->createSUT()->exists('shared/file.txt');
    }

    public function testExistsWithAnIncompleteSearch(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = $this->json(['incompleteSearch' => true, 'files' => []]);

        $this->createSUT()->exists('shared/file.txt');
    }

    public function testExistsWithAnEmptyPath(): void
    {
        $this->assertFalse($this->createSUT()->exists(''));
        $this->assertCount(0, $this->requests);
    }

    public function testDeleteTrashesEveryMatch(): void
    {
        $this->responses[] = $this->found('file-1', 'file-2');
        $this->responses[] = $this->json(['id' => 'file-1']);
        $this->responses[] = $this->json(['id' => 'file-2']);

        $this->createSUT()->delete('shared/file.txt');

        $this->assertCount(3, $this->requests);
        $this->assertSame(['GET', 'PATCH', 'PATCH'], array_column($this->requests, 'method'));
        $this->assertSame(self::FILES_URL . '/file-1', $this->requests[1]['path']);
        $this->assertSame(self::FILES_URL . '/file-2', $this->requests[2]['path']);
        $this->assertSame(['trashed' => true], json_decode($this->requests[1]['body'], true));
    }

    public function testDeleteWithoutMatches(): void
    {
        $this->responses[] = $this->found();

        $this->createSUT()->delete('shared/file.txt');

        $this->assertCount(1, $this->requests);
    }

    public function testDeleteIgnoresAFileAlreadyGone(): void
    {
        $this->responses[] = $this->found('file-1', 'file-2');
        $this->responses[] = new MockResponse('', ['http_code' => 404]);
        $this->responses[] = $this->json(['id' => 'file-2']);

        $this->createSUT()->delete('shared/file.txt');

        $this->assertCount(3, $this->requests);
    }

    public function testDeleteFailure(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = $this->found('file-1');
        $this->responses[] = new MockResponse('', ['http_code' => 403]);

        $this->createSUT()->delete('shared/file.txt');
    }

    public function testDeleteWithAnEmptyPath(): void
    {
        $this->createSUT()->delete('');

        $this->assertCount(0, $this->requests);
    }

    public function testReadAfterDeleteSearchesAgain(): void
    {
        $this->responses[] = $this->found('file-1');
        $this->responses[] = $this->found('file-1');
        $this->responses[] = $this->json(['id' => 'file-1']);
        $this->responses[] = $this->found();

        $sut = $this->createSUT();

        $this->assertTrue($sut->exists('shared/file.txt'));

        $sut->delete('shared/file.txt');

        try {
            $sut->read('shared/file.txt');
            $this->fail('A NotStoredMediaException was expected');
        } catch (NotStoredMediaException) {
        }

        $this->assertCount(4, $this->requests);
        $this->assertSame(self::FILES_URL, $this->requests[3]['path']);
        $this->assertArrayHasKey('q', $this->requests[3]['query']);
    }

    public function testWriteWithAMissingLocalFile(): void
    {
        $this->expectException(NotStoredMediaException::class);

        try {
            $this->createSUT()->write('shared/file.txt', sys_get_temp_dir() . '/google-drive-missing-' . bin2hex(random_bytes(4)));
        } finally {
            $this->assertCount(0, $this->requests);
        }
    }

    public function testWriteFailure(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = $this->found();
        $this->responses[] = new MockResponse('', ['http_code' => 403]);

        $this->createSUT()->write('shared/file.txt', MediaStorageMother::localFile());
    }

    public function testEmptyFolder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GOOGLE_DRIVE_*');

        $this->createSUT('')->exists('shared/file.txt');
    }

    private function createSUT(string $folderId = 'folder-1'): GoogleDriveMediaStorage
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $query = [];
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            $this->requests[] = [
                'method' => $method,
                'path' => strtok($url, '?') ?: $url,
                'query' => $query,
                'headers' => $this->headers($options['headers'] ?? []),
                'body' => $this->body($options['body'] ?? ''),
            ];

            $response = array_shift($this->responses);

            $this->assertInstanceOf(MockResponse::class, $response);

            return $response;
        });

        $client = new GoogleDriveClient($httpClient, GoogleDriveMother::tokenProviderWithCachedToken($httpClient));

        return new GoogleDriveMediaStorage($client, $folderId);
    }

    private function found(string ...$ids): MockResponse
    {
        return $this->json(['files' => array_map(static fn(string $id): array => ['id' => $id], $ids)]);
    }

    private function session(): MockResponse
    {
        return new MockResponse('', ['http_code' => 200, 'response_headers' => ['Location' => self::SESSION_URL]]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(array $data): MockResponse
    {
        return new MockResponse((string) json_encode($data), ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/json']]);
    }

    /**
     * @param array<array-key, mixed> $headers
     *
     * @return array<string, string>
     */
    private function headers(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $header) {
            if (!is_string($header) || !str_contains($header, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $header, 2);

            $normalized[strtolower(trim($name))] = trim($value);
        }

        return $normalized;
    }

    private function body(mixed $body): string
    {
        if (is_string($body)) {
            return $body;
        }

        if (is_resource($body)) {
            return (string) stream_get_contents($body);
        }

        if ($body instanceof \Closure) {
            $contents = '';

            while ('' !== $chunk = (string) $body(16372)) {
                $contents .= $chunk;
            }

            return $contents;
        }

        return '';
    }
}
