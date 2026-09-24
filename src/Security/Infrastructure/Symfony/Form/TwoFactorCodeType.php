<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<mixed>
 */
class TwoFactorCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => new TranslatableMessage('Código de verificación'),
                'required' => true,
                'attr' => [
                    'inputmode' => 'numeric',
                    'autocomplete' => 'one-time-code',
                    'maxlength' => 6,
                    'pattern' => '\d{6}',
                    'autofocus' => true,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Regex(pattern: '/^\d{6}$/'),
                ],
            ])
            ->add('verify', SubmitType::class, [
                'label' => new TranslatableMessage('Verificar'),
            ])
        ;
    }
}
