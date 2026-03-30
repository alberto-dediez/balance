<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementView;

use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measurements/{measurementId}', name: 'api_measurements_view', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(description: 'View an owned measurement', summary: 'View Measurement', tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of measurement',
    content: new Model(type: MeasurementResponse::class),
)]
class MeasurementViewController extends AbstractController
{
    public function __invoke(
        string $measurementId,
        MeasurementAuthorizer $measurementAuthorizer,
        MeasurementRepository $measurementRepository,
        MeasurementResponseGenerator $measurementGenerator,
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $measurement = $measurementRepository->find(
            new MeasurementId($measurementId),
        );

        $measurementAuthorizer->denyUnlessCanDoThat($measurement, Action::View, $user);

        assert(null !== $measurement);

        return $this->json(
            $measurementGenerator->generate($measurement),
        );
    }
}
