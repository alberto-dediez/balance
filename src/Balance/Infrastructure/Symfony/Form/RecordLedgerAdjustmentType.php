<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Infrastructure\Symfony\Form\Type\LedgerAdjustmentTargetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RecordLedgerAdjustmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('target', LedgerAdjustmentTargetType::class, [
                'label' => new TranslatableMessage('Beneficiario'),
                'required' => true,
                'disabled' => $options['isReversal'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('amount', MoneyType::class, [
                'label' => new TranslatableMessage('Importe'),
                'required' => true,
                'currency' => 'EUR',
                'constraints' => [new Assert\NotNull(), new Assert\NotEqualTo(0)],
            ])
            ->add('concept', TextareaType::class, [
                'label' => new TranslatableMessage('Motivo'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar ajuste'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => LedgerAdjustmentFormData::class,
                'isReversal' => false,
            ])
            ->setAllowedTypes('isReversal', 'bool')
        ;
    }
}
