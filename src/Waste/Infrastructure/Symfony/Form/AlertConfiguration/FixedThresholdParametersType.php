<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Waste\Application\Alert\Check\Checker\FixedThresholdAlertChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class FixedThresholdParametersType extends AbstractType
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return ['thresholdKg' => FixedThresholdAlertChecker::DEFAULT_THRESHOLD_KG];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('thresholdKg', NumberType::class, [
            'label' => new TranslatableMessage('Umbral (kg)'),
            'required' => true,
            'scale' => 2,
            'constraints' => [new Assert\NotBlank(), new Assert\Range(min: 0.1)],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => self::defaults(),
        ]);
    }
}
