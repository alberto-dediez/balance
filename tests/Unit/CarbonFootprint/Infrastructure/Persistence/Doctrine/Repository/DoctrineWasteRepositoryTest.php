<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineWasteRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineWasteRepositoryTest extends WasteRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): WasteRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineWasteRepository($manager);
    }
}
