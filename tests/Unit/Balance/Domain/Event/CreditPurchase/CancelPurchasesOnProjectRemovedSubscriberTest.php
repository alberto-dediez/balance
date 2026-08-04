<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\CreditPurchase;

use App\Balance\Domain\Event\CreditPurchase\CancelPurchasesOnProjectRemovedSubscriber;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRegistered;
use App\Balance\Domain\Model\Project\ProjectRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use PHPUnit\Framework\TestCase;

class CancelPurchasesOnProjectRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToProjectRemoved(): void
    {
        $sut = $this->createSUT(CreditPurchaseRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new ProjectRemoved(ProjectId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(CreditPurchaseRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new ProjectRegistered(
            projectId: ProjectId::generate(),
            name: 'name',
            description: 'description',
            location: null,
            gallery: [],
            projectType: null,
            projectStatus: null,
            mitecoId: null,
        )));
    }

    public function testHandleCancelsPendingPurchasesLinkedToProject(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $projectId = ProjectId::generate();

        $pending = CreditPurchaseMother::pending(projectId: $projectId);
        $repository->add($pending);

        $validated = CreditPurchaseMother::validated(projectId: $projectId);
        $repository->add($validated);

        $other = CreditPurchaseMother::random();
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new ProjectRemoved($projectId));

        $this->assertSame(CreditPurchaseStatus::Cancelled, $repository->find($pending->id())?->status());
        $this->assertSame(CreditPurchaseStatus::Validated, $repository->find($validated->id())?->status());
        $this->assertCount(0, $repository->findByFilter(new CreditPurchaseFilter(projectId: $projectId, status: CreditPurchaseStatus::Pending, limit: 0)));
    }

    private function createSUT(CreditPurchaseRepository $creditPurchaseRepository): CancelPurchasesOnProjectRemovedSubscriber
    {
        return new CancelPurchasesOnProjectRemovedSubscriber($creditPurchaseRepository);
    }
}
