<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

enum CreditAlertThreshold: int
{
    case Warning = 80;
    case High = 90;
    case Depleted = 100;

    public static function highestReachedBy(float $consumedTonnes, float $purchasedTonnes): ?self
    {
        $highest = null;

        foreach (self::cases() as $threshold) {
            if ($threshold->isReachedBy($consumedTonnes, $purchasedTonnes)) {
                $highest = $threshold;
            }
        }

        return $highest;
    }

    public function isReachedBy(float $consumedTonnes, float $purchasedTonnes): bool
    {
        return $consumedTonnes >= $purchasedTonnes * $this->value / 100 - CreditBalance::TONNE_TOLERANCE;
    }

    /** @return list<self> */
    public function andLower(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(self $threshold): bool => $threshold->value <= $this->value,
        ));
    }
}
