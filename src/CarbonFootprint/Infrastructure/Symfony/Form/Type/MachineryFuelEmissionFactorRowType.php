<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\EmissionFactorRow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<EmissionFactorRow> */
class MachineryFuelEmissionFactorRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $numberAttr = ['step' => 'any', 'min' => 0];

        $builder
            ->add('machineryFuelId', HiddenType::class)
            ->add('emissionFactorGroupId', HiddenType::class)
            ->add('co2eq', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2eq'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('co2', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('ch4', TextType::class, [
                'label' => new TranslatableMessage('g CH4'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('n2o', TextType::class, [
                'label' => new TranslatableMessage('g N2O'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
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
            'data_class' => EmissionFactorRow::class,
            'constraints' => [new Assert\Callback([self::class, 'validateAllOrNothing'])],
        ]);
    }

    public static function validateAllOrNothing(?EmissionFactorRow $row, ExecutionContextInterface $context): void
    {
        if (!$row instanceof EmissionFactorRow || ($row->isEmpty() && $row->isWellToTankEmpty())) {
            return;
        }

        $fields = ['co2eq', 'co2', 'ch4', 'n2o'];

        if (!$row->isWellToTankEmpty()) {
            $fields = [...$fields, 'wellToTankCo2eq', 'wellToTankCo2', 'wellToTankCh4', 'wellToTankN2o'];
        }

        foreach ($fields as $field) {
            if ('' === (string) $row->{$field}) {
                $context->buildViolation('This value should not be blank.')
                    ->atPath($field)
                    ->addViolation()
                ;
            }
        }
    }
}
