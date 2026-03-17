<?php

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryRequest;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UpdateServiceCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar')
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UpdateServiceCategoryRequest::class,
            'admin' => false
        ]);
    }
}