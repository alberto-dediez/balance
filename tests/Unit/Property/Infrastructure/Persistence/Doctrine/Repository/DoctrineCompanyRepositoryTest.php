<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrineCompanyRepository;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCompanyRepositoryTest extends CompanyRepositoryTest
{
    protected function createSUT(): CompanyRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCompanyRepository($manager);
    }
}
