<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\Reject\RejectCreditPurchaseRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RejectCreditPurchaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', TextareaType::class, [
                'label' => new TranslatableMessage('Motivo del rechazo'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Rechazar solicitud'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RejectCreditPurchaseRequest::class,
        ]);
    }
}
