<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;

class InMemoryRecipientOrganizationRepositoryTest extends RecipientOrganizationRepositoryTest
{
    protected function createSUT(): RecipientOrganizationRepository
    {
        return RecipientOrganizationRepositoryMother::inMemory();
    }
}
