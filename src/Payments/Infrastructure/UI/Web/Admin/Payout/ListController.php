<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\List\ListPayoutsRequest;
use App\Payments\Application\Payout\List\ListPayoutsService;
use App\Payments\Domain\Model\Payee\PayeeFilter;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Infrastructure\Symfony\Form\ListPayoutsType;
use App\Payments\Infrastructure\UI\Web\PayeeNames;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payouts', name: 'admin_payouts')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListPayoutsService $listPayouts,
        PayeeRepository $payeeRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            list($filter, $form) = $this->filter($request, static fn(): ListPayoutsRequest => new ListPayoutsRequest(), ListPayoutsType::class);

            $response = $listPayouts($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@payments/admin/payout/list.html.twig', [
            'payouts' => $response->payouts,
            'totalResults' => $response->totalResults,
            'payeeNames' => new PayeeNames($payeeRepository->findByFilter(new PayeeFilter(limit: 0))),
            'form' => $form->createView(),
        ]);
    }
}
