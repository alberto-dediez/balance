<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

enum SurplusDestination: string
{
    case Transformation = 'Transformation';

    case HumanDonation = 'HumanDonation';

    case AnimalFeed = 'AnimalFeed';

    case Composting = 'Composting';

    case Elimination = 'Elimination';

    public function style(): string
    {
        return match ($this) {
            self::Transformation => 'bg-success',
            self::HumanDonation  => 'bg-primary',
            self::AnimalFeed     => 'bg-info',
            self::Composting     => 'bg-warning',
            self::Elimination    => 'bg-danger',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Transformation => 'var(--bs-success)',
            self::HumanDonation  => 'var(--bs-primary)',
            self::AnimalFeed     => 'var(--bs-info)',
            self::Composting     => 'var(--bs-warning)',
            self::Elimination    => 'var(--bs-danger)',
        };
    }
}
