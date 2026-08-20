<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Infrastructure\Symfony\Form\DataTransformer\HexColorTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<HexColor> */
class HexColorType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new HexColorTransformer());
    }

    /** @param FormInterface<HexColor> $form */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['fallback'] = $options['fallback'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'fallback' => '#FFFFFF',
            'invalid_message' => 'Introduce un color hexadecimal de 6 dígitos, por ejemplo #8A2432.',
            'attr' => [
                'placeholder' => '#RRGGBB',
                'maxlength' => 7,
            ],
        ]);

        $resolver->setAllowedTypes('fallback', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'hex_color';
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
