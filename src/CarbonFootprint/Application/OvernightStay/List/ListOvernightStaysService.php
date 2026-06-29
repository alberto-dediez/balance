<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\List;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListOvernightStaysService
{
    public function __construct(private OvernightStayRepository $repository, private OvernightStayAuthorizer $authorizer) {}

    public function __invoke(ListOvernightStaysRequest $request, User $user): ListOvernightStaysResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new OvernightStay(id: OvernightStayId::generate()), SecurityAction::List, $user);

        $overnightStays = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListOvernightStaysResponse($overnightStays, $totalResults);
    }
}
