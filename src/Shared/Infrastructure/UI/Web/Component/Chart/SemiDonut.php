<?php

namespace App\Shared\Infrastructure\UI\Web\Component\Chart;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chart:SemiDonut', template: '@components/Chart/Chart.html.twig')]
final class SemiDonut extends Chart
{
    public ?string $chartType = 'semidonut';
}