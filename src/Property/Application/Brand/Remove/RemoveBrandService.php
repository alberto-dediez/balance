<?php

namespace App\Property\Application\Brand\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveBrandService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

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

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveBrandRequest;
    }
}