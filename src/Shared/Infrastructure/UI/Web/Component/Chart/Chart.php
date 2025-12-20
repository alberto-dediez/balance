<?php

namespace App\Shared\Infrastructure\UI\Web\Component\Chart;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chart:Chart', template: '@components/Chart/Chart.html.twig')]
class Chart
{
    public ?string $chartType = 'line';

    public ?string $title = null;

    public array $values = [];

    public array $categories = [];

    public ?string $unit = '';

    public ?array $colors = [];

    public ?array $series = [];

    public bool $legend = false;

    public bool $dataLabels = false;
}
