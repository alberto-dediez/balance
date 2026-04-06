<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\Remove;

use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveRecipientOrganizationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RecipientOrganizationRepository $repository,
        private RecipientOrganizationAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveRecipientOrganizationRequest $request, User $user): RemoveRecipientOrganizationResponse
    {
        $recipientOrganization = $this->repository->find($request->recipientOrganizationId);

        if (null == $recipientOrganization) {
            throw new \InvalidArgumentException('Entidad receptora no encontrada');
        }

        $this->authorizer->denyUnlessCanDoThat($recipientOrganization, SecurityAction::Remove, $user);

        $recipientOrganization->remove();

        $this->repository->add($recipientOrganization);

        return new RemoveRecipientOrganizationResponse(
            recipientOrganizationId: $recipientOrganization->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveRecipientOrganizationRequest;
    }
}
