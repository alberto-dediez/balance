<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryTest;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Infrastructure\Persistence\Doctrine\Repository\DoctrineWasteRepository;
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
