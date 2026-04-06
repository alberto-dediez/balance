<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\List;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListDeliveryLogsService
{
    public function __construct(
        private DeliveryLogRepository $repository,
        private DeliveryLogAuthorizer $authorizer,
    ) {}

    public function __invoke(ListDeliveryLogsRequest $request, User $user): ListDeliveryLogsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new DeliveryLog(id: DeliveryLogId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $deliveryLogs = $this->repository->findByFilter($request);
        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListDeliveryLogsResponse($deliveryLogs, $totalResults);
    }
}
