<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic;

use App\Materiality\Application\MaterialityTopic\List\ListMaterialityTopicsRequest;
use App\Materiality\Application\MaterialityTopic\List\ListMaterialityTopicsService;
use App\Materiality\Infrastructure\Symfony\Form\ListMaterialityTopicsType;
use App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/materiality-topics', name: 'admin_materiality_topics')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMaterialityTopicsService $listMaterialityTopics, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListMaterialityTopicsRequest();
            }, ListMaterialityTopicsType::class);

            $response = $listMaterialityTopics($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->materialityTopics,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@materiality/admin/materiality-topics/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'materialityTopics' => $response->materialityTopics,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
