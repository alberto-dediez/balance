<?php

namespace App\Waste\Application\Waste\List;

use App\Waste\Domain\Model\Waste\WasteRepository;

class ListWastesService
{
    public function __construct(private WasteRepository $repository)
    {

    }

    public function __invoke(ListWastesRequest $request): ListWastesResponse
    {
        $users = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListWastesResponse($users, $totalResults);
    }
}