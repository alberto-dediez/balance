<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Validate;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\DuplicatedPurchaseReferenceException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ValidateCreditPurchaseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(ValidateCreditPurchaseRequest $request, User $user): ValidateCreditPurchaseResponse
    {
        $creditPurchase = $this->creditPurchaseRepository->find($request->creditPurchaseId);

        if (null === $creditPurchase) {
            throw new \InvalidArgumentException('Credit purchase not found');
        }

        $this->authorizer->denyUnlessCanDoThat($creditPurchase, SecurityAction::Edit, $user);

        $duplicated = $this->creditPurchaseRepository->findOneByReference($request->reference);

        if (null !== $duplicated && !$duplicated->id()->equals($creditPurchase->id())) {
            throw new DuplicatedPurchaseReferenceException($request->reference);
        }

        $creditPurchase->validate(
            purchasedTonnes: $request->purchasedTonnes,
            paymentType: $request->paymentType
                ?? throw new \InvalidArgumentException('Payment type is required to validate a credit purchase.'),
            reference: $request->reference,
            certificate: $request->certificate,
            validatedBy: $user->id(),
        );

        $this->creditPurchaseRepository->add($creditPurchase);

        return new ValidateCreditPurchaseResponse(
            creditPurchaseId: $creditPurchase->id(),
            partiallyFulfilled: $creditPurchase->isPartiallyFulfilled(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ValidateCreditPurchaseRequest;
    }
}
