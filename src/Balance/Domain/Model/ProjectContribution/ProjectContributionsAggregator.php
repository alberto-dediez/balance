<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

final class ProjectContributionsAggregator
{
    /** @param iterable<ProjectContribution> $projectContributions */
    public function aggregate(iterable $projectContributions): ProjectContributionsAggregation
    {
        $contributions = 0;
        $counts = [
            ProjectContributionStatus::Paid->value => 0,
            ProjectContributionStatus::Pending->value => 0,
            ProjectContributionStatus::Failed->value => 0,
            ProjectContributionStatus::Expired->value => 0,
        ];

        $paidTotalAmount = 0.0;
        $paidContributionAmount = 0.0;
        $paidManagementFeeAmount = 0.0;
        $paidTonnes = 0.0;
        $paidCo2eqKg = 0.0;

        foreach ($projectContributions as $projectContribution) {
            ++$contributions;
            ++$counts[$projectContribution->status()->value];

            if (!$projectContribution->isPaid()) {
                continue;
            }

            $paidTotalAmount += $projectContribution->totalAmount();
            $paidContributionAmount += $projectContribution->contributionAmount();
            $paidManagementFeeAmount += $projectContribution->managementFeeAmount();
            $paidTonnes += $projectContribution->tonnes();
            $paidCo2eqKg += $projectContribution->co2eqKg() ?? 0.0;
        }

        $paid = $counts[ProjectContributionStatus::Paid->value];

        return new ProjectContributionsAggregation(
            contributions: $contributions,
            paid: $paid,
            pending: $counts[ProjectContributionStatus::Pending->value],
            failed: $counts[ProjectContributionStatus::Failed->value],
            expired: $counts[ProjectContributionStatus::Expired->value],
            paidTotalAmount: $paidTotalAmount,
            paidContributionAmount: $paidContributionAmount,
            paidManagementFeeAmount: $paidManagementFeeAmount,
            paidTonnes: $paidTonnes,
            paidCo2eqKg: $paidCo2eqKg,
            averagePaidAmount: 0 === $paid ? 0.0 : $paidTotalAmount / $paid,
        );
    }
}
