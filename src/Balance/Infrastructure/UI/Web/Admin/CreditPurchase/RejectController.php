<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\CreditPurchase;

use App\Balance\Application\CreditPurchase\Reject\RejectCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Reject\RejectCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Infrastructure\Symfony\Form\RejectCreditPurchaseType;
use App\Balance\Infrastructure\UI\Web\CreditPurchase\GetCreditPurchaseFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/credits/{creditPurchaseId}/reject', name: 'admin_credit_purchases_reject')]
class RejectController extends AbstractController
{
    use GetCreditPurchaseFromIdTrait;
    use EditTrait;

    public function __invoke(
        string $creditPurchaseId,
        CreditPurchaseRepository $creditPurchaseRepository,
        RejectCreditPurchaseService $rejectCreditPurchase,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $creditPurchase = $this->getCreditPurchaseFromId($creditPurchaseId, $creditPurchaseRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $creditPurchase);

        $rejectRequest = new RejectCreditPurchaseRequest(
            creditPurchaseId: $creditPurchase->id(),
            reason: '',
        );

        try {
            $form = $this->createForm(RejectCreditPurchaseType::class, $rejectRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($rejectCreditPurchase, $loggedUser) {
                $rejectCreditPurchase($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Solicitud rechazada correctamente'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_credit_purchases');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/credits/reject.html.twig', [
            'creditPurchase' => $creditPurchase,
            'form' => $form->createView(),
        ]);
    }
}
