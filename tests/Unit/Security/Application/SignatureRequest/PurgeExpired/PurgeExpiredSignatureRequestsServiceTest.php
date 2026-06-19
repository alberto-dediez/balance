<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\PurgeExpired;

use App\Security\Application\SignatureRequest\PurgeExpired\PurgeExpiredSignatureRequestsRequest;
use App\Security\Application\SignatureRequest\PurgeExpired\PurgeExpiredSignatureRequestsService;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestExpired;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PurgeExpiredSignatureRequestsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testItExpiresSignatureRequestsPastTheirExpirationDate(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $expired = SignatureRequestMother::registeredSignatureRequest();
        $notExpired = SignatureRequestMother::registeredSignatureRequest();

        $repository->add($expired);
        $repository->add($notExpired);

        $past = new \DateTimeImmutable('+30 days');

        $sut = $this->createSUT($repository);
        $response = $sut(new PurgeExpiredSignatureRequestsRequest($past), UserMother::adminUser());

        $this->assertSame(2, $response->expired);

        $this->assertSame(SignatureRequestStatus::Expired, $repository->find($expired->id())?->status());
        $this->assertSame(SignatureRequestStatus::Expired, $repository->find($notExpired->id())?->status());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SignatureRequestExpired::class, $lastEvent);
    }

    public function testItDoesNotExpireSignatureRequestsNotYetDue(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $repository->add($signatureRequest);

        $now = new \DateTimeImmutable('NOW');

        $sut = $this->createSUT($repository);
        $response = $sut(new PurgeExpiredSignatureRequestsRequest($now), UserMother::adminUser());

        $this->assertSame(0, $response->expired);
        $this->assertSame(SignatureRequestStatus::Pending, $repository->find($signatureRequest->id())?->status());
    }

    public function testItDoesNotExpireAlreadyExpiredRequests(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->expire();
        $repository->add($signatureRequest);

        $past = new \DateTimeImmutable('+30 days');

        $sut = $this->createSUT($repository);
        $response = $sut(new PurgeExpiredSignatureRequestsRequest($past), UserMother::adminUser());

        $this->assertSame(0, $response->expired);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(SignatureRequestRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new PurgeExpiredSignatureRequestsRequest()));
    }

    private function createSUT(SignatureRequestRepository $repository): PurgeExpiredSignatureRequestsService
    {
        return new PurgeExpiredSignatureRequestsService(repository: $repository);
    }
}
