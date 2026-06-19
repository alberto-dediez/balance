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
    public function __construct(
        private readonly bool $smsEnabled,
        private readonly bool $whatsappEnabled,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'sms_enabled' => false,
            'whatsapp_enabled' => false,
            'choice_value' => fn(?NotificationChannel $case) => $case?->value,
            'translation_domain' => 'notificationChannelType',
        ]);

        $smsEnabled = $this->smsEnabled;
        $whatsappEnabled = $this->whatsappEnabled;

        $resolver->setNormalizer('choices', static function (OptionsResolver $options, mixed $value) use ($smsEnabled, $whatsappEnabled): array {
            $choices = [];
            foreach (NotificationChannel::cases() as $case) {
                if (!$smsEnabled && NotificationChannel::Sms === $case) {
                    continue;
                }
                if (!$whatsappEnabled && NotificationChannel::Whatsapp === $case) {
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
