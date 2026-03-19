<?php

namespace App\Property\Application\Brand\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRepository;

class ListBrandsService
{
    public function __construct(private readonly BrandRepository $repository, private readonly BrandAuthorizer $authorizer)
    {

    }

    public function __invoke(ListBrandsRequest $request, User $user): ListBrandsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Brand(
            id: BrandId::generate()
        ), Action::List, $user);

        $brands = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListBrandsResponse($brands, $totalResults);
    }
}