<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Country;

use Doctrine\ORM\EntityManagerInterface;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryTest;
use App\Property\Infrastructure\Repository\Country\DoctrineCountryRepository;

class DoctrineCountryRepositoryTest extends CountryRepositoryTest
{
    public function createSUT(): CountryRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCountryRepository($manager);
    }
}