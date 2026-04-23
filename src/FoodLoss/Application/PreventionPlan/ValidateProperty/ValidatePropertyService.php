<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidateProperty;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Translation\TranslatableMessage;

class ValidatePropertyService
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly CriticalPointRepository $criticalPointRepository,
    ) {}

    public function __invoke(ValidatePropertyRequest $request): ValidatePropertyResponse
    {
        $errors = [];

        $property = $this->propertyRepository->find($request->propertyId);

        if (null === $property) {
            return new ValidatePropertyResponse(hasErrors: false);
        }

        $propertyName = $property->name();

        $companyId = $property->companyId();
        if (null === $companyId) {
            $errors[] = new TranslatableMessage('Sin empresa asignada');
        } else {
            $company = $this->companyRepository->find($companyId);
            if (null === $company || '' === $company->vatNumber()) {
                $errors[] = new TranslatableMessage('La empresa no tiene CIF');
            }
        }

        $criticalPointCount = $this->criticalPointRepository->totalResultsByFilter(
            new CriticalPointFilter(propertyId: $request->propertyId, limit: 1),
        );

        if (0 === $criticalPointCount) {
            $errors[] = new TranslatableMessage('Sin puntos críticos asignados');
        }

        return new ValidatePropertyResponse(
            hasErrors: [] !== $errors,
            propertyName: $propertyName,
            errors: $errors,
        );
    }
}
