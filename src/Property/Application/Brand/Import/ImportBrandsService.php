<?php

namespace App\Property\Application\Brand\Import;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Application\Brand\Update\UpdateBrandRequest;
use App\Property\Application\Brand\Update\UpdateBrandService;
use App\Property\Application\Brand\Register\RegisterBrandRequest;
use App\Property\Application\Brand\Register\RegisterBrandService;

class ImportBrandsService
{
    public function __construct(
        private readonly BrandRepository $repository, 
        private readonly RegisterBrandService $registerBrand, 
        private readonly UpdateBrandService $updateBrand, 
        private readonly BrandAuthorizer $authorizer
    ) {

    }

    public function __invoke(ImportBrandsRequest $request, User $user): ImportBrandsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Brand(
            id: BrandId::generate()
        ), Action::Admin, $user);

        $brandsMatch = $this->matchBrands($request->brands, $user);

        $totalResults = array_sum(
            array_map(fn(BrandResponse $response) => $response->brandId instanceof BrandId ? 1 : 0, $brandsMatch)
        );

        return new ImportBrandsResponse($brandsMatch, $totalResults);
    }

    private function matchBrands(array $brandRequests, User $user): array
    {
        if (count($brandRequests) === 0) {
            return [];
        }

        $result = [];

        foreach ($brandRequests as $request) {
            $brandMatch = $this->repository->findBySourceAndExternalId(
                source: $request->source,
                externalId: $request->externalId
            );

            if (null == $brandMatch) {
                $brandMatch = $this->repository->findByName($request->name);

                if ($brandMatch instanceof Brand) {
                    $this->updateBrand->__invoke(new UpdateBrandRequest(
                        $brandMatch->id(),
                        $brandMatch->name(),
                        $brandMatch->externalId()->combine(
                            $request->externalId
                        )
                    ), $user);
                }
            }

            if (null == $brandMatch && $request->createIfNotExists) {
                $registerResponse = $this->registerBrand->__invoke(new RegisterBrandRequest(
                    name: $request->name,
                    externalId: $request->externalId,
                ), $user);

                $brandMatch = new Brand($registerResponse->brandId);
            }

            $result[] = new BrandResponse(
                name: $request->name,
                externalId: $request->externalId,
                createIfNotExists: $request->createIfNotExists,
                brandId: $brandMatch?->id()
            );
        }

        return $result;
    }
}