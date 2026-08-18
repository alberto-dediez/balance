<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury;

use App\Balance\Application\LedgerEntry\Adjust\RecordLedgerAdjustmentService;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Infrastructure\Symfony\Form\LedgerAdjustmentFormData;
use App\Balance\Infrastructure\Symfony\Form\RecordLedgerAdjustmentType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/treasury/adjustments/register', name: 'admin_treasury_adjustments_register')]
class AdjustmentController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RecordLedgerAdjustmentService $recordLedgerAdjustment,
        LedgerEntryRepository $ledgerEntryRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new LedgerEntry(id: LedgerEntryId::generate()));

        $reversedEntry = $this->reversedEntry($request, $ledgerEntryRepository);

        $form = $this->createForm(
            RecordLedgerAdjustmentType::class,
            null !== $reversedEntry ? LedgerAdjustmentFormData::toReverse($reversedEntry) : new LedgerAdjustmentFormData(),
            ['isReversal' => null !== $reversedEntry],
        );

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($recordLedgerAdjustment, $loggedUser) {
                $formData = $form->getData();

                assert($formData instanceof LedgerAdjustmentFormData);

                $recordLedgerAdjustment($formData->toRequest(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Ajuste registrado correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_treasury_ledger');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->render('@balance/admin/treasury/adjustment.html.twig', [
            'form' => $form->createView(),
            'reversedEntry' => $reversedEntry,
        ]);
    }

    private function reversedEntry(Request $request, LedgerEntryRepository $ledgerEntryRepository): ?LedgerEntry
    {
        $reverses = $request->query->getString('reverses');

        if ('' === $reverses) {
            return null;
        }

        $ledgerEntry = $ledgerEntryRepository->find(new LedgerEntryId($reverses));

        if (null === $ledgerEntry) {
            throw $this->createNotFoundException('Asiento no encontrado');
        }

        return $ledgerEntry;
    }
}
