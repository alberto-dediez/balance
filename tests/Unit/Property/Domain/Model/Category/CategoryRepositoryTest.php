<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Category;

use App\Property\Domain\Model\Category\CategoryFilter;
use App\Property\Domain\Model\Category\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CategoryRepositoryTest extends KernelTestCase
{
    public function testAddCategoryToRepository(): void
    {
        $repository = $this->createSUT();

        $category = CategoryMother::random();

        $this->assertNull($repository->find($category->id()));

        $repository->add($category);

        $recoverCategory = $repository->find($category->id());

        $this->assertNotNull($recoverCategory);
        $this->assertEquals($category->id(), $recoverCategory->id());
    }

    public function testRemoveCategoryToRepository(): void
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

    public function testFindByName(): void
    {
        $repository = $this->createSUT();

        $category = CategoryMother::random();

        $this->assertNull($repository->findByName($category->name()));

        $repository->add($category);

        $recoverCategory = $repository->findByName($category->name());

        $this->assertNotNull($recoverCategory);
        $this->assertEquals($category->id(), $recoverCategory->id());
    }

    public function testFindByFilter(): void
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
            name: '3 Estrellas',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
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
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
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
