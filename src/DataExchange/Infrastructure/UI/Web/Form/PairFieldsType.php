<?php

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PairFieldsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('configurations', CollectionType::class, [
                'entry_type' => ConfigurationFieldType::class,
                'entry_options' => [
                    'fields' => $options['fields'],
                ]
            ])
            ->add('continue', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PairFieldsImportationRequest::class,
            'fields' => []
        ]);
    }
}
