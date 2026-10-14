<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class AutocompleteRemoteType extends AbstractType
{
    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $options['attr'] ?? [];
        if (!isset($attr['data-controller'])) {
            $attr = array_merge($attr, ['data-controller' => 'autocomplete']);
        } else {
            $attr['data-controller'] .= ' autocomplete';
        }

        if (!empty($options['autocomplete-path'])) {
            $attr = array_merge($attr, [
                'data-autocomplete-path-value' => $options['autocomplete-path'],
            ]);
        }

        if (!empty($options['submit-on-change'])) {
            $attr = array_merge($attr, [
                'data-autocomplete-submit-on-change-value' => '1',
            ]);
        }

        $view->vars['attr'] = $attr;
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['valueLabel'] = $this->valueLabel($form, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete-path' => '',
            'placeholder' => null,
            'repository' => null,
            'value_label' => null,
            'submit-on-change' => false,
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }

    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    private function valueLabel(FormInterface $form, array $options): mixed
    {
        if (null !== $options['value_label']) {
            return $options['value_label'];
        }

        if (null === $options['repository'] || null === $form->getData()) {
            return null;
        }

        return $options['repository']->find($form->getData());
    }
}
