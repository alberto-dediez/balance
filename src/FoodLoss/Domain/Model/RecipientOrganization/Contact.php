<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

class Contact
{
    public function __construct(
        private readonly string $name = '',
        private readonly string $email = '',
        private readonly string $phone = '',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function phone(): string
    {
        return $this->phone;
    }
}
