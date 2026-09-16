<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Remove\RemoveCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\RemoveCompanyCarbonFootprintsOnCompanyRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveCompanyCarbonFootprintsOnCompanyRemovedSubscriberTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
    }

    public function testIsSubscribedToCompanyRemoved(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo(new CompanyRemoved(CompanyId::generate())));
    }

    public function testRemovesEveryFootprintOfTheCompanyAndLeavesTheOthersAlone(): void
    {
        $companyId = CompanyId::generate();

        $first = $this->givenAFootprint($companyId, 2023);
        $second = $this->givenAFootprint($companyId, 2024);
        $other = $this->givenAFootprint(CompanyId::generate(), 2024);

        $this->createSUT()->handle(new CompanyRemoved($companyId));

        $this->assertTrue($first->isRemoved());
        $this->assertTrue($second->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    public function testAnAdminWithoutImpersonationCanRemoveTheFootprintsOfTheCompany(): void
    {
        $companyId = CompanyId::generate();
        $footprint = $this->givenAFootprint($companyId, 2024);

        $this->createSUT(UserMother::adminUser())->handle(new CompanyRemoved($companyId));

        $this->assertTrue($footprint->isRemoved());
    }

    private function givenAFootprint(CompanyId $companyId, int $year): \App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from(
            (string) $companyId,
            (string) CompanyCarbonFootprintId::generate(),
            'account-id-test',
            $year,
        );
        $this->repository->add($footprint);

        return $footprint;
    }

    private function createSUT(?User $user = null): RemoveCompanyCarbonFootprintsOnCompanyRemovedSubscriber
    {
        return new RemoveCompanyCarbonFootprintsOnCompanyRemovedSubscriber(
            $this->repository,
            new RemoveCompanyCarbonFootprintService($this->repository, new CompanyCarbonFootprintAuthorizer()),
            new FakeLoggedUser($user ?? UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'))),
        );
    }
}
