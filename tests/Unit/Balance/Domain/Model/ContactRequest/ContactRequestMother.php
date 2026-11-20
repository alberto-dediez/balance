<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Locale\Locale;

class ContactRequestMother
{
    public static function pending(
        ?AccountId $accountId = null,
        ?ContactRequestId $id = null,
        ProjectContributionOrigin $origin = ProjectContributionOrigin::AttendeeSurveyResponse,
        ?string $originId = null,
        string $contactName = 'Ana García',
        string $contactEmail = 'ana@example.com',
        float $co2eqKg = 120.5,
        ?PropertyId $propertyId = null,
        ?\DateTimeImmutable $requestedAt = null,
    ): ContactRequest {
        $contactRequest = new ContactRequest(id: $id ?? ContactRequestId::generate());
        $contactRequest->register(
            accountId: $accountId ?? AccountId::generate(),
            propertyId: $propertyId,
            origin: $origin,
            originId: $originId ?? (string) ContactRequestId::generate(),
            originName: 'Congreso Anual',
            co2eqKg: $co2eqKg,
            contactName: $contactName,
            contactEmail: $contactEmail,
            contactPhone: '600000000',
            contactCountry: 'ESP',
            contactIsCompany: false,
            contactCompanyName: null,
            contactVatNumber: null,
            comment: null,
            locale: Locale::Spanish,
            requestedAt: $requestedAt ?? new \DateTimeImmutable('NOW'),
        );

        return $contactRequest;
    }

    public static function managed(?AccountId $accountId = null, ?string $originId = null): ContactRequest
    {
        $contactRequest = self::pending(accountId: $accountId, originId: $originId);
        $contactRequest->markAsManaged(UserId::generate(), new \DateTimeImmutable('NOW'));

        return $contactRequest;
    }

    public static function discarded(?AccountId $accountId = null, ?string $originId = null): ContactRequest
    {
        $contactRequest = self::pending(accountId: $accountId, originId: $originId);
        $contactRequest->discard(UserId::generate(), new \DateTimeImmutable('NOW'));

        return $contactRequest;
    }

    public static function ofAccount(AccountId $accountId): ContactRequest
    {
        return self::pending(accountId: $accountId);
    }
}
