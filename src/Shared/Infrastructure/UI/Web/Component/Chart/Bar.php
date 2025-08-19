<?php

namespace App\Shared\Infrastructure\UI\Web\Component\Chart;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chart:Bar', template: '@components/Chart/Chart.html.twig')]
final class Bar extends Chart
{
    public ?string $chartType = 'bar';
}