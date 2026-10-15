<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrinePurchaseServiceRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePurchaseServiceRepositoryTest extends PurchaseServiceRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): PurchaseServiceRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrinePurchaseServiceRepository($manager);
    }
}
