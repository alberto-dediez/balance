<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Waste\Domain\Model\Alert\AlertType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class AlertConfigurationItemType extends AbstractType
{
    /**
     * Returns default item data for all AlertType cases.
     *
     * @return array<string, array{enabled: bool, parameters: array<string, mixed>}>
     */
    public static function allDefaults(): array
    {
        $defaults = [];
        foreach (AlertType::cases() as $type) {
            $parametersClass = $type->parametersFormType();
            $defaults[$type->value] = [
                'enabled' => false,
                'parameters' => $parametersClass::defaults(),
            ];
        }

        return $defaults;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AlertType $alertType */
        $alertType = $options['alert_type'];

        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => false,
            ])
            ->add('parameters', $alertType->parametersFormType(), [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('alert_type');
        $resolver->setAllowedTypes('alert_type', AlertType::class);
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
