<?php

declare(strict_types=1);

namespace App\Shared\Application\Shortcut;

final readonly class Shortcut
{
    /** @param array<string, string> $routeParameters */
    public function __construct(
        public KeyCombination $combination,
        public string $label,
        public string $icon,
        public int $position,
        public ?string $route = null,
        public array $routeParameters = [],
        public ?string $event = null,
        public bool $submit = false,
        public bool $withinModal = false,
    ) {
        if (1 !== count(array_filter([null !== $route, null !== $event, $submit]))) {
            throw new \InvalidArgumentException(sprintf('The shortcut "%s" must have either a route, an event or submit the active form', $label));
        }

        if ($withinModal && null === $event) {
            throw new \InvalidArgumentException(sprintf('The shortcut "%s" can only act within a modal when it dispatches an event', $label));
        }
    }

    /** @param array<string, string> $routeParameters */
    public static function toRoute(KeyCombination $combination, string $label, string $icon, int $position, string $route, array $routeParameters = []): self
    {
        return new self($combination, $label, $icon, $position, route: $route, routeParameters: $routeParameters);
    }

    public static function dispatching(KeyCombination $combination, string $label, string $icon, int $position, string $event, bool $withinModal = false): self
    {
        return new self($combination, $label, $icon, $position, event: $event, withinModal: $withinModal);
    }

    public static function submitting(KeyCombination $combination, string $label, string $icon, int $position): self
    {
        return new self($combination, $label, $icon, $position, submit: true);
    }
}
