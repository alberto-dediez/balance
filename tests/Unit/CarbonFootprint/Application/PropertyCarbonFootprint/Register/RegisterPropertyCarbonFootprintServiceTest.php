<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\Register;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAlreadyRegisteredException;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Register\RegisterPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Register\RegisterPropertyCarbonFootprintService;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterPropertyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private RegisterPropertyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();

        $this->sut = new RegisterPropertyCarbonFootprintService(
            $this->repository,
            $this->propertyRepository,
            new PropertyCarbonFootprintAuthorizer(),
        );
    }

    public function testRegistersTheFootprint(): void
    {
        $propertyId = $this->givenAProperty('account-id-test');

        $response = ($this->sut)($this->request($propertyId, 'account-id-test'), $this->ownerUser());

        $footprint = $this->repository->find($response->propertyCarbonFootprintId);
        $this->assertNotNull($footprint);
        $this->assertSame(2025, $footprint->year());
        $this->assertSame((string) $propertyId, (string) $footprint->propertyId());
    }

    public function testPropertyFromAnotherAccountIsRejected(): void
    {
        $propertyId = $this->givenAProperty('other-account-id');

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request($propertyId, 'account-id-test'), $this->ownerUser());
    }

    public function testUnknownPropertyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(PropertyId::generate(), 'account-id-test'), $this->ownerUser());
    }

    public function testDuplicatedYearIsRejected(): void
    {
        $propertyId = $this->givenAProperty('account-id-test');
        ($this->sut)($this->request($propertyId, 'account-id-test'), $this->ownerUser());

        $this->expectException(PropertyCarbonFootprintAlreadyRegisteredException::class);

        ($this->sut)($this->request($propertyId, 'account-id-test'), $this->ownerUser());
    }

    public function testDuplicatedYearIsAllowedAfterRemovingTheFirstOne(): void
    {
        $propertyId = $this->givenAProperty('account-id-test');
        $first = ($this->sut)($this->request($propertyId, 'account-id-test'), $this->ownerUser());

        $footprint = $this->repository->find($first->propertyCarbonFootprintId);
        $this->assertNotNull($footprint);
        $footprint->remove();

        $second = ($this->sut)($this->request($propertyId, 'account-id-test'), $this->ownerUser());

        $this->assertNotNull($this->repository->find($second->propertyCarbonFootprintId));
    }

    public function testPlainUserIsDenied(): void
    {
        $propertyId = $this->givenAProperty('account-id-test');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)($this->request($propertyId, 'account-id-test'), UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test')));
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled($this->request(PropertyId::generate(), 'account-id-test')));
    }

    private function request(PropertyId $propertyId, string $accountId): RegisterPropertyCarbonFootprintRequest
    {
        return new RegisterPropertyCarbonFootprintRequest(
            propertyId: $propertyId,
            year: 2025,
            accountId: new AccountId($accountId),
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function givenAProperty(string $accountId): PropertyId
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', $accountId, (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        return $property->id();
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
