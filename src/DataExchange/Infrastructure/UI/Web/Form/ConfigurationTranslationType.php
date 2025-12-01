<?php

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Infrastructure\UI\Web\DTO\TranslationDefinition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ConfigurationTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $definitions = $options['definitions'];

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($definitions) {
                $configuration = $event->getData();

                /** @var ?TranslationDefinition $definition */
                $definition = $definitions[$configuration->field] ?? null;

                if (null === $definition) {
                    return ;
                }

                $event
                    ->getForm()
                    ->add(
                        'translations', CollectionType::class, [
                            'entry_type' => ColumnValueTranslationType::class,
                            'entry_options' => ['definition' => $definition],
                        ]
                    )
                ;
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ImportationColumnConfiguration::class,
            'definitions' => []
        ]);
    }
}
