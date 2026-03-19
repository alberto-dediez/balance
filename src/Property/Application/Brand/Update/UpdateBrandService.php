<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Update;

use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateBrandService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private BrandRepository $repository, private BrandAuthorizer $authorizer) {}

    public function __invoke(UpdateBrandRequest $request, User $user): UpdateBrandResponse
    {
        $brand = $this->repository->find($request->brandId);

        if (null == $brand) {
            throw new \InvalidArgumentException('Brand not found');
        }

        $this->authorizer->denyUnlessCanDoThat($brand, Action::Edit, $user);

        $brand->update($request->name, $request->externalId);

        $this->repository->add($brand);

        return new UpdateBrandResponse(
            brandId: $brand->id(),
            name: $brand->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateBrandRequest;
    }
}
