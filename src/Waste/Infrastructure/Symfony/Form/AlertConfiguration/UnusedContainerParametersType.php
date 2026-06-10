<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Waste\Application\Alert\Check\Checker\UnusedContainerAlertChecker;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class UnusedContainerParametersType extends AlertParametersType
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return ['unusedDays' => UnusedContainerAlertChecker::DEFAULT_UNUSED_DAYS];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('unusedDays', IntegerType::class, [
            'label' => new TranslatableMessage('Días sin uso'),
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
