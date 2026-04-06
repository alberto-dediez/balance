<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

interface RecipientOrganizationRepository
{
    public function add(RecipientOrganization $recipientOrganization): void;

    public function remove(RecipientOrganization $recipientOrganization): void;

    public function find(RecipientOrganizationId $recipientOrganizationId): ?RecipientOrganization;

    /** @return array<RecipientOrganization> */
    public function findByFilter(RecipientOrganizationFilter $filter): array;

    public function totalResultsByFilter(RecipientOrganizationFilter $filter): int;
}
