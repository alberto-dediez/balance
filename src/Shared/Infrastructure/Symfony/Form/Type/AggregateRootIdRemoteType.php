<?php

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteRemoteType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;

/** @extends AbstractType<mixed> */
class AggregateRootIdRemoteType extends AbstractType
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

    public function getParent(): string
    {
        return AutocompleteRemoteType::class;
    }
}
