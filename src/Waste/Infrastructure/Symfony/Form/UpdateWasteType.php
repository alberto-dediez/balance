<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use App\Waste\Application\Waste\Update\UpdateWasteRequest;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;

class UpdateWasteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
             ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
            ->add('isDangerous', BooleanType::class, [
                'label' => new TranslatableMessage('¿Es peligroso?'),
                'required' => true
            ])
            ->add('density', NumberType::class, [
                'required' => true,
                'label' => new TranslatableMessage('Densidad')
            ]) 
            ->add('color', ColorType::class, [
                'required' => true,
                'label' => new TranslatableMessage('Color')
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
            'data_class' => UpdateWasteRequest::class,
            'admin' => false
        ]);
    }
}