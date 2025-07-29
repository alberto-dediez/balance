<?php

namespace App\Property\Application\Brand\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRepository;

class UpdateBrandService
{
    public function __construct(private BrandRepository $repository, private BrandAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdateBrandRequest $request, User $user): UpdateBrandResponse
    {
        $brand = $this->repository->find($request->brandId);

        if (null == $brand) {
            throw new InvalidArgumentException("Brand not found");
        }

        $this->authorizer->denyUnlessCanDoThat($brand, Action::Edit, $user);

        $brand->update($request->name, $request->externalId);

        $this->repository->add($brand);

        return new UpdateBrandResponse(
            brandId: $brand->id(),
            name: $brand->name()
        );
    }
}