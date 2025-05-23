<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AggregateRootIdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                //AggregateRootId a string
                function ($propertyId): string {
                    return $propertyId ? (string) $propertyId : '';
                },
                //string to AggregateRootId
                function (?string $propertyId) use ($options) {
                    $class = $options['class'];

                    return (null == $propertyId || empty($propertyId)) ? null : new $class($propertyId);
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => []
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
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