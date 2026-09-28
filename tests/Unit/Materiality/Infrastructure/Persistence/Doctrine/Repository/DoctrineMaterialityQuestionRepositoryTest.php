<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Infrastructure\Persistence\Doctrine\Repository\DoctrineMaterialityQuestionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMaterialityQuestionRepositoryTest extends MaterialityQuestionRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MaterialityQuestionRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMaterialityQuestionRepository($manager);
    }
}
