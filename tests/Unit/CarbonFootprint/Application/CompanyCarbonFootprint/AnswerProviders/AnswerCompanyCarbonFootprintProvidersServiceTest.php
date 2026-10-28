<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerProviders;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerProviders\AnswerCompanyCarbonFootprintProvidersRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerProviders\AnswerCompanyCarbonFootprintProvidersService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerCompanyCarbonFootprintProvidersServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private AnswerCompanyCarbonFootprintProvidersService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new AnswerCompanyCarbonFootprintProvidersService($this->repository, new CompanyCarbonFootprintAuthorizer());
    }

    public function testOwnerAnswersTheProvidersStep(): void
    {
        $footprint = $this->scope3AnsweredFootprint();

        ($this->sut)(new AnswerCompanyCarbonFootprintProvidersRequest($footprint->id()), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::ProvidersAnswered, $footprint->status());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->scope3AnsweredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerCompanyCarbonFootprintProvidersRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->scope3AnsweredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerCompanyCarbonFootprintProvidersRequest($footprint->id()), UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('other-account')));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        ($this->sut)(new AnswerCompanyCarbonFootprintProvidersRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerCompanyCarbonFootprintProvidersRequest(CompanyCarbonFootprintId::generate()), $this->ownerUser());
    }

    private function scope3AnsweredFootprint(): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
