<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\CreditPurchase;

use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Infrastructure\Symfony\Form\ValidateCreditPurchaseType;
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

#[Route('/admin/credits/{creditPurchaseId}/validate', name: 'admin_credit_purchases_validate')]
class ValidateController extends AbstractController
{
    use GetCreditPurchaseFromIdTrait;
    use EditTrait;

    public function __invoke(
        string $creditPurchaseId,
        CreditPurchaseRepository $creditPurchaseRepository,
        ValidateCreditPurchaseService $validateCreditPurchase,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $creditPurchase = $this->getCreditPurchaseFromId($creditPurchaseId, $creditPurchaseRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $creditPurchase);

        $validateRequest = new ValidateCreditPurchaseRequest(
            creditPurchaseId: $creditPurchase->id(),
            purchasedTonnes: $creditPurchase->requestedTonnes(),
            amount: 0.0,
            reference: '',
        );

        try {
            $form = $this->createForm(ValidateCreditPurchaseType::class, $validateRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($validateCreditPurchase, $loggedUser) {
                $response = $validateCreditPurchase($form->getData(), $loggedUser());

                $message = $response->partiallyFulfilled
                    ? new TranslatableMessage('Compra validada parcialmente')
                    : new TranslatableMessage('Compra validada correctamente');

                $this->addFlash('success', $message);
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_credit_purchases');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/credits/validate.html.twig', [
            'creditPurchase' => $creditPurchase,
            'form' => $form->createView(),
        ]);
    }
}
