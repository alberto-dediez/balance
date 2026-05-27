<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\WasteManager\List\ListWasteManagersRequest;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListWasteManagersType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('animalFeedAuthorized', BooleanType::class, [
                'label' => new TranslatableMessage('Alimentación animal'),
                'placeholder' => new TranslatableMessage('Todos'),
                'required' => false,
            ])
            ->add('compostAuthorized', BooleanType::class, [
                'label' => new TranslatableMessage('Compost / Biogás'),
                'placeholder' => new TranslatableMessage('Todos'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListWasteManagersRequest::class,
        ]);
    }
}
