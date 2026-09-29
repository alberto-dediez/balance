<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint\RemoveMitigationActionsOfCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\RemoveMitigationActionsOnCompanyCarbonFootprintRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationActionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveMitigationActionsOnCompanyCarbonFootprintRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRemoved(): void
    {
        [$sut] = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new CompanyCarbonFootprintMitigationPlanStarted(CompanyCarbonFootprintId::generate())));
    }

    public function testRemovesTheActionsOfTheRemovedFootprint(): void
    {
        $id = CompanyCarbonFootprintId::generate();
        [$sut, $repository] = $this->createSUT();
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $id, subjectType: CarbonFootprintReportSubjectType::Company);
        $other = MitigationActionMother::manual(carbonFootprintId: (string) CompanyCarbonFootprintId::generate(), subjectType: CarbonFootprintReportSubjectType::Company);
        $repository->add($action);
        $repository->add($other);

        $sut->handle(new CompanyCarbonFootprintRemoved($id));

        $this->assertTrue($action->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    /** @return array{0: RemoveMitigationActionsOnCompanyCarbonFootprintRemovedSubscriber, 1: InMemoryMitigationActionRepository} */
    private function createSUT(): array
    {
        $repository = new InMemoryMitigationActionRepository();

        return [
            new RemoveMitigationActionsOnCompanyCarbonFootprintRemovedSubscriber(
                new RemoveMitigationActionsOfCarbonFootprintService($repository, new MitigationActionAuthorizer()),
                new FakeLoggedUser(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'))),
            ),
            $repository,
        ];
    }
}
