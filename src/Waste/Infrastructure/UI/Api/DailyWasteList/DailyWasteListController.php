<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\DailyWasteList;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\DailyWaste\List\ListDailyWastesRequest;
use App\Waste\Application\DailyWaste\List\ListDailyWastesService;
use App\Waste\Infrastructure\UI\Api\DailyWaste\DailyWasteResponse;
use App\Waste\Infrastructure\UI\Api\DailyWaste\DailyWasteResponseGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/daily-wastes', name: 'api_daily_wastes_list', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(description: 'Returns all daily wastes for the given account. Admins may query any account; owners are restricted to their own.', summary: 'List daily wastes', tags: ['Waste'])]
#[OA\RequestBody(content: new Model(type: DailyWasteListRequest::class))]
#[OA\Response(
    response: 200,
    description: 'Returns list of daily wastes',
    content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: DailyWasteResponse::class)),
    ),
)]
class DailyWasteListController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload]
        DailyWasteListRequest $payload,
        ListDailyWastesService $listDailyWastes,
        DailyWasteResponseGenerator $dailyWasteGenerator,
        LoggedUser $loggedUser,
    ): JsonResponse {
        $user = $loggedUser();

        $response = $listDailyWastes(
            new ListDailyWastesRequest(
                accountId: null !== $payload->accountId ? new AccountId($payload->accountId) : null,
                properties: null !== $payload->propertyId ? [new PropertyId($payload->propertyId)] : [],
                dayFrom: null !== $payload->dayFrom ? new \DateTimeImmutable($payload->dayFrom) : null,
                dayTo: null !== $payload->dayTo ? new \DateTimeImmutable($payload->dayTo) : null,
            ),
            $user,
        );

        $items = [];
        foreach ($response->dailyWastes as $dailyWaste) {
            $items[] = $dailyWasteGenerator->generate($dailyWaste);
        }

        return $this->json($items);
    }
}
