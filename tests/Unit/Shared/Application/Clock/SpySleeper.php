<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Clock;

use App\Shared\Application\Clock\Sleeper;

class SpySleeper implements Sleeper
{
    public int $calls = 0;

    /** @var (callable(int): void)|null */
    private $onSleep;

    /**
     * @param (callable(int): void)|null $onSleep
     */
    public function __construct(?callable $onSleep = null)
    {
        $this->onSleep = $onSleep;
    }

    public function sleep(int $milliseconds): void
    {
        ++$this->calls;

        if (null !== $this->onSleep) {
            ($this->onSleep)($this->calls);
        }
    }
}
