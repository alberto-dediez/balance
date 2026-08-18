<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutReference;
use App\Security\Domain\Model\User\UserId;

class PayoutMother
{
    public static function draft(?PayeeId $payeeId = null, float $amount = 280.0, int $linesCount = 3): Payout
    {
        $payout = new Payout(id: PayoutId::generate());

        $payout->create(
            number: '2026/0001',
            payeeId: $payeeId ?? PayeeId::generate(),
            baseAmount: $amount,
            currency: 'EUR',
            linesCount: $linesCount,
            createdBy: UserId::generate(),
        );

        return $payout;
    }

    public static function sent(?PayeeId $payeeId = null): Payout
    {
        $payout = self::draft($payeeId);
        $payout->send(new PayoutReference(gateway: 'manual'));

        return $payout;
    }

    public static function paid(?PayeeId $payeeId = null): Payout
    {
        $payout = self::sent($payeeId);
        $payout->pay('TRF-1', null, UserId::generate());

        return $payout;
    }

    public static function failed(?PayeeId $payeeId = null, string $reason = 'IBAN incorrecto'): Payout
    {
        $payout = self::draft($payeeId);
        $payout->fail($reason);

        return $payout;
    }

    public static function cancelled(?PayeeId $payeeId = null): Payout
    {
        $payout = self::draft($payeeId);
        $payout->cancel();

        return $payout;
    }

    public static function random(): Payout
    {
        $payout = new Payout(id: PayoutId::generate());

        $payout->create(
            number: sprintf('2026/%s', uniqid('', true)),
            payeeId: PayeeId::generate(),
            baseAmount: 280.0,
            currency: 'EUR',
            linesCount: 3,
            createdBy: UserId::generate(),
        );

        return $payout;
    }
}
