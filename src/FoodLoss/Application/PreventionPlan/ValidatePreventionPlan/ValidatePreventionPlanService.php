<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan;

use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyRequest;
use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;

class ValidatePreventionPlanService
{
    public function __construct(
        private readonly PreventionPlanRepository $preventionPlanRepository,
        private readonly ValidatePropertyService $validateProperty,
    ) {}

    public function __invoke(ValidatePreventionPlanRequest $request): ValidatePreventionPlanResponse
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (null === $preventionPlan) {
            return new ValidatePreventionPlanResponse(hasInvalidProperties: false);
        }

        $invalidProperties = [];

        foreach ($preventionPlan->propertyIds() as $propertyId) {
            $response = ($this->validateProperty)(new ValidatePropertyRequest($propertyId));

            if ($response->hasErrors) {
                $invalidProperties[] = [
                    'propertyName' => $response->propertyName,
                    'errors' => $response->errors,
                ];
            }
        }

        if ([] === $invalidProperties) {
            $preventionPlan->markAsValidated();
            $this->preventionPlanRepository->add($preventionPlan);
        }

        return new ValidatePreventionPlanResponse(
            hasInvalidProperties: [] !== $invalidProperties,
            invalidProperties: $invalidProperties,
        );
    }
}
