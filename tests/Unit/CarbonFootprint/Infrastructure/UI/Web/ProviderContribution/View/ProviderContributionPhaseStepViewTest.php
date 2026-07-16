<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\ProviderContribution\View;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowViewFactoryRegistry;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CombustibleRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MachineryFuelRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MobilityRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\OvernightStayRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\ProviderContribution\View\ProviderContributionPhaseStepView;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use PHPUnit\Framework\TestCase;

class ProviderContributionPhaseStepViewTest extends TestCase
{
    private ProviderContributionPhaseStepView $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sut = new ProviderContributionPhaseStepView(new ActivityRowViewFactoryRegistry([
            new CombustibleRowViewFactory(),
            new MachineryFuelRowViewFactory(),
            new OvernightStayRowViewFactory(),
            new MobilityRowViewFactory(),
        ]));
    }

    public function testOvernightStaysOnlyIncludeSuppliersCategory(): void
    {
        $organizingTeam = OvernightStayMother::from(name: 'organizing-team', id: (string) OvernightStayId::generate(), category: CategoryOvernight::OrganizingTeam);
        $suppliers = OvernightStayMother::from(name: 'suppliers', id: (string) OvernightStayId::generate(), category: CategoryOvernight::Suppliers);
        $attendees = OvernightStayMother::from(name: 'attendees', id: (string) OvernightStayId::generate(), category: CategoryOvernight::Attendees);

        $sections = Phase::Assembly->providerSections();

        $view = $this->sut->build([
            new PhaseSection($sections[0], [], []),
            new PhaseSection($sections[1], [], []),
            new PhaseSection($sections[2], [], [$organizingTeam, $suppliers, $attendees]),
            new PhaseSection($sections[3], [], []),
        ]);

        $this->assertSame([$suppliers], $view['overnightStays']);
    }
}
