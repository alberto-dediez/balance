<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration\Declare;

use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectResolver;
use App\CarbonFootprint\Application\CarbonFootprintKpi\SubjectFootprints;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearNotCompletedException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class DeclareBaseYearService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private KpiSubjectResolver $subjectResolver,
        private SubjectFootprints $subjectFootprints,
        private BaseYearDeclarationRepository $baseYearDeclarationRepository,
        private CarbonFootprintKpiAuthorizer $authorizer,
    ) {}

    public function __invoke(DeclareBaseYearRequest $request, User $user): DeclareBaseYearResponse
    {
        $subject = $this->subjectResolver->resolve($request->subjectType, $request->subjectId);

        if (null === $subject) {
            throw new \InvalidArgumentException('Carbon footprint KPI subject not found');
        }

        $this->authorizer->denyUnlessCanDoThat($subject, Action::Edit, $user);

        $footprint = null !== $request->year ? $this->subjectFootprints->completedOfYear($subject, $request->year) : null;

        if (null === $footprint || null === $subject->accountId) {
            throw new BaseYearNotCompletedException('The base year must have a completed carbon footprint');
        }

        $total = $footprint->totalEmissions()->co2eq();
        $threshold = $request->recalculationThresholdPercent ?? '5';
        $justification = $request->justification ?? '';

        $declaration = $this->baseYearDeclarationRepository->findBySubject($subject->type, $subject->id);

        if (null === $declaration) {
            $declaration = BaseYearDeclaration::declare(
                BaseYearDeclarationId::generate(),
                $subject->type,
                $subject->id,
                $subject->accountId,
                $footprint->year(),
                $justification,
                $threshold,
                $request->primaryIndicator,
                $total,
            );
        } else {
            $declaration->redeclare($footprint->year(), $justification, $threshold, $request->primaryIndicator, $total);
        }

        $this->baseYearDeclarationRepository->add($declaration);

        return new DeclareBaseYearResponse($declaration);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof DeclareBaseYearRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeclareBaseYearRequest;
    }
}
