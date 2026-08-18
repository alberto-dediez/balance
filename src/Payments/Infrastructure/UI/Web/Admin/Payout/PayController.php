<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\Pay\MarkPayoutPaidRequest;
use App\Payments\Application\Payout\Pay\MarkPayoutPaidService;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Infrastructure\Symfony\Form\MarkPayoutPaidType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/payouts/{payoutId}/pay', name: 'admin_payouts_pay')]
class PayController extends AbstractController
{
    use EditTrait;

    public function __invoke(
        string $payoutId,
        PayoutRepository $payoutRepository,
        MarkPayoutPaidService $markPayoutPaid,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $payout = $payoutRepository->find(new PayoutId($payoutId));

        if (null === $payout) {
            throw $this->createNotFoundException('Liquidación no encontrada');
        }

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $payout);

        $payRequest = new MarkPayoutPaidRequest(payoutId: $payout->id());

        try {
            $form = $this->createForm(MarkPayoutPaidType::class, $payRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($markPayoutPaid, $loggedUser) {
                $markPayoutPaid($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Liquidación marcada como pagada'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_payouts');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@payments/admin/payout/pay.html.twig', [
            'payout' => $payout,
            'form' => $form->createView(),
        ]);
    }
}
