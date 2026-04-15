<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\Container\List\ListContainersRequest;
use App\Waste\Application\Container\List\ListContainersService;
use App\Waste\Infrastructure\Symfony\Form\ListContainersType;
use App\Waste\Infrastructure\UI\Web\Container\Bulk\RemoveContainer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/containers', name: 'containers')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListContainersService $listContainers,
        Request $request,
    ): Response {
        [$filter, $form] = $this->filter($request, fn(): ListContainersRequest => new ListContainersRequest(
            accountId: $loggedAccount()?->id(),
        ), ListContainersType::class);

        try {
            $response = $listContainers($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->containers,
            'actions' => [
                new RemoveContainer(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@waste/containers/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'containers' => $response->containers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
