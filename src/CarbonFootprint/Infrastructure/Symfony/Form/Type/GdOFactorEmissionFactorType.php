<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Application\GdOFactor\Update\EmissionFactorData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<EmissionFactorData> */
class GdOFactorEmissionFactorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $numberAttr = ['step' => 'any', 'min' => 0];

        $builder
            ->add('year', IntegerType::class, [
                'label' => new TranslatableMessage('Año'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Range(min: 1900, max: 2200),
                ],
            ])
            ->add('co2eq', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2eq'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('co2', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('ch4', TextType::class, [
                'label' => new TranslatableMessage('g CH4'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('n2o', TextType::class, [
                'label' => new TranslatableMessage('g N2O'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('wellToTankCo2eq', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2eq (WTT)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('wellToTankCo2', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2 (WTT)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('wellToTankCh4', TextType::class, [
                'label' => new TranslatableMessage('g CH4 (WTT)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('wellToTankN2o', TextType::class, [
                'label' => new TranslatableMessage('g N2O (WTT)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('wellToTankSource', TextType::class, [
                'label' => new TranslatableMessage('Fuente (WTT)'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('source', TextType::class, [
                'label' => new TranslatableMessage('Fuente'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmissionFactorData::class,
            'constraints' => [new Assert\Callback([self::class, 'validateWellToTankAllOrNothing'])],
        ]);
    }

    public static function validateWellToTankAllOrNothing(?EmissionFactorData $data, ExecutionContextInterface $context): void
    {
        if (!$data instanceof EmissionFactorData) {
            return;
        }

        $fields = ['wellToTankCo2eq', 'wellToTankCo2', 'wellToTankCh4', 'wellToTankN2o'];
        $filled = array_filter($fields, static fn(string $field): bool => '' !== (string) $data->{$field});

        if ([] === $filled) {
            return;
        }

        foreach ($fields as $field) {
            if ('' === (string) $data->{$field}) {
                $context->buildViolation('This value should not be blank.')
                    ->atPath($field)
                    ->addViolation()
                ;
            }
        }
    }
}
