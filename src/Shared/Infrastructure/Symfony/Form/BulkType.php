<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class BulkType extends AbstractType
{
    /**
     * @param array<string,mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('elements', ChoiceType::class, [
                'required' => false,
                'label' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['elements'],
                'choice_value' => fn($element): string => (string) $element->id(),
            ])
            ->add('action', AutocompleteType::class, [
                'label' => new TranslatableMessage('Acción a realizar'),
                'required' => true,
                'choices' => $options['actions'],
                'choice_label' => fn(?BulkAction $action): string|TranslatableMessage|null => $action?->name(),
                'choice_value' => fn(?BulkAction $action): ?string => $action?->id(),
            ])
            ->add('execute', SubmitType::class, [
                'label' => new TranslatableMessage('Realizar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'elements' => [],
            'actions' => [],
            'data_class' => BulkRequest::class,
        ]);
    }
}
