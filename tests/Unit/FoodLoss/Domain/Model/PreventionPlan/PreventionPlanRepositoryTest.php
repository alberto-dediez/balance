<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PreventionPlanRepositoryTest extends KernelTestCase
{
    public function testAddPreventionPlanToRepository(): void
    {
        $repository = $this->createSUT();

        $preventionPlan = PreventionPlanMother::registeredPreventionPlan();

        $this->assertNull($repository->find($preventionPlan->id()));

        $repository->add($preventionPlan);

        $recoveredPreventionPlan = $repository->find($preventionPlan->id());

        $this->assertNotNull($recoveredPreventionPlan);
        $this->assertEquals($preventionPlan->id(), $recoveredPreventionPlan->id());
    }

    public function testRemovePreventionPlanFromRepository(): void
    {
        $repository = $this->createSUT();

        $preventionPlan = PreventionPlanMother::registeredPreventionPlan();

        $repository->add($preventionPlan);

        $recoveredPreventionPlan = $repository->find($preventionPlan->id());

        $this->assertNotNull($recoveredPreventionPlan);
        $this->assertEquals($preventionPlan->id(), $recoveredPreventionPlan->id());

        $repository->remove($preventionPlan);

        $this->assertNull($repository->find($preventionPlan->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(PreventionPlanMother::fromName('Plan A'));
        $repository->add(PreventionPlanMother::fromName('Plan B'));
        $repository->add(PreventionPlanMother::fromName('Plan C'));
        $repository->add(PreventionPlanMother::fromName('Plan D'));
        $repository->add(PreventionPlanMother::fromName('Plan E'));
        $repository->add(PreventionPlanMother::fromName('Nombre específico'));

        $filter = new PreventionPlanFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PreventionPlanFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(PreventionPlanMother::fromName('Plan A'));
        $repository->add(PreventionPlanMother::fromName('Plan B'));
        $repository->add(PreventionPlanMother::fromName('Plan C'));
        $repository->add(PreventionPlanMother::fromName('Plan D'));
        $repository->add(PreventionPlanMother::fromName('Plan E'));
        $repository->add(PreventionPlanMother::fromName('Nombre específico'));

        $filter = new PreventionPlanFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $preventionPlan = PreventionPlanMother::registeredPreventionPlan();

        $repository->add($preventionPlan);

        $recoveredPreventionPlan = $repository->find($preventionPlan->id());

        $this->assertNotNull($recoveredPreventionPlan);

        $recoveredPreventionPlan->remove();

        $repository->add($preventionPlan);

        $this->assertNull($repository->find($preventionPlan->id()));
    }

    abstract protected function createSUT(): PreventionPlanRepository;
}
