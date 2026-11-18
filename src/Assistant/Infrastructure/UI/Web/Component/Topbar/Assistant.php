<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\Component\Topbar;

use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Topbar:Assistant', template: '@components/Topbar/Assistant.html.twig')]
final readonly class Assistant
{
    public function __construct(private AssistantAccess $access) {}

    public function enabled(): bool
    {
        return $this->access->isGranted();
    }
}
