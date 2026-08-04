<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Cancel;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CancelCreditPurchaseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(CancelCreditPurchaseRequest $request, User $user): CancelCreditPurchaseResponse
    {
        $creditPurchase = $this->creditPurchaseRepository->find($request->creditPurchaseId);

        if (null === $creditPurchase) {
            throw new \InvalidArgumentException('Credit purchase not found');
        }

        $this->authorizer->denyUnlessCanDoThat($creditPurchase, SecurityAction::Remove, $user);

        $creditPurchase->cancel();

        $this->creditPurchaseRepository->add($creditPurchase);

        return new CancelCreditPurchaseResponse(creditPurchaseId: $creditPurchase->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CancelCreditPurchaseRequest;
    }
}
