<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryRecipientOrganizationRepository;

class RecipientOrganizationRepositoryMother
{
    public static function inMemory(): InMemoryRecipientOrganizationRepository
    {
        return new InMemoryRecipientOrganizationRepository();
    }
}
