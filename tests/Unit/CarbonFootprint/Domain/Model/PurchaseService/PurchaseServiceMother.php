<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Unit;

class PurchaseServiceMother
{
    public static function registeredPurchaseService(): PurchaseService
    {
        return self::fromName('purchase-service-name');
    }

    public static function fromName(string $name): PurchaseService
    {
        return self::from(
            name: $name,
            id: (string) PurchaseServiceId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): PurchaseService
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        PurchaseServiceCategory $category = PurchaseServiceCategory::ProfessionalServices,
        Unit $unit = Unit::Euro,
    ): PurchaseService {
        $purchaseService = new PurchaseService(
            id: new PurchaseServiceId($id),
        );
        $purchaseService->register($name, $category, $unit);

        return $purchaseService;
    }

    public static function random(): PurchaseService
    {
        return self::from(
            name: 'purchase-service-' . rand(100, 999),
            id: (string) PurchaseServiceId::generate(),
        );
    }
}
