<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<mixed>
 */
class AssignValuesType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (count($options['fields']) > 0) {
            foreach ($options['fields'] as $field) {
                $definition = $options['definitions'][$field] ?? null;

                if (null === $definition) {
                    continue;
                }

                $builder->add($field, $definition->fieldType, $definition->options);
            }
        }

        $builder
            ->add('continue', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'fields' => [],
            'definitions' => [],
        ]);
    }
}
