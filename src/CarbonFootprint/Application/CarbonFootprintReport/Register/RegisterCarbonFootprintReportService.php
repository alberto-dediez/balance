<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Register;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;

class RegisterCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly CarbonFootprintReportSubjects $subjects,
        private readonly EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private readonly PropertyCarbonFootprintAuthorizer $propertyCarbonFootprintAuthorizer,
        private readonly CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
        private readonly CarbonFootprintReportRepository $reportRepository,
        private readonly string $localeList,
    ) {}

    public function __invoke(RegisterCarbonFootprintReportRequest $request, User $user): RegisterCarbonFootprintReportResponse
    {
        $carbonFootprint = $this->subjects->find($request->subjectType, $request->carbonFootprintId);

        if (null === $carbonFootprint) {
            throw new \InvalidArgumentException('CarbonFootprint not found');
        }

        $this->authorizerFor($request->subjectType)->denyUnlessCanDoThat($carbonFootprint, SecurityAction::View, $user);

        if (!$carbonFootprint->isCompleted()) {
            throw new \RuntimeException('CarbonFootprint is not completed');
        }

        if (null !== $this->reportRepository->findBySubject($request->subjectType, $request->carbonFootprintId)) {
            throw new CarbonFootprintReportAlreadyExistsException('A report already exists for this CarbonFootprint');
        }

        $accountId = $carbonFootprint->accountId();
        if (null === $accountId) {
            throw new \RuntimeException('CarbonFootprint has no account');
        }

        $locales = Locale::listFrom($this->localeList);

        if ([] === $locales) {
            throw new \RuntimeException('No locales configured');
        }

        $report = new CarbonFootprintReport(id: CarbonFootprintReportId::generate());
        $report->register($request->subjectType, $request->carbonFootprintId, $accountId, $user->id(), $locales[0]);

        $this->reportRepository->add($report);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GenerateCarbonFootprintReportTextsRequest($report->id()),
                priority: Priority::LOW,
            ),
            $user,
        );

        return new RegisterCarbonFootprintReportResponse($report->id());
    }

    private function authorizerFor(CarbonFootprintReportSubjectType $subjectType): AbstractAuthorizer
    {
        return match ($subjectType) {
            CarbonFootprintReportSubjectType::Event => $this->eventCarbonFootprintAuthorizer,
            CarbonFootprintReportSubjectType::Property => $this->propertyCarbonFootprintAuthorizer,
            CarbonFootprintReportSubjectType::Company => $this->companyCarbonFootprintAuthorizer,
        };
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCarbonFootprintReportRequest;
    }
}
