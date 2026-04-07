<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\Register;

use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterRecipientOrganizationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RecipientOrganizationRepository $recipientOrganizationRepository,
        private RecipientOrganizationAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterRecipientOrganizationRequest $request, User $user): RegisterRecipientOrganizationResponse
    {
        $accountId = $user->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('User must have an account');
        }

        $id = RecipientOrganizationId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new RecipientOrganization(
                id: $id,
                accountId: $accountId,
            ),
            SecurityAction::List,
            $user,
        );

        $recipientOrganization = new RecipientOrganization($id);
        $recipientOrganization->register(
            name: $request->name,
            type: $request->type,
            nif: $request->nif,
            socialReason: $request->socialReason,
            address: $request->address,
            contactName: $request->contactName,
            contactEmail: $request->contactEmail,
            contactPhone: $request->contactPhone,
            operatingHours: $request->operatingHours,
            pickupTypes: $request->pickupTypes,
            accountId: $accountId,
        );

        $this->recipientOrganizationRepository->add($recipientOrganization);

        return new RegisterRecipientOrganizationResponse(
            recipientOrganizationId: $recipientOrganization->id(),
            name: $recipientOrganization->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterRecipientOrganizationRequest;
    }
}
