<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\Account\Account;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationFilter;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientOrganizationIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly RecipientOrganizationRepository $repository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $recipientOrganizations = $this->repository->findByFilter(
            new RecipientOrganizationFilter(
                accountId: $account instanceof Account ? $account->id() : null,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($recipientOrganizations),
            'class' => RecipientOrganizationId::class,
        ]);
    }
}
