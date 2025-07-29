<?php

namespace App\Property\Application\Brand\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRepository;

class RemoveBrandService
{
    public function __construct(private BrandRepository $repository, private BrandAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveBrandRequest $request, User $user): RemoveBrandResponse
    {
        $brand = $this->repository->find($request->brandId);

        if (null == $brand) {
            throw new InvalidArgumentException("Brand not found");
        }

        $this->authorizer->denyUnlessCanDoThat($brand, Action::Remove, $user);

        $brand->remove();

        $this->repository->add($brand);

        return new RemoveBrandResponse(
            brandId: $brand->id()
        );
    }
}