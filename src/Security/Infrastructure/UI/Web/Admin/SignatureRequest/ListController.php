<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Application\SignatureRequest\List\ListSignatureRequestsRequest;
use App\Security\Application\SignatureRequest\List\ListSignatureRequestsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Form\ListSignatureRequestsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\Admin\SignatureRequest\Bulk\RemoveAction;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/signature-requests', name: 'admin_signature_requests')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListSignatureRequestsService $listSignatureRequests, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn(): ListSignatureRequestsRequest => new ListSignatureRequestsRequest(), ListSignatureRequestsType::class);

            $response = $listSignatureRequests($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->signatureRequests,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@security/admin/signature-requests/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'signatureRequests' => $response->signatureRequests,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
