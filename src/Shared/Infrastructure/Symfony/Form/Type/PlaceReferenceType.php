<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use App\Shared\Domain\Model\Geo\PlaceKind;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/** @extends AbstractType<mixed> */
class PlaceReferenceType extends AbstractType
{
    public function __construct(private readonly RouterInterface $router) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'kind' => PlaceKind::Any,
            'autocomplete-path' => fn(Options $options): string => $this->router->generate('places_suggest', ['kind' => $options['kind']->value]),
        ]);

        $resolver->setAllowedTypes('kind', PlaceKind::class);
    }

    public function getParent(): string
    {
        return AutocompleteRemoteType::class;
    }
}
