<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\AccountProject;

use App\Balance\Domain\Event\AccountProject\DeselectProjectsOnAccountRemovedSubscriber;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use PHPUnit\Framework\TestCase;

class DeselectProjectsOnAccountRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToAccountRemoved(): void
    {
        $sut = $this->createSUT(AccountProjectRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new AccountRemoved(AccountId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(AccountProjectRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new AccountRegistered(
            accountId: AccountId::generate(),
            name: 'account-name',
            active: true,
        )));
    }

    public function testHandleRemovesAccountProjectsLinkedToAccount(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $accountProject = AccountProjectMother::from($accountId, ProjectId::generate());
        $repository->add($accountProject);

        $other = AccountProjectMother::random();
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new AccountRemoved($accountId));

        $this->assertCount(0, $repository->findByFilter(new AccountProjectFilter(accountId: $accountId, limit: 0)));
        $this->assertNotNull($repository->find($other->id()));
    }

    private function createSUT(AccountProjectRepository $accountProjectRepository): DeselectProjectsOnAccountRemovedSubscriber
    {
        return new DeselectProjectsOnAccountRemovedSubscriber($accountProjectRepository);
    }
}
