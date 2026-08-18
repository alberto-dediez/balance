<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury;

use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Infrastructure\Symfony\Form\ListLedgerEntriesType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/treasury/ledger', name: 'admin_treasury_ledger')]
class LedgerController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListLedgerEntriesService $listLedgerEntries, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::Admin->value, new LedgerEntry(id: LedgerEntryId::generate()));

        $beneficiary = LedgerBeneficiary::tryFrom((string) $request->query->get('beneficiary', ''));

        try {
            list($filter, $form) = $this->filter(
                $request,
                static function () use ($beneficiary): ListLedgerEntriesRequest {
                    $filter = new ListLedgerEntriesRequest();
                    $filter->beneficiary = $beneficiary;

                    return $filter;
                },
                ListLedgerEntriesType::class,
            );

            $response = $listLedgerEntries($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/treasury/ledger.html.twig', [
            'ledgerEntries' => $response->ledgerEntries,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
