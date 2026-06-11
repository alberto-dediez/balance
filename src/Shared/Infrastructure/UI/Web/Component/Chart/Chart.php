<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Chart;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chart:Chart', template: '@components/Chart/Chart.html.twig')]
class Chart
{
    public ?string $chartType = 'line';

    public ?string $title = null;


    /**
     * @var array<int, mixed>
     */
    public array $values = [];

    /**
     * @var array<int, mixed>
     */
    public array $categories = [];

    public ?string $unit = '';

    public ?string $labelUnit = null;

    public int $decimals = 2;

    public ?int $labelDecimals = null;

    /**
     * @var array<int, mixed>
     */
    public ?array $colors = [];

    /**
     * @var array<int, mixed>
     */
    public ?array $series = [];

    public bool $legend = false;

    public bool $dataLabels = false;
}
