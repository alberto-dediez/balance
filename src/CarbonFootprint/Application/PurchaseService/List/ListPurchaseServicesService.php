<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\List;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListPurchaseServicesService
{
    public function __construct(private PurchaseServiceRepository $repository, private PurchaseServiceAuthorizer $authorizer) {}

    public function __invoke(ListPurchaseServicesRequest $request, User $user): ListPurchaseServicesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new PurchaseService(id: PurchaseServiceId::generate()), SecurityAction::List, $user);

        $purchaseServices = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListPurchaseServicesResponse($purchaseServices, $totalResults);
    }
}
