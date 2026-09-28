<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion;

use App\Materiality\Application\MaterialityQuestion\List\ListMaterialityQuestionsRequest;
use App\Materiality\Application\MaterialityQuestion\List\ListMaterialityQuestionsService;
use App\Materiality\Infrastructure\Symfony\Form\ListMaterialityQuestionsType;
use App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion\Bulk\RemoveAction;
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

#[Route('/admin/materiality-questions', name: 'admin_materiality_questions')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMaterialityQuestionsService $listMaterialityQuestions, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListMaterialityQuestionsRequest();
            }, ListMaterialityQuestionsType::class);

            $response = $listMaterialityQuestions($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->materialityQuestions,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@materiality/admin/materiality-questions/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'materialityQuestions' => $response->materialityQuestions,
            'topicsById' => $response->topicsById,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
