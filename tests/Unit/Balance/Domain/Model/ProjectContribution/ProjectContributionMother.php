<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

class ProjectContributionMother
{
    public static function pending(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
        ?\DateTimeImmutable $expiresAt = null,
        ?PaymentId $paymentId = null,
        ?ProjectContributionId $id = null,
        ?string $originId = null,
        ?string $originName = 'Hotel Los Olivos',
        bool $isCompany = false,
        ProjectContributionOrigin $origin = ProjectContributionOrigin::PropertyTripCalculation,
        Locale $locale = Locale::Spanish,
    ): ProjectContribution {
        $projectContribution = new ProjectContribution(id: $id ?? ProjectContributionId::generate());
        $projectContribution->register(
            accountId: $accountId ?? AccountId::generate(),
            projectId: $projectId ?? ProjectId::generate(),
            origin: $origin,
            originId: $originId ?? (string) ProjectContributionId::generate(),
            originName: $originName,
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
            contributorIsCompany: $isCompany,
            contributorCompanyName: $isCompany ? 'Acme SL' : null,
            contributorVatNumber: $isCompany ? 'B12345678' : null,
            locale: $locale,
            expiresAt: $expiresAt ?? new \DateTimeImmutable('+12 hours'),
            paymentId: $paymentId,
        );

        return $projectContribution;
    }

    public static function paid(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
        ?string $originId = null,
        ProjectContributionOrigin $origin = ProjectContributionOrigin::PropertyTripCalculation,
        ?string $originName = 'Hotel Los Olivos',
        Locale $locale = Locale::Spanish,
    ): ProjectContribution {
        $projectContribution = self::pending($accountId, $projectId, $tonnes, originId: $originId, originName: $originName, origin: $origin, locale: $locale);
        $projectContribution->pay();

        return $projectContribution;
    }

    public static function ownCreditFunded(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
        ?ProjectContributionId $id = null,
        ?string $originId = null,
        ProjectContributionOrigin $origin = ProjectContributionOrigin::EventCarbonFootprint,
        ?string $originName = 'Congreso Andalucía Sostenible',
    ): ProjectContribution {
        $projectContribution = new ProjectContribution(id: $id ?? ProjectContributionId::generate());
        $projectContribution->register(
            accountId: $accountId ?? AccountId::generate(),
            projectId: $projectId ?? ProjectId::generate(),
            origin: $origin,
            originId: $originId ?? (string) ProjectContributionId::generate(),
            originName: $originName,
            co2eqKg: $tonnes * 1000,
            tonnes: $tonnes,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            freeAmount: false,
            contributionAmount: round($tonnes * 15.0, 2),
            managementFeeAmount: 0.0,
            taxableAmount: 0.0,
            vatRate: 0.0,
            vatAmount: 0.0,
            totalAmount: 0.0,
            vatReason: null,
            currency: 'EUR',
            contributorName: 'Hoteles del Sur SL',
            contributorEmail: 'owner@example.com',
            contributorCountry: 'ES',
            contributorIsCompany: true,
            contributorCompanyName: 'Hoteles del Sur SL',
            contributorVatNumber: 'B12345678',
            locale: Locale::Spanish,
            expiresAt: null,
            funding: ProjectContributionFunding::OwnCredit,
        );
        $projectContribution->pay();

        return $projectContribution;
    }

    public static function pendingFromAttendeeSurveyResponse(
        AccountId $accountId,
        ProjectId $projectId,
        string $originId,
        float $tonnes = 1.0,
    ): ProjectContribution {
        return self::pending(
            $accountId,
            $projectId,
            $tonnes,
            originId: $originId,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originName: 'Congreso Andalucía Sostenible',
        );
    }

    public static function paidFromAttendeeSurveyResponse(
        AccountId $accountId,
        ProjectId $projectId,
        string $originId,
        float $tonnes = 1.0,
    ): ProjectContribution {
        return self::paid(
            $accountId,
            $projectId,
            $tonnes,
            originId: $originId,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originName: 'Congreso Andalucía Sostenible',
        );
    }

    public static function paidAt(
        \DateTimeImmutable $paidAt,
        AccountId $accountId,
        ProjectId $projectId,
        float $tonnes = 1.0,
    ): ProjectContribution {
        return new ProjectContribution(
            id: ProjectContributionId::generate(),
            accountId: $accountId,
            projectId: $projectId,
            originId: (string) ProjectContributionId::generate(),
            originName: 'Hotel Los Olivos',
            co2eqKg: $tonnes * 1000,
            tonnes: $tonnes,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            contributionAmount: round($tonnes * 15.0, 2),
            managementFeeAmount: round($tonnes * 5.0, 2),
            taxableAmount: round($tonnes * 20.0, 2),
            vatRate: 21.0,
            vatAmount: round($tonnes * 20.0 * 0.21, 2),
            totalAmount: round($tonnes * 20.0 * 1.21, 2),
            vatReason: VatExemptionReason::Standard,
            contributorName: 'Guest',
            contributorEmail: 'guest@example.com',
            contributorCountry: 'ES',
            status: ProjectContributionStatus::Paid,
            registeredAt: $paidAt,
            paidAt: $paidAt,
            resolvedAt: $paidAt,
        );
    }

    public static function paidCompany(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
        float $tonnes = 1.0,
    ): ProjectContribution {
        $projectContribution = self::pending(
            $accountId,
            $projectId,
            $tonnes,
            isCompany: true,
        );
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
