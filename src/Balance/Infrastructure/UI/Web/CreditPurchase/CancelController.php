<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\CreditPurchase;

use App\Balance\Application\CreditPurchase\Cancel\CancelCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Cancel\CancelCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/credits/purchases/{creditPurchaseId}/cancel', name: 'credit_purchases_cancel')]
class CancelController extends AbstractController
{
    use GetCreditPurchaseFromIdTrait;

    public function __invoke(
        string $creditPurchaseId,
        CreditPurchaseRepository $creditPurchaseRepository,
        CancelCreditPurchaseService $cancelCreditPurchase,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $creditPurchase = $this->getCreditPurchaseFromId($creditPurchaseId, $creditPurchaseRepository);

        try {
            $cancelCreditPurchase(new CancelCreditPurchaseRequest($creditPurchase->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Solicitud de créditos cancelada'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido cancelar la solicitud de créditos'));
        }

        $referer = $request->headers->get('referer');

        if (null !== $referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('credits');
    }
}
