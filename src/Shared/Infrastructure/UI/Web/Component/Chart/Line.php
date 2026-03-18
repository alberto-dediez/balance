<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Chart;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chart:Line', template: '@components/Chart/Chart.html.twig')]
final class Line extends Chart
{
    public ?string $chartType = 'line';
}
