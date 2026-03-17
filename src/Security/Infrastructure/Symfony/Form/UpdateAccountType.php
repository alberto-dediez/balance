<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Security\Application\Account\Update\UpdateAccountRequest;

/**
 * @extends AbstractType<UpdateAccountRequest>
 */
class UpdateAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateAccountRequest::class
        ]);
    }
}
