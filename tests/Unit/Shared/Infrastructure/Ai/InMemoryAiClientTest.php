<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Infrastructure\Ai\InMemoryAiClient;
use PHPUnit\Framework\TestCase;

class InMemoryAiClientTest extends TestCase
{
    public function testAnswersTheQueuedResponsesInOrder(): void
    {
        $client = new InMemoryAiClient();
        $client->enqueue(['answer' => 1]);
        $client->enqueue(['answer' => 2], new AiUsage(AiProvider::Anthropic, 'claude-haiku-5-5', 10, 5));

        $first = $client->send(new FakeAiPrompt());
        $second = $client->send(new FakeAiPrompt());

        $this->assertSame(['answer' => 1], $first->data);
        $this->assertSame(InMemoryAiClient::MODEL, $first->usage->model);
        $this->assertSame(['answer' => 2], $second->data);
        $this->assertSame('claude-haiku-5-5', $second->usage->model);
    }

    public function testRepeatsTheLastResponseWhenTheQueueIsEmpty(): void
    {
        $client = new InMemoryAiClient();
        $client->enqueue(['answer' => 1]);

        $client->send(new FakeAiPrompt());

        $this->assertSame(['answer' => 1], $client->send(new FakeAiPrompt())->data);
    }

    public function testAnswersAnEmptyResponseWhenNothingWasQueued(): void
    {
        $response = (new InMemoryAiClient())->send(new FakeAiPrompt());

        $this->assertSame([], $response->data);
        $this->assertNull($response->usage->model);
    }

    public function testFails(): void
    {
        $client = new InMemoryAiClient();
        $client->fail('Overloaded');

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('Overloaded');

        $client->send(new FakeAiPrompt());
    }

    public function testRemembersThePrompts(): void
    {
        $client = new InMemoryAiClient();
        $first = new FakeAiPrompt(name: 'first');
        $second = new FakeAiPrompt(name: 'second');

        $this->assertNull($client->lastPrompt());

        $client->send($first);
        $client->send($second);

        $this->assertSame([$first, $second], $client->prompts());
        $this->assertSame($second, $client->lastPrompt());

        $client->reset();

        $this->assertSame([], $client->prompts());
    }
}
