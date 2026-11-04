<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\ProjectContribution\List\ListProjectContributionsRequest;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Symfony\Form\Type\ProjectContributionStatusType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListProjectContributionsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('projectId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Proyecto'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
                'choices' => $options['projects'],
                'choice_value' => static fn(?ProjectId $projectId): ?string => $projectId?->id(),
            ])
        ;

        if ([] !== $options['properties']) {
            $builder->add('propertyId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
                'choices' => $options['properties'],
                'choice_value' => static fn(?PropertyId $propertyId): ?string => $propertyId?->id(),
            ]);
        }

        $builder
            ->add('status', ProjectContributionStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListProjectContributionsRequest::class,
            'projects' => [],
            'properties' => [],
        ]);

        $resolver->setAllowedTypes('projects', 'array');
        $resolver->setAllowedTypes('properties', 'array');
    }
}
