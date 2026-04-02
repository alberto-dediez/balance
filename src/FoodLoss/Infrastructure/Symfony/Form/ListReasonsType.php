<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Reason\List\ListReasonsRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PriorityType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListReasonsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => false,
            ])
            ->add('priority', PriorityType::class, [
                'label' => new TranslatableMessage('Prioridad'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListReasonsRequest::class,
        ]);
    }
}
