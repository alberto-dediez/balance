<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class BaseYearDeclarationRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();
        $declaration = BaseYearDeclarationMother::declared();

        $this->assertNull($repository->find($declaration->id()));

        $repository->add($declaration);

        $this->assertEquals($declaration->id(), $repository->find($declaration->id())?->id());
    }

    public function testFindBySubject(): void
    {
        $repository = $this->createSUT();
        $property = BaseYearDeclarationMother::declared('subject-1');
        $company = BaseYearDeclarationMother::declared('subject-1', CarbonFootprintReportSubjectType::Company);
        $repository->add($property);
        $repository->add($company);

        $this->assertEquals($property->id(), $repository->findBySubject(CarbonFootprintReportSubjectType::Property, 'subject-1')?->id());
        $this->assertEquals($company->id(), $repository->findBySubject(CarbonFootprintReportSubjectType::Company, 'subject-1')?->id());
        $this->assertNull($repository->findBySubject(CarbonFootprintReportSubjectType::Property, 'subject-2'));
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();
        $declaration = BaseYearDeclarationMother::declared();
        $repository->add($declaration);

        $repository->remove($declaration);

        $this->assertNull($repository->find($declaration->id()));
    }

    abstract protected function createSUT(): BaseYearDeclarationRepository;
}
