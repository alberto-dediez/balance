<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject\Deselect;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\AccountProject\ProjectHasAvailableCreditsException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class DeselectProjectService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private AccountProjectRepository $repository,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private AccountProjectAuthorizer $authorizer,
    ) {}

    public function __invoke(DeselectProjectRequest $request, User $user): DeselectProjectResponse
    {
        $existing = $this->repository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, projectId: $request->projectId, limit: 1),
        );

        if (0 === count($existing)) {
            throw new \InvalidArgumentException('Account project not found');
        }

        $accountProject = $existing[0];

        $this->authorizer->denyUnlessCanDoThat($accountProject, SecurityAction::Remove, $user);

        $balance = $this->creditPurchaseRepository->balanceFor($request->accountId, $request->projectId);

        if ($balance->availableTonnes() > 0.0) {
            throw new ProjectHasAvailableCreditsException($request->projectId);
        }

        $accountProject->deselect();

        $this->repository->remove($accountProject);

        return new DeselectProjectResponse(
            accountId: $request->accountId,
            projectId: $request->projectId,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeselectProjectRequest;
    }
}
