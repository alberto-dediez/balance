<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\Remove;

use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRemoved;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveSignatureRequestServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $repository->add($signatureRequest);

        $sut = $this->createSUT($repository);

        $sut(new RemoveSignatureRequestRequest($signatureRequest->id()), UserMother::adminUser());

        $this->assertNull($repository->find($signatureRequest->id()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(SignatureRequestRemoved::class, $lastEvent);
        $this->assertTrue($signatureRequest->id()->equals($lastEvent->signatureRequestId()));
    }

    public function testIsHandle(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $repository->add($signatureRequest);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RemoveSignatureRequestRequest($signatureRequest->id()));

        $this->assertTrue($result);
    }

    private function createSUT(SignatureRequestRepository $repository): RemoveSignatureRequestService
    {
        return new RemoveSignatureRequestService(
            repository: $repository,
            authorizer: new SignatureRequestAuthorizer(),
        );
    }
}
