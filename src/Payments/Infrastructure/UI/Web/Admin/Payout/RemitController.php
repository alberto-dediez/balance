<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payout;

use App\Payments\Application\Payout\Remit\RemitPayoutsRequest;
use App\Payments\Application\Payout\Remit\RemitPayoutsService;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/payouts-remit', name: 'admin_payouts_remit', methods: ['GET'])]
class RemitController extends AbstractController
{
    public function __invoke(
        RemitPayoutsService $remitPayouts,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('payouts_remit', $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $response = $remitPayouts(
                new RemitPayoutsRequest(
                    payoutIds: $this->payoutIds($request),
                    format: (string) $request->query->get('format', 'sepa'),
                    reference: $this->reference($request),
                ),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        if (0 === $response->remittedCount) {
            $this->addFlash('warning', new TranslatableMessage('No hay liquidaciones pendientes de remesar'));

            return $this->redirectToRoute('admin_payouts');
        }

        $this->addFlash('success', new TranslatableMessage(
            'Liquidaciones marcadas como remesadas: {count}',
            ['{count}' => $response->remittedCount],
        ));

        return $this->redirectToRoute('admin_payouts');
    }

    /** @return list<PayoutId> */
    private function payoutIds(Request $request): array
    {
        $ids = $request->query->get('ids');

        if (!is_string($ids) || '' === $ids) {
            return [];
        }

        return array_map(static fn(string $id): PayoutId => new PayoutId($id), explode(',', $ids));
    }

    private function reference(Request $request): ?string
    {
        $reference = $request->query->get('reference');

        return is_string($reference) && '' !== $reference ? $reference : null;
    }
}
