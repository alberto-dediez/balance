<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\Update;

use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateRecipientOrganizationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RecipientOrganizationRepository $recipientOrganizationRepository,
        private RecipientOrganizationAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateRecipientOrganizationRequest $request, User $user): UpdateRecipientOrganizationResponse
    {
        $recipientOrganization = $this->recipientOrganizationRepository->find($request->recipientOrganizationId);

        if (null == $recipientOrganization) {
            throw new \InvalidArgumentException('Entidad receptora no encontrada');
        }

        $this->authorizer->denyUnlessCanDoThat($recipientOrganization, SecurityAction::Edit, $user);

        $recipientOrganization->update(
            name: $request->name,
            type: $request->type,
            nif: $request->nif,
            socialReason: $request->socialReason,
            address: $request->address,
            contactName: $request->contactName,
            contactEmail: $request->contactEmail,
            contactPhone: $request->contactPhone,
            contactLocale: $request->contactLocale,
            contactNotificationChannel: $request->contactNotificationChannel,
            operatingHours: $request->operatingHours,
            pickupTypes: $request->pickupTypes,
        );

        $this->recipientOrganizationRepository->add($recipientOrganization);

        return new UpdateRecipientOrganizationResponse(
            recipientOrganizationId: $recipientOrganization->id(),
            name: $recipientOrganization->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateRecipientOrganizationRequest;
    }
}
