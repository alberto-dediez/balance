<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\Register;

use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRegistered;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterSignatureRequestServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $request = new RegisterSignatureRequestRequest(
            documentToSignPath: '/tmp/test.pdf',
            signerName: 'Test Signer',
            signerLocale: Locale::Spanish,
            signerEmail: 'signer@example.com',
            signerPhone: null,
            accountId: new AccountId('account-id-test'),
        );

        // DocumentReference::fromPath checks file readability - use an existing path
        touch('/tmp/test.pdf');

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->signatureRequestId);
        $this->assertEquals(36, strlen((string) $result->signatureRequestId));
        $this->assertNotEmpty($result->tokenPlainValue);

        $persisted = $repository->find($result->signatureRequestId);

        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(SignatureRequestRegistered::class, $lastEvent);
        $this->assertEquals('Test Signer', $lastEvent->signer()->name());
        $this->assertTrue($persisted->id()->equals($lastEvent->signatureRequestId()));
    }

    public function testIsHandle(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterSignatureRequestRequest());

        $this->assertTrue($result);
    }

    private function createSUT(SignatureRequestRepository $repository): RegisterSignatureRequestService
    {
        return new RegisterSignatureRequestService(
            signatureRequestRepository: $repository,
            authorizer: new SignatureRequestAuthorizer(),
        );
    }
}
