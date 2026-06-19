<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Deliver;

final class SignerDataMissingException extends \InvalidArgumentException
{
    public static function missingName(): self
    {
        return new self('Signer name is required to start a signature process.');
    }

    public static function missingEmailForEmailChannel(): self
    {
        return new self('Signer email is required when notification channel is Email.');
    }

    public static function missingPhoneForSmsChannel(): self
    {
        return new self('Signer phone is required when notification channel is SMS or WhatsApp.');
    }

    public static function missingNotificationChannel(): self
    {
        return new self('Notification channel is required to start a signature process.');
    }
}
