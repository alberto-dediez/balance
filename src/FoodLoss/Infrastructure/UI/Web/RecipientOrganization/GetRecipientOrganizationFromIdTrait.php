<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;

trait GetRecipientOrganizationFromIdTrait
{
    private function getRecipientOrganizationFromId(string $recipientOrganizationId, RecipientOrganizationRepository $repository): RecipientOrganization
    {
        $recipientOrganization = $repository->find(new RecipientOrganizationId($recipientOrganizationId));

        if (null == $recipientOrganization) {
            throw $this->createNotFoundException('Entidad receptora no encontrada');
        }

        return $recipientOrganization;
    }
}
