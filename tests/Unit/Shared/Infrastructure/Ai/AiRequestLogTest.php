<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiProvider;
use App\Shared\Infrastructure\Ai\AiRequestLog;
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

    public function testDoesNotWriteWhenDisabled(): void
    {
        $log = new AiRequestLog(false, $this->dir);

        $log->write(AiProvider::Anthropic, 'claude-haiku-5-5', new FakeAiPrompt(), new \DateTimeImmutable(), [], [], []);

        $this->assertFalse($log->enabled());
        $this->assertDirectoryDoesNotExist($this->dir);
    }

    public function testWritesTheRequestAndTheResponseWithoutTheAttachmentData(): void
    {
        $log = new AiRequestLog(true, $this->dir);
        $data = base64_encode('%PDF-1.4 contenido');
        $request = ['model' => 'claude-haiku-5-5', 'messages' => [['role' => 'user', 'content' => [
            ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $data]],
            ['type' => 'text', 'text' => 'Prompt'],
        ]]]];

        $log->write(AiProvider::Anthropic, 'claude-haiku-5-5', new FakeAiPrompt([new AiAttachment('factura.pdf', 'application/pdf', '%PDF-1.4 contenido')], name: 'invoice-extraction'), new \DateTimeImmutable(), $request, ['stop_reason' => 'end_turn'], ['content' => 'Texto']);

        $files = glob($this->dir . '/*_invoice-extraction.json') ?: [];
        $this->assertCount(1, $files);

        $contents = (string) file_get_contents($files[0]);
        $this->assertStringNotContainsString($data, $contents);

        $content = json_decode($contents, true);
        $this->assertIsArray($content);
        $this->assertSame('anthropic', $content['provider']);
        $this->assertSame('claude-haiku-5-5', $content['model']);
        $this->assertSame('invoice-extraction', $content['prompt']);
        $this->assertSame('System', $content['request']['system']);
        $this->assertSame(['factura.pdf (18 bytes)'], $content['request']['attachments']);
        $this->assertSame('factura.pdf (18 bytes)', $content['request']['body']['messages'][0]['content'][0]['source']['data']);
        $this->assertSame(['stop_reason' => 'end_turn'], $content['raw_response']);
        $this->assertSame(['content' => 'Texto'], $content['parsed_result']);
    }

    public function testWriteAgentRedactsTheSourceDataOfMessagesAndToolResults(): void
    {
        $log = new AiRequestLog(true, $this->dir);
        $data = base64_encode('%PDF-1.4 contenido');
        $request = ['model' => 'claude-haiku-5-5', 'messages' => [
            ['role' => 'user', 'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => $data]],
                ['type' => 'text', 'text' => 'Hola'],
            ]],
            ['role' => 'user', 'content' => [
                ['type' => 'tool_result', 'tool_use_id' => 'toolu_1', 'content' => [
                    ['type' => 'text', 'text' => 'Resultado'],
                    ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $data]],
                ]],
                ['type' => 'tool_result', 'tool_use_id' => 'toolu_2', 'content' => 'Texto plano'],
            ]],
        ]];

        $log->writeAgent(AiProvider::Anthropic, 'claude-haiku-5-5', 'assistant', new \DateTimeImmutable(), $request, ['stop_reason' => 'end_turn']);

        $files = glob($this->dir . '/*_assistant.json') ?: [];
        $this->assertCount(1, $files);

        $contents = (string) file_get_contents($files[0]);
        $this->assertStringNotContainsString($data, $contents);

        $content = json_decode($contents, true);
        $this->assertIsArray($content);
        $messages = $content['request']['body']['messages'];
        $this->assertSame('(18 bytes)', $messages[0]['content'][0]['source']['data']);
        $this->assertSame('(18 bytes)', $messages[1]['content'][0]['content'][1]['source']['data']);
        $this->assertSame('Resultado', $messages[1]['content'][0]['content'][0]['text']);
        $this->assertSame('Texto plano', $messages[1]['content'][1]['content']);
    }
}
