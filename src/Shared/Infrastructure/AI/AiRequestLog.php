<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

final class AiRequestLog
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $dir,
    ) {}

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param array<mixed> $request
     * @param array<mixed> $rawResponse
     * @param array<mixed> $parsedResult
     */
    public function write(AiProvider $provider, string $model, AiPrompt $prompt, \DateTimeImmutable $requestedAt, array $request, array $rawResponse, array $parsedResult): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        $reflection = new \ReflectionClass($prompt);
        $promptClass = $reflection->isAnonymous() ? 'AnonymousPrompt' : $reflection->getShortName();
        $filename = sprintf('%s/%s_%s.json', $this->dir, $requestedAt->format('Y-m-d_H-i-s-u'), $promptClass);

        $log = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'provider' => $provider->value,
            'model' => $model,
            'model_tier' => $prompt->modelTier()->name,
            'prompt_class' => $prompt::class,
            'request' => [
                'text' => $prompt->text(),
                'thinkingLevel' => $prompt->thinkingLevel()->name,
                'responseSchema' => $prompt->responseSchema(),
                'body' => $request,
            ],
            'raw_response' => $rawResponse,
            'parsed_result' => $parsedResult,
        ];

        file_put_contents($filename, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
