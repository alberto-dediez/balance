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
        $destinationAccountId = $request->destinationAccountId;

        if (
            !$sourceAccountId instanceof AccountId
            || !$projectId instanceof ProjectId
            || null === $reference
            || CreditPurchasePaymentType::PaidByBioscore !== $source->paymentType()
        ) {
            throw new NonTransferableCreditPurchaseException($source->id());
        }

        if (!$destinationAccountId instanceof AccountId || $destinationAccountId->equals($sourceAccountId)) {
            throw new NonTransferableCreditPurchaseException($source->id());
        }

        $this->selectProject->__invoke(
            new SelectProjectRequest(accountId: $destinationAccountId, projectId: $projectId),
            $user,
        );

        /** @var array{destination: CreditPurchase, tonnes: float, amount: ?float} $transfer */
        $transfer = $this->creditReservationLock->withLock(
            $sourceAccountId,
            $projectId,
            fn(): array => $this->transfer($source, $projectId, $destinationAccountId, $reference, $request->tonnes, $user),
        );

        $reallocation = $this->reallocateUnderallocatedContributions->__invoke(
            new ReallocateUnderallocatedContributionsRequest(accountId: $destinationAccountId, projectId: $projectId),
        );

        return new TransferCreditPurchaseResponse(
            creditPurchaseId: $source->id(),
            destinationCreditPurchaseId: $transfer['destination']->id(),
            transferredTonnes: $transfer['tonnes'],
            transferredAmount: $transfer['amount'],
            absorbedTonnes: $reallocation->reallocatedTonnes,
        );
    }

    /** @return array{destination: CreditPurchase, tonnes: float, amount: ?float} */
    private function transfer(
        CreditPurchase $source,
        ProjectId $projectId,
        AccountId $destinationAccountId,
        string $reference,
        ?float $requestedTonnes,
        User $user,
    ): array {
        $transferTonnes = $this->transferTonnes($source, $requestedTonnes);

        $pricePerTonne = $source->pricePerTonne();
        $destinationAmount = null !== $pricePerTonne ? round($pricePerTonne * $transferTonnes, 2) : null;

        $destination = new CreditPurchase(id: CreditPurchaseId::generate());

        $source->transfer(
            tonnes: $transferTonnes,
            amount: $destinationAmount ?? 0.0,
            destinationCreditPurchaseId: $destination->id(),
            destinationAccountId: $destinationAccountId,
            transferredBy: $user->id(),
        );

        $purchasedTonnes = $source->purchasedTonnes() ?? 0.0;

        $destination->receive(
            sourceCreditPurchaseId: $source->id(),
            accountId: $destinationAccountId,
            projectId: $projectId,
            purchasedTonnes: $purchasedTonnes,
            offsetTonnes: round($purchasedTonnes - $source->voidedTonnes(), 6),
            amount: $destinationAmount,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: $reference,
            lotNumber: $this->creditPurchaseRepository->nextLotNumberFor($reference),
            certificate: $source->certificate(),
            transferredBy: $user->id(),
        );

        $this->creditPurchaseRepository->add($source);
        $this->creditPurchaseRepository->add($destination);

        return ['destination' => $destination, 'tonnes' => $transferTonnes, 'amount' => $destinationAmount];
    }

    private function transferTonnes(CreditPurchase $source, ?float $requestedTonnes): float
    {
        $remaining = $this->transferableCredit->forPurchase($source);

        if ($remaining <= CreditBalance::TONNE_TOLERANCE) {
            throw new InsufficientTransferableCreditException($source->id());
        }

        return round(min($requestedTonnes ?? $remaining, $remaining), 6);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof TransferCreditPurchaseRequest;
    }
}
