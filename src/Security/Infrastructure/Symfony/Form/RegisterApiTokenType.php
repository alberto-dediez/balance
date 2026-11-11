<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\ApiToken\Register\RegisterApiTokenRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<RegisterApiTokenRequest>
 */
class RegisterApiTokenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 100),
                ],
            ])
            ->add('expiresAt', DateType::class, [
                'label' => new TranslatableMessage('Fecha de caducidad'),
                'help' => new TranslatableMessage('Déjala vacía para que no caduque'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new GreaterThan('today'),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Crear token'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterApiTokenRequest::class,
        ]);
    }
}
