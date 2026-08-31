<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\ErrorReport;

class ErrorReportRequest
{
    /**
     * @param array<string,string> $query
     * @param array<string,string> $body
     * @param array<string,string> $headers
     */
    public function __construct(
        public string $uri = '',
        public string $method = '',
        public ?string $route = null,
        public ?string $clientIp = null,
        public ?string $userAgent = null,
        public ?string $referer = null,
        public ?string $locale = null,
        public array $query = [],
        public array $body = [],
        public array $headers = [],
    ) {}
}
