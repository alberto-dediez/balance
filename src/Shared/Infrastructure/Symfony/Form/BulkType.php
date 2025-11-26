<?php

namespace App\Shared\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;

class BulkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('elements', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['elements'],
                'choice_value' => function ($element) {
                    return (string) $element->id();
                }
            ])
            ->add('action', AutocompleteType::class, [
                'label' => new TranslatableMessage('Acción a realizar'),
                'required' => true,
                'choices' => $options['actions'],
                'choice_label' => function (?BulkAction $action) {
                    return $action?->name();
                },
                'choice_value' => function (?BulkAction $action) {
                    return $action?->id();
                }
            ])
            ->add('execute', SubmitType::class, [
                'label' => new TranslatableMessage('Realizar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'elements' => [],
            'actions' => [],
            'data_class' => BulkRequest::class
        ]);
    }
}
