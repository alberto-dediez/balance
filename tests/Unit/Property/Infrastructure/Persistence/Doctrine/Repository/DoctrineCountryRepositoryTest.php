<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrineCountryRepository;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCountryRepositoryTest extends CountryRepositoryTest
{
    public function createSUT(): CountryRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCountryRepository($manager);
    }
}
