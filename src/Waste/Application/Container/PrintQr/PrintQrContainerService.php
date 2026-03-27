<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\PrintQr;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\QrPdfGenerator;

class PrintQrContainerService
{
    public function __construct(private readonly ContainerRepository $repository, private readonly QrPdfGenerator $pdfGenerator, private readonly ContainerAuthorizer $authorizer) {}

    public function __invoke(PrintQrContainerRequest $request, User $user): PrintQrContainerResponse
    {
        $container = $this->repository->find($request->containerId);

        if (null === $container) {
            throw new \InvalidArgumentException('Container not found');
        }

        $this->authorizer->denyUnlessCanDoThat($container, Action::View, $user);

        $path = $container->printQr($this->pdfGenerator);

        return new PrintQrContainerResponse(path: $path);
    }
}
