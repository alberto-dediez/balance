<?php

namespace App\Shared\Domain\Event;

use BadMethodCallException;

class EventRegistry
{
    private readonly string $pathToFile;

    protected static ?EventRegistry $instance = null;

    /**
     * @param array<string,string> $events
     */
    private function __construct(
        protected array $events = []
    ) {
        $this->pathToFile = __DIR__ . '/../../../../var/events.json';
    }

    public static function instance(): EventRegistry
    {
        if (null === static::$instance) {
            static::$instance = new self();

            static::$instance->load();
        }

        return static::$instance;
    }

    public function __clone()
    {
        throw new BadMethodCallException("Clone is not supported");
    }

    public function registerEvent(string $fullName, string $class): void
    {
        $this->events[$fullName] = $class;
    }

    public function getEvent(string $context, string $eventName): ?string
    {
        $fullName = sprintf("%s.%s", $context, $eventName);

        return $this->events[$fullName] ?? null;
    }

    public function save(): void
    {
        file_put_contents($this->pathToFile, json_encode($this->events));
    }

    protected function load(): void
    {
        if (file_exists($this->pathToFile)) {
            $events = @json_decode(file_get_contents($this->pathToFile), true);

            if (is_array($events)) {
                $this->events = $events;
            }
        }
    }
}
