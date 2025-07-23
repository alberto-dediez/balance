<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AutocompleteEntityType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = isset($options['attr']) ? $options['attr'] : [];


        $view->vars['attr'] = array_merge($attr, ['data-controller' => 'autocomplete']);
    }

    public function getParent()
    {
        return EntityType::class;
    }
}