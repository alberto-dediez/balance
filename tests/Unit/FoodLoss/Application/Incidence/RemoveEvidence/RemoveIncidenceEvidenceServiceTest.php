<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Incidence\RemoveEvidence;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Application\Incidence\IncidenceEvidenceFileRemover;
use App\FoodLoss\Application\Incidence\RemoveEvidence\RemoveIncidenceEvidenceRequest;
use App\FoodLoss\Application\Incidence\RemoveEvidence\RemoveIncidenceEvidenceService;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceRemoved;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveIncidenceEvidenceServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $evidencePath = 'incidence_evidences/account-id-test/test.jpg';

        $repository = IncidenceRepositoryMother::inMemory();
        $incidence = IncidenceMother::from(name: 'Test incidence', id: 'incidence-test-id', accountId: 'account-id-test');

        $evidenceId = IncidenceEvidenceId::generate();
        $incidence->addEvidence(
            evidenceId: $evidenceId,
            path: $evidencePath,
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
        );
        $repository->add($incidence);

        $fileRemover = $this->createMock(IncidenceEvidenceFileRemover::class);
        $fileRemover->expects($this->once())->method('remove')->with($evidencePath);

        $sut = $this->createSUT($repository, $fileRemover);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $sut(new RemoveIncidenceEvidenceRequest(
            incidenceId: $incidence->id(),
            evidenceId: $evidenceId,
        ), $user);

        $this->assertTrue($evidenceId->equals($result->evidenceId));

        $persisted = $repository->find($incidence->id());
        $this->assertNotNull($persisted);
        $this->assertCount(0, $persisted->evidences()->toArray());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof IncidenceEvidenceRemoved);
        $this->assertTrue($incidence->id()->equals($lastEvent->incidenceId()));
        $this->assertTrue($evidenceId->equals($lastEvent->evidenceId()));
    }

    public function testRemovingNonExistentEvidenceDoesNothing(): void
    {
        $repository = IncidenceRepositoryMother::inMemory();
        $incidence = IncidenceMother::from(name: 'Test incidence', id: 'incidence-test-id', accountId: 'account-id-test');
        $repository->add($incidence);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $nonExistentEvidenceId = IncidenceEvidenceId::generate();
        $result = $sut(new RemoveIncidenceEvidenceRequest(
            incidenceId: $incidence->id(),
            evidenceId: $nonExistentEvidenceId,
        ), $user);

        $this->assertTrue($nonExistentEvidenceId->equals($result->evidenceId));
        $this->assertNotInstanceOf(IncidenceEvidenceRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testIsHandled(): void
    {
        $repository = IncidenceRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $incidence = IncidenceMother::random();

        $result = $sut->isHandled(new RemoveIncidenceEvidenceRequest(
            incidenceId: $incidence->id(),
            evidenceId: IncidenceEvidenceId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(IncidenceRepository $repository, ?IncidenceEvidenceFileRemover $fileRemover = null): RemoveIncidenceEvidenceService
    {
        return new RemoveIncidenceEvidenceService(
            incidenceRepository: $repository,
            authorizer: new IncidenceAuthorizer(),
            fileRemover: $fileRemover ?? $this->createMock(IncidenceEvidenceFileRemover::class),
        );
    }
}
