<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Application\AccountProject\Select\SelectProjectRequest;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\InsufficientTransferableCreditException;
use App\Balance\Domain\Model\CreditPurchase\NonTransferableCreditPurchaseException;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class TransferCreditPurchaseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    private const int MICRO_TONNES = 1_000_000;
    private const int CENTS = 100;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private TransferableCredit $transferableCredit,
        private SelectProjectService $selectProject,
        private CreditReservationLock $creditReservationLock,
        private ReallocateUnderallocatedContributionsService $reallocateUnderallocatedContributions,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(TransferCreditPurchaseRequest $request, User $user): TransferCreditPurchaseResponse
    {
        $source = $this->creditPurchaseRepository->find($request->creditPurchaseId);

        if (null === $source) {
            throw new \InvalidArgumentException('Credit purchase not found');
        }

        $this->authorizer->denyUnlessCanDoThat($source, SecurityAction::Edit, $user);

        $sourceAccountId = $source->accountId();
        $projectId = $source->projectId();
        $reference = $source->reference();

        if (
            !$sourceAccountId instanceof AccountId
            || !$projectId instanceof ProjectId
            || null === $reference
            || CreditPurchasePaymentType::PaidByBioscore !== $source->paymentType()
        ) {
            throw new NonTransferableCreditPurchaseException($source->id());
        }

        $destinationAccountIds = $this->destinationAccountIds($source, $sourceAccountId, $request->destinationAccountIds);

        $this->split($source, $request->tonnes, count($destinationAccountIds));

        foreach ($destinationAccountIds as $destinationAccountId) {
            $this->selectProject->__invoke(
                new SelectProjectRequest(accountId: $destinationAccountId, projectId: $projectId),
                $user,
            );
        }

        /** @var array{split: array{share: float, lastShare: float, retained: float, emptied: bool}, transfers: list<array{accountId: AccountId, destination: CreditPurchase, tonnes: float, amount: ?float}>} $result */
        $result = $this->creditReservationLock->withLock(
            $sourceAccountId,
            $projectId,
            function () use ($source, $projectId, $destinationAccountIds, $reference, $request, $user): array {
                $split = $this->split($source, $request->tonnes, count($destinationAccountIds));
                $amounts = $this->amounts($source, $split, count($destinationAccountIds));
                $lastIndex = array_key_last($destinationAccountIds);
                $transfers = [];

                foreach ($destinationAccountIds as $index => $destinationAccountId) {
                    $isLast = $index === $lastIndex;

                    $transfers[] = $this->transfer(
                        $source,
                        $projectId,
                        $destinationAccountId,
                        $reference,
                        null === $amounts ? null : ($isLast ? $amounts['lastShare'] : $amounts['share']),
                        $isLast ? $split['lastShare'] : $split['share'],
                        $user,
                    );
                }

                return ['split' => $split, 'transfers' => $transfers];
            },
        );

        $lots = [];

        foreach ($result['transfers'] as $transfer) {
            $reallocation = $this->reallocateUnderallocatedContributions->__invoke(
                new ReallocateUnderallocatedContributionsRequest(accountId: $transfer['accountId'], projectId: $projectId),
            );

            $lots[] = new TransferredLot(
                destinationAccountId: $transfer['accountId'],
                destinationCreditPurchaseId: $transfer['destination']->id(),
                tonnes: $transfer['tonnes'],
                amount: $transfer['amount'],
                absorbedTonnes: $reallocation->reallocatedTonnes,
            );
        }

        $split = $result['split'];
        $amounts = array_values(array_filter(
            array_map(static fn(TransferredLot $lot): ?float => $lot->amount, $lots),
            static fn(?float $amount): bool => null !== $amount,
        ));

        return new TransferCreditPurchaseResponse(
            creditPurchaseId: $source->id(),
            lots: $lots,
            transferredTonnes: round(array_sum(array_map(static fn(TransferredLot $lot): float => $lot->tonnes, $lots)), 6),
            transferredAmount: [] === $amounts ? null : round(array_sum($amounts), 2),
            tonnesPerAccount: $split['share'],
            retainedTonnes: $split['retained'],
            lastAccountExtraTonnes: round($split['lastShare'] - $split['share'], 6),
            absorbedTonnes: array_sum(array_map(static fn(TransferredLot $lot): float => $lot->absorbedTonnes, $lots)),
        );
    }

    /**
     * @param list<AccountId> $requestedAccountIds
     *
     * @return non-empty-list<AccountId>
     */
    private function destinationAccountIds(CreditPurchase $source, AccountId $sourceAccountId, array $requestedAccountIds): array
    {
        $destinationAccountIds = [];

        foreach ($requestedAccountIds as $requestedAccountId) {
            if ($requestedAccountId->equals($sourceAccountId)) {
                throw new NonTransferableCreditPurchaseException($source->id());
            }

            foreach ($destinationAccountIds as $destinationAccountId) {
                if ($destinationAccountId->equals($requestedAccountId)) {
                    continue 2;
                }
            }

            $destinationAccountIds[] = $requestedAccountId;
        }

        if ([] === $destinationAccountIds) {
            throw new NonTransferableCreditPurchaseException($source->id());
        }

        return $destinationAccountIds;
    }

    /** @return array{share: float, lastShare: float, retained: float, emptied: bool} */
    private function split(CreditPurchase $source, ?float $requestedTonnes, int $accounts): array
    {
        $transferable = $this->transferableCredit->forPurchase($source);

        if ($transferable <= CreditBalance::TONNE_TOLERANCE) {
            throw new InsufficientTransferableCreditException($source->id());
        }

        $total = round(min($requestedTonnes ?? $transferable, $transferable), 6);
        $micro = (int) round($total * self::MICRO_TONNES);
        $shareMicro = intdiv($micro, $accounts);
        $remainderMicro = $micro - $shareMicro * $accounts;

        if (0 === $shareMicro) {
            throw new InsufficientTransferableCreditException($source->id());
        }

        $share = $shareMicro / self::MICRO_TONNES;

        if ($total < $transferable - CreditBalance::TONNE_TOLERANCE) {
            return ['share' => $share, 'lastShare' => $share, 'retained' => $remainderMicro / self::MICRO_TONNES, 'emptied' => false];
        }

        return ['share' => $share, 'lastShare' => ($shareMicro + $remainderMicro) / self::MICRO_TONNES, 'retained' => 0.0, 'emptied' => true];
    }

    /**
     * @param array{share: float, lastShare: float, retained: float, emptied: bool} $split
     *
     * @return array{share: float, lastShare: float}|null
     */
    private function amounts(CreditPurchase $source, array $split, int $accounts): ?array
    {
        $pricePerTonne = $source->pricePerTonne();
        $sourceAmount = $source->amount();

        if (null === $pricePerTonne || null === $sourceAmount) {
            return null;
        }

        $tonnes = round($split['share'] * ($accounts - 1) + $split['lastShare'], 6);
        $cents = (int) round(min(round($pricePerTonne * $tonnes, 2), $sourceAmount) * self::CENTS);
        $shareCents = intdiv($cents, $accounts);
        $remainderCents = $split['emptied'] ? $cents - $shareCents * $accounts : 0;

        return [
            'share' => $shareCents / self::CENTS,
            'lastShare' => ($shareCents + $remainderCents) / self::CENTS,
        ];
    }

    /** @return array{accountId: AccountId, destination: CreditPurchase, tonnes: float, amount: ?float} */
    private function transfer(
        CreditPurchase $source,
        ProjectId $projectId,
        AccountId $destinationAccountId,
        string $reference,
        ?float $destinationAmount,
        float $tonnes,
        User $user,
    ): array {
        $purchasedTonnes = $source->purchasedTonnes() ?? 0.0;
        $ceiling = $source->ceilingTonnes();

        $destination = new CreditPurchase(id: CreditPurchaseId::generate());

        $source->transfer(
            tonnes: $tonnes,
            amount: $destinationAmount ?? 0.0,
            destinationCreditPurchaseId: $destination->id(),
            destinationAccountId: $destinationAccountId,
            transferredBy: $user->id(),
        );

        $destination->receive(
            sourceCreditPurchaseId: $source->id(),
            accountId: $destinationAccountId,
            projectId: $projectId,
            purchasedTonnes: $purchasedTonnes,
            offsetTonnes: round($ceiling - $tonnes, 6),
            upperOffsetTonnes: round($purchasedTonnes - $ceiling, 6),
            amount: $destinationAmount,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: $reference,
            lotNumber: $this->creditPurchaseRepository->nextLotNumberFor($reference),
            certificate: $source->certificate(),
            transferredBy: $user->id(),
        );

        $this->creditPurchaseRepository->add($source);
        $this->creditPurchaseRepository->add($destination);

        return [
            'accountId' => $destinationAccountId,
            'destination' => $destination,
            'tonnes' => $tonnes,
            'amount' => $destinationAmount,
        ];
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof TransferCreditPurchaseRequest;
    }
}
