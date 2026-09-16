<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RemovePropertyCarbonFootprintsOnPropertyRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemovePropertyCarbonFootprintsOnPropertyRemovedSubscriberTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
    }

    public function testIsSubscribedToPropertyRemoved(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo(new PropertyRemoved(PropertyId::generate())));
    }

    public function testRemovesEveryFootprintOfThePropertyAndLeavesTheOthersAlone(): void
    {
        $propertyId = PropertyId::generate();

        $first = $this->givenAFootprint($propertyId, 2023);
        $second = $this->givenAFootprint($propertyId, 2024);
        $other = $this->givenAFootprint(PropertyId::generate(), 2024);

        $this->createSUT()->handle(new PropertyRemoved($propertyId));

        $this->assertTrue($first->isRemoved());
        $this->assertTrue($second->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    public function testAnAdminWithoutImpersonationCanRemoveTheFootprintsOfTheProperty(): void
    {
        $propertyId = PropertyId::generate();
        $footprint = $this->givenAFootprint($propertyId, 2024);

        $this->createSUT(UserMother::adminUser())->handle(new PropertyRemoved($propertyId));

        $this->assertTrue($footprint->isRemoved());
    }

    private function givenAFootprint(PropertyId $propertyId, int $year): \App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::from(
            (string) $propertyId,
            (string) PropertyCarbonFootprintId::generate(),
            'account-id-test',
            $year,
        );
        $this->repository->add($footprint);

        return $footprint;
    }

    private function createSUT(?User $user = null): RemovePropertyCarbonFootprintsOnPropertyRemovedSubscriber
    {
        return new RemovePropertyCarbonFootprintsOnPropertyRemovedSubscriber(
            $this->repository,
            new RemovePropertyCarbonFootprintService($this->repository, new PropertyCarbonFootprintAuthorizer()),
            new FakeLoggedUser($user ?? UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'))),
        );
    }
}
