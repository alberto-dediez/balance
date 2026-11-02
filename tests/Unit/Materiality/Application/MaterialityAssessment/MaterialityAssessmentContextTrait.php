<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Application\MaterialityAssessment\MaterialityQuestionnaireBuilder;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityAssessmentRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySectorRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityTopicRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

trait MaterialityAssessmentContextTrait
{
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprints;
    private InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprints;
    private InMemoryMaterialityAssessmentRepository $materialityAssessments;
    private InMemoryMaterialityTopicRepository $materialityTopics;
    private InMemoryMaterialityQuestionRepository $materialityQuestions;
    private InMemoryMaterialitySectorRepository $materialitySectors;

    private function initMaterialityAssessment(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyCarbonFootprints = new InMemoryPropertyCarbonFootprintRepository();
        $this->companyCarbonFootprints = new InMemoryCompanyCarbonFootprintRepository();
        $this->materialityAssessments = MaterialityAssessmentRepositoryMother::inMemory();
        $this->materialityTopics = MaterialityTopicRepositoryMother::inMemory();
        $this->materialityQuestions = MaterialityQuestionRepositoryMother::inMemory();
        $this->materialitySectors = MaterialitySectorRepositoryMother::inMemory();
    }

    private function ownerOf(string $accountId = 'account-id'): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner-' . $accountId, Role::Owner, new AccountId($accountId));
    }

    private function footprints(): MaterialityAssessmentFootprints
    {
        $eventCarbonFootprints = new InMemoryEventCarbonFootprintRepository();

        return new MaterialityAssessmentFootprints(
            new CarbonFootprintReportSubjects(
                $eventCarbonFootprints,
                $this->propertyCarbonFootprints,
                $this->companyCarbonFootprints,
                new ReopenEventCarbonFootprintService($eventCarbonFootprints, new EventCarbonFootprintAuthorizer()),
                new ReopenPropertyCarbonFootprintService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer()),
                new ReopenCompanyCarbonFootprintService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer()),
            ),
            $this->propertyCarbonFootprints,
            $this->companyCarbonFootprints,
            new PropertyCarbonFootprintAuthorizer(),
            new CompanyCarbonFootprintAuthorizer(),
        );
    }

    private function questionnaireBuilder(): MaterialityQuestionnaireBuilder
    {
        return new MaterialityQuestionnaireBuilder($this->materialityTopics, $this->materialityQuestions);
    }

    private function authorizer(): MaterialityAssessmentAuthorizer
    {
        return new MaterialityAssessmentAuthorizer();
    }

    private function propertyFootprint(int $year = 2025, ?PropertyId $propertyId = null, string $accountId = 'account-id', bool $completed = false): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) ($propertyId ?? PropertyId::generate()), (string) PropertyCarbonFootprintId::generate(), $accountId, $year);

        if ($completed) {
            $footprint->complete(new Emissions());
        }

        $this->propertyCarbonFootprints->add($footprint);

        return $footprint;
    }

    private function scope2AnsweredPropertyFootprint(string $accountId = 'account-id'): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), $accountId);
        $this->propertyCarbonFootprints->add($footprint);

        return $footprint;
    }

    private function companyFootprint(int $year = 2025, ?CompanyId $companyId = null, string $accountId = 'account-id'): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered((string) ($companyId ?? CompanyId::generate()), (string) CompanyCarbonFootprintId::generate(), $accountId, $year);
        $this->companyCarbonFootprints->add($footprint);

        return $footprint;
    }

    private function assessmentOf(PropertyCarbonFootprint|CompanyCarbonFootprint $footprint, bool $completed = false): MaterialityAssessment
    {
        $subjectType = $footprint instanceof PropertyCarbonFootprint ? MaterialityAssessmentSubjectType::Property : MaterialityAssessmentSubjectType::Company;

        $assessment = $completed
            ? MaterialityAssessmentMother::completed(subjectType: $subjectType, carbonFootprintId: (string) $footprint->id(), accountId: (string) $footprint->accountId(), year: $footprint->calculationYear())
            : MaterialityAssessmentMother::draft(subjectType: $subjectType, carbonFootprintId: (string) $footprint->id(), accountId: (string) $footprint->accountId(), year: $footprint->calculationYear());

        $this->materialityAssessments->add($assessment);

        return $assessment;
    }
}
