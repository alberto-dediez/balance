<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Register;

use App\Payments\Domain\Model\Payee\PayeeType;
use App\Shared\Domain\Model\Address;

class RegisterPayeeRequest
{
    public function __construct(
        public ?PayeeType $type = null,
        public ?string $subjectId = null,
        public string $legalName = '',
        public ?string $taxId = null,
        public ?string $street = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $iban = null,
        public ?string $bic = null,
        public ?string $holderName = null,
        public ?string $notes = null,
    ) {}

    public function address(): Address
    {
        return new Address(
            street: $this->street,
            postalCode: $this->postalCode,
            city: $this->city,
            country: $this->country,
        );
    }
}
