<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStorage;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class DownloadPropertyCarbonFootprintCertificateService
{
    public function __construct(
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
        private PropertyCarbonFootprintCertificateAuthorizer $authorizer,
        private PropertyCarbonFootprintCertificateStorage $storage,
    ) {}

    public function __invoke(DownloadPropertyCarbonFootprintCertificateRequest $request, User $user): DownloadPropertyCarbonFootprintCertificateResponse
    {
        $certificate = $this->certificateRepository->find($request->propertyCarbonFootprintCertificateId);

        if (null === $certificate) {
            throw new \InvalidArgumentException('PropertyCarbonFootprintCertificate not found');
        }

        $this->authorizer->denyUnlessCanDoThat($certificate, SecurityAction::View, $user);

        if (!$certificate->isValid()) {
            throw new \InvalidArgumentException('PropertyCarbonFootprintCertificate is not valid');
        }

        $locale = $request->locale ?? LocaleGetter::locale();
        $path = $certificate->fileFor($locale);

        if (null === $path) {
            throw new \InvalidArgumentException('PropertyCarbonFootprintCertificate has no file');
        }

        return new DownloadPropertyCarbonFootprintCertificateResponse(
            stream: $this->storage->read($path),
            filename: sprintf('certificado-huella-%s-%s.pdf', $certificate->code(), $locale->value),
        );
    }
}
