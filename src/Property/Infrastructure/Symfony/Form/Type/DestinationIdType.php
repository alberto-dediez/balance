<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdRemoteType;

class DestinationIdType extends AggregateRootIdRemoteType
{
    public function __construct(
        private readonly RouterInterface $router, private readonly DestinationRepository $repository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => DestinationId::class,
            'autocomplete-path' => $this->router->generate('destinations_autocomplete'),
            'repository' => $this->repository
        ]);
    }
}