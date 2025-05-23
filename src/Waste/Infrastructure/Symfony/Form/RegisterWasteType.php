<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Waste\Application\Waste\Register\RegisterWasteRequest;

class RegisterWasteType extends AbstractType
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
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegisterWasteRequest::class
        ]);
    }
}