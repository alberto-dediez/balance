<?php

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ConfigurationFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field', ChoiceType::class, [
                'choices' => $options['fields'],
                'required' => false,
                'placeholder' => new TranslatableMessage('Ignorar'),
                'translation_domain' => 'fields',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportationColumnConfiguration::class,
            'fields' => []
        ]);
    }
}
