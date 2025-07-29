<?php

namespace App\Tests\Unit\Property\Domain\Model\Category;

use App\Property\Domain\Model\Category\CategoryFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Property\Domain\Model\Category\CategoryRepository;

abstract class CategoryRepositoryTest extends KernelTestCase
{
    public function testAddCategoryToRepository()
    {   
        $repository = $this->createSUT();

        $category = CategoryMother::random();

        $this->assertNull($repository->find($category->id()));

        $repository->add($category);

        $recoverCategory = $repository->find($category->id());

        $this->assertNotNull($recoverCategory);
        $this->assertEquals($category->id(), $recoverCategory->id());
    }

    public function testRemoveCategoryToRepository()
    {   
        $repository = $this->createSUT();

        $category = CategoryMother::random();

        $repository->add($category);

        $recoverCategory = $repository->find($category->id());

        $this->assertNotNull($recoverCategory);
        $this->assertEquals($category->id(), $recoverCategory->id());

        $repository->remove($category);

        $this->assertNull($repository->find($category->id()));
    }

    public function testFindByName()
    {
        $repository = $this->createSUT();

        $category = CategoryMother::random();

        $this->assertNull($repository->findByName($category->name()));

        $repository->add($category);

        $recoverCategory = $repository->findByName($category->name());

        $this->assertNotNull($recoverCategory);
        $this->assertEquals($category->id(), $recoverCategory->id());
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::fromName('3 Estrellas'));

        $filter = new CategoryFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CategoryFilter(
            name: '3 Estrellas'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::fromName('3 Estrellas'));

        $filter = new CategoryFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CategoryFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
    {
        $repository = $this->createSUT();

        $category = CategoryMother::random();

        $repository->add($category);

        $recoverCategory = $repository->find($category->id());

        $this->assertNotNull($recoverCategory);

        $recoverCategory->remove();

        $repository->add($category);

        $this->assertNull($repository->find($category->id()));
    }

    abstract protected function createSUT(): CategoryRepository;
}
