<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Check;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerCheckCompleted;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Event\DomainEventPublisher;

final class CheckLedgerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    private const float AMOUNT_TOLERANCE = 0.01;

    private const float TONNE_TOLERANCE = 0.000001;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private LedgerEntryRepository $ledgerEntryRepository,
        private PayoutRepository $payoutRepository,
        private RecordContributionLedgerEntriesService $recordContributionLedgerEntries,
        private LedgerEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(CheckLedgerRequest $request, User $user): CheckLedgerResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new LedgerEntry(id: LedgerEntryId::generate()),
            SecurityAction::Admin,
            $user,
        );

        $contributions = $this->contributions($request);
        $findings = [];

        foreach ($contributions as $projectContribution) {
            $finding = $this->checkContribution($projectContribution);

            if (null === $finding) {
                continue;
            }

            $findings[] = $request->repair ? $this->repair($projectContribution, $finding) : $finding;
        }

        $response = new CheckLedgerResponse(
            findings: array_merge(
                $findings,
                $this->checkOverallocatedPurchases($contributions),
                $this->checkPayouts($request),
            ),
            checkedContributions: count($contributions),
        );

        $this->publishCompletion($request, $response);

        return $response;
    }

    /** @return list<ProjectContribution> */
    private function contributions(CheckLedgerRequest $request): array
    {
        return array_values($this->projectContributionRepository->findByFilter(
            new ProjectContributionFilter(from: $request->from, to: $request->to, limit: 0),
        ));
    }

    private function publishCompletion(CheckLedgerRequest $request, CheckLedgerResponse $response): void
    {
        if (!$response->hasFindings()) {
            return;
        }

        DomainEventPublisher::instance()->publish(new LedgerCheckCompleted(
            ledgerCheckId: LedgerEntryId::generate(),
            findings: array_map(
                static fn(LedgerFinding $finding): array => [
                    'code' => $finding->code->value,
                    'subjectId' => $finding->subjectId,
                    'detail' => $finding->detail,
                ],
                $response->findings,
            ),
            checkedContributions: $response->checkedContributions,
            from: $request->from,
            to: $request->to,
        ));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CheckLedgerRequest;
    }

    private function checkContribution(ProjectContribution $projectContribution): ?LedgerFinding
    {
        $entriesCount = $this->ledgerEntryRepository->totalResultsByFilter(
            new LedgerEntryFilter(projectContributionId: $projectContribution->id()),
        );

        if (!$projectContribution->isPaid()) {
            if (0 === $entriesCount) {
                return null;
            }

            return new LedgerFinding(
                code: LedgerFindingCode::OrphanEntries,
                subjectId: (string) $projectContribution->id(),
                detail: sprintf(
                    '%d asiento(s) de una aportación en estado %s.',
                    $entriesCount,
                    $projectContribution->status()->value,
                ),
            );
        }

        if (0 === $entriesCount) {
            if ($projectContribution->isOwnCreditFunded() || $projectContribution->funding()->isImported()) {
                return null;
            }

            return new LedgerFinding(
                code: LedgerFindingCode::PaidContributionWithoutEntries,
                subjectId: (string) $projectContribution->id(),
                detail: sprintf('Aportación pagada de %.2f € sin asientos.', $projectContribution->totalAmount()),
            );
        }

        $recorded = $this->ledgerEntryRepository->recordedAmountFor($projectContribution->id());
        $difference = abs($recorded - $projectContribution->totalAmount());

        if ($difference <= self::AMOUNT_TOLERANCE) {
            return null;
        }

        return new LedgerFinding(
            code: LedgerFindingCode::ContributionSumMismatch,
            subjectId: (string) $projectContribution->id(),
            detail: sprintf('Asientos %.2f € frente a un total cobrado de %.2f €.', $recorded, $projectContribution->totalAmount()),
        );
    }

    private function repair(ProjectContribution $projectContribution, LedgerFinding $finding): LedgerFinding
    {
        if (LedgerFindingCode::PaidContributionWithoutEntries !== $finding->code) {
            return $finding;
        }

        $this->recordContributionLedgerEntries->__invoke(
            new RecordContributionLedgerEntriesRequest($projectContribution->id()),
        );

        return $finding->repaired();
    }

    /**
     * @param list<ProjectContribution> $contributions
     *
     * @return list<LedgerFinding>
     */
    private function checkOverallocatedPurchases(array $contributions): array
    {
        $allocatedByPurchase = [];

        foreach ($contributions as $projectContribution) {
            if (ProjectContributionStatus::Failed === $projectContribution->status()) {
                continue;
            }

            foreach ($projectContribution->creditAllocations() as $allocation) {
                $key = (string) $allocation->creditPurchaseId();

                $allocatedByPurchase[$key] = ($allocatedByPurchase[$key] ?? 0.0) + $allocation->tonnes();
            }
        }

        $findings = [];

        foreach ($allocatedByPurchase as $creditPurchaseId => $allocatedTonnes) {
            $creditPurchase = $this->creditPurchaseRepository->find(new CreditPurchaseId((string) $creditPurchaseId));

            if (null === $creditPurchase) {
                continue;
            }

            if ($allocatedTonnes <= $creditPurchase->availableTonnes() + self::TONNE_TOLERANCE) {
                continue;
            }

            $findings[] = new LedgerFinding(
                code: LedgerFindingCode::OverallocatedCreditPurchase,
                subjectId: $creditPurchaseId,
                detail: sprintf(
                    'Asignadas %.6f t sobre %.6f t compradas.',
                    $allocatedTonnes,
                    $creditPurchase->availableTonnes(),
                ),
            );
        }

        return $findings;
    }

    /** @return list<LedgerFinding> */
    private function checkPayouts(CheckLedgerRequest $request): array
    {
        $payouts = array_values($this->payoutRepository->findByFilter(
            new PayoutFilter(from: $request->from, to: $request->to, limit: 0),
        ));

        return array_merge(
            $this->checkPayoutBalances($payouts),
            $this->checkPayoutNumbering(array_values($this->payoutRepository->findByFilter(new PayoutFilter(limit: 0)))),
        );
    }

    /**
     * @param list<Payout> $payouts
     *
     * @return list<LedgerFinding>
     */
    private function checkPayoutBalances(array $payouts): array
    {
        $findings = [];

        foreach ($payouts as $payout) {
            if (PayoutStatus::Cancelled === $payout->status()) {
                continue;
            }

            $settled = 0.0;

            foreach ($this->ledgerEntryRepository->findByPayout($payout->id()) as $entry) {
                $settled += $entry->amount();
            }

            if (abs($settled - $payout->baseAmount()) <= self::AMOUNT_TOLERANCE) {
                continue;
            }

            $findings[] = new LedgerFinding(
                code: LedgerFindingCode::UnbalancedPayout,
                subjectId: (string) $payout->number(),
                detail: sprintf('Asientos %.2f € frente a un importe liquidado de %.2f €.', $settled, $payout->baseAmount()),
            );
        }

        return $findings;
    }

    /**
     * @param list<Payout> $payouts
     *
     * @return list<LedgerFinding>
     */
    private function checkPayoutNumbering(array $payouts): array
    {
        $sequencesByYear = [];

        foreach ($payouts as $payout) {
            $number = $payout->number();

            if (null === $number || !str_contains($number, '/')) {
                continue;
            }

            [$year, $sequence] = explode('/', $number, 2);

            $sequencesByYear[$year][] = (int) $sequence;
        }

        $findings = [];

        foreach ($sequencesByYear as $year => $sequences) {
            sort($sequences);

            $expected = 1;

            foreach ($sequences as $sequence) {
                if ($sequence === $expected) {
                    ++$expected;

                    continue;
                }

                $findings[] = new LedgerFinding(
                    code: LedgerFindingCode::PayoutNumberingGap,
                    subjectId: sprintf('%s/%04d', $year, $sequence),
                    detail: sprintf('Se esperaba la secuencia %04d.', $expected),
                );

                $expected = $sequence + 1;
            }
        }

        return $findings;
    }
}
