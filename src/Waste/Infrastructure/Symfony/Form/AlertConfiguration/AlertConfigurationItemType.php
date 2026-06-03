<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<AlertConfigurationItemData> */
class AlertConfigurationItemType extends AbstractType
{
    /**
     * Validates that no two items in the collection share the same combination of
     * propertyId, wasteId and alert type. Meant to be applied as a `constraints`
     * option on the `items` CollectionType field.
     */
    public static function uniqueItemsConstraint(): Callback
    {
        return new Callback(static function (mixed $items, ExecutionContextInterface $context): void {
            if (!is_iterable($items)) {
                return;
            }

            $seen = [];
            $index = 0;
            foreach ($items as $item) {
                if (!$item instanceof AlertConfigurationItemData) {
                    ++$index;
                    continue;
                }

                $key = implode('|', [
                    $item->type ?? '',
                    $item->propertyId?->__toString() ?? '',
                    $item->wasteId?->__toString() ?? '',
                ]);

                if (isset($seen[$key])) {
                    $context->buildViolation('alert_configuration.item_cannot_be_duplicated')
                        ->atPath("[$index].type")
                        ->addViolation();
                }

                $seen[$key] = true;
                ++$index;
            }
        });
    }

    /**
     * Default parameters for every AlertType, keyed by type value.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function parameterDefaults(): array
    {
        $defaults = [];
        foreach (AlertType::cases() as $type) {
            $parametersClass = $type->parametersFormType();
            $defaults[$type->value] = $parametersClass::defaults();
        }

        return $defaults;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeChoices = [];
        foreach (AlertType::cases() as $type) {
            $typeChoices[$type->value] = $type->value;
        }

        $builder
            ->add('type', ChoiceType::class, [
                'label' => new TranslatableMessage('Tipo de alerta'),
                'choices' => $typeChoices,
                'choice_label' => static fn(string $choice) => new TranslatableMessage($choice, [], 'alertType'),
                'placeholder' => false,
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las propiedades'),
            ])
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Residuo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los residuos'),
            ])
            ->add('enabled', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => true,
            ]);

        $parametersBuilder = $builder->create('parameters', FormType::class, [
            'compound' => true,
            'label' => false,
        ]);

        foreach (AlertType::cases() as $type) {
            $parametersBuilder->add($type->value, $type->parametersFormType(), [
                'label' => false,
            ]);
        }

        $builder->add($parametersBuilder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AlertConfigurationItemData::class,
        ]);
    }
}
