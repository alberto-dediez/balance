<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

use App\Payments\Application\Payee\List\ListPayeesRequest;
use App\Payments\Application\Payee\List\ListPayeesService;
use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Infrastructure\Symfony\Form\ListPayeesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payees', name: 'admin_payees')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListPayeesService $listPayees,
        PayeeSubjectDirectory $subjectDirectory,
        PayeeSettlementProvider $settlementProvider,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            list($filter, $form) = $this->filter($request, static fn(): ListPayeesRequest => new ListPayeesRequest(), ListPayeesType::class);

            $response = $listPayees($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@payments/admin/payee/list.html.twig', [
            'payees' => $response->payees,
            'totalResults' => $response->totalResults,
            'subjectDirectory' => $subjectDirectory,
            'settlements' => $settlementProvider->settlementsOf($response->payees),
            'form' => $form->createView(),
        ]);
    }
}
