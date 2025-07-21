<?php

namespace App\Waste\Infrastructure\UI\Api\MeasurementEdit;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use App\Waste\Application\Measurement\Update\UpdateMeasurementService;
use App\Waste\Infrastructure\UI\Api\MeasurementEdit\MeasurementEditRequest;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;

#[Route('/measurements/{measurementId}', name: 'api_measurements_edit', methods: ['PUT'])]
#[OA\Put(
    summary: "Edit Measurement",
    description: "Edit an owned measurement",
    tags: ['Waste']
)]
#[OA\Response(
    response: 200,
    description: 'Returns data of edited measurement',
    content: new Model(type: MeasurementResponse::class)
)]
class MeasurementEditController extends AbstractController
{
    public function __invoke(
        string $measurementId,
        #[MapRequestPayload()]
        MeasurementEditRequest $request,
        UpdateMeasurementService $updateMeasurement,
        MeasurementRepository $measurementRepository,
        MeasurementResponseGenerator $measurementGenerator
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $updateResponse = $updateMeasurement(
            new UpdateMeasurementRequest(
                measurementId: new MeasurementId($measurementId),
                quantity: $request->quantity,
                load: (float) $request->load,
                justification: $request->justification
            ),
            $user
        );

        $measurement = $measurementRepository->find($updateResponse->measurementId);
        
        return $this->json(
            $measurementGenerator->generate($measurement)
        );
    }
}
