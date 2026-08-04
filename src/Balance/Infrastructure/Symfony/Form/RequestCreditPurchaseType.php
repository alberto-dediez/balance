<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RequestCreditPurchaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tonnes', NumberType::class, [
                'label' => new TranslatableMessage('Toneladas de CO2 solicitadas'),
                'required' => true,
                'scale' => 2,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('request', SubmitType::class, [
                'label' => new TranslatableMessage('Solicitar créditos'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RequestCreditPurchaseRequest::class,
        ]);
    }
}
