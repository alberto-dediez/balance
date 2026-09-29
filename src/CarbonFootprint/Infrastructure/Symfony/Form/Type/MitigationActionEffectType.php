<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class MitigationActionEffectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['manual']) {
            $builder->add('emissionSource', ChoiceType::class, [
                'label' => new TranslatableMessage('Fuente de emisión'),
                'choices' => $options['emission_sources'],
                'choice_translation_domain' => false,
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ]);
        } else {
            $builder->add('emissionSource', HiddenType::class);
        }

        /** @var array<string, array{min: float, max: float}> $ranges */
        $ranges = $options['ranges'];

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($ranges): void {
            $data = $event->getData();
            $emissionSource = is_array($data) ? (string) ($data['emissionSource'] ?? '') : '';
            $range = $ranges[$emissionSource] ?? ['min' => 0.0, 'max' => 1.0];
            $isFullRange = 0.0 === $range['min'] && 1.0 === $range['max'];

            $event->getForm()->add('reduction', PercentType::class, [
                'label' => new TranslatableMessage('Reducción esperada'),
                'scale' => 2,
                'required' => true,
                'help' => $isFullRange ? null : new TranslatableMessage('Rango establecido entre {min} % y {max} %', [
                    '{min}' => number_format($range['min'] * 100, 2, ',', '.'),
                    '{max}' => number_format($range['max'] * 100, 2, ',', '.'),
                ]),
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Range(
                        notInRangeMessage: 'El valor de reducción de esta medida debe estar dentro del rango establecido',
                        min: $range['min'],
                        max: $range['max'],
                    ),
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'manual' => true,
            'emission_sources' => [],
            'ranges' => [],
        ]);
        $resolver->setAllowedTypes('manual', 'bool');
        $resolver->setAllowedTypes('emission_sources', 'array');
        $resolver->setAllowedTypes('ranges', 'array');
    }
}
