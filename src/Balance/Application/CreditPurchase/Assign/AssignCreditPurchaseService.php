<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Assign;

use App\Balance\Application\AccountProject\Select\SelectProjectRequest;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CompensationNotEnabledForAccountException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\CreditPurchase\DuplicatedPurchaseReferenceException;
use App\Balance\Domain\Model\CreditPurchase\ProjectNotCompensableException;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssignCreditPurchaseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectRepository $projectRepository,
        private AccountProjectRepository $accountProjectRepository,
        private SelectProjectService $selectProjectService,
        private ValidateCreditPurchaseService $validateCreditPurchase,
        private AccountFeaturesPolicy $accountFeaturesPolicy,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(AssignCreditPurchaseRequest $request, User $user): AssignCreditPurchaseResponse
    {
        $accountId = $request->accountId ?? throw new \InvalidArgumentException('Account is required to assign credits.');
        $projectId = $request->projectId ?? throw new \InvalidArgumentException('Project is required to assign credits.');
        $purchasedTonnes = $request->purchasedTonnes
            ?? throw new \InvalidArgumentException('Purchased tonnes are required to assign credits.');
        $paymentType = $request->paymentType
            ?? throw new \InvalidArgumentException('Payment type is required to assign credits.');

        $id = CreditPurchaseId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: $id, accountId: $accountId),
            SecurityAction::Admin,
            $user,
        );

        if (!$this->accountFeaturesPolicy->isEnabled(AccountFeature::Compensation, $accountId)) {
            throw new CompensationNotEnabledForAccountException($accountId);
        }

        $project = $this->projectRepository->find($projectId);

        if (null === $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        if (ProjectType::EmissionCompensation !== $project->projectType()) {
            throw new ProjectNotCompensableException($projectId);
        }

        $pendingFilter = new CreditPurchaseFilter(
            accountId: $accountId,
            projectId: $projectId,
            status: CreditPurchaseStatus::Pending,
            limit: 0,
        );
        $pendingFilter->orderBy = 'requestedAt';
        $pendingFilter->orderWay = 'ASC';

        $pending = array_values($this->creditPurchaseRepository->findByFilter($pendingFilter));

        if ([] !== $pending) {
            $oldest = $pending[0];

            $this->ensureAccountAdheresToProject($accountId, $projectId, $user);

            $validation = $this->validateCreditPurchase->__invoke(
                new ValidateCreditPurchaseRequest(
                    creditPurchaseId: $oldest->id(),
                    purchasedTonnes: $purchasedTonnes,
                    reference: $request->reference,
                    paymentType: $paymentType,
                    amount: $request->amount,
                    certificate: $request->certificate,
                ),
                $user,
            );

            return new AssignCreditPurchaseResponse(
                creditPurchaseId: $oldest->id(),
                validatedPendingRequest: true,
                partiallyFulfilled: $validation->partiallyFulfilled,
                remainingPendingRequests: count($pending) - 1,
            );
        }

        if ($this->creditPurchaseRepository->existsByReference($request->reference)) {
            throw new DuplicatedPurchaseReferenceException($request->reference);
        }

        $this->ensureAccountAdheresToProject($accountId, $projectId, $user);

        $creditPurchase = new CreditPurchase(id: $id);
        $creditPurchase->assign(
            accountId: $accountId,
            projectId: $projectId,
            purchasedTonnes: $purchasedTonnes,
            paymentType: $paymentType,
            reference: $request->reference,
            certificate: $request->certificate,
            assignedBy: $user->id(),
            amount: $request->amount,
        );

        $this->creditPurchaseRepository->add($creditPurchase);

        return new AssignCreditPurchaseResponse(
            creditPurchaseId: $creditPurchase->id(),
            validatedPendingRequest: false,
            partiallyFulfilled: false,
            remainingPendingRequests: 0,
        );
    }

    private function ensureAccountAdheresToProject(AccountId $accountId, ProjectId $projectId, User $user): void
    {
        $existing = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $projectId, limit: 1),
        );

        if (0 === count($existing)) {
            $this->selectProjectService->__invoke(
                new SelectProjectRequest(accountId: $accountId, projectId: $projectId),
                $user,
            );
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssignCreditPurchaseRequest;
    }
}
