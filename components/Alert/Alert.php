<?php

namespace App\Component\Alert;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Alert', template: '@components/Alert/Alert.html.twig')]
final class Alert
{
    public ?string $type = null;
}