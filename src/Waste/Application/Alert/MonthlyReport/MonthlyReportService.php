<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\MonthlyReport;

use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertRepository;

class MonthlyReportService
{
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly AccountRepository $accountRepository,
        private readonly MonthlyReportNotifier $notifier,
    ) {}

    public function __invoke(MonthlyReportRequest $request, User $user): MonthlyReportResponse
    {
        $monthStart = $request->month->modify('first day of this month midnight');
        $monthEnd = $request->month->modify('last day of this month 23:59:59');

        $accounts = $this->accountRepository->findByFilter(new AccountFilter(limit: 0));
        $alertsByAccount = [];
        $totalAlerts = 0;

        foreach ($accounts as $account) {
            $alerts = $this->alertRepository->findByFilter(new AlertFilter(
                accountId: $account->id(),
                from: $monthStart,
                to: $monthEnd,
                limit: 0,
            ));

            if (0 === count($alerts)) {
                continue;
            }

            $alertsByAccount[(string) $account->id()] = $alerts;
            $totalAlerts += count($alerts);
        }

        $response = new MonthlyReportResponse(
            alertsByAccount: $alertsByAccount,
            totalAlerts: $totalAlerts,
        );

        if ($totalAlerts > 0) {
            $this->notifier->send($response, $request->month);
        }

        return $response;
    }
}
