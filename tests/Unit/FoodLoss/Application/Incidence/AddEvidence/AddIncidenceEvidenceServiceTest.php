<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Incidence\AddEvidence;

use App\FoodLoss\Application\Incidence\AddEvidence\AddIncidenceEvidenceRequest;
use App\FoodLoss\Application\Incidence\AddEvidence\AddIncidenceEvidenceService;
use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Application\Incidence\IncidenceEvidenceUploader;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceAdded;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AddIncidenceEvidenceServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = IncidenceRepositoryMother::inMemory();
        $incidence = IncidenceMother::from(name: 'Test incidence', id: 'incidence-test-id', accountId: 'account-id-test');
        $repository->add($incidence);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $request = new AddIncidenceEvidenceRequest(
            incidenceId: $incidence->id(),
            accountId: new AccountId('account-id-test'),
            temporaryFilePath: '/tmp/evidence.jpg',
            fileExtension: 'jpg',
            originalName: 'evidence.jpg',
            mimeType: 'image/jpeg',
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->evidenceId);
        $this->assertEquals(36, strlen((string) $result->evidenceId));
        $this->assertStringStartsWith('incidence_evidences/account-id-test/', $result->path);
        $this->assertStringEndsWith('.jpg', $result->path);

        $persisted = $repository->find($incidence->id());
        $this->assertNotNull($persisted);
        $this->assertCount(1, $persisted->evidences()->toArray());

        $evidence = $persisted->evidences()->toArray()[0];
        $this->assertEquals('evidence.jpg', $evidence->originalName());
        $this->assertEquals('image/jpeg', $evidence->mimeType());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof IncidenceEvidenceAdded);
        $this->assertTrue($incidence->id()->equals($lastEvent->incidenceId()));
        $this->assertEquals('evidence.jpg', $lastEvent->originalName());
        $this->assertEquals('image/jpeg', $lastEvent->mimeType());
    }

    public function testIsHandled(): void
    {
        $repository = IncidenceRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $incidence = IncidenceMother::random();

        $result = $sut->isHandled(new AddIncidenceEvidenceRequest(
            incidenceId: $incidence->id(),
            accountId: new AccountId('account-id-test'),
            temporaryFilePath: '/tmp/file.pdf',
            fileExtension: 'pdf',
            originalName: 'file.pdf',
            mimeType: 'application/pdf',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(IncidenceRepository $repository): AddIncidenceEvidenceService
    {
        $uploader = new class implements IncidenceEvidenceUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId, \App\FoodLoss\Domain\Model\Incidence\IncidenceId $incidenceId): string
            {
                return 'incidence_evidences/' . $accountId . '/test.' . $extension;
            }
        };

        return new AddIncidenceEvidenceService(
            incidenceRepository: $repository,
            authorizer: new IncidenceAuthorizer(),
            uploader: $uploader,
        );
    }
}
