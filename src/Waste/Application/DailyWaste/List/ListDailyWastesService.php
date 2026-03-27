<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;

class ListDailyWastesService
{
    public function __construct(private readonly DailyWasteRepository $repository, private readonly DailyWasteAuthorizer $authorizer) {}

    public function __invoke(ListDailyWastesRequest $request, User $user): ListDailyWastesResponse
    {
        $dailyWaste = new DailyWaste(
            id: DailyWasteId::generate(),
            propertyId: null,
            wasteId: $request->wasteId,
            purpose: $request->purpose ?? Purpose::Waste,
            accountId: $request->accountId,
            day: $request->day ?? new \DateTimeImmutable(),
        );

        $this->authorizer->denyUnlessCanDoThat($dailyWaste, Action::List, $user);

        $dailyWastes = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListDailyWastesResponse($dailyWastes, $totalResults);
    }
}
