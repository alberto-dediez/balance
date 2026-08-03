<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\Persistence\Doctrine\Repository\DoctrineProjectRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineProjectRepositoryTest extends ProjectRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ProjectRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineProjectRepository($manager);
    }
}
