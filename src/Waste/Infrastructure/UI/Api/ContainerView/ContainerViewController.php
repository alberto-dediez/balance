<?php

namespace App\Waste\Infrastructure\UI\Api\ContainerView;

use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use App\Security\Application\Action;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponse;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponseGenerator;

#[Route('/containers/{containerId}', name: 'api_containers_view', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(
    summary: "View Container",
    description: "View an owned container",
    tags: ['Waste']
)]
#[OA\Response(
    response: 200,
    description: 'Returns data of container',
    content: new Model(type: ContainerResponse::class)
)]
class ContainerViewController extends AbstractController
{
    public function __invoke(
        string $containerId,
        ContainerAuthorizer $containerAuthorizer,
        ContainerRepository $containerRepository,
        ContainerResponseGenerator $containerGenerator
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $container = $containerRepository->find(
            new ContainerId($containerId)
        );

        $containerAuthorizer->denyUnlessCanDoThat($container, Action::View, $user);

        return $this->json(
            $containerGenerator->generate($container)
        );
    }
}
