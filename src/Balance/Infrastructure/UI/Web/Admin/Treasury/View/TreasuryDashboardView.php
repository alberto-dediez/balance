<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury\View;

use App\Balance\Application\LedgerEntry\Series\ListLedgerSeriesResponse;
use App\Balance\Application\LedgerEntry\Summary\SummarizeLedgerResponse;
use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Infrastructure\UI\Web\Admin\Treasury\SettleController;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\Payout;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class TreasuryDashboardView
{
    /** @var list<BeneficiaryRow> */
    private array $rows = [];

    /** @var list<TreasuryAlert> */
    private array $alerts = [];

    /**
     * @param array<string, LedgerBalance> $accountBalances
     * @param array<string, LedgerBalance> $projectBalances
     * @param array<Payee>                 $payees
     * @param array<Payout>                $failedPayouts
     */
    public function __construct(
        private readonly SummarizeLedgerResponse $summary,
        private readonly ListLedgerSeriesResponse $series,
        array $accountBalances,
        array $projectBalances,
        array $payees,
        array $failedPayouts,
        private readonly LedgerBalance $unassignedBalance,
        private readonly int $unassignedEntries,
        private readonly PayeeSubjectDirectory $subjectDirectory,
        private readonly UrlGeneratorInterface $router,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
        $payeeBySubject = [];

        foreach ($payees as $payee) {
            $payeeBySubject[$payee->type()?->value . ':' . $payee->subjectId()] = $payee;
        }

        $this->rows = [
            ...$this->buildRows(LedgerBeneficiary::Account, PayeeType::Account, $accountBalances, $payeeBySubject),
            ...$this->buildRows(LedgerBeneficiary::Project, PayeeType::Project, $projectBalances, $payeeBySubject),
        ];

        usort($this->rows, static fn(BeneficiaryRow $a, BeneficiaryRow $b): int => $b->pending <=> $a->pending);

        $this->alerts = $this->buildAlerts($failedPayouts);
    }

    public function collectedAmount(): float
    {
        return $this->summary->collectedAmount();
    }

    public function netToAccounts(): float
    {
        return $this->summary->accruedFor(LedgerBeneficiary::Account);
    }

    public function pendingToAccounts(): float
    {
        return $this->summary->pendingFor(LedgerBeneficiary::Account);
    }

    public function netToProjects(): float
    {
        return $this->summary->accruedFor(LedgerBeneficiary::Project);
    }

    public function pendingToProjects(): float
    {
        return $this->summary->pendingFor(LedgerBeneficiary::Project);
    }

    public function netRetainedByBioscore(): float
    {
        return round(
            $this->summary->accruedFor(LedgerBeneficiary::Bioscore)
            - $this->managementFee()
            - $this->vat(),
            2,
        );
    }

    public function managementFee(): float
    {
        return $this->summary->totalFor(LedgerEntryType::ManagementFee);
    }

    public function vat(): float
    {
        return $this->summary->totalFor(LedgerEntryType::Vat);
    }

    public function unassigned(): float
    {
        return $this->summary->accruedFor(LedgerBeneficiary::Unassigned);
    }

    /** @return list<BeneficiaryRow> */
    public function rows(): array
    {
        return $this->rows;
    }

    /** @return list<TreasuryAlert> */
    public function alerts(): array
    {
        return $this->alerts;
    }

    /** @return list<string> */
    public function months(): array
    {
        return $this->series->months;
    }

    /** @return list<array{name: string, data: list<float>}> */
    public function splitSeries(): array
    {
        $labels = [
            LedgerBeneficiary::Account->value => 'Cuentas',
            LedgerBeneficiary::Project->value => 'Proyectos',
            LedgerBeneficiary::Bioscore->value => 'Bioscore',
            LedgerBeneficiary::Unassigned->value => 'Sin asignar',
        ];

        $series = [];

        foreach ($labels as $key => $label) {
            $series[] = ['name' => $label, 'data' => $this->series->amountsByBeneficiary[$key] ?? []];
        }

        return $series;
    }

    /** @return list<array{name: string, data: list<float>}> */
    public function accruedVsSettledSeries(): array
    {
        return [
            ['name' => 'Devengado', 'data' => $this->series->accruedByMonth],
            ['name' => 'Liquidado', 'data' => $this->series->settledByMonth],
        ];
    }

    /** @return list<string> */
    public function topPendingLabels(): array
    {
        return array_map(fn(array $row): string => $this->nameOf($row['id']), $this->series->topPending);
    }

    /** @return list<float> */
    public function topPendingValues(): array
    {
        return array_map(static fn(array $row): float => $row['amount'], $this->series->topPending);
    }

    /**
     * @param array<string, LedgerBalance> $balances
     * @param array<string, Payee>         $payeeBySubject
     *
     * @return list<BeneficiaryRow>
     */
    private function buildRows(
        LedgerBeneficiary $beneficiary,
        PayeeType $payeeType,
        array $balances,
        array $payeeBySubject,
    ): array {
        $rows = [];

        foreach ($balances as $key => $balance) {
            $beneficiaryId = (string) $key;

            if ('' === $beneficiaryId) {
                continue;
            }

            $payee = $payeeBySubject[$payeeType->value . ':' . $beneficiaryId] ?? null;

            $rows[] = new BeneficiaryRow(
                beneficiary: $beneficiary,
                beneficiaryId: $beneficiaryId,
                name: $this->subjectDirectory->label($payeeType, $beneficiaryId) ?? $beneficiaryId,
                accrued: $balance->accruedAmount(),
                settled: $balance->settledAmount(),
                pending: $balance->pendingAmount(),
                payeeStatus: $payee?->status()->value,
                detailPath: $this->detailPath($beneficiary, $beneficiaryId),
                settlePath: $this->router->generate('admin_treasury_settle', [
                    'beneficiary' => $beneficiary->value,
                    'beneficiaryId' => $beneficiaryId,
                    '_token' => $this->csrfTokenManager
                        ->getToken(SettleController::csrfTokenId($beneficiary->value, $beneficiaryId))
                        ->getValue(),
                ]),
            );
        }

        return $rows;
    }

    private function detailPath(LedgerBeneficiary $beneficiary, string $beneficiaryId): string
    {
        return LedgerBeneficiary::Account === $beneficiary
            ? $this->router->generate('admin_treasury_account', ['accountId' => $beneficiaryId])
            : $this->router->generate('admin_treasury_project', ['projectId' => $beneficiaryId]);
    }

    private function nameOf(string $beneficiaryId): string
    {
        return $this->subjectDirectory->label(PayeeType::Account, $beneficiaryId)
            ?? $this->subjectDirectory->label(PayeeType::Project, $beneficiaryId)
            ?? $beneficiaryId;
    }

    /**
     * @param array<Payout> $failedPayouts
     *
     * @return list<TreasuryAlert>
     */
    private function buildAlerts(array $failedPayouts): array
    {
        $alerts = [];

        if ($this->unassignedBalance->pendingAmount() > 0.0) {
            $alerts[] = new TreasuryAlert(
                message: sprintf(
                    '%s € sin respaldo de crédito en %d asiento(s)',
                    number_format($this->unassignedBalance->pendingAmount(), 2, ',', '.'),
                    $this->unassignedEntries,
                ),
                actionLabel: 'Ver',
                actionPath: $this->router->generate('admin_treasury_ledger', ['beneficiary' => LedgerBeneficiary::Unassigned->value]),
            );
        }

        foreach ($this->rows as $row) {
            if ($row->pending <= 0.0 || 'ready' === $row->payeeStatus) {
                continue;
            }

            $alerts[] = new TreasuryAlert(
                message: sprintf(
                    '%s tiene %s € pendientes y su ficha de cobro no está lista',
                    $row->name,
                    number_format($row->pending, 2, ',', '.'),
                ),
                actionLabel: 'Completar',
                actionPath: $this->router->generate('admin_payees'),
            );
        }

        foreach ($failedPayouts as $payout) {
            $alerts[] = new TreasuryAlert(
                message: sprintf('Liquidación %s fallida', (string) $payout->number()),
                actionLabel: 'Reintentar',
                actionPath: $this->router->generate('admin_payouts_pay', ['payoutId' => (string) $payout->id()]),
                level: 'danger',
            );
        }

        return $alerts;
    }
}
