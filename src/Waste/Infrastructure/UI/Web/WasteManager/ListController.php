<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\WasteManager;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\WasteManager\List\ListWasteManagersRequest;
use App\Waste\Application\WasteManager\List\ListWasteManagersService;
use App\Waste\Infrastructure\Symfony\Form\ListWasteManagersType;
use App\Waste\Infrastructure\UI\Web\WasteManager\Bulk\RemoveWasteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/waste-managers', name: 'waste_managers')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListWasteManagersService $listWasteManagers,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, fn() => new ListWasteManagersRequest(
                accountId: $account->id(),
            ), ListWasteManagersType::class);

            $response = $listWasteManagers($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->wasteManagers,
            'actions' => [
                new RemoveWasteManager(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@waste/waste-managers/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'wasteManagers' => $response->wasteManagers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
