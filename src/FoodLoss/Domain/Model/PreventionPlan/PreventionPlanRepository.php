<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;

interface PreventionPlanRepository
{
    public function add(PreventionPlan $preventionPlan): void;

    public function remove(PreventionPlan $preventionPlan): void;

    public function find(PreventionPlanId $preventionPlanId): ?PreventionPlan;

    public function findBySignatureRequestId(SignatureRequestId $signatureRequestId): ?PreventionPlan;

    /** @return array<PreventionPlan> */
    public function findByFilter(PreventionPlanFilter $filter): array;

    public function totalResultsByFilter(PreventionPlanFilter $filter): int;
}
