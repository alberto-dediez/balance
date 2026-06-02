<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Waste\Application\Alert\Check\Checker\AbnormallyHighValueAlertChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class AbnormallyHighParametersType extends AbstractType
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return ['deviationMultiplier' => AbnormallyHighValueAlertChecker::DEFAULT_DEVIATION_MULTIPLIER];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('deviationMultiplier', NumberType::class, [
            'label' => new TranslatableMessage('Multiplicador de desviación'),
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
