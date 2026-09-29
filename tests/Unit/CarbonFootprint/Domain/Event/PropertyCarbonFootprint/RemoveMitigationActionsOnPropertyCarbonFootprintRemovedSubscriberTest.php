<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint\RemoveMitigationActionsOfCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RemoveMitigationActionsOnPropertyCarbonFootprintRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationActionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveMitigationActionsOnPropertyCarbonFootprintRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRemoved(): void
    {
        [$sut] = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintMitigationPlanStarted(PropertyCarbonFootprintId::generate())));
    }

    public function testRemovesTheActionsOfTheRemovedFootprint(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        [$sut, $repository] = $this->createSUT();
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $id, subjectType: CarbonFootprintReportSubjectType::Property);
        $other = MitigationActionMother::manual(carbonFootprintId: (string) PropertyCarbonFootprintId::generate(), subjectType: CarbonFootprintReportSubjectType::Property);
        $repository->add($action);
        $repository->add($other);

        $sut->handle(new PropertyCarbonFootprintRemoved($id));

        $this->assertTrue($action->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    /** @return array{0: RemoveMitigationActionsOnPropertyCarbonFootprintRemovedSubscriber, 1: InMemoryMitigationActionRepository} */
    private function createSUT(): array
    {
        $repository = new InMemoryMitigationActionRepository();

        return [
            new RemoveMitigationActionsOnPropertyCarbonFootprintRemovedSubscriber(
                new RemoveMitigationActionsOfCarbonFootprintService($repository, new MitigationActionAuthorizer()),
                new FakeLoggedUser(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'))),
            ),
            $repository,
        ];
    }
}
