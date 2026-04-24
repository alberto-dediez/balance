<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Symfony\Form;

use App\HumanResources\Application\Employee\List\ListEmployeesRequest;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListEmployeesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre completo'),
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => new TranslatableMessage('Email'),
                'required' => false,
            ])
        ;

        if ($options['admin']) {
            $builder
                ->add(
                    'accountId',
                    AccountIdType::class,
                    [
                        'label' => new TranslatableMessage('Cuenta'),
                        'required' => false,
                    ],
                )
            ;
        }


        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListEmployeesRequest::class,
            'admin' => false,
        ]);
    }
}
