<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<PairFieldsImportationRequest>
 */
class TranslationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pairedConfigurations', CollectionType::class, [
                'entry_type' => ConfigurationTranslationType::class,
                'entry_options' => [
                    'definitions' => $options['definitions'],
                ],
            ])
            ->add('continue', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PairFieldsImportationRequest::class,
            'definitions' => [],
        ]);
    }
}
