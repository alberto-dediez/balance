<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivity;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivity;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivity;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\Activity\RoadTransportActivity;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivity;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActivityItemNameResolver
{
    /** @var array<string, array<string, string>> */
    private array $cache = [];

    public function __construct(
        private ActivityCatalogProviderRegistry $catalogProviders,
        private TranslatorInterface $translator,
    ) {}

    public function resolve(Activity $activity, int $year, ?EmissionFactorGroupId $emissionFactorGroupId): string
    {
        $activityType = ActivityType::fromActivity($activity);
        $itemId = $this->itemId($activity);

        if (null === $itemId) {
            return $this->translator->trans($activityType->value, [], 'messages');
        }

        $names = $this->namesFor($activityType, $year, $emissionFactorGroupId);

        return $names[$itemId] ?? $this->translator->trans($activityType->value, [], 'messages');
    }

    /** @return array<string, string> */
    private function namesFor(ActivityType $activityType, int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        $cacheKey = $activityType->value . ':' . $year . ':' . ($emissionFactorGroupId instanceof EmissionFactorGroupId ? (string) $emissionFactorGroupId : '');

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $provider = $this->catalogProviders->forActivityType($activityType);

        $names = [];
        foreach ([...$provider->catalog($year, $emissionFactorGroupId), ...$provider->secondaryCatalog($year, $emissionFactorGroupId)] as $item) {
            if (method_exists($item, 'id') && method_exists($item, 'name')) {
                $names[(string) $item->id()] = (string) $item->name();
            }
        }

        return $this->cache[$cacheKey] = $names;
    }

    public function itemId(Activity $activity): ?string
    {
        return match (true) {
            $activity instanceof MaterialActivity => (string) $activity->materialId(),
            $activity instanceof CateringItemActivity => (string) $activity->cateringItemId(),
            $activity instanceof MerchandisingItemActivity => (string) $activity->merchandisingItemId(),
            $activity instanceof PurchaseServiceActivity => (string) $activity->purchaseServiceId(),
            $activity instanceof CapitalGoodActivity => (string) $activity->capitalGoodId(),
            $activity instanceof UseProfileActivity => (string) $activity->useProfileId(),
            $activity instanceof CombustibleActivity => (string) $activity->combustibleId(),
            $activity instanceof ElectricityActivity => null !== $activity->electricityMarketerId() ? (string) $activity->electricityMarketerId() : null,
            $activity instanceof MachineryFuelActivity => (string) $activity->machineryFuelId(),
            $activity instanceof MobilityActivity => (string) $activity->mobilityId(),
            $activity instanceof OvernightStayActivity => (string) $activity->overnightStayId(),
            $activity instanceof RoadTransportActivity => (string) $activity->roadTransportId(),
            $activity instanceof WasteActivity => (string) $activity->wasteId(),
            $activity instanceof RefrigerantGasActivity => (string) $activity->refrigerantGasId(),
            default => null,
        };
    }
}
