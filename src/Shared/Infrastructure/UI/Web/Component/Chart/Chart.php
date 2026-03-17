<?php

namespace App\Shared\Infrastructure\UI\Web\Component\Chart;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chart:Chart', template: '@components/Chart/Chart.html.twig')]
class Chart
{
    public ?string $chartType = 'line';

    public ?string $title = null;


    /**
     * @var array<int, mixed> $values
     */
    public array $values = [];

    /**
     * @var array<int, mixed> $categories
     */
    public array $categories = [];

    public ?string $unit = '';

    /**
     * @var array<int, mixed> $colors
     */
    public ?array $colors = [];

    /**
     * @var array<int, mixed> $series
     */
    public ?array $series = [];

    public bool $legend = false;

    public bool $dataLabels = false;
}
