<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan;

use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyRequest;
use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use Symfony\Component\Translation\TranslatableMessage;

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

        $planErrors = [];

        if (null === $preventionPlan->responsible()) {
            $planErrors[] = new TranslatableMessage('El plan no tiene un responsable asignado');
        }

        $invalidProperties = [];

        foreach ($preventionPlan->propertyIds() as $propertyId) {
            $propertyResponse = ($this->validateProperty)(new ValidatePropertyRequest($propertyId));

            if ($propertyResponse->hasErrors) {
                $invalidProperties[] = [
                    'propertyName' => $propertyResponse->propertyName,
                    'errors' => $propertyResponse->errors,
                ];
            }
        }

        $response = new ValidatePreventionPlanResponse(
            hasInvalidProperties: [] !== $invalidProperties,
            invalidProperties: $invalidProperties,
            planErrors: $planErrors,
        );

        if (!$response->hasErrors) {
            $preventionPlan->markAsValidated();
            $this->preventionPlanRepository->add($preventionPlan);
        }

        return $response;
    }
}
