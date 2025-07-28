<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;

class AggregateRootIdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new AggregateRootIdTransformer($options['class'])
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => []
        ]);
    }
    
    public function getParent()
    {
        return AutocompleteType::class;
    }

    protected function getChoices(array $aggregateRootElements): array
    {
        if (count($aggregateRootElements) == 0) {
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