<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\JustifiedExclusionsDocumentPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\Security\Application\Action;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class DownloadJustifiedExclusionsDocumentService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportSubjects $carbonFootprintReportSubjects,
        private PropertyCarbonFootprintAuthorizer $propertyCarbonFootprintAuthorizer,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
        private CarbonFootprintReportDataCollectorRegistry $carbonFootprintReportDataCollectorRegistry,
        private BrandingResolver $brandingResolver,
        private JustifiedExclusionsDocumentPdfGenerator $pdfGenerator,
    ) {}

    public function __invoke(DownloadJustifiedExclusionsDocumentRequest $request, User $user): DownloadJustifiedExclusionsDocumentResponse
    {
        if (CarbonFootprintReportSubjectType::Event === $request->subjectType) {
            throw new \InvalidArgumentException('Justified exclusions are only available for organizational carbon footprints');
        }

        $carbonFootprint = $this->carbonFootprintReportSubjects->find($request->subjectType, $request->carbonFootprintId);

        if (null === $carbonFootprint) {
            throw new \InvalidArgumentException('CarbonFootprint not found');
        }

        $authorizer = CarbonFootprintReportSubjectType::Company === $request->subjectType ? $this->companyCarbonFootprintAuthorizer : $this->propertyCarbonFootprintAuthorizer;
        $authorizer->denyUnlessCanDoThat($carbonFootprint, Action::View, $user);

        if (!$carbonFootprint->isCompleted()) {
            throw new JustifiedExclusionsRequireCompletedFootprintException('Justified exclusions are only available for completed carbon footprints');
        }

        $locale = LocaleGetter::locale();
        $data = $this->carbonFootprintReportDataCollectorRegistry->collectFor($request->subjectType, $request->carbonFootprintId, $locale);

        if (!$data instanceof OrganizationalCarbonFootprintReportData) {
            throw new \InvalidArgumentException('Unsupported carbon footprint report data');
        }

        $branding = PdfBranding::fromTokens($this->brandingResolver->resolve($carbonFootprint->accountId()), $data->subjectName);

        return new DownloadJustifiedExclusionsDocumentResponse(
            path: $this->pdfGenerator->generate($data, $locale, $branding),
            filename: sprintf('exclusiones-justificadas-%s-%d-%s.pdf', $request->carbonFootprintId, $data->calculationYear, $locale->value),
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof DownloadJustifiedExclusionsDocumentRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DownloadJustifiedExclusionsDocumentRequest;
    }
}
