<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Storage\Ovh;

use AsyncAws\S3\S3Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OvhS3ClientFactory
{
    public function __construct(
        private readonly string $endpoint,
        private readonly string $region,
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly ?HttpClientInterface $httpClient = null,
    ) {}

    public function create(): S3Client
    {
        if ('' === $this->endpoint || '' === $this->region || '' === $this->accessKey || '' === $this->secretKey) {
            throw new \InvalidArgumentException('OVH S3 storage is enabled but OVH_S3_* variables are not configured');
        }

        return new S3Client(
            [
                'endpoint' => $this->endpoint,
                'region' => $this->region,
                'accessKeyId' => $this->accessKey,
                'accessKeySecret' => $this->secretKey,
                'pathStyleEndpoint' => 'true',
            ],
            null,
            $this->httpClient,
        );
    }
}
