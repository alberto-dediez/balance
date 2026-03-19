<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<int|float|string|null> */
class NumberUnitType extends AbstractType
{
    /** @param FormInterface<int|float|string|null> $form */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['symbol'] = $options['symbol'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'symbol' => '%',
            'invalid_message' => 'Please enter a number value.',
        ]);

        $resolver->setAllowedTypes('symbol', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'number_unit';
    }

    public function getParent(): string
    {
        return NumberType::class;
    }
}
