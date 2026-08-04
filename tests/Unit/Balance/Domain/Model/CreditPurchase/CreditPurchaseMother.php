<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class CreditPurchaseMother
{
    public static function pending(?AccountId $accountId = null, ?ProjectId $projectId = null, float $tonnes = 10.0): CreditPurchase
    {
        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(
            $accountId ?? AccountId::generate(),
            $projectId ?? ProjectId::generate(),
            UserId::generate(),
            $tonnes,
        );

        return $creditPurchase;
    }

    public static function validated(?AccountId $accountId = null, ?ProjectId $projectId = null, float $tonnes = 10.0): CreditPurchase
    {
        $creditPurchase = self::pending($accountId, $projectId, $tonnes);
        $creditPurchase->validate(
            purchasedTonnes: $tonnes,
            amount: $tonnes * 15.0,
            reference: 'ref-' . (string) $creditPurchase->id(),
            certificate: null,
            validatedBy: UserId::generate(),
        );

        return $creditPurchase;
    }

    public static function partiallyValidated(?AccountId $accountId = null, ?ProjectId $projectId = null): CreditPurchase
    {
        $creditPurchase = self::pending($accountId, $projectId, 10.0);
        $creditPurchase->validate(
            purchasedTonnes: 6.0,
            amount: 90.0,
            reference: 'ref-' . (string) $creditPurchase->id(),
            certificate: null,
            validatedBy: UserId::generate(),
        );

        return $creditPurchase;
    }

    public static function rejected(?AccountId $accountId = null, ?ProjectId $projectId = null): CreditPurchase
    {
        $creditPurchase = self::pending($accountId, $projectId);
        $creditPurchase->reject('No hay disponibilidad', UserId::generate());

        return $creditPurchase;
    }

    public static function forAccountAndProject(AccountId $accountId, ProjectId $projectId): CreditPurchase
    {
        return self::pending($accountId, $projectId);
    }

    public static function random(): CreditPurchase
    {
        return self::pending();
    }
}
