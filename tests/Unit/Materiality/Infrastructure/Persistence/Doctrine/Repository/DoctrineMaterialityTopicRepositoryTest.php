<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Infrastructure\Persistence\Doctrine\Repository\DoctrineMaterialityTopicRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMaterialityTopicRepositoryTest extends MaterialityTopicRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MaterialityTopicRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMaterialityTopicRepository($manager);
    }
}
