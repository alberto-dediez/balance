<?php

namespace App\Waste\Infrastructure\UI\Web\WasteManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\Symfony\Form\ListWasteManagersType;
use App\Waste\Application\WasteManager\List\ListWasteManagersRequest;
use App\Waste\Application\WasteManager\List\ListWasteManagersService;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/waste-managers', name: 'waste_managers')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListWasteManagersService $listWasteManagers, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }
        
        try {
            list($filter, $form) = $this->filter($request, function () use ($account) {
                return new ListWasteManagersRequest(
                    accountId: $account->id(),
                );
            }, ListWasteManagersType::class);

            $response = $listWasteManagers($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/waste-managers/list.html.twig', [
            'wasteManagers' => $response->wasteManagers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
