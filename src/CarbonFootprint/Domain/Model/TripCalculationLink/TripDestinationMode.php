<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

enum TripDestinationMode: string
{
    case Free = 'free';

    case Countries = 'countries';

    case Region = 'region';

    case AllProperties = 'all_properties';

    case SelectedProperties = 'selected_properties';

    public function usesPlaceFinder(): bool
    {
        return self::Free === $this || self::Countries === $this || self::Region === $this;
    }

    public function usesProperties(): bool
    {
        return self::AllProperties === $this || self::SelectedProperties === $this;
    }
}
