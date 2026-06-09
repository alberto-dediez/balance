<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Infrastructure\UI\Web\DTO\FieldDefinition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ImportationColumnConfiguration>
 */
class ConfigurationTranslationType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $definitions = $options['definitions'];

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($definitions): void {
                $configuration = $event->getData();

                /** @var ?FieldDefinition $definition */
                $definition = $definitions[$configuration->field] ?? null;

                if (null === $definition) {
                    return;
                }

                $event
                    ->getForm()
                    ->add(
                        'translations',
                        CollectionType::class,
                        [
                            'entry_type' => ColumnValueTranslationType::class,
                            'entry_options' => ['definition' => $definition],
                        ],
                    )
                ;
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportationColumnConfiguration::class,
            'definitions' => [],
        ]);
    }
}
