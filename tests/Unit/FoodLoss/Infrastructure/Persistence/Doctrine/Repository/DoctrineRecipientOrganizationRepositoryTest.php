<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineRecipientOrganizationRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineRecipientOrganizationRepositoryTest extends RecipientOrganizationRepositoryTest
{
    protected function createSUT(): RecipientOrganizationRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineRecipientOrganizationRepository($manager);
    }
}
