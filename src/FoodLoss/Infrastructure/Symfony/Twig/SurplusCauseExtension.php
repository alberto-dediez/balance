<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SurplusCauseExtension extends AbstractExtension
{
    /** @var SurplusCause[] */
    private array $surplusCauses = [];

    public function __construct(private readonly SurplusCauseRepository $surplusCauseRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('surplus_cause_name', $this->surplusCauseName(...)),
        ];
    }

    public function surplusCauseName(SurplusCauseId $surplusCauseId): ?SurplusCause
    {
        if (!isset($this->surplusCauses[(string) $surplusCauseId])) {
            $surplusCause = $this->surplusCauseRepository->find($surplusCauseId);

            if ($surplusCause instanceof SurplusCause) {
                $this->surplusCauses[(string) $surplusCauseId] = $surplusCause;
            }
        }

        return $this->surplusCauses[(string) $surplusCauseId] ?? null;
    }
}
