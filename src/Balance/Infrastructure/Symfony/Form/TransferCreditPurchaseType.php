<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseRequest;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class TransferCreditPurchaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destinationAccountId', AccountIdType::class, [
                'label' => new TranslatableMessage('Cuenta de destino'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona una cuenta'),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('tonnes', NumberType::class, [
                'label' => new TranslatableMessage('Toneladas a transferir'),
                'help' => new TranslatableMessage('Déjalo vacío para transferir todo el remanente'),
                'required' => false,
                'scale' => 4,
                'constraints' => [
                    new Assert\Positive(),
                ],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Transferir crédito'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferCreditPurchaseRequest::class,
        ]);
    }
}
