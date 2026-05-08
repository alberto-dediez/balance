<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonId;

class ReasonMother
{
    public static function registeredReason(): Reason
    {
        return self::fromDescription('description');
    }

    public static function fromDescription(string $description): Reason
    {
        return self::from(
            description: $description,
            id: (string) ReasonId::generate(),
        );
    }

    public static function fromDescriptionAndId(string $description, string $id): Reason
    {
        return self::from(
            description: $description,
            id: $id,
        );
    }

    public static function from(
        string $description,
        string $id,
    ): Reason {
        $reason = new Reason(
            id: new ReasonId($id),
        );
        $reason->register($description);

        return $reason;
    }

    public static function random(): Reason
    {
        return self::from(
            description: 'reason-' . random_int(100, 999),
            id: (string) ReasonId::generate(),
        );
    }
}
