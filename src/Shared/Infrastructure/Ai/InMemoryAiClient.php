<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiClient;
use App\Shared\Application\Ai\AiPrompt;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponse;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAiClient implements AiClient
{
    public const string MODEL = 'in-memory';

    /** @var list<AiResponse|AiResponseException> */
    private array $queue = [];

    private ?AiResponse $last = null;

    /** @var list<AiPrompt> */
    private array $prompts = [];

    /** @param array<mixed> $data */
    public function enqueue(array $data, ?AiUsage $usage = null): void
    {
        $this->queue[] = new AiResponse($data, $usage ?? self::defaultUsage());
    }

    public function fail(string $message): void
    {
        $this->queue[] = new AiResponseException($message);
    }

    public function send(AiPrompt $prompt): AiResponse
    {
        $this->prompts[] = $prompt;

        $next = array_shift($this->queue);

        if ($next instanceof AiResponseException) {
            throw $next;
        }

        if ($next instanceof AiResponse) {
            $this->last = $next;

            return $next;
        }

        return $this->last ?? new AiResponse([], AiUsage::none());
    }

    /** @return list<AiPrompt> */
    public function prompts(): array
    {
        return $this->prompts;
    }

    public function lastPrompt(): ?AiPrompt
    {
        return [] === $this->prompts ? null : $this->prompts[array_key_last($this->prompts)];
    }

    public function reset(): void
    {
        $this->queue = [];
        $this->last = null;
        $this->prompts = [];
    }

    public static function defaultUsage(): AiUsage
    {
        return new AiUsage(AiProvider::Anthropic, self::MODEL, 1000, 200, 0, 0, 0.0002, 1200);
    }
}
