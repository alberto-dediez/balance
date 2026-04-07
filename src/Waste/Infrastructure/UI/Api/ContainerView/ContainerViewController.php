<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\ContainerView;

use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponse;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponseGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/containers/{containerId}', name: 'api_containers_view', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(description: 'View an owned container', summary: 'View Container', tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of container',
    content: new Model(type: ContainerResponse::class),
)]
class ContainerViewController extends AbstractController
{
    public function __invoke(
        string $containerId,
        ContainerAuthorizer $containerAuthorizer,
        ContainerRepository $containerRepository,
        ContainerResponseGenerator $containerGenerator,
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $container = $containerRepository->find(
            new ContainerId($containerId),
        );

        $containerAuthorizer->denyUnlessCanDoThat($container, Action::View, $user);

        assert($container instanceof Container);

        return $this->json(
            $containerGenerator->generate($container),
        );
    }
}
