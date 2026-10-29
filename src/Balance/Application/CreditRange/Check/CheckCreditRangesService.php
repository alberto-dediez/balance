<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditRange\Check;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseTransferred;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

final class CheckCreditRangesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    private const string CONTEXT = 'balance';

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private RecordedEventRepository $recordedEventRepository,
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(CheckCreditRangesRequest $request, User $user): CheckCreditRangesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: CreditPurchaseId::generate()),
            SecurityAction::Admin,
            $user,
        );

        /** @var array<string, CreditRangeFinding> $missing */
        $missing = [];
        /** @var list<array{destinationId: string, finding: CreditRangeFinding}> $overceded */
        $overceded = [];
        $expectedRanges = $this->simulate($missing, $overceded);
        $findings = [];
        $boundedLots = [];

        foreach ($expectedRanges as $lotId => $expectedRange) {
            $lot = $this->creditPurchaseRepository->find(new CreditPurchaseId($lotId));

            if (null === $lot) {
                continue;
            }

            $lotFindings = $this->checkLot($lot, $expectedRange['offset'], $expectedRange['upperOffset'], $request->repair);
            array_push($findings, ...$lotFindings);

            if (!$this->hasPendingMismatch($lotFindings)) {
                $boundedLots[$lotId] = true;
            }
        }

        $receivedLots = $this->receivedLots();

        foreach ($receivedLots as $lot) {
            $lotId = (string) $lot->id();

            if (isset($expectedRanges[$lotId]) || isset($missing[$lotId])) {
                continue;
            }

            $missing[$lotId] = $this->missingTransferEvent($lot);
        }

        return new CheckCreditRangesResponse(
            findings: array_merge($findings, $this->overcededFindings($overceded, $boundedLots), array_values($missing)),
            checkedLots: count($receivedLots),
        );
    }

    /**
     * @param array<string, CreditRangeFinding>                               $missing
     * @param list<array{destinationId: string, finding: CreditRangeFinding}> $overceded
     *
     * @return array<string, array{offset: float, upperOffset: float}>
     */
    private function simulate(array &$missing, array &$overceded): array
    {
        /** @var array<string, array{purchased: float, ceiling: float, floor: float, ceded: float}> $ranges */
        $ranges = [];
        $expectedRanges = [];

        foreach ($this->transfers() as $transfer) {
            $sourceId = (string) $transfer->creditPurchaseId();

            if (!isset($ranges[$sourceId])) {
                $source = $this->creditPurchaseRepository->find($transfer->creditPurchaseId());

                if (null === $source) {
                    continue;
                }

                if ($source->lotNumber() > 1) {
                    $missing[$sourceId] ??= $this->missingTransferEvent($source);

                    continue;
                }

                $ranges[$sourceId] = [
                    'purchased' => $source->purchasedTonnes() ?? 0.0,
                    'ceiling' => ($source->purchasedTonnes() ?? 0.0) - $source->upperOffsetTonnes(),
                    'floor' => 0.0,
                    'ceded' => 0.0,
                ];
            }

            $range = $ranges[$sourceId];
            $top = max($range['floor'], $range['ceiling'] - $range['ceded']);
            $destinationId = (string) $transfer->destinationCreditPurchaseId();
            $bottom = $top - $transfer->transferredTonnes();

            if ($bottom < $range['floor'] - CreditBalance::TONNE_TOLERANCE) {
                $overceded[] = ['destinationId' => $destinationId, 'finding' => $this->overcededRange($transfer, $range['floor'] - $bottom)];
            }

            $offset = round(max($range['floor'], $bottom), 6);

            $expectedRanges[$destinationId] = [
                'offset' => $offset,
                'upperOffset' => round($range['purchased'] - $top, 6),
            ];
            $ranges[$destinationId] = ['purchased' => $range['purchased'], 'ceiling' => $top, 'floor' => $offset, 'ceded' => 0.0];
            $ranges[$sourceId] = [...$range, 'ceded' => $range['ceded'] + $transfer->transferredTonnes()];
        }

        return $expectedRanges;
    }

    /**
     * @param list<CreditRangeFinding> $lotFindings
     */
    private function hasPendingMismatch(array $lotFindings): bool
    {
        foreach ($lotFindings as $finding) {
            if (CreditRangeFindingCode::RangeMismatch === $finding->code && !$finding->repaired) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{destinationId: string, finding: CreditRangeFinding}> $overceded
     * @param array<string, true>                                             $boundedLots
     *
     * @return list<CreditRangeFinding>
     */
    private function overcededFindings(array $overceded, array $boundedLots): array
    {
        return array_map(
            static fn(array $overcededRange): CreditRangeFinding => isset($boundedLots[$overcededRange['destinationId']])
                ? $overcededRange['finding']->repaired()
                : $overcededRange['finding'],
            $overceded,
        );
    }

    private function overcededRange(CreditPurchaseTransferred $transfer, float $excessTonnes): CreditRangeFinding
    {
        $source = $this->creditPurchaseRepository->find($transfer->creditPurchaseId());
        $destination = $this->creditPurchaseRepository->find($transfer->destinationCreditPurchaseId());

        return new CreditRangeFinding(
            code: CreditRangeFindingCode::OvercededRange,
            subjectId: null === $source ? (string) $transfer->creditPurchaseId() : $this->lotLabel($source),
            detail: sprintf(
                '%s t ceded below the exact range to %s',
                $this->tonnes($excessTonnes),
                null === $destination ? (string) $transfer->destinationCreditPurchaseId() : $this->lotLabel($destination),
            ),
        );
    }

    /** @return list<CreditPurchaseTransferred> */
    private function transfers(): array
    {
        $filter = new RecordedEventFilter(
            eventName: CreditPurchaseTransferred::eventName(),
            context: self::CONTEXT,
            limit: 0,
        );
        $filter->orderWay = 'ASC';

        $recordedEvents = $this->recordedEventRepository->findByFilter($filter);

        $transfers = [];

        foreach ($recordedEvents as $recordedEvent) {
            $event = $recordedEvent->rebuild();

            if ($event instanceof CreditPurchaseTransferred) {
                $transfers[] = ['event' => $event, 'lotNumber' => $this->destinationLotNumber($event)];
            }
        }

        usort(
            $transfers,
            static fn(array $a, array $b): int => [$a['event']->occurredOn(), $a['lotNumber']] <=> [$b['event']->occurredOn(), $b['lotNumber']],
        );

        return array_map(static fn(array $transfer): CreditPurchaseTransferred => $transfer['event'], $transfers);
    }

    private function destinationLotNumber(CreditPurchaseTransferred $transfer): int
    {
        return $this->creditPurchaseRepository->find($transfer->destinationCreditPurchaseId())?->lotNumber() ?? \PHP_INT_MAX;
    }

    /** @return list<CreditRangeFinding> */
    private function checkLot(CreditPurchase $lot, float $offset, float $upperOffset, bool $repair): array
    {
        if ($this->hasRange($lot, $offset, $upperOffset)) {
            return [];
        }

        $findings = [];
        $purchasedTonnes = $lot->purchasedTonnes() ?? 0.0;
        $expectedAvailable = $purchasedTonnes - $offset - $upperOffset - $lot->voidedTonnes();
        $allocated = $this->allocatedTonnes($lot);

        if ($allocated > $expectedAvailable + CreditBalance::TONNE_TOLERANCE) {
            $upperOffset = round(max(0.0, $purchasedTonnes - $offset - $lot->voidedTonnes() - $allocated), 6);
            $offset = round(min($offset, $purchasedTonnes - $upperOffset - $lot->voidedTonnes()), 6);

            $findings[] = new CreditRangeFinding(
                code: CreditRangeFindingCode::OverallocatedAfterRepair,
                subjectId: $this->lotLabel($lot),
                detail: sprintf(
                    '%s t ceded and %s t allocated over the exact range',
                    $this->tonnes(max(0.0, -$expectedAvailable)),
                    $this->tonnes($allocated - max(0.0, $expectedAvailable)),
                ),
            );
        }

        if ($this->hasRange($lot, $offset, $upperOffset)) {
            return $findings;
        }

        $mismatch = new CreditRangeFinding(
            code: CreditRangeFindingCode::RangeMismatch,
            subjectId: $this->lotLabel($lot),
            detail: sprintf(
                'offset %s→%s, upperOffset %s→%s',
                $this->tonnes($lot->offsetTonnes()),
                $this->tonnes($offset),
                $this->tonnes($lot->upperOffsetTonnes()),
                $this->tonnes($upperOffset),
            ),
        );

        if ($repair) {
            $lot->correctRange($offset, $upperOffset);
            $this->creditPurchaseRepository->add($lot);

            $mismatch = $mismatch->repaired();
        }

        return [$mismatch, ...$findings];
    }

    private function hasRange(CreditPurchase $lot, float $offset, float $upperOffset): bool
    {
        return abs($offset - $lot->offsetTonnes()) <= CreditBalance::TONNE_TOLERANCE
            && abs($upperOffset - $lot->upperOffsetTonnes()) <= CreditBalance::TONNE_TOLERANCE;
    }

    private function allocatedTonnes(CreditPurchase $lot): float
    {
        $accountId = $lot->accountId();
        $projectId = $lot->projectId();

        if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
            return 0.0;
        }

        return $this->projectContributionRepository->allocatedTonnesByPurchase($accountId, $projectId)[(string) $lot->id()] ?? 0.0;
    }

    /** @return list<CreditPurchase> */
    private function receivedLots(): array
    {
        return array_values(array_filter(
            $this->creditPurchaseRepository->findByFilter(
                new CreditPurchaseFilter(status: CreditPurchaseStatus::Validated, limit: 0),
            ),
            static fn(CreditPurchase $creditPurchase): bool => $creditPurchase->lotNumber() > 1,
        ));
    }

    private function missingTransferEvent(CreditPurchase $lot): CreditRangeFinding
    {
        return new CreditRangeFinding(
            code: CreditRangeFindingCode::MissingTransferEvent,
            subjectId: $this->lotLabel($lot),
            detail: 'No recorded transfer created this lot',
        );
    }

    private function lotLabel(CreditPurchase $lot): string
    {
        return sprintf('%s #%d (%s)', $lot->reference() ?? '-', $lot->lotNumber(), (string) $lot->id());
    }

    private function tonnes(float $tonnes): string
    {
        return number_format($tonnes, 6, '.', '');
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CheckCreditRangesRequest;
    }
}
