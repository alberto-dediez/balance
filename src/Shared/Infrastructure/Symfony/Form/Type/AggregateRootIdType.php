<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;

/** @extends AbstractType<mixed> */
class AggregateRootIdType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new AggregateRootIdTransformer($options['class'])
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => []
        ]);
    }

    public function getParent()
    {
        return AutocompleteType::class;
    }

    /**
     * @param array<int, mixed> $aggregateRootElements
     * @return array<string, mixed>
     */
    protected function getChoices(array $aggregateRootElements): array
    {
        if (count($aggregateRootElements) === 0) {
            return [];
        }

        $choices = [];

        foreach ($aggregateRootElements as $element) {
            $name = (string) $element;

            $i = 0;

            while (isset($choices[$name])) {
                $name = sprintf("%s (%d)", (string) $element, ++$i);
            }

            $choices[$name] = (string) $element->id();
        }

        return $choices;
    }
}
