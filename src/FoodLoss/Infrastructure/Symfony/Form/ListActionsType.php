<?php

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\FoodLoss\Application\Action\List\ListActionsRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PriorityType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;

class ListActionsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => true
            ])
            ->add('priority', PriorityType::class, [
                'label' => new TranslatableMessage('Prioridad'),
                'required' => true
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListActionsRequest::class
        ]);
    }
}