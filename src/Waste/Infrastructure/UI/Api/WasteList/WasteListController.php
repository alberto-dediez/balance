<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\WasteList;

use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Waste\List\ListWastesRequest;
use App\Waste\Application\Waste\List\ListWastesService;
use App\Waste\Infrastructure\UI\Api\Waste\WasteResponse;
use App\Waste\Infrastructure\UI\Api\Waste\WasteResponseGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/wastes', name: 'api_wastes_list', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(description: 'Returns all wastes registered in the platform.', summary: 'List wastes', tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns list of wastes',
    content: new Model(type: WasteResponse::class),
)]
class WasteListController extends AbstractController
{
    public function __invoke(
        ListWastesService $listWastes,
        WasteResponseGenerator $wasteGenerator,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $user = $loggedUser();

        $response = $listWastes(
            new ListWastesRequest(),
            $user,
        );

        $items = [];
        foreach ($response->wastes as $waste) {
            $items[] = $wasteGenerator->generate($waste);
        }

        return $this->json($items);
    }
}
