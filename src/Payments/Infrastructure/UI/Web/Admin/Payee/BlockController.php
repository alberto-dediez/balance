<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

use App\Payments\Application\Payee\Block\BlockPayeeRequest;
use App\Payments\Application\Payee\Block\BlockPayeeService;
use App\Payments\Application\Payee\Unblock\UnblockPayeeRequest;
use App\Payments\Application\Payee\Unblock\UnblockPayeeService;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/payees/block/{payeeId}', name: 'admin_payees_block')]
class BlockController extends AbstractController
{
    use GetPayeeFromIdTrait;

    public function __invoke(
        string $payeeId,
        PayeeRepository $payeeRepository,
        BlockPayeeService $blockPayee,
        UnblockPayeeService $unblockPayee,
        LoggedUser $loggedUser,
    ): Response {
        $payee = $this->getPayeeFromId($payeeId, $payeeRepository);

        try {
            if ($payee->isBlocked()) {
                $unblockPayee(new UnblockPayeeRequest($payee->id()), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Beneficiario desbloqueado'));
            } else {
                $blockPayee(new BlockPayeeRequest($payee->id()), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Beneficiario bloqueado'));
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('admin_payees');
    }
}
