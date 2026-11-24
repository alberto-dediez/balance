<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Storage\GoogleDrive;

use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveClient;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveException;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveFileNotFoundException;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GoogleDriveClientTest extends TestCase
{
    private const string SESSION_URL = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session';

    /**
     * @var list<array{method: string, url: string, path: string, query: array<array-key, mixed>, headers: array<string, string>, body: string}>
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

    public function testFindFileIds(): void
    {
        $this->responses[] = $this->json(['files' => [['id' => 'file-1'], ['id' => 'file-2']]]);

        $ids = $this->createSUT()->findFileIds('folder-1', 'shared/a.pdf');

        $this->assertSame(['file-1', 'file-2'], $ids);
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('https://www.googleapis.com/drive/v3/files', $this->requests[0]['path']);
        $this->assertSame("name = 'shared/a.pdf' and 'folder-1' in parents and trashed = false", $this->requests[0]['query']['q']);
        $this->assertSame('incompleteSearch,files(id)', $this->requests[0]['query']['fields']);
        $this->assertSame('createdTime', $this->requests[0]['query']['orderBy']);
        $this->assertSame('allDrives', $this->requests[0]['query']['corpora']);
        $this->assertSame('true', $this->requests[0]['query']['supportsAllDrives']);
        $this->assertSame('true', $this->requests[0]['query']['includeItemsFromAllDrives']);
        $this->assertAllRequestsAreAuthorized();
    }

    public function testFindFileIdsEscapesTheName(): void
    {
        $this->responses[] = $this->json(['files' => []]);

        $this->createSUT()->findFileIds('folder-1', "shared/o'brien\\a.pdf");

        $this->assertSame("name = 'shared/o\\'brien\\\\a.pdf' and 'folder-1' in parents and trashed = false", $this->requests[0]['query']['q']);
    }

    public function testFindFileIdsWithoutResults(): void
    {
        $this->responses[] = $this->json(['files' => []]);

        $this->assertSame([], $this->createSUT()->findFileIds('folder-1', 'shared/a.pdf'));
    }

    public function testFindFileIdsWithAnIncompleteSearch(): void
    {
        $this->expectException(GoogleDriveException::class);
        $this->expectExceptionMessage('incomplete search');

        $this->responses[] = $this->json(['incompleteSearch' => true, 'files' => []]);

        $this->createSUT()->findFileIds('folder-1', 'shared/a.pdf');
    }

    public function testUploadNewFile(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 200, 'response_headers' => ['Location' => self::SESSION_URL]]);
        $this->responses[] = $this->json(['id' => 'file-1']);

        $id = $this->createSUT()->upload('folder-1', 'shared/a.txt', MediaStorageMother::localFile('hola'), 'text/plain');

        $this->assertSame('file-1', $id);
        $this->assertCount(2, $this->requests);
        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertSame('https://www.googleapis.com/upload/drive/v3/files', $this->requests[0]['path']);
        $this->assertSame('resumable', $this->requests[0]['query']['uploadType']);
        $this->assertSame('text/plain', $this->requests[0]['headers']['x-upload-content-type']);
        $this->assertSame('4', $this->requests[0]['headers']['x-upload-content-length']);
        $this->assertSame(['name' => 'shared/a.txt', 'parents' => ['folder-1']], json_decode($this->requests[0]['body'], true));
        $this->assertSame('PUT', $this->requests[1]['method']);
        $this->assertStringStartsWith('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session', $this->requests[1]['url']);
        $this->assertSame('text/plain', $this->requests[1]['headers']['content-type']);
        $this->assertSame('hola', $this->requests[1]['body']);
        $this->assertAllRequestsAreAuthorized();
    }

    public function testUploadExistingFile(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 200, 'response_headers' => ['Location' => self::SESSION_URL]]);
        $this->responses[] = $this->json(['id' => 'file-1']);

        $id = $this->createSUT()->upload('folder-1', 'shared/a.txt', MediaStorageMother::localFile('hola'), 'text/plain', 'file-1');

        $this->assertSame('file-1', $id);
        $this->assertSame('PATCH', $this->requests[0]['method']);
        $this->assertSame('https://www.googleapis.com/upload/drive/v3/files/file-1', $this->requests[0]['path']);
        $this->assertSame('resumable', $this->requests[0]['query']['uploadType']);
        $this->assertSame('PUT', $this->requests[1]['method']);
    }

    public function testUploadExistingFileNotFound(): void
    {
        $this->expectException(GoogleDriveFileNotFoundException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 404]);

        $this->createSUT()->upload('folder-1', 'shared/a.txt', MediaStorageMother::localFile(), 'text/plain', 'file-1');
    }

    public function testUploadWithoutSessionLocation(): void
    {
        $this->expectException(GoogleDriveException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 200]);

        $this->createSUT()->upload('folder-1', 'shared/a.txt', MediaStorageMother::localFile(), 'text/plain');
    }

    public function testDownload(): void
    {
        $this->responses[] = new MockResponse('hola', ['http_code' => 200]);

        $stream = $this->createSUT()->download('file-1');

        $this->assertSame('stream', get_resource_type($stream));
        $this->assertSame('hola', stream_get_contents($stream));
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('https://www.googleapis.com/drive/v3/files/file-1', $this->requests[0]['path']);
        $this->assertSame('media', $this->requests[0]['query']['alt']);
        $this->assertSame('true', $this->requests[0]['query']['supportsAllDrives']);
        $this->assertAllRequestsAreAuthorized();

        fclose($stream);
    }

    public function testDownloadNotFound(): void
    {
        $this->expectException(GoogleDriveFileNotFoundException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 404]);

        $this->createSUT()->download('file-1');
    }

    public function testDownloadInterruptedMidBody(): void
    {
        $this->expectException(GoogleDriveException::class);

        $this->responses[] = new MockResponse(['ho', new TransportException('Connection reset by peer')], ['http_code' => 200]);

        $this->createSUT()->download('file-1');
    }

    public function testTrash(): void
    {
        $this->responses[] = $this->json(['id' => 'file-1']);

        $this->createSUT()->trash('file-1');

        $this->assertCount(1, $this->requests);
        $this->assertSame('PATCH', $this->requests[0]['method']);
        $this->assertSame('https://www.googleapis.com/drive/v3/files/file-1', $this->requests[0]['path']);
        $this->assertSame('true', $this->requests[0]['query']['supportsAllDrives']);
        $this->assertStringStartsWith('application/json', $this->requests[0]['headers']['content-type']);
        $this->assertSame(['trashed' => true], json_decode($this->requests[0]['body'], true));
        $this->assertNotContains('DELETE', array_column($this->requests, 'method'));
        $this->assertAllRequestsAreAuthorized();
    }

    public function testTrashNotFound(): void
    {
        $this->expectException(GoogleDriveFileNotFoundException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 404]);

        $this->createSUT()->trash('file-1');
    }

    public function testForbidden(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 403]);

        try {
            $this->createSUT()->findFileIds('folder-1', 'shared/a.pdf');
            $this->fail('A GoogleDriveException was expected');
        } catch (GoogleDriveException $exception) {
            $this->assertNotInstanceOf(GoogleDriveFileNotFoundException::class, $exception);
            $this->assertStringContainsString('GET', $exception->getMessage());
            $this->assertStringContainsString('403', $exception->getMessage());
            $this->assertStringNotContainsString(GoogleDriveMother::TOKEN, $exception->getMessage());
        }
    }

    public function testNetworkFailure(): void
    {
        $this->expectException(GoogleDriveException::class);

        $this->responses[] = new MockResponse('', ['error' => 'Connection refused']);

        $this->createSUT()->download('file-1');
    }

    private function createSUT(): GoogleDriveClient
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $query = [];
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'path' => strtok($url, '?') ?: $url,
                'query' => $query,
                'headers' => $this->headers($options['headers'] ?? []),
                'body' => $this->body($options['body'] ?? ''),
            ];

            $response = array_shift($this->responses);

            $this->assertInstanceOf(MockResponse::class, $response);

            return $response;
        });

        return new GoogleDriveClient($httpClient, GoogleDriveMother::tokenProviderWithCachedToken($httpClient));
    }

    private function assertAllRequestsAreAuthorized(): void
    {
        foreach ($this->requests as $request) {
            $this->assertSame('Bearer ' . GoogleDriveMother::TOKEN, $request['headers']['authorization'] ?? null);
        }
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
