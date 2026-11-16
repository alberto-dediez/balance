<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Assistant;

use App\Shared\Infrastructure\Assistant\ResettableCurrentAssistantExecution;
use PHPUnit\Framework\TestCase;

class ResettableCurrentAssistantExecutionTest extends TestCase
{
    public function testBeginAndEndTheExecution(): void
    {
        $execution = new ResettableCurrentAssistantExecution();

        $execution->begin('0190f3a4-1b2c-7d3e-8f40-123456789abc');
        $this->assertSame('0190f3a4-1b2c-7d3e-8f40-123456789abc', $execution->executionId());

        $execution->end();
        $this->assertNull($execution->executionId());
    }

    public function testResetEndsTheExecution(): void
    {
        $execution = new ResettableCurrentAssistantExecution();

        $execution->begin('0190f3a4-1b2c-7d3e-8f40-123456789abc');
        $execution->reset();

        $this->assertNull($execution->executionId());
    }
}
