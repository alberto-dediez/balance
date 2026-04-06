<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\List;

use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListRecipientOrganizationsService
{
    public function __construct(
        private readonly RecipientOrganizationRepository $repository,
        private readonly RecipientOrganizationAuthorizer $authorizer,
    ) {}

    public function __invoke(ListRecipientOrganizationsRequest $request, User $user): ListRecipientOrganizationsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new RecipientOrganization(
                id: RecipientOrganizationId::generate(),
                accountId: $request->accountId,
            ),
            SecurityAction::List,
            $user,
        );

        $recipientOrganizations = $this->repository->findByFilter($request);
        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListRecipientOrganizationsResponse($recipientOrganizations, $totalResults);
    }
}
