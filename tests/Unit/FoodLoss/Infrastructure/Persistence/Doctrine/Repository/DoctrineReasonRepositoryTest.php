<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineReasonRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineReasonRepositoryTest extends ReasonRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ReasonRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineReasonRepository($manager);
    }
}
