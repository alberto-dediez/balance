<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Security\Domain\Model\Account\AccountId;

class ProjectContributionMother
{
    public static function pending(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
        ?\DateTimeImmutable $expiresAt = null,
        ?PaymentId $paymentId = null,
        ?ProjectContributionId $id = null,
    ): ProjectContribution {
        $projectContribution = new ProjectContribution(id: $id ?? ProjectContributionId::generate());
        $projectContribution->register(
            accountId: $accountId ?? AccountId::generate(),
            projectId: $projectId ?? ProjectId::generate(),
            origin: ProjectContributionOrigin::PropertyTripCalculation,
            originId: (string) ProjectContributionId::generate(),
            co2eqKg: $tonnes * 1000,
            tonnes: $tonnes,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            freeAmount: false,
            contributionAmount: round($tonnes * 15.0, 2),
            managementFeeAmount: round($tonnes * 5.0, 2),
            taxableAmount: round($tonnes * 20.0, 2),
            vatRate: 21.0,
            vatAmount: round($tonnes * 20.0 * 0.21, 2),
            totalAmount: round($tonnes * 20.0 * 1.21, 2),
            vatReason: VatExemptionReason::Standard,
            currency: 'EUR',
            contributorName: 'Guest',
            contributorEmail: 'guest@example.com',
            contributorCountry: 'ES',
            contributorIsCompany: false,
            contributorCompanyName: null,
            contributorVatNumber: null,
            expiresAt: $expiresAt ?? new \DateTimeImmutable('+12 hours'),
            paymentId: $paymentId,
        );

        return $projectContribution;
    }

    public static function paid(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
    ): ProjectContribution {
        $projectContribution = self::pending($accountId, $projectId, $tonnes);
        $projectContribution->pay();

        return $projectContribution;
    }

    public static function expired(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
    ): ProjectContribution {
        $projectContribution = self::pending($accountId, $projectId, $tonnes, new \DateTimeImmutable('-1 hour'));
        $projectContribution->expire();

        return $projectContribution;
    }

    /** Still pending but past its reservation window, so it no longer holds credit. */
    public static function overdue(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
        ?PaymentId $paymentId = null,
    ): ProjectContribution {
        return self::pending($accountId, $projectId, $tonnes, new \DateTimeImmutable('-1 hour'), $paymentId);
    }

    public static function random(): ProjectContribution
    {
        return self::pending();
    }
}
