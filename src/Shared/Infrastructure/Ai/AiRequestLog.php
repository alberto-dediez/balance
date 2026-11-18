<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiPrompt;
use App\Shared\Application\Ai\AiProvider;

final readonly class AiRequestLog
{
    public function __construct(
        private bool $enabled,
        private string $dir,
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
        if (!$this->enabled) {
            return;
        }

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        $filename = sprintf('%s/%s_%s.json', $this->dir, $requestedAt->format('Y-m-d_H-i-s-u'), $this->promptName($prompt));

        $log = [
            'timestamp' => $requestedAt->format(\DateTimeInterface::ATOM),
            'provider' => $provider->value,
            'model' => $model,
            'prompt' => $prompt->name(),
            'model_tier' => $prompt->modelTier()->value,
            'effort' => $prompt->effort()->value,
            'prompt_class' => $prompt::class,
            'request' => [
                'system' => $prompt->system(),
                'text' => $prompt->text(),
                'attachments' => array_map(static fn($attachment): string => sprintf('%s (%d bytes)', $attachment->name, $attachment->size()), $prompt->attachments()),
                'responseSchema' => $prompt->responseSchema(),
                'body' => $this->withoutAttachmentData($request, $prompt),
            ],
            'raw_response' => $rawResponse,
            'parsed_result' => $parsedResult,
        ];

        file_put_contents($filename, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array<mixed> $request
     * @param array<mixed> $rawResponse
     */
    public function writeAgent(AiProvider $provider, string $model, string $name, \DateTimeImmutable $requestedAt, array $request, array $rawResponse): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name);
        $filename = sprintf('%s/%s_%s.json', $this->dir, $requestedAt->format('Y-m-d_H-i-s-u'), null === $safeName || '' === $safeName ? 'agent' : $safeName);

        $log = [
            'timestamp' => $requestedAt->format(\DateTimeInterface::ATOM),
            'provider' => $provider->value,
            'model' => $model,
            'prompt' => $name,
            'request' => ['body' => $this->withoutSourceData($request)],
            'raw_response' => $rawResponse,
        ];

        file_put_contents($filename, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function promptName(AiPrompt $prompt): string
    {
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $prompt->name());

        return null === $name || '' === $name ? 'prompt' : $name;
    }

    /**
     * @param array<mixed> $request
     *
     * @return array<mixed>
     */
    private function withoutSourceData(array $request): array
    {
        if (!isset($request['messages']) || !is_array($request['messages'])) {
            return $request;
        }

        foreach ($request['messages'] as $messageKey => $message) {
            if (is_array($message) && isset($message['content']) && is_array($message['content'])) {
                $request['messages'][$messageKey]['content'] = $this->blocksWithoutSourceData($message['content']);
            }
        }

        return $request;
    }

    /**
     * @param array<mixed> $blocks
     *
     * @return array<mixed>
     */
    private function blocksWithoutSourceData(array $blocks): array
    {
        foreach ($blocks as $key => $block) {
            if (!is_array($block)) {
                continue;
            }

            if (isset($block['source']) && is_array($block['source']) && is_string($block['source']['data'] ?? null)) {
                $blocks[$key]['source']['data'] = sprintf('(%d bytes)', strlen((string) base64_decode($block['source']['data'], true)));
            }

            if ('tool_result' === ($block['type'] ?? null) && isset($block['content']) && is_array($block['content'])) {
                $blocks[$key]['content'] = $this->blocksWithoutSourceData($block['content']);
            }
        }

        return $blocks;
    }

    /**
     * @param array<mixed> $request
     *
     * @return array<mixed>
     */
    private function withoutAttachmentData(array $request, AiPrompt $prompt): array
    {
        $attachments = $prompt->attachments();
        $position = 0;

        if (isset($request['contents']) && is_array($request['contents'])) {
            return $this->withoutInlineData($request, $attachments);
        }

        if (!isset($request['messages']) || !is_array($request['messages'])) {
            return $request;
        }

        foreach ($request['messages'] as $messageKey => $message) {
            if (!is_array($message) || !isset($message['content']) || !is_array($message['content'])) {
                continue;
            }

            foreach ($message['content'] as $blockKey => $block) {
                if (!is_array($block) || !isset($block['source']) || !is_array($block['source']) || !isset($block['source']['data'])) {
                    continue;
                }

                $attachment = $attachments[$position] ?? null;
                $data = $block['source']['data'];
                $request['messages'][$messageKey]['content'][$blockKey]['source']['data'] = null !== $attachment
                    ? sprintf('%s (%d bytes)', $attachment->name, $attachment->size())
                    : sprintf('(%d bytes)', is_string($data) ? strlen((string) base64_decode($data, true)) : 0);
                ++$position;
            }
        }

        return $request;
    }

    /**
     * @param array<mixed>       $request
     * @param list<AiAttachment> $attachments
     *
     * @return array<mixed>
     */
    private function withoutInlineData(array $request, array $attachments): array
    {
        $position = 0;
        $contents = is_array($request['contents'] ?? null) ? $request['contents'] : [];

        foreach ($contents as $contentKey => $content) {
            if (!is_array($content) || !isset($content['parts']) || !is_array($content['parts'])) {
                continue;
            }

            foreach ($content['parts'] as $partKey => $part) {
                if (!is_array($part) || !isset($part['inlineData']) || !is_array($part['inlineData']) || !isset($part['inlineData']['data'])) {
                    continue;
                }

                $attachment = $attachments[$position] ?? null;
                $data = $part['inlineData']['data'];
                $request['contents'][$contentKey]['parts'][$partKey]['inlineData']['data'] = null !== $attachment
                    ? sprintf('%s (%d bytes)', $attachment->name, $attachment->size())
                    : sprintf('(%d bytes)', is_string($data) ? strlen((string) base64_decode($data, true)) : 0);
                ++$position;
            }
        }

        return $request;
    }
}
