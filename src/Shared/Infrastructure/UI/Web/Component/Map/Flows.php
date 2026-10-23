<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Map;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Map:Flows', template: '@components/Map/Flows.html.twig')]
final class Flows
{
    /** @var array<int, mixed> */
    public array $flows = [];

    /** @var array<string, mixed>|null */
    public ?array $destination = null;

    public int $height = 420;

    public bool $arrows = false;

    public function __construct(
        private readonly string $tileKey,
    ) {}

    public function tileKey(): string
    {
        return $this->tileKey;
    }
}
