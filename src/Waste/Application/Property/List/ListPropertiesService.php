<?php

namespace App\Waste\Application\Property\List;

use App\Waste\Domain\Model\Property\PropertyRepository;

class ListPropertiesService
{
    public function __construct(private PropertyRepository $repository)
    {

    }

    public function __invoke(ListPropertiesRequest $request): ListPropertiesResponse
    {
        $properties = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListPropertiesResponse($properties, $totalResults);
    }
}