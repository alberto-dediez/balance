<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerProviders;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerProviders\AnswerPropertyCarbonFootprintProvidersRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerProviders\AnswerPropertyCarbonFootprintProvidersService;
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

class AnswerPropertyCarbonFootprintProvidersServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private AnswerPropertyCarbonFootprintProvidersService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new AnswerPropertyCarbonFootprintProvidersService($this->repository, new PropertyCarbonFootprintAuthorizer());
    }

    public function testOwnerAnswersTheProvidersStep(): void
    {
        $footprint = $this->scope3AnsweredFootprint();

        ($this->sut)(new AnswerPropertyCarbonFootprintProvidersRequest($footprint->id()), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::ProvidersAnswered, $footprint->status());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->scope3AnsweredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintProvidersRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->scope3AnsweredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintProvidersRequest($footprint->id()), UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('other-account')));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintProvidersRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintProvidersRequest(PropertyCarbonFootprintId::generate()), $this->ownerUser());
    }

    private function scope3AnsweredFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
