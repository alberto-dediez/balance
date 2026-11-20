<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\Register;

use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

final class RegisterContactRequestRequest
{
    public function __construct(
        public readonly ContactRequestId $contactRequestId,
        public readonly AccountId $accountId,
        public readonly ?PropertyId $propertyId,
        public readonly ProjectContributionOrigin $origin,
        public readonly string $originId,
        public readonly ?string $originName,
        public readonly float $co2eqKg,
        public readonly string $contactName,
        public readonly string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly string $contactCountry,
        public readonly bool $contactIsCompany,
        public readonly ?string $contactCompanyName,
        public readonly ?string $contactVatNumber,
        public readonly ?string $comment,
        public readonly Locale $locale,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
