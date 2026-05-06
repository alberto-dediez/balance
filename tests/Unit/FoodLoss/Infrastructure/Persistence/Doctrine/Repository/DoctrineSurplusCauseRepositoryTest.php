<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineSurplusCauseRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineSurplusCauseRepositoryTest extends SurplusCauseRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): SurplusCauseRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineSurplusCauseRepository($manager);
    }
}
