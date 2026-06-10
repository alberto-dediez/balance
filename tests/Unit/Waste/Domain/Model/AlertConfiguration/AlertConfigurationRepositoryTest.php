<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertConfiguration;

use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationFilter;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AlertConfigurationRepositoryTest extends KernelTestCase
{
    public function testAddAlertConfigurationToRepository(): void
    {
        $repository = $this->createSUT();

        $config = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertNull($repository->find($config->id()));

        $repository->add($config);

        $recovered = $repository->find($config->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($config->id(), $recovered->id());
    }

    public function testRemoveAlertConfiguration(): void
    {
        $repository = $this->createSUT();

        $config = AlertConfigurationMother::registeredAlertConfiguration();
        $repository->add($config);

        $recovered = $repository->find($config->id());
        $this->assertNotNull($recovered);

        $repository->remove($config);
        $this->assertNull($repository->find($config->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(AlertConfigurationMother::random());
        $repository->add(AlertConfigurationMother::random());
        $repository->add(AlertConfigurationMother::fromIdAndAccount('config-1', 'specific-account'));

        $filter = new AlertConfigurationFilter();
        $all = $repository->findByFilter($filter);

        $this->assertCount(3, $all);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(AlertConfigurationMother::random());
        $repository->add(AlertConfigurationMother::random());
        $repository->add(AlertConfigurationMother::random());

        $filter = new AlertConfigurationFilter(limit: 0);
        $results = $repository->findByFilter($filter);

        $this->assertCount(3, $results);
    }

    abstract protected function createSUT(): AlertConfigurationRepository;
}
