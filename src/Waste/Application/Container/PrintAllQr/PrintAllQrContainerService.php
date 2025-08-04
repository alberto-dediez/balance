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
use App\Waste\Domain\Model\Container\QrPdfMerger;
use Symfony\Component\Translation\TranslatableMessage;

class PrintAllQrContainerService
{
    public function __construct(private ContainerRepository $repository, private QrPdfGenerator $pdfGenerator, private QrPdfMerger $pdfMerger, private ContainerAuthorizer $authorizer)
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

        if (null === $containers || count($containers) === 0) {
            throw new \RuntimeException(new TranslatableMessage("No hay contenedores disponibles para impresión"));
        }

        $pdf = null;

        foreach ($containers as $container) {
            $this->authorizer->denyUnlessCanDoThat($container, Action::View, $user);

            $pathToNewPdf = $container->printQr($this->pdfGenerator);

            if (null === $pdf) {
                $pdf = $pathToNewPdf;
            } else {
                $pdf = $this->pdfMerger->merge($pdf, $pathToNewPdf);
            }
        }

        return new PrintAllQrContainerResponse(path: $pdf);
    }
}