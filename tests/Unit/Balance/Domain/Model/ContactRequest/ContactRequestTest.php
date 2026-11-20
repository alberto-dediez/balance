<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyResolvedException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestContactDataUpdated;
use App\Balance\Domain\Model\ContactRequest\ContactRequestDiscarded;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestManaged;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRegistered;
use App\Balance\Domain\Model\ContactRequest\ContactRequestStatus;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ContactRequestTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterLeavesRequestPendingAndPublishesEvent(): void
    {
        $accountId = AccountId::generate();
        $requestedAt = new \DateTimeImmutable('2026-11-20 10:00:00');

        $contactRequest = ContactRequestMother::pending(
            accountId: $accountId,
            origin: ProjectContributionOrigin::TripCalculation,
            originId: 'calc-1',
            requestedAt: $requestedAt,
        );

        $this->assertTrue($contactRequest->isPending());
        $this->assertSame(ContactRequestStatus::Pending, $contactRequest->status());
        $this->assertNotNull($contactRequest->accountId());
        $this->assertTrue($accountId->equals($contactRequest->accountId()));
        $this->assertSame(ProjectContributionOrigin::TripCalculation, $contactRequest->origin());
        $this->assertSame('calc-1', $contactRequest->originId());
        $this->assertSame('Ana García', $contactRequest->contactName());
        $this->assertSame('ana@example.com', $contactRequest->contactEmail());
        $this->assertEquals($requestedAt, $contactRequest->requestedAt());
        $this->assertNull($contactRequest->resolvedAt());
        $this->assertNull($contactRequest->resolvedBy());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(ContactRequestRegistered::class, $lastEvent);
        $this->assertTrue($contactRequest->id()->equals($lastEvent->contactRequestId()));
    }

    public function testRegisterWithEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactRequestMother::pending(contactName: '');
    }

    public function testRegisterWithEmptyEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactRequestMother::pending(contactEmail: '');
    }

    public function testRegisterWithMalformedEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactRequestMother::pending(contactEmail: 'not-an-email');
    }

    public function testRegisterWithNegativeFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactRequestMother::pending(co2eqKg: -1.0);
    }

    public function testMarkAsManaged(): void
    {
        $contactRequest = ContactRequestMother::pending();
        $userId = UserId::generate();
        $at = new \DateTimeImmutable('2026-11-21 09:00:00');

        $contactRequest->markAsManaged($userId, $at);

        $this->assertSame(ContactRequestStatus::Managed, $contactRequest->status());
        $this->assertFalse($contactRequest->isPending());
        $this->assertEquals($at, $contactRequest->resolvedAt());
        $this->assertNotNull($contactRequest->resolvedBy());
        $this->assertTrue($userId->equals($contactRequest->resolvedBy()));
        $this->assertInstanceOf(ContactRequestManaged::class, $this->spySubscriber->lastEvent());
    }

    public function testDiscard(): void
    {
        $contactRequest = ContactRequestMother::pending();
        $userId = UserId::generate();
        $at = new \DateTimeImmutable('2026-11-21 09:00:00');

        $contactRequest->discard($userId, $at);

        $this->assertSame(ContactRequestStatus::Discarded, $contactRequest->status());
        $this->assertEquals($at, $contactRequest->resolvedAt());
        $this->assertNotNull($contactRequest->resolvedBy());
        $this->assertTrue($userId->equals($contactRequest->resolvedBy()));
        $this->assertInstanceOf(ContactRequestDiscarded::class, $this->spySubscriber->lastEvent());
    }

    public function testMarkingAsManagedAManagedRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::managed();

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $contactRequest->markAsManaged(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testDiscardingAManagedRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::managed();

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $contactRequest->discard(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testMarkingAsManagedADiscardedRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::discarded();

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $contactRequest->markAsManaged(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testDiscardingADiscardedRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::discarded();

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $contactRequest->discard(UserId::generate(), new \DateTimeImmutable('NOW'));
    }

    public function testUpdateContactDataOnPendingRequest(): void
    {
        $requestedAt = new \DateTimeImmutable('2026-11-20 10:00:00');
        $contactRequest = ContactRequestMother::pending(originId: 'calc-1', co2eqKg: 80.0, requestedAt: $requestedAt);

        $contactRequest->updateContactData(
            contactName: 'Luis Pérez',
            contactEmail: 'luis@example.com',
            contactPhone: '611111111',
            contactCountry: 'FRA',
            contactIsCompany: true,
            contactCompanyName: 'Acme SL',
            contactVatNumber: 'B12345678',
            comment: 'Llamadme por la tarde',
            locale: Locale::English,
        );

        $this->assertSame('Luis Pérez', $contactRequest->contactName());
        $this->assertSame('luis@example.com', $contactRequest->contactEmail());
        $this->assertSame('611111111', $contactRequest->contactPhone());
        $this->assertSame('FRA', $contactRequest->contactCountry());
        $this->assertTrue($contactRequest->contactIsCompany());
        $this->assertSame('Acme SL', $contactRequest->contactCompanyName());
        $this->assertSame('B12345678', $contactRequest->contactVatNumber());
        $this->assertSame('Llamadme por la tarde', $contactRequest->comment());
        $this->assertSame(Locale::English, $contactRequest->locale());
        $this->assertEquals($requestedAt, $contactRequest->requestedAt());
        $this->assertSame('calc-1', $contactRequest->originId());
        $this->assertSame(80.0, $contactRequest->co2eqKg());
        $this->assertInstanceOf(ContactRequestContactDataUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUpdateContactDataOnResolvedRequestThrows(): void
    {
        $contactRequest = ContactRequestMother::managed();

        $this->expectException(ContactRequestAlreadyResolvedException::class);

        $contactRequest->updateContactData('Luis', 'luis@example.com', null, 'ESP', false, null, null, null, Locale::Spanish);
    }

    public function testUpdateContactDataWithMalformedEmailThrows(): void
    {
        $contactRequest = ContactRequestMother::pending();

        $this->expectException(\InvalidArgumentException::class);

        $contactRequest->updateContactData('Luis', 'not-an-email', null, 'ESP', false, null, null, null, Locale::Spanish);
    }

    public function testRebuildReconstructsState(): void
    {
        $id = ContactRequestId::generate();
        $accountId = AccountId::generate();
        $userId = UserId::generate();

        $registered = new ContactRequestRegistered(
            contactRequestId: $id,
            accountId: $accountId,
            propertyId: null,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: 'response-1',
            originName: 'Congreso',
            co2eqKg: 50.0,
            contactName: 'Ana',
            contactEmail: 'ana@example.com',
            contactPhone: null,
            contactCountry: 'ESP',
            contactIsCompany: false,
            contactCompanyName: null,
            contactVatNumber: null,
            comment: null,
            locale: Locale::Spanish,
        );
        $managed = new ContactRequestManaged($id, $userId, new \DateTimeImmutable('NOW'));

        $contactRequest = new ContactRequest(id: $id, events: [$registered, $managed]);
        $contactRequest->rebuild();

        $this->assertSame('response-1', $contactRequest->originId());
        $this->assertSame(ContactRequestStatus::Managed, $contactRequest->status());
        $this->assertNotNull($contactRequest->resolvedBy());
        $this->assertTrue($userId->equals($contactRequest->resolvedBy()));
    }
}
