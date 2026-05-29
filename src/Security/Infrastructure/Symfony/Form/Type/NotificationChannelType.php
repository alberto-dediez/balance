<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class NotificationChannelType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'sms_enabled' => false,
            'choice_value' => fn(?NotificationChannel $case) => $case?->value,
            'translation_domain' => 'notificationChannelType',
        ]);

        $resolver->setAllowedTypes('sms_enabled', 'bool');

        $resolver->setNormalizer('choices', static function (OptionsResolver $options, mixed $value): array {
            $choices = [];
            foreach (NotificationChannel::cases() as $case) {
                if (!$options['sms_enabled'] && NotificationChannel::Sms === $case) {
                    continue;
                }
                $choices[$case->value] = $case;
            }

            return $choices;
        });
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
