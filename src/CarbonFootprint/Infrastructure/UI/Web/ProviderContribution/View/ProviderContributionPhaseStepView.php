<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\ProviderContribution\View;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowViewFactoryRegistry;

final class ProviderContributionPhaseStepView
{
    /** @var list<CategoryOvernight> */
    private const ALLOWED_OVERNIGHT_STAY_CATEGORIES = [CategoryOvernight::Suppliers];

    public function __construct(private ActivityRowViewFactoryRegistry $rowViewFactories) {}

    /**
     * @param list<StageSection> $sections
     *
     * @return array<string, mixed>
     */
    public function build(array $sections): array
    {
        $combustibles = $this->section($sections, 'combustibles');
        $machineryFuels = $this->section($sections, 'machineryFuels');
        $overnightStays = $this->section($sections, 'overnightStays');
        $mobilities = $this->section($sections, 'mobilities');

        $combustibleRows = $this->rows($combustibles);
        $machineryFuelRows = $this->rows($machineryFuels);
        $overnightStayRows = $this->rows($overnightStays);
        $mobilityRows = $this->rows($mobilities);

        return [
            'combustibles' => $combustibles->catalog,
            'machineryFuels' => $machineryFuels->catalog,
            'overnightStays' => $this->allowedOvernightStays($overnightStays->catalog),
            'mobilities' => $mobilities->catalog,
            'units' => Unit::cases(),
            'combustibleRows' => $combustibleRows,
            'machineryFuelRows' => $machineryFuelRows,
            'overnightStayRows' => $overnightStayRows,
            'mobilityRows' => $mobilityRows,
            'hasActivities' => [] !== $combustibleRows || [] !== $machineryFuelRows || [] !== $overnightStayRows || [] !== $mobilityRows,
        ];
    }

    /**
     * @param list<object> $catalog
     *
     * @return list<OvernightStay>
     */
    private function allowedOvernightStays(array $catalog): array
    {
        return array_values(array_filter(
            $catalog,
            static fn(object $overnightStay): bool => $overnightStay instanceof OvernightStay
                && in_array($overnightStay->category(), self::ALLOWED_OVERNIGHT_STAY_CATEGORIES, true),
        ));
    }

    /** @return list<ActivityRowView> */
    private function rows(StageSection $section): array
    {
        $factory = $this->rowViewFactories->forActivityType($section->definition->activityType);

        return array_map(static fn($activity) => $factory->create($activity), $section->activities);
    }

    /** @param list<StageSection> $sections */
    private function section(array $sections, string $key): StageSection
    {
        foreach ($sections as $section) {
            if ($section->definition->key === $key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(sprintf('Section "%s" not found.', $key));
    }
}
