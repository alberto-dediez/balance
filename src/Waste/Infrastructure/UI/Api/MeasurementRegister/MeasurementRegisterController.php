<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementRegister;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;
use App\Waste\Infrastructure\UI\Api\MeasurementRegister\MeasurementRegisterRequest as ApiRequest;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measurements', name: 'api_measurements_register', methods: ['POST'])]
#[Areas(['mobile', 'admin'])]
#[OA\Post(description: 'Register a measurement in an owned container', summary: 'Register Measurement', tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of registered measurement',
    content: new Model(type: MeasurementResponse::class),
)]
class MeasurementRegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        ApiRequest $request,
        RegisterMeasurementService $registerMeasurement,
        MeasurementRepository $measurementRepository,
        MeasurementResponseGenerator $measurementGenerator,
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
                justification: $request->justification,
            ),
            $user,
        );

        $measurement = $measurementRepository->find($registerResponse->measurementId);

        return $this->json(
            $measurementGenerator->generate($measurement),
        );
    }
}
