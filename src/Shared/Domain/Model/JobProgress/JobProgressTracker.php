<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\JobProgress;

final class JobProgressTracker
{
    private static ?self $instance = null;

    private ?JobProgress $current = null;

    private function __construct() {}

    public static function instance(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __clone()
    {
        throw new \BadMethodCallException('Clone is not supported');
    }

    public function set(JobProgress $jobProgress): void
    {
        $this->current = $jobProgress;
    }

    public function advance(string $step = ''): void
    {
        $this->current?->advance($step);
    }

    public function resize(int $steps): void
    {
        $this->current?->resize($steps);
    }

    public function clear(): void
    {
        $this->current = null;
    }
}
