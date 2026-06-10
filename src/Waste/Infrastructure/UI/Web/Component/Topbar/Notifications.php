<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Component\Topbar;

use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Topbar:Notifications', template: '@components/Topbar/Notifications.html.twig')]
final class Notifications
{
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly LoggedAccount $loggedAccount,
    ) {}

    /** @return array<Alert> */
    public function pendingAlerts(): array
    {
        $account = ($this->loggedAccount)();

        if (! $account instanceof Account) {
            return [];
        }

        return $this->alertRepository->findByFilter(new AlertFilter(
            accountId: $account->id(),
            status: AlertStatus::New,
            limit: 5,
            orderBy: 'detectedAt',
            orderWay: 'DESC',
        ));
    }

    public function pendingCount(): int
    {
        $account = ($this->loggedAccount)();

        if (! $account instanceof Account) {
            return 0;
        }

        return $this->alertRepository->totalResultsByFilter(new AlertFilter(
            accountId: $account->id(),
            status: AlertStatus::New,
        ));
    }
}
