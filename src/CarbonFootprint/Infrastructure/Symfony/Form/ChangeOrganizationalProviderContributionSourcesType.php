<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class ChangeOrganizationalProviderContributionSourcesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sources', ChoiceType::class, [
                'label' => new TranslatableMessage('Categorías'),
                'choices' => Scope::Scope3->providerSources(),
                'choice_value' => static fn(?Source $source): string => $source->value ?? '',
                'choice_label' => static fn(Source $source): TranslatableMessage => new TranslatableMessage($source->value, [], 'source'),
                'group_by' => static fn(Source $source): string => in_array($source, Scope::Scope3->upstreamSources(), true) ? 'Aguas arriba' : 'Aguas abajo',
                'multiple' => true,
                'expanded' => true,
                'constraints' => [new Assert\Count(min: 1)],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }
}
