<?php

declare(strict_types=1);

namespace App\Property\Application\Company\List;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCompaniesService
{
    public function __construct(private CompanyRepository $repository, private CompanyAuthorizer $authorizer) {}

    public function __invoke(ListCompaniesRequest $request, User $user): ListCompaniesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Company(
            id: CompanyId::generate(),
            accountId: $request->accountId,
        ), SecurityAction::List, $user);

        $companies = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCompaniesResponse($companies, $totalResults);
    }
}
