<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/** @extends AbstractType<mixed> */
class AutocompleteEntityType extends AbstractType
{
    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $options['attr'] ?? [];

        $view->vars['attr'] = array_merge($attr, ['data-controller' => 'autocomplete']);
    }

    public function getParent()
    {
        return EntityType::class;
    }
}
