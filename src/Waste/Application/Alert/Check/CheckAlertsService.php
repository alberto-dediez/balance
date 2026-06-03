<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Alert\Check\Checker\AlertChecker;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class CheckAlertsService
{
    /** @param iterable<AlertChecker> $checkers */
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly AlertConfigurationRepository $alertConfigurationRepository,
        private readonly AccountRepository $accountRepository,
        private readonly iterable $checkers,
    ) {}

    public function __invoke(CheckAlertsRequest $request, User $user): CheckAlertsResponse
    {
        $checkDate = $request->checkDate ?? new \DateTimeImmutable('NOW');
        $detectedCount = 0;

        if (null !== $request->accountId) {
            $accountIds = [$request->accountId];
        } else {
            $accounts = $this->accountRepository->findByFilter(new AccountFilter(limit: 0));
            $accountIds = array_map(fn($account) => $account->id(), $accounts);
        }

        foreach ($accountIds as $accountId) {
            $config = $this->alertConfigurationRepository->findByAccount($accountId);

            if (null === $config) {
                continue;
            }

            foreach ($this->checkers as $checker) {
                $type = $checker->type();
                $rules = $config->enabledItemsForType($type);

                if (0 === count($rules)) {
                    continue;
                }

                $alerts = $checker->check($accountId, $rules, $checkDate);

                foreach ($alerts as $alert) {
                    $this->alertRepository->add($alert);
                    ++$detectedCount;
                }
            }
        }

        return new CheckAlertsResponse(detectedAlerts: $detectedCount);
    }
}
