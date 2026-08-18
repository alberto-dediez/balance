<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Payee;

use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Infrastructure\UI\Web\Admin\Treasury\SettleController;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\UI\Web\Admin\Payee\PayeeSettlement;
use App\Payments\Infrastructure\UI\Web\Admin\Payee\PayeeSettlementProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class LedgerPayeeSettlementProvider implements PayeeSettlementProvider
{
    public function __construct(
        private LedgerEntryRepository $ledgerEntryRepository,
        private UrlGeneratorInterface $router,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<Payee> $payees
     *
     * @return array<string, PayeeSettlement>
     */
    public function settlementsOf(array $payees): array
    {
        $balances = [];
        $settlements = [];

        foreach ($payees as $payee) {
            $beneficiary = $this->beneficiaryFor($payee->type());

            if (!$beneficiary instanceof LedgerBeneficiary) {
                $settlements[(string) $payee->id()] = new PayeeSettlement();

                continue;
            }

            $balances[$beneficiary->value] ??= $this->ledgerEntryRepository->balancesByBeneficiary($beneficiary);

            $settlements[(string) $payee->id()] = $this->settlementOf(
                $payee,
                $beneficiary,
                $balances[$beneficiary->value][(string) $payee->subjectId()] ?? null,
            );
        }

        return $settlements;
    }

    private function settlementOf(Payee $payee, LedgerBeneficiary $beneficiary, ?LedgerBalance $balance): PayeeSettlement
    {
        $pendingAmount = $balance instanceof LedgerBalance ? $balance->pendingAmount() : 0.0;

        if (!$payee->isReady() || $pendingAmount <= 0.0) {
            return new PayeeSettlement(pendingAmount: $pendingAmount);
        }

        $beneficiaryId = (string) $payee->subjectId();

        return new PayeeSettlement(
            pendingAmount: $pendingAmount,
            settlePath: $this->router->generate('admin_treasury_settle', [
                'beneficiary' => $beneficiary->value,
                'beneficiaryId' => $beneficiaryId,
                '_token' => $this->csrfTokenManager
                    ->getToken(SettleController::csrfTokenId($beneficiary->value, $beneficiaryId))
                    ->getValue(),
            ]),
        );
    }

    private function beneficiaryFor(?PayeeType $type): ?LedgerBeneficiary
    {
        return match ($type) {
            PayeeType::Account => LedgerBeneficiary::Account,
            PayeeType::Project => LedgerBeneficiary::Project,
            default => null,
        };
    }
}
