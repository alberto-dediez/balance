<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AutocompleteType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = isset($options['attr']) ? $options['attr'] : [];

        if (!isset($attr['data-controller'])) {
            $view->vars['attr'] = array_merge($attr, ['data-controller' => 'autocomplete']);
        } else {
            $view->vars['attr']['data-controller'] .= ' autocomplete';
        }
        
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}