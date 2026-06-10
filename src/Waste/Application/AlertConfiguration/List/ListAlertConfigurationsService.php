<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class ListAlertConfigurationsService
{
    public function __construct(
        private readonly AlertConfigurationRepository $repository,
        private readonly AlertConfigurationAuthorizer $authorizer,
    ) {}

    public function __invoke(ListAlertConfigurationsRequest $request, User $user): ListAlertConfigurationsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new AlertConfiguration(id: AlertConfigurationId::generate()), Action::List, $user);

        $configs = $this->repository->findByFilter($request);
        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListAlertConfigurationsResponse($configs, $totalResults);
    }
}
