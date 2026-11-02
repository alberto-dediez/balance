<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerMateriality\AnswerCompanyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Materiality\Domain\Event\AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentCompleted;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRemoved;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

final class AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriberTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testIsSubscribedToMaterialityAssessmentCompleted(): void
    {
        $events = MaterialityAssessmentMother::completed()->recordedEvents();
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo($events[array_key_last($events)]));
        $this->assertFalse($sut->isSubscribedTo(new MaterialityAssessmentRemoved(MaterialityAssessmentId::generate())));
    }

    public function testAdvancesThePropertyFootprint(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $this->createSUT();

        $this->assessmentOf($footprint, completed: true);

        $this->assertSame(OrganizationalCarbonFootprintStatus::MaterialityAnswered, $footprint->status());
    }

    public function testAdvancesTheCompanyFootprint(): void
    {
        $footprint = $this->companyFootprint();
        $this->createSUT();

        $this->assessmentOf($footprint, completed: true);

        $this->assertSame(OrganizationalCarbonFootprintStatus::MaterialityAnswered, $footprint->status());
    }

    public function testAnAdvancedFootprintDoesNotGoBack(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id');
        $this->propertyCarbonFootprints->add($footprint);

        $this->createSUT()->handle(new MaterialityAssessmentCompleted(
            materialityAssessmentId: MaterialityAssessmentId::generate(),
            subjectType: MaterialityAssessmentSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            accountId: new AccountId('account-id'),
            year: 2025,
            completedBy: UserId::generate(),
            completedAt: new \DateTimeImmutable(),
            scores: [],
        ));

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
    }

    private function createSUT(): AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber
    {
        return new AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber(
            new AnswerPropertyCarbonFootprintMaterialityService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer()),
            new AnswerCompanyCarbonFootprintMaterialityService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer()),
            new FakeLoggedUser($this->ownerOf()),
        );
    }
}
