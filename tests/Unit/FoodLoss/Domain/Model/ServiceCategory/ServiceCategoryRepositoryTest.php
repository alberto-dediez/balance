<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory;

use App\Shared\Domain\Model\Locale\Locale;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ServiceCategoryRepositoryTest extends KernelTestCase
{
    public function testAddServiceCategoryToRepository()
    {   
        $repository = $this->createSUT();

        $serviceCategory = ServiceCategoryMother::registeredServiceCategory();

        $this->assertNull($repository->find($serviceCategory->id()));

        $repository->add($serviceCategory);

        $recoveredServiceCategory = $repository->find($serviceCategory->id());

        $this->assertNotNull($recoveredServiceCategory);
        $this->assertEquals($serviceCategory->id(), $recoveredServiceCategory->id());
    }

    public function testRemoveServiceCategoryFromRepository()
    {   
        $repository = $this->createSUT();

        $serviceCategory = ServiceCategoryMother::registeredServiceCategory();

        $repository->add($serviceCategory);

        $recoveredServiceCategory = $repository->find($serviceCategory->id());

        $this->assertNotNull($recoveredServiceCategory);
        $this->assertEquals($serviceCategory->id(), $recoveredServiceCategory->id());

        $repository->remove($serviceCategory);

        $this->assertNull($repository->find($serviceCategory->id()));
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::fromName('Categoría de servicio 1'));

        $filter = new ServiceCategoryFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ServiceCategoryFilter(
            name: 'Categoría de servicio 1'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::fromName('Categoría de servicio 1'));

        $filter = new ServiceCategoryFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ServiceCategoryFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
    {
        $repository = $this->createSUT();

        $serviceCategory = ServiceCategoryMother::registeredServiceCategory();

        $repository->add($serviceCategory);

        $recoveredServiceCategory = $repository->find($serviceCategory->id());

        $this->assertNotNull($recoveredServiceCategory);

        $recoveredServiceCategory->remove();

        $repository->add($serviceCategory);

        $this->assertNull($repository->find($serviceCategory->id()));
    }

    public function testFindRecoverWithTranslation()
    {
        $repository = $this->createSUT(Locale::English);

        $serviceCategory = ServiceCategoryMother::registeredServiceCategory();

        $this->assertNull($repository->find($serviceCategory->id()));

        $serviceCategory->translate('translated-name', Locale::English);

        $repository->add($serviceCategory);

        $recoveredServiceCategory = $repository->find($serviceCategory->id());

        $this->assertNotNull($recoveredServiceCategory);
        $this->assertEquals($serviceCategory->id(), $recoveredServiceCategory->id());

        $this->assertEquals('translated-name', $recoveredServiceCategory->name());
    }

    public function testFindByFilterWithNameTranslated()
    {
        $repository = $this->createSUT(Locale::English);

        $serviceCategory = ServiceCategoryMother::registeredServiceCategory();

        $this->assertNull($repository->find($serviceCategory->id()));

        $serviceCategory->translate('translated-name', Locale::English);

        $repository->add($serviceCategory);

        $recoveredServiceCategories = $repository->findByFilter(new ServiceCategoryFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredServiceCategories);
        $this->assertEquals($serviceCategory->id(), $recoveredServiceCategories[0]->id());

        $this->assertEquals('translated-name', $recoveredServiceCategories[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): ServiceCategoryRepository;
}
