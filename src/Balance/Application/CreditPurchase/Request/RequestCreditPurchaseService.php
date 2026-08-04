<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Request;

use App\Balance\Application\AccountProject\Select\SelectProjectRequest;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\ProjectNotCompensableException;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RequestCreditPurchaseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectRepository $projectRepository,
        private AccountProjectRepository $accountProjectRepository,
        private SelectProjectService $selectProjectService,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(RequestCreditPurchaseRequest $request, User $user): RequestCreditPurchaseResponse
    {
        $id = CreditPurchaseId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: $id, accountId: $request->accountId),
            SecurityAction::Register,
            $user,
        );

        $project = $this->projectRepository->find($request->projectId);

        if (null === $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        if (ProjectType::EmissionCompensation !== $project->projectType()) {
            throw new ProjectNotCompensableException($request->projectId);
        }

        $existing = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, projectId: $request->projectId, limit: 1),
        );

        if (0 === count($existing)) {
            $this->selectProjectService->__invoke(
                new SelectProjectRequest(accountId: $request->accountId, projectId: $request->projectId),
                $user,
            );
        }

        $creditPurchase = new CreditPurchase(id: $id);
        $creditPurchase->request($request->accountId, $request->projectId, $user->id(), $request->tonnes);

        $this->creditPurchaseRepository->add($creditPurchase);

        return new RequestCreditPurchaseResponse(creditPurchaseId: $creditPurchase->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RequestCreditPurchaseRequest;
    }
}
