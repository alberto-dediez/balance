<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsResponse;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensation;
use App\CarbonFootprint\Infrastructure\UI\Web\Chart\TripChartBuilder;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ResultsView;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class ResultsViewTest extends TestCase
{
    private ResultsView $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $translator = new IdentityTranslator();

        $this->sut = new ResultsView($translator, new TripChartBuilder($translator));
    }

    public function testItSplitsCompensationBetweenScopesAndPendingEmissions(): void
    {
        $view = $this->sut->build(
            EventMother::random(),
            EventCarbonFootprintMother::random(),
            $this->results('10000'),
            null,
            $this->compensation(ownTonnes: 4.0, attendeesTonnes: 1.0),
        );

        self::assertSame([4.0, 1.0, 5.0], $view['compensationChart']['values']);
    }

    public function testItDoesNotAllowNegativePendingEmissions(): void
    {
        $view = $this->sut->build(
            EventMother::random(),
            EventCarbonFootprintMother::random(),
            $this->results('1000'),
            null,
            $this->compensation(ownTonnes: 3.0, attendeesTonnes: 0.0),
        );

        self::assertSame([3.0, 0.0, 0.0], $view['compensationChart']['values']);
    }

    public function testItLeavesTheCompensationChartEmptyWithoutCompensations(): void
    {
        $view = $this->sut->build(
            EventMother::random(),
            EventCarbonFootprintMother::random(),
            $this->results('1000'),
            null,
        );

        self::assertSame(['categories' => [], 'values' => []], $view['compensationChart']);
    }

    public function testItLeavesTheOriginsChartEmptyWithoutSurveyStats(): void
    {
        $view = $this->sut->build(
            EventMother::random(),
            EventCarbonFootprintMother::random(),
            $this->results('1000'),
            null,
        );

        self::assertSame(['categories' => [], 'values' => []], $view['originsChart']);
    }

    private function results(string $co2eq): GetResultsResponse
    {
        $bySource = [];
        foreach (Source::cases() as $source) {
            $bySource[$source->value] = new Emissions();
        }

        $byPhase = [];
        $byPhaseAndSource = [];
        foreach (Phase::cases() as $phase) {
            $byPhase[$phase->value] = new Emissions();
            $byPhaseAndSource[$phase->value] = $bySource;
        }

        return new GetResultsResponse(
            new Emissions($co2eq),
            new Emissions(),
            $bySource,
            $byPhase,
            $byPhaseAndSource,
        );
    }

    private function compensation(float $ownTonnes, float $attendeesTonnes): EventCarbonFootprintReportCompensation
    {
        return new EventCarbonFootprintReportCompensation(
            ownTonnes: $ownTonnes,
            attendeesTonnes: $attendeesTonnes,
            totalTonnes: $ownTonnes + $attendeesTonnes,
            coveragePercentage: 0.0,
            ownContributions: 1,
            attendeesContributions: 0,
            cutOffAt: new \DateTimeImmutable(),
            lastCompensatedAt: null,
        );
    }
}
