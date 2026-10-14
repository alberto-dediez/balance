<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Register;

use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterBrandService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private BrandRepository $brandRepository, private BrandAuthorizer $authorizer) {}

    public function __invoke(RegisterBrandRequest $request, User $user): RegisterBrandResponse
    {
        $id = BrandId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Brand(
            id: BrandId::generate(),
        ), Action::Register, $user);

        $brand = new Brand($id);
        $brand->register($request->name, $request->externalId);

        $this->brandRepository->add($brand);

        return new RegisterBrandResponse($brand->id(), $brand->name());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterBrandRequest;
    }
}
