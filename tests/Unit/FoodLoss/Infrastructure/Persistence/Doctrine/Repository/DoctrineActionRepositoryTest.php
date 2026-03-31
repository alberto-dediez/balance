<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineActionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineActionRepositoryTest extends ActionRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ActionRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineActionRepository($manager);
    }
}
