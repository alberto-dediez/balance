<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects;

use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubject;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectOption;
use App\CarbonFootprint\Application\CarbonFootprintKpi\SubjectFootprints;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class ListKpiSubjectsService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CurrentAccountIdProvider $currentAccountIdProvider,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
        private SubjectFootprints $subjectFootprints,
        private CarbonFootprintKpiAuthorizer $authorizer,
    ) {}

    public function __invoke(ListKpiSubjectsRequest $request, User $user): ListKpiSubjectsResponse
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        $this->authorizer->denyUnlessCanDoThat(KpiSubject::forAccount($request->subjectType, $accountId), Action::List, $user);

        if (!$accountId instanceof AccountId) {
            return new ListKpiSubjectsResponse([]);
        }

        $options = [];
        foreach ($this->subjects($request->subjectType, $accountId) as $subject) {
            $years = array_map(
                static fn($footprint): int => $footprint->year(),
                $this->subjectFootprints->completedOf($subject),
            );

            if ([] === $years) {
                continue;
            }

            $options[] = new KpiSubjectOption($subject->id, $subject->name, $years);
        }

        usort($options, static fn(KpiSubjectOption $a, KpiSubjectOption $b): int => strcasecmp($a->name, $b->name));

        return new ListKpiSubjectsResponse($options);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ListKpiSubjectsRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ListKpiSubjectsRequest;
    }

    /** @return list<KpiSubject> */
    private function subjects(CarbonFootprintReportSubjectType $type, AccountId $accountId): array
    {
        $subjects = [];

        if (CarbonFootprintReportSubjectType::Company === $type) {
            foreach ($this->companyRepository->findByFilter(new CompanyFilter(accountId: $accountId, limit: 0)) as $company) {
                $subjects[] = new KpiSubject($type, (string) $company->id(), $company->accountId(), $company->name(), KpiSubject::COMPANY_SEGMENT);
            }

            return $subjects;
        }

        foreach ($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0)) as $property) {
            $subjects[] = new KpiSubject($type, (string) $property->id(), $property->accountId(), $property->name(), KpiSubject::OTHER_SEGMENT);
        }

        return $subjects;
    }
}
