<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Use the named constructors:
 *
 *   ScheduledCommand::everyMinutes('my-command', $command, 30)
 *   ScheduledCommand::everyHours('my-command', $command, 2)
 *   ScheduledCommand::dailyAt('my-command', $command, '02:00')
 */
#[Exclude]
class ScheduledCommand
{
    private const MINUTES_PER_HOUR = 60;
    private const MINUTES_PER_DAY = 24 * 60;

    private function __construct(
        private readonly string $id,
        private readonly Command $command,
        private readonly int $intervalInMinutes,
        private readonly ?string $dailyAtLabel = null,
    ) {}

    /**
     * Dispatches the command every $minutes minutes. $minutes must be positive.
     */
    public static function everyMinutes(string $id, Command $command, int $minutes, Priority $priority = Priority::LOW): self
    {
        if ($minutes <= 0) {
            throw new \InvalidArgumentException(sprintf('The interval must be a positive number of minutes, %d given.', $minutes));
        }

        return new self($id, new DeferredCommand(command: $command, priority: $priority), $minutes);
    }

    /**
     * Dispatches the command every $hours hours. $hours must be positive.
     */
    public static function everyHours(string $id, Command $command, int $hours, Priority $priority = Priority::LOW): self
    {
        if ($hours <= 0) {
            throw new \InvalidArgumentException(sprintf('The interval must be a positive number of hours, %d given.', $hours));
        }

        return new self($id, new DeferredCommand(command: $command, priority: $priority), $hours * self::MINUTES_PER_HOUR);
    }

    /**
     * Dispatches the command once a day at the given UTC time (HH:MM).
     * The exact time is delegated to the queue via {@see DeferredCommand::$executeAt};
     * the scheduler only enforces that at least 24 hours have elapsed since the last run.
     */
    public static function dailyAt(string $id, Command $command, string $time, Priority $priority = Priority::LOW): self
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
            throw new \InvalidArgumentException(sprintf('Time must be in HH:MM format (00:00–23:59), "%s" given.', $time));
        }

        $executeAt = (new \DateTimeImmutable('NOW'))->setTimezone(new \DateTimeZone('UTC'))->setTime((int) $m[1], (int) $m[2]);

        if ($executeAt < new \DateTimeImmutable('NOW')) {
            $executeAt = $executeAt->modify('+1 day');
        }

        return new self($id, new DeferredCommand(command: $command, priority: $priority, executeAt: $executeAt), self::MINUTES_PER_DAY, $time);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function intervalInMinutes(): int
    {
        return $this->intervalInMinutes;
    }

    /**
     * Human-readable schedule description, used for logging.
     */
    public function describe(): string
    {
        if (null !== $this->dailyAtLabel) {
            return sprintf('daily at %s UTC', $this->dailyAtLabel);
        }

        if (0 === $this->intervalInMinutes % 60) {
            $hours = intdiv($this->intervalInMinutes, 60);

            return sprintf('every %d hour%s', $hours, $hours > 1 ? 's' : '');
        }

        return sprintf('every %d minutes', $this->intervalInMinutes);
    }

    public function isDue(\DateTimeImmutable $now, ?\DateTimeImmutable $lastRunAt): bool
    {
        if (null === $lastRunAt) {
            return true;
        }

        $elapsed = $now->getTimestamp() - $lastRunAt->getTimestamp();

        return $elapsed >= $this->intervalInMinutes * 60;
    }
}
