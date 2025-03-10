<?php

namespace App\Waste\Application\Waste\Register;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;

class RegisterWasteService
{
    public function __construct(private WasteRepository $wasteRepository)
    {
    }

    public function __invoke(RegisterWasteRequest $request): RegisterWasteResponse
    {
        $id = WasteId::generate();

        $waste = new Waste($id);
        $waste->register($request->name);

        $this->wasteRepository->add($waste);

        return new RegisterWasteResponse($waste->id(), $waste->name());
    }
}