<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Application\Alert\Check\Checker\AlertChecker;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class CheckAlertsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    /** @param iterable<AlertChecker> $checkers */
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly AlertConfigurationRepository $alertConfigurationRepository,
        private readonly DailyWasteWindowProvider $dailyWasteWindowProvider,
        private readonly AlertAuthorizer $authorizer,
        private readonly iterable $checkers,
    ) {}

    public function __invoke(CheckAlertsRequest $request, User $user): CheckAlertsResponse
    {
        $accountId = $request->accountId;
        $checkDate = $request->checkDate;

        $this->authorizer->denyUnlessCanDoThat(new Alert(id: AlertId::generate(), accountId: $accountId), Action::Register, $user);

        $config = $this->alertConfigurationRepository->findByAccount($accountId);

        if (null === $config) {
            return new CheckAlertsResponse(0);
        }

        $existingAlerts = new ExistingAlertsIndex($this->alertRepository->findByFilter(new AlertFilter(
            accountId: $accountId,
            status: null,
            periodDate: $checkDate,
            limit: 0,
        )));

        $alerts = [];

        foreach ($this->checkers as $checker) {
            $rules = $config->enabledItemsForType($checker->type());

            if (0 === count($rules)) {
                continue;
            }

            $newAlerts = $checker->check($accountId, $rules, $checkDate, $existingAlerts);

            if (count($newAlerts) > 0) {
                $alerts = array_merge($alerts, $newAlerts);
            }
        }

        $this->alertRepository->addMultiple($alerts);
        $this->dailyWasteWindowProvider->clear();

        return new CheckAlertsResponse(detectedAlerts: count($alerts));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CheckAlertsRequest;
    }
}
