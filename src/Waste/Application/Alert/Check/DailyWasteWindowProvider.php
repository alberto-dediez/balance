<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteId;

final class DailyWasteWindowProvider
{
    /** @var array<string, DailyWaste[]> */
    private array $cache = [];

    public function __construct(
        private readonly DailyWasteRepository $dailyWasteRepository,
    ) {}

    /** @return DailyWaste[] */
    public function windowFor(
        AccountId $accountId,
        PropertyId $propertyId,
        \DateTimeImmutable $dayFrom,
        \DateTimeImmutable $dayTo,
        ?WasteId $wasteId = null,
    ): array {
        $key = (string) $propertyId . '|' . $dayFrom->format('Y-m-d') . '|' . $dayTo->format('Y-m-d');

        if (null !== $wasteId) {
            $key .= '|' . (string) $wasteId;
        }

        return $this->cache[$key] ??= $this->dailyWasteRepository->findByFilter(new DailyWasteFilter(
            properties: [$propertyId],
            wasteId: $wasteId,
            accountId: $accountId,
            dayFrom: $dayFrom,
            dayTo: $dayTo,
            limit: 0,
        ));
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
