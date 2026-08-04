<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectDeselected;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectSelected;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AccountProjectTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testSelectPublishesAccountProjectSelected(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $accountProject = new AccountProject(id: AccountProjectId::generate());
        $accountProject->select($accountId, $projectId);

        $this->assertNotNull($accountProject->accountId());
        $this->assertNotNull($accountProject->projectId());
        $this->assertTrue($accountId->equals($accountProject->accountId()));
        $this->assertTrue($projectId->equals($accountProject->projectId()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(AccountProjectSelected::class, $lastEvent);
        $this->assertTrue($accountId->equals($lastEvent->accountId()));
        $this->assertTrue($projectId->equals($lastEvent->projectId()));
    }

    public function testDeselectPublishesAccountProjectDeselected(): void
    {
        $accountProject = AccountProjectMother::random();

        $accountProject->deselect();

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(AccountProjectDeselected::class, $lastEvent);
        $this->assertTrue($accountProject->id()->equals($lastEvent->accountProjectId()));
    }

    public function testRebuildReconstructsState(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();
        $id = AccountProjectId::generate();

        $selected = new AccountProjectSelected(
            accountProjectId: $id,
            accountId: $accountId,
            projectId: $projectId,
        );

        $accountProject = new AccountProject(id: $id, events: [$selected]);
        $accountProject->rebuild();

        $this->assertNotNull($accountProject->accountId());
        $this->assertNotNull($accountProject->projectId());
        $this->assertTrue($accountId->equals($accountProject->accountId()));
        $this->assertTrue($projectId->equals($accountProject->projectId()));
    }
}
