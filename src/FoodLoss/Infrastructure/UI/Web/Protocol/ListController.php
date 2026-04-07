<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\Protocol\List\ListProtocolsRequest;
use App\FoodLoss\Application\Protocol\List\ListProtocolsService;
use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Infrastructure\Symfony\Form\ListProtocolsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/protocols', name: 'protocols')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListProtocolsService $listProtocols,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): ListProtocolsRequest {
                $filterRequest = new ListProtocolsRequest();
                $filterRequest->accountId = $loggedAccount()?->id();

                return $filterRequest;
            }, ListProtocolsType::class);

            $response = $listProtocols($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/protocol/list.html.twig', [
            'protocols' => $response->protocols,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'dummyProtocol' => new Protocol(id: ProtocolId::generate(), accountId: $loggedAccount()?->id()),
        ]);
    }
}
