<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Chart;

use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryApexChartClient implements ApexChartClient
{
    /** @var list<array<string, mixed>> */
    private array $calls = [];

    public function __construct(private string $response = 'PNG') {}

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    /** @param array<string, mixed> $options */
    public function generate(array $options): string
    {
        $this->calls[] = $options;

        return $this->response;
    }

    /** @return list<array<string, mixed>> */
    public function calls(): array
    {
        return $this->calls;
    }
}
