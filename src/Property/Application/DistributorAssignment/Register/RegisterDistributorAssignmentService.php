<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\Register;

use App\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizer;
use App\Property\Domain\Model\DistributorAssignment\AccountIsNotDistributorException;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\DistributorAssignment\DistributorCannotDistributeItselfException;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterDistributorAssignmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly DistributorAssignmentRepository $repository,
        private readonly PropertyRepository $propertyRepository,
        private readonly AccountRepository $accountRepository,
        private readonly DistributorAssignmentAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterDistributorAssignmentRequest $request, User $user): RegisterDistributorAssignmentResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new DistributorAssignment(id: DistributorAssignmentId::generate()), Action::Register, $user);

        if (($request->accountId instanceof AccountId) === ($request->propertyId instanceof PropertyId)) {
            throw new \InvalidArgumentException('Either accountId or propertyId is required');
        }

        $distributor = $this->accountRepository->find($request->distributorAccountId);

        if (!$distributor instanceof Account || !$distributor->isDistributor()) {
            throw new AccountIsNotDistributorException($request->distributorAccountId);
        }

        $assignment = $request->accountId instanceof AccountId
            ? $this->assignmentForAccount($request->distributorAccountId, $request->accountId, $user)
            : $this->assignmentForProperty($request->distributorAccountId, $request->propertyId, $user);

        $this->repository->add($assignment);

        return new RegisterDistributorAssignmentResponse(
            id: $assignment->id(),
            distributorAccountId: $assignment->distributorAccountId(),
            accountId: $assignment->accountId(),
            propertyId: $assignment->propertyId(),
        );
    }

    private function assignmentForAccount(AccountId $distributorAccountId, AccountId $accountId, User $user): DistributorAssignment
    {
        if (!$this->accountRepository->find($accountId) instanceof Account) {
            throw new \InvalidArgumentException('Account not found');
        }

        $existing = $this->repository->findForAccount($distributorAccountId, $accountId);

        if ($existing instanceof DistributorAssignment) {
            return $existing;
        }

        $assignment = new DistributorAssignment(id: DistributorAssignmentId::generate());
        $assignment->registerForAccount($distributorAccountId, $accountId, $user->id());

        return $assignment;
    }

    private function assignmentForProperty(AccountId $distributorAccountId, ?PropertyId $propertyId, User $user): DistributorAssignment
    {
        $property = $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId) : null;
        $propertyAccountId = $property?->accountId();

        if (!$property instanceof Property || $property->isRemoved() || !$propertyAccountId instanceof AccountId) {
            throw new \InvalidArgumentException('Property not found');
        }

        if ($propertyAccountId->equals($distributorAccountId)) {
            throw new DistributorCannotDistributeItselfException($distributorAccountId);
        }

        $existing = $this->repository->findForProperty($distributorAccountId, $property->id());

        if ($existing instanceof DistributorAssignment) {
            return $existing;
        }

        $assignment = new DistributorAssignment(id: DistributorAssignmentId::generate());
        $assignment->registerForProperty($distributorAccountId, $property->id(), $user->id());

        return $assignment;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterDistributorAssignmentRequest;
    }
}
