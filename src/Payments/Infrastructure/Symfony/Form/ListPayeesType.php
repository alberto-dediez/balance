<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use App\Payments\Application\Payee\List\ListPayeesRequest;
use App\Payments\Infrastructure\Symfony\Form\Type\PayeeStatusType;
use App\Payments\Infrastructure\Symfony\Form\Type\PayeeTypeType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListPayeesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', PayeeTypeType::class, [
                'label' => new TranslatableMessage('Tipo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('status', PayeeStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('search', TextType::class, [
                'label' => new TranslatableMessage('Buscar'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListPayeesRequest::class,
        ]);
    }
}
