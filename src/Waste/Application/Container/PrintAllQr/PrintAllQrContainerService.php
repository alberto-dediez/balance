<?php

namespace App\Waste\Application\Container\PrintAllQr;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\QrPdfGenerator;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\NotValidContainersForPrintException;
use App\Waste\Domain\Model\Container\QrPdfMerger;

class PrintAllQrContainerService
{
    public function __construct(private readonly ContainerRepository $repository, private readonly QrPdfGenerator $pdfGenerator, private readonly QrPdfMerger $pdfMerger, private readonly ContainerAuthorizer $authorizer)
    {

    }

    public function __invoke(PrintAllQrContainerRequest $request, User $user): PrintAllQrContainerResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Container(
            id: ContainerId::generate(),
            accountId: $request->accountId
        ), Action::List, $user);

        $filter = new ContainerFilter(
            accountId: $request->accountId,
            limit: 0
        );

        $containers = $this->repository->findByFilter($filter);

        if (count($containers) === 0) {
            throw new NotValidContainersForPrintException();
        }

        $pdf = null;

        foreach ($containers as $container) {
            $this->authorizer->denyUnlessCanDoThat($container, Action::View, $user);

            $pathToNewPdf = $container->printQr($this->pdfGenerator);

            $pdf = null === $pdf ? $pathToNewPdf : $this->pdfMerger->merge($pdf, $pathToNewPdf);
        }

        return new PrintAllQrContainerResponse(path: $pdf);
    }
}