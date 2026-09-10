<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
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

    public static function validated(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 10.0,
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByClient,
    ): CreditPurchase {
        $creditPurchase = self::pending($accountId, $projectId, $tonnes);
        $creditPurchase->validate(
            purchasedTonnes: $tonnes,
            paymentType: $paymentType,
            reference: 'ref-' . (string) $creditPurchase->id(),
            certificate: null,
            validatedBy: UserId::generate(),
        );

        return $creditPurchase;
    }

    /** @param list<CreditAlertThreshold> $notifiedThresholds */
    public static function validatedAt(
        \DateTimeImmutable $resolvedAt,
        AccountId $accountId,
        ProjectId $projectId,
        float $tonnes = 10.0,
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByClient,
        array $notifiedThresholds = [],
    ): CreditPurchase {
        $id = CreditPurchaseId::generate();

        return new CreditPurchase(
            id: $id,
            accountId: $accountId,
            projectId: $projectId,
            requestedBy: UserId::generate(),
            requestedTonnes: $tonnes,
            status: CreditPurchaseStatus::Validated,
            purchasedTonnes: $tonnes,
            paymentType: $paymentType,
            reference: 'ref-' . $id,
            resolvedBy: UserId::generate(),
            notifiedThresholds: array_map(
                static fn(CreditAlertThreshold $threshold): int => $threshold->value,
                $notifiedThresholds,
            ),
            resolvedAt: $resolvedAt,
        );
    }

    public static function transferred(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 100.0,
        float $transferredTonnes = 70.0,
        ?float $amount = null,
    ): CreditPurchase {
        $creditPurchase = self::pending($accountId, $projectId, $tonnes);
        $creditPurchase->validate(
            purchasedTonnes: $tonnes,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: 'ref-' . (string) $creditPurchase->id(),
            certificate: null,
            validatedBy: UserId::generate(),
            amount: $amount,
        );

        $pricePerTonne = $creditPurchase->pricePerTonne();

        $creditPurchase->transfer(
            tonnes: $transferredTonnes,
            amount: null !== $pricePerTonne ? round($pricePerTonne * $transferredTonnes, 2) : 0.0,
            destinationCreditPurchaseId: CreditPurchaseId::generate(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );

        return $creditPurchase;
    }

    public static function receivedLot(
        string $reference,
        float $purchasedTonnes = 100.0,
        float $offsetTonnes = 30.0,
        int $lotNumber = 2,
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        ?float $amount = null,
    ): CreditPurchase {
        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->receive(
            sourceCreditPurchaseId: CreditPurchaseId::generate(),
            accountId: $accountId ?? AccountId::generate(),
            projectId: $projectId ?? ProjectId::generate(),
            purchasedTonnes: $purchasedTonnes,
            offsetTonnes: $offsetTonnes,
            amount: $amount,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: $reference,
            lotNumber: $lotNumber,
            certificate: null,
            transferredBy: UserId::generate(),
        );

        return $creditPurchase;
    }

    public static function partiallyValidated(?AccountId $accountId = null, ?ProjectId $projectId = null): CreditPurchase
    {
        $creditPurchase = self::pending($accountId, $projectId, 10.0);
        $creditPurchase->validate(
            purchasedTonnes: 6.0,
            paymentType: CreditPurchasePaymentType::PaidByClient,
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
