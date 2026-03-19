<?php

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColumnValueTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null === $options['definition']) {
            return ;
        }

        $builder
            ->add(
                'translation',
                $options['definition']->fieldType,
                $options['definition']->options
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ColumnValueTranslation::class,
            'definition' => null
        ]);
    }
}
