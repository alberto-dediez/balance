<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Storage\OvhMediaStorage;
use App\Shared\Infrastructure\Storage\Ovh\OvhS3ClientFactory;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OvhMediaStorageTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    /**
     * @var list<array{method: string, url: string, headers: array<string, string>, body: string}>
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
        $this->responses[] = new MockResponse('', ['http_code' => 200]);

        $mediaId = MediaId::generate();
        $contents = (string) base64_decode(self::PNG);

        $path = $this->createSUT()->store($mediaId, null, 'png', MediaStorageMother::localFile($contents));

        $this->assertSame(sprintf('shared/%s.png', (string) $mediaId), $path);
        $this->assertCount(1, $this->requests);
        $this->assertSame('PUT', $this->requests[0]['method']);
        $this->assertSame(sprintf('http://s3.test/bucket/library/shared/%s.png', (string) $mediaId), $this->requests[0]['url']);
        $this->assertSame('image/png', $this->requests[0]['headers']['content-type']);
        $this->assertSame($contents, $this->requests[0]['body']);
    }

    public function testRead(): void
    {
        $this->responses[] = new MockResponse('hola', ['http_code' => 200]);

        $stream = $this->createSUT()->read('shared/file.txt');

        $this->assertSame('stream', get_resource_type($stream));
        $this->assertSame('hola', stream_get_contents($stream));
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('http://s3.test/bucket/library/shared/file.txt', $this->requests[0]['url']);

        fclose($stream);
    }

    public function testExists(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 200]);

        $this->assertTrue($this->createSUT()->exists('shared/file.txt'));
        $this->assertSame('HEAD', $this->requests[0]['method']);
        $this->assertSame('http://s3.test/bucket/library/shared/file.txt', $this->requests[0]['url']);
    }

    public function testNotExists(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 404]);

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

    public function testDelete(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 204]);

        $this->createSUT()->delete('shared/file.txt');

        $this->assertSame('DELETE', $this->requests[0]['method']);
        $this->assertSame('http://s3.test/bucket/library/shared/file.txt', $this->requests[0]['url']);
    }

    public function testWriteFailure(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 403]);

        $this->createSUT()->write('shared/file.txt', MediaStorageMother::localFile());
    }

    public function testReadFailure(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $this->responses[] = new MockResponse('', ['http_code' => 404]);

        $this->createSUT()->read('shared/file.txt');
    }

    public function testEmptyPrefix(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 200]);

        $mediaId = MediaId::generate();

        $this->createSUT('')->store($mediaId, null, 'png', MediaStorageMother::localFile());

        $this->assertSame(sprintf('http://s3.test/bucket/shared/%s.png', (string) $mediaId), $this->requests[0]['url']);
    }

    public function testPrefixWithTrailingSlash(): void
    {
        $this->responses[] = new MockResponse('', ['http_code' => 200]);

        $mediaId = MediaId::generate();

        $this->createSUT('library/')->store($mediaId, null, 'png', MediaStorageMother::localFile());

        $this->assertSame(sprintf('http://s3.test/bucket/library/shared/%s.png', (string) $mediaId), $this->requests[0]['url']);
    }

    private function createSUT(string $prefix = 'library'): OvhMediaStorage
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'headers' => $this->headers($options['headers'] ?? []),
                'body' => $this->body($options['body'] ?? ''),
            ];

            $response = array_shift($this->responses);

            $this->assertInstanceOf(MockResponse::class, $response);

            return $response;
        });

        $client = (new OvhS3ClientFactory('http://s3.test', 'gra', 'access', 'secret', $httpClient))->create();

        return new OvhMediaStorage($client, 'bucket', $prefix);
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
