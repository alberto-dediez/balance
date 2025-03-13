<?php

namespace App\Waste\Application\Container\List;

use App\Waste\Domain\Model\Container\ContainerRepository;

class ListContainersService
{
    public function __construct(private ContainerRepository $repository)
    {

    }

    public function __invoke(ListContainersRequest $request): ListContainersResponse
    {
        $containers = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListContainersResponse($containers, $totalResults);
    }
}