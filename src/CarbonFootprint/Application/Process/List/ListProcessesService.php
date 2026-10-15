<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\List;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListProcessesService
{
    public function __construct(private ProcessRepository $repository, private ProcessAuthorizer $authorizer) {}

    public function __invoke(ListProcessesRequest $request, User $user): ListProcessesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Process(id: ProcessId::generate()), SecurityAction::List, $user);

        $processes = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListProcessesResponse($processes, $totalResults);
    }
}
