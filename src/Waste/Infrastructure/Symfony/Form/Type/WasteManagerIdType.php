<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerFilter;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WasteManagerIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly WasteManagerRepository $wasteRepository,
        private readonly LoggedAccount $loggedAccount,
    ) {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = $this->loggedAccount->__invoke();

        $wastes = $this->wasteRepository->findByFilter(
            new WasteManagerFilter(
                accountId: $account?->id(),
                limit: 0
            )
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($wastes),
            'class' => WasteManagerId::class
        ]);
    }
}
