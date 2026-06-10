<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Alert;

use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AlertRepositoryTest extends KernelTestCase
{
    public function testAddAlertToRepository(): void
    {
        $repository = $this->createSUT();

        $alert = AlertMother::detectedAlert();

        $this->assertNull($repository->find($alert->id()));

        $repository->add($alert);

        $recovered = $repository->find($alert->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($alert->id(), $recovered->id());
    }

    public function testSaveUpdatesAlert(): void
    {
        $repository = $this->createSUT();

        $alert = AlertMother::detectedAlert();
        $repository->add($alert);

        $alert->acknowledge();
        $repository->add($alert);

        $recovered = $repository->find($alert->id());

        $this->assertNotNull($recovered);
        $this->assertEquals(AlertStatus::Acknowledged, $recovered->status());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(AlertMother::random());
        $repository->add(AlertMother::random());
        $repository->add(AlertMother::fromIdAndAccount('alert-1', 'specific-account'));

        $filter = new AlertFilter(status: null);
        $allResults = $repository->findByFilter($filter);

        $this->assertCount(3, $allResults);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(AlertMother::random());
        $repository->add(AlertMother::random());
        $repository->add(AlertMother::random());

        $filter = new AlertFilter(status: null, limit: 0);
        $results = $repository->findByFilter($filter);

        $this->assertCount(3, $results);
    }

    abstract protected function createSUT(): AlertRepository;
}
