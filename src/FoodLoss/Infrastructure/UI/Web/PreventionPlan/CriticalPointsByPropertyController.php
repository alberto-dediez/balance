<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/critical-points-by-property', name: 'prevention_plans_critical_points_by_property', methods: ['GET'])]
class CriticalPointsByPropertyController extends AbstractController
{
    public function __invoke(
        Request $request,
        CriticalPointRepository $criticalPointRepository,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $loggedUser();

        $propertyIdStr = $request->query->getString('propertyId');

        if ('' === $propertyIdStr) {
            return $this->json([]);
        }

        try {
            $propertyId = new PropertyId($propertyIdStr);
        } catch (\Throwable) {
            return $this->json([]);
        }

        $criticalPoints = $criticalPointRepository->findByFilter(
            new CriticalPointFilter(propertyId: $propertyId, limit: 0),
        );

        return $this->json(
            array_map(
                static fn($cp): array => ['id' => (string) $cp->id(), 'name' => (string) $cp],
                $criticalPoints,
            ),
        );
    }
}
