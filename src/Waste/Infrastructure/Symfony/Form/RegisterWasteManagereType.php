<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Shared\Infrastructure\Symfony\Type\BooleanType;
use App\Waste\Application\WasteManager\Register\RegisterWasteManagerRequest;

class RegisterWasteManagerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
            ->add('isPublic', BooleanType::class, [
                'label' => new TranslatableMessage('¿Es público?'),
                'required' => true
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegisterWasteManagerRequest::class,
            'admin' => false
        ]);
    }
}