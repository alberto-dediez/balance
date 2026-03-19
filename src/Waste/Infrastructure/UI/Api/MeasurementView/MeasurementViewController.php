<?php

namespace App\Waste\Infrastructure\UI\Api\MeasurementView;

use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use App\Security\Application\Action;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;

#[Route('/measurements/{measurementId}', name: 'api_measurements_view', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(description: "View an owned measurement", summary: "View Measurement", tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of measurement',
    content: new Model(type: MeasurementResponse::class)
)]
class MeasurementViewController extends AbstractController
{
    public function __invoke(
        string $measurementId,
        MeasurementAuthorizer $measurementAuthorizer,
        MeasurementRepository $measurementRepository,
        MeasurementResponseGenerator $measurementGenerator
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $measurement = $measurementRepository->find(
            new MeasurementId($measurementId)
        );

        $measurementAuthorizer->denyUnlessCanDoThat($measurement, Action::View, $user);

        return $this->json(
            $measurementGenerator->generate($measurement)
        );
    }
}
