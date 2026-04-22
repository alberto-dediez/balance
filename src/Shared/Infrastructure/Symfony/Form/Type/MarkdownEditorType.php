<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class MarkdownEditorType extends AbstractType
{
    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['height'] = $options['height'];
        $view->vars['initial_edit_type'] = $options['initial_edit_type'];
        $view->vars['hide_image'] = $options['hide_image'];
        $view->vars['hide_link'] = $options['hide_link'];
        $view->vars['hide_mode_switch'] = $options['hide_mode_switch'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'height' => '400px',
            'initial_edit_type' => 'wysiwyg',
            'hide_image' => true,
            'hide_link' => true,
            'hide_mode_switch' => true,
        ]);

        $resolver->setAllowedTypes('height', 'string');
        $resolver->setAllowedTypes('hide_image', 'bool');
        $resolver->setAllowedTypes('hide_link', 'bool');
        $resolver->setAllowedTypes('hide_mode_switch', 'bool');
        $resolver->setAllowedValues('initial_edit_type', ['wysiwyg', 'markdown']);
    }

    public function getBlockPrefix(): string
    {
        return 'markdown_editor';
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
