<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementEdit;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use App\Waste\Application\Measurement\Update\UpdateMeasurementService;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measurements/{measurementId}', name: 'api_measurements_edit', methods: ['PUT'])]
#[Areas(['mobile', 'admin'])]
#[OA\Put(description: 'Edit an owned measurement', summary: 'Edit Measurement', tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of edited measurement',
    content: new Model(type: MeasurementResponse::class),
)]
class MeasurementEditController extends AbstractController
{
    public function __invoke(
        string $measurementId,
        #[MapRequestPayload()]
        MeasurementEditRequest $request,
        UpdateMeasurementService $updateMeasurement,
        MeasurementRepository $measurementRepository,
        MeasurementResponseGenerator $measurementGenerator,
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
                justification: $request->justification,
            ),
            $user,
        );

        $measurement = $measurementRepository->find($updateResponse->measurementId);

        assert(null !== $measurement);

        return $this->json(
            $measurementGenerator->generate($measurement),
        );
    }
}
