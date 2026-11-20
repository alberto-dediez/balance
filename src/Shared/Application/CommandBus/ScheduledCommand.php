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
 *   ScheduledCommand::weeklyOn('my-command', $command, 'sunday', '21:00')
 */
#[Exclude]
class ScheduledCommand
{
    private const MINUTES_PER_HOUR = 60;
    private const MINUTES_PER_DAY = 24 * 60;
    private const MINUTES_PER_WEEK = 7 * self::MINUTES_PER_DAY;
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    private function __construct(
        private readonly string $id,
        private readonly Command $command,
        private readonly int $intervalInMinutes,
        private readonly ?string $time = null,
        private readonly ?string $day = null,
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
     * Dispatches the command once a day, on the first scheduler tick at or after the given UTC time (HH:MM).
     */
    public static function dailyAt(string $id, Command $command, string $time, Priority $priority = Priority::LOW): self
    {
        self::assertValidTime($time);

        return new self($id, new DeferredCommand(command: $command, priority: $priority), self::MINUTES_PER_DAY, $time);
    }

    /**
     * Dispatches the command once a week, on the first scheduler tick at or after the given day and UTC time (HH:MM).
     */
    public static function weeklyOn(string $id, Command $command, string $day, string $time, Priority $priority = Priority::LOW): self
    {
        if (!in_array($day, self::DAYS, true)) {
            throw new \InvalidArgumentException(sprintf('Day must be one of %s, "%s" given.', implode(', ', self::DAYS), $day));
        }

        self::assertValidTime($time);

        return new self($id, new DeferredCommand(command: $command, priority: $priority), self::MINUTES_PER_WEEK, $time, $day);
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
        if (null !== $this->time && null !== $this->day) {
            return sprintf('weekly on %s %s UTC', $this->day, $this->time);
        }

        if (null !== $this->time) {
            return sprintf('daily at %s UTC', $this->time);
        }

        if (0 === $this->intervalInMinutes % 60) {
            $hours = intdiv($this->intervalInMinutes, 60);

            return sprintf('every %d hour%s', $hours, $hours > 1 ? 's' : '');
        }

        return sprintf('every %d minutes', $this->intervalInMinutes);
    }

    public function waitsForItsTime(): bool
    {
        return null !== $this->time;
    }

    public function isDue(\DateTimeImmutable $now, ?\DateTimeImmutable $lastRunAt): bool
    {
        if ($this->waitsForItsTime()) {
            return null !== $lastRunAt && $lastRunAt < $this->latestOccurrence($now);
        }

        if (null === $lastRunAt) {
            return true;
        }

        $elapsed = $now->getTimestamp() - $lastRunAt->getTimestamp();

        return $elapsed >= $this->intervalInMinutes * 60;
    }

    private static function assertValidTime(string $time): void
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
            throw new \InvalidArgumentException(sprintf('Time must be in HH:MM format (00:00–23:59), "%s" given.', $time));
        }
    }

    private function latestOccurrence(\DateTimeImmutable $now): \DateTimeImmutable
    {
        [$hours, $minutes] = array_map('intval', explode(':', (string) $this->time));
        $utcNow = $now->setTimezone(new \DateTimeZone('UTC'));

        if (null === $this->day) {
            $occurrence = $utcNow->setTime($hours, $minutes);

            return $occurrence > $utcNow ? $occurrence->modify('-1 day') : $occurrence;
        }

        $occurrence = $utcNow->modify($this->day . ' this week')->setTime($hours, $minutes);

        return $occurrence > $utcNow ? $occurrence->modify('-7 days') : $occurrence;
    }
}
