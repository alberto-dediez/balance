<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AutocompleteRemoteType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = isset($options['attr']) ? $options['attr'] : [];
        if (!isset($attr['data-controller'])) {
            $attr = array_merge($attr, ['data-controller' => 'autocomplete']);
        } else {
            $attr['data-controller'] .= ' autocomplete';
        }

        if (null !== $options['autocomplete-path'] && !empty($options['autocomplete-path'])) {
            $attr = array_merge($attr, [
                'data-autocomplete-path-value' => $options['autocomplete-path']
            ]);
        }

        $view->vars['attr'] = $attr;
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['valueLabel'] = null !== $options['repository'] && null !== $form->getData() ? $options['repository']->find($form->getData()) : null;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'autocomplete-path' => '',
            'placeholder' => null
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }
}