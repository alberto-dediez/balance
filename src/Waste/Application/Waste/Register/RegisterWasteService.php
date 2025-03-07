<?php

namespace App\Waste\Application\Waste\Register;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;

class RegisterWasteService
{
    public function __construct(private WasteRepository $accountRepository)
    {
    }

    public function __invoke(RegisterWasteRequest $request): RegisterWasteResponse
    {
        $id = WasteId::generate();

        $account = new Waste($id);
        $account->register($request->name);

        $this->accountRepository->add($account);

        return new RegisterWasteResponse($account->id(), $account->name());
    }
}