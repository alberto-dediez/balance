<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\EmissionFactorRow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<EmissionFactorRow> */
class MerchandisingItemEmissionFactorRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $numberAttr = ['step' => 'any', 'min' => 0];

        $builder
            ->add('merchandisingItemId', HiddenType::class)
            ->add('co2eq', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2eq (nueva fabricación)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('co2', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2 (nueva fabricación)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('ch4', TextType::class, [
                'label' => new TranslatableMessage('g CH4 (nueva fabricación)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('n2o', TextType::class, [
                'label' => new TranslatableMessage('g N2O (nueva fabricación)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('recycledCo2eq', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2eq (reciclado)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('recycledCo2', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2 (reciclado)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('recycledCh4', TextType::class, [
                'label' => new TranslatableMessage('g CH4 (reciclado)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('recycledN2o', TextType::class, [
                'label' => new TranslatableMessage('g N2O (reciclado)'),
                'required' => false,
                'attr' => $numberAttr,
                'constraints' => [new Assert\Type(type: 'numeric')],
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
        if (!$row instanceof EmissionFactorRow || $row->isEmpty()) {
            return;
        }

        foreach (['co2eq', 'co2', 'ch4', 'n2o', 'recycledCo2eq', 'recycledCo2', 'recycledCh4', 'recycledN2o'] as $field) {
            if ('' === (string) $row->{$field}) {
                $context->buildViolation('Este valor no debería estar vacío.')
                    ->atPath($field)
                    ->addViolation()
                ;
            }
        }
    }
}
