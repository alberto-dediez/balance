<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\AI;

use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiPrompt;
use App\Shared\Infrastructure\AI\AiProvider;
use App\Shared\Infrastructure\AI\AiRequestLog;
use App\Shared\Infrastructure\AI\AiThinkingLevel;
use PHPUnit\Framework\TestCase;

class AiRequestLogTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sprintf('%s/ai-logs-%s', sys_get_temp_dir(), uniqid());
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    public function testWritesTheRequestAndTheResponse(): void
    {
        $log = new AiRequestLog(true, $this->dir);
        $prompt = new TranslationPrompt();

        $log->write(AiProvider::Anthropic, 'claude-haiku-5-5', $prompt, new \DateTimeImmutable(), ['model' => 'claude-haiku-5-5'], ['stop_reason' => 'end_turn'], ['content' => 'Texto']);

        $files = glob($this->dir . '/*_TranslationPrompt.json') ?: [];
        $this->assertCount(1, $files);

        $content = json_decode((string) file_get_contents($files[0]), true);
        $this->assertIsArray($content);
        $this->assertSame('anthropic', $content['provider']);
        $this->assertSame('claude-haiku-5-5', $content['model']);
        $this->assertSame('MEDIUM', $content['model_tier']);
        $this->assertSame(TranslationPrompt::class, $content['prompt_class']);
        $this->assertSame(['model' => 'claude-haiku-5-5'], $content['request']['body']);
        $this->assertSame(['stop_reason' => 'end_turn'], $content['raw_response']);
        $this->assertSame(['content' => 'Texto'], $content['parsed_result']);
    }

    public function testWritesTheLogOfAnAnonymousPrompt(): void
    {
        $log = new AiRequestLog(true, $this->dir);
        $prompt = new class implements AiPrompt {
            public function text(): string
            {
                return 'Prompt';
            }

            public function responseSchema(): array
            {
                return ['type' => 'OBJECT'];
            }

            public function modelTier(): AiModelTier
            {
                return AiModelTier::LOW;
            }

            public function thinkingLevel(): AiThinkingLevel
            {
                return AiThinkingLevel::LOW;
            }
        };

        $log->write(AiProvider::Gemini, 'gemini-3-flash-preview', $prompt, new \DateTimeImmutable(), [], [], []);

        $this->assertCount(1, glob($this->dir . '/*_AnonymousPrompt.json') ?: []);
    }

    public function testIsDisabled(): void
    {
        $this->assertFalse((new AiRequestLog(false, $this->dir))->enabled());
    }
}

class TranslationPrompt implements AiPrompt
{
    public function text(): string
    {
        return 'Prompt';
    }

    public function responseSchema(): array
    {
        return ['type' => 'OBJECT'];
    }

    public function modelTier(): AiModelTier
    {
        return AiModelTier::MEDIUM;
    }

    public function thinkingLevel(): AiThinkingLevel
    {
        return AiThinkingLevel::LOW;
    }
}
