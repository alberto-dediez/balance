<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Provider;

use App\EventOperations\Application\Provider\List\ListProvidersRequest;
use App\EventOperations\Application\Provider\List\ListProvidersService;
use App\EventOperations\Infrastructure\Symfony\Form\ListProvidersType;
use App\EventOperations\Infrastructure\UI\Web\Provider\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/providers', name: 'providers')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListProvidersService $listProviders,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, function () use ($account): ListProvidersRequest {
                $request = new ListProvidersRequest();
                $request->accountId = $account->id();

                return $request;
            }, ListProvidersType::class);

            $response = $listProviders($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->providers,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@event_operations/providers/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'providers' => $response->providers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
