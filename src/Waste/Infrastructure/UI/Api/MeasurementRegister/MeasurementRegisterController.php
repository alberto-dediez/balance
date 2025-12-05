<?php

namespace App\Waste\Infrastructure\UI\Api\MeasurementRegister;

use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\Container\ContainerId;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;
use App\Waste\Infrastructure\UI\Api\MeasurementRegister\MeasurementRegisterRequest as ApiRequest;

#[Route('/measurements', name: 'api_measurements_register', methods: ['POST'])]
#[Areas(['mobile', 'admin'])]
#[OA\Post(
    summary: "Register Measurement",
    description: "Register a measurement in an owned container",
    tags: ['Waste']
)]
#[OA\Response(
    response: 200,
    description: 'Returns data of registered measurement',
    content: new Model(type: MeasurementResponse::class)
)]
class MeasurementRegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        ApiRequest $request,
        RegisterMeasurementService $registerMeasurement,
        MeasurementRepository $measurementRepository,
        MeasurementResponseGenerator $measurementGenerator
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $registerResponse = $registerMeasurement(
            new RegisterMeasurementRequest(
                containerId: new ContainerId($request->containerId),
                quantity: $request->quantity,
                load: (float) $request->load,
                registeredBy: $user->id(),
                justification: $request->justification
            ),
            $user
        );

        $measurement = $measurementRepository->find($registerResponse->measurementId);

        return $this->json(
            $measurementGenerator->generate($measurement)
        );
    }
}
