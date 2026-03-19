<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Alert', template: '@components/Alert/Alert.html.twig')]
final class Alert
{
    public ?string $type = null;
}
