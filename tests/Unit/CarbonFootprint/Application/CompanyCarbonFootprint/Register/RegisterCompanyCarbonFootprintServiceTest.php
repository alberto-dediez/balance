<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\Register;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAlreadyRegisteredException;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Register\RegisterCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Register\RegisterCompanyCarbonFootprintService;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterCompanyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryCompanyRepository $propertyRepository;
    private RegisterCompanyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = CompanyRepositoryMother::inMemory();

        $this->sut = new RegisterCompanyCarbonFootprintService(
            $this->repository,
            $this->propertyRepository,
            new CompanyCarbonFootprintAuthorizer(),
        );
    }

    public function testRegistersTheFootprint(): void
    {
        $companyId = $this->givenACompany('account-id-test');

        $response = ($this->sut)($this->request($companyId, 'account-id-test'), $this->ownerUser());

        $footprint = $this->repository->find($response->companyCarbonFootprintId);
        $this->assertNotNull($footprint);
        $this->assertSame(2025, $footprint->year());
        $this->assertSame((string) $companyId, (string) $footprint->companyId());
    }

    public function testCompanyFromAnotherAccountIsRejected(): void
    {
        $companyId = $this->givenACompany('other-account-id');

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request($companyId, 'account-id-test'), $this->ownerUser());
    }

    public function testUnknownCompanyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(CompanyId::generate(), 'account-id-test'), $this->ownerUser());
    }

    public function testDuplicatedYearIsRejected(): void
    {
        $companyId = $this->givenACompany('account-id-test');
        ($this->sut)($this->request($companyId, 'account-id-test'), $this->ownerUser());

        $this->expectException(CompanyCarbonFootprintAlreadyRegisteredException::class);

        ($this->sut)($this->request($companyId, 'account-id-test'), $this->ownerUser());
    }

    public function testDuplicatedYearIsAllowedAfterRemovingTheFirstOne(): void
    {
        $companyId = $this->givenACompany('account-id-test');
        $first = ($this->sut)($this->request($companyId, 'account-id-test'), $this->ownerUser());

        $footprint = $this->repository->find($first->companyCarbonFootprintId);
        $this->assertNotNull($footprint);
        $footprint->remove();

        $second = ($this->sut)($this->request($companyId, 'account-id-test'), $this->ownerUser());

        $this->assertNotNull($this->repository->find($second->companyCarbonFootprintId));
    }

    public function testPlainUserIsDenied(): void
    {
        $companyId = $this->givenACompany('account-id-test');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)($this->request($companyId, 'account-id-test'), UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test')));
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled($this->request(CompanyId::generate(), 'account-id-test')));
    }

    private function request(CompanyId $companyId, string $accountId): RegisterCompanyCarbonFootprintRequest
    {
        return new RegisterCompanyCarbonFootprintRequest(
            companyId: $companyId,
            year: 2025,
            accountId: new AccountId($accountId),
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function givenACompany(string $accountId): CompanyId
    {
        $company = CompanyMother::fromNameAndAccountAndId('Hotel', $accountId, (string) CompanyId::generate());
        $this->propertyRepository->add($company);

        return $company->id();
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
