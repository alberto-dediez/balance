<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

class RegisterContactRequestFormData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $country = null,
        public bool $isCompany = false,
        public ?string $companyName = null,
        public ?string $vatNumber = null,
        public ?string $comment = null,
    ) {}
}
