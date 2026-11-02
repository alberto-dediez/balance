<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerPropertyCarbonFootprintMaterialityServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private AnswerPropertyCarbonFootprintMaterialityService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new AnswerPropertyCarbonFootprintMaterialityService($this->repository, new PropertyCarbonFootprintAuthorizer());
    }

    public function testOwnerAnswersTheMaterialityStep(): void
    {
        $footprint = $this->scope2AnsweredFootprint();

        ($this->sut)(new AnswerPropertyCarbonFootprintMaterialityRequest($footprint->id()), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::MaterialityAnswered, $footprint->status());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->scope2AnsweredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintMaterialityRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->scope2AnsweredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintMaterialityRequest($footprint->id()), UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('other-account')));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintMaterialityRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintMaterialityRequest(PropertyCarbonFootprintId::generate()), $this->ownerUser());
    }

    private function scope2AnsweredFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
