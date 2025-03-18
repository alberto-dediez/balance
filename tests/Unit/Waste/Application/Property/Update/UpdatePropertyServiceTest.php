<?php

namespace App\Tests\Unit\Waste\Application\Property\Update;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Property\PropertyUpdated;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Application\Property\Update\UpdatePropertyRequest;
use App\Waste\Application\Property\Update\UpdatePropertyService;
use App\Waste\Application\Property\Update\UpdatePropertyResponse;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;

class UpdatePropertyServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $request = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'Hotel',
            accountId: new AccountId('6666')
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdatePropertyResponse);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertTrue($propertyPersisted instanceof Property);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyUpdated);
        $this->assertEquals('Hotel', $lastEvent->name());
        $this->assertEquals('6666', (string) $lastEvent->accountId());
        $this->assertTrue($propertyPersisted->id()->equals($lastEvent->propertyId()));
    }

    private function createSUT($repository)
    {
        return new UpdatePropertyService($repository);
    }
}