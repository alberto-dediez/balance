<?php

namespace App\Waste\Application\Waste\Remove;

use App\Waste\Domain\Model\Waste\WasteRepository;
use InvalidArgumentException;

class RemoveWasteService
{
    public function __construct(private WasteRepository $repository)
    {

    }

    public function __invoke(RemoveWasteRequest $request): RemoveWasteResponse
    {
        $waste = $this->repository->find($request->wasteId);

        if (null == $waste) {
            throw new InvalidArgumentException("Waste not found");
        }

        $waste->remove();

        $this->repository->add($waste);

        return new RemoveWasteResponse(
            wasteId: $waste->id()
        );
    }
}