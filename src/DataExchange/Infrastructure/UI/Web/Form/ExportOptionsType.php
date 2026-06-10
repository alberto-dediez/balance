<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Form;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Infrastructure\UI\Web\DTO\ExportOptionsRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<ExportOptionsRequest> */
class ExportOptionsType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ExportColumnDefinition[] $definitions */
        $definitions = $options['definitions'];

        /** @var class-string<FormTypeInterface<mixed>> $filterFormClass */
        $filterFormClass = $options['filter_form_class'];

        $choices = [];
        $labels = [];
        foreach ($definitions as $definition) {
            $choices[$definition->key] = $definition->key;
            $labels[$definition->key] = $definition->label;
        }

        $builder
            ->add('filter', $filterFormClass, [
                'label' => false,
            ])
            ->add('columns', ChoiceType::class, [
                'label' => new TranslatableMessage('Columnas a exportar'),
                'choices' => $choices,
                'choice_label' => static fn(string $key): string|TranslatableMessage => $labels[$key] ?? $key,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
            ->add('format', EnumType::class, [
                'label' => new TranslatableMessage('Formato'),
                'class' => ExportationFormat::class,
                'choice_label' => static fn(ExportationFormat $format): string => strtoupper($format->value),
                'required' => true,
            ])
            ->add('export', SubmitType::class, [
                'label' => new TranslatableMessage('Exportar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExportOptionsRequest::class,
            'definitions' => [],
        ]);

        $resolver->setRequired('filter_form_class');
        $resolver->setAllowedTypes('filter_form_class', 'string');
    }
}
