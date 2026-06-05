<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Waste\Application\Alert\Check\Checker\ThresholdExceededAlertChecker;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class ThresholdExceededParametersType extends AlertParametersType
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'thresholdPercentage' => ThresholdExceededAlertChecker::DEFAULT_THRESHOLD_PERCENTAGE,
            'statisticsDays' => ThresholdExceededAlertChecker::DEFAULT_STATS_DAYS,
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('thresholdPercentage', NumberType::class, [
                'label' => new TranslatableMessage('Umbral (%)'),
                'required' => true,
                'scale' => 1,
                'constraints' => [new Assert\NotBlank(), new Assert\Range(min: 1)],
            ])
            ->add('statisticsDays', IntegerType::class, [
                'label' => new TranslatableMessage('Días para calcular la estadística'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Range(min: 1)],
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
