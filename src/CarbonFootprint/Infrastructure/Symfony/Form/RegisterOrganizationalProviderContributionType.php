<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\Property\Domain\Model\Property\Property;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterOrganizationalProviderContributionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<Provider> $providers */
        $providers = $options['providers'];

        $providerChoices = [];
        foreach ($providers as $provider) {
            $providerChoices[sprintf('%s (%s)', $provider->name(), $provider->contact()->email())] = (string) $provider->id();
        }

        $builder
            ->add('provider', ChoiceType::class, [
                'label' => new TranslatableMessage('Proveedor'),
                'choices' => $providerChoices,
                'choice_translation_domain' => false,
                'placeholder' => new TranslatableMessage('Selecciona un proveedor'),
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('mode', EnumType::class, [
                'label' => new TranslatableMessage('Modo'),
                'class' => ProviderContributionMode::class,
                'choice_label' => static fn(ProviderContributionMode $mode): string => $mode->value,
                'choice_translation_domain' => 'providerContributionMode',
                'expanded' => true,
                'data' => ProviderContributionMode::Manual,
                'constraints' => [new Assert\NotNull()],
            ])
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
        ;

        if (null !== $options['properties']) {
            /** @var list<Property> $properties */
            $properties = $options['properties'];

            $propertyChoices = [];
            foreach ($properties as $property) {
                $propertyChoices[$property->name()] = (string) $property->id();
            }

            $builder->add('property', ChoiceType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'choices' => $propertyChoices,
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin asignar a propiedad'),
            ]);
        }

        $builder->add('register', SubmitType::class, [
            'label' => new TranslatableMessage('Registrar'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('providers');
        $resolver->setAllowedTypes('providers', 'array');
        $resolver->setDefaults(['properties' => null]);
        $resolver->setAllowedTypes('properties', ['null', 'array']);
    }
}
