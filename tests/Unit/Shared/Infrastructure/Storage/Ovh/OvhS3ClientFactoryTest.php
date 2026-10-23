<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Storage\Ovh;

use App\Shared\Infrastructure\Storage\Ovh\OvhS3ClientFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OvhS3ClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $requests = [];

        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$requests): MockResponse {
            $requests[] = [$method, $url];

            return new MockResponse('', ['http_code' => 200]);
        });

        $client = (new OvhS3ClientFactory('http://s3.test', 'gra', 'access', 'secret', $httpClient))->create();

        $this->assertTrue($client->objectExists(['Bucket' => 'bucket', 'Key' => 'key'])->isSuccess());
        $this->assertSame([['HEAD', 'http://s3.test/bucket/key']], $requests);
    }

    /**
     * @dataProvider incompleteConfigurations
     */
    public function testIncompleteConfiguration(string $endpoint, string $region, string $accessKey, string $secretKey): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OVH_S3_*');

        $httpClient = new MockHttpClient(function (): MockResponse {
            $this->fail('No request expected');
        });

        (new OvhS3ClientFactory($endpoint, $region, $accessKey, $secretKey, $httpClient))->create();
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function incompleteConfigurations(): array
    {
        return [
            'endpoint' => ['', 'gra', 'access', 'secret'],
            'region' => ['http://s3.test', '', 'access', 'secret'],
            'access key' => ['http://s3.test', 'gra', '', 'secret'],
            'secret key' => ['http://s3.test', 'gra', 'access', ''],
        ];
    }
}
