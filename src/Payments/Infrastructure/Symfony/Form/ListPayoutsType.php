<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use App\Payments\Application\Payout\List\ListPayoutsRequest;
use App\Payments\Infrastructure\Symfony\Form\Type\PayoutStatusType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListPayoutsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', PayoutStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('number', TextType::class, [
                'label' => new TranslatableMessage('Número'),
                'required' => false,
            ])
            ->add('reference', TextType::class, [
                'label' => new TranslatableMessage('Referencia'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListPayoutsRequest::class,
        ]);
    }
}
