<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\GeneratePdf\GeneratePdfPreventionPlanRequest;
use App\FoodLoss\Domain\Event\PreventionPlan\GeneratePdfOnPreventionPlanVersionDraftApprovedSubscriber;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionDraftApproved;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionValidated;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class GeneratePdfOnPreventionPlanVersionDraftApprovedSubscriberTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function testIsSubscribedToPreventionPlanVersionDraftApproved(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PreventionPlanVersionDraftApproved(
            PreventionPlanId::generate(),
            1,
        )));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new PreventionPlanVersionValidated(
            PreventionPlanId::generate(),
            1,
        )));
    }

    public function testHandleDispatchesOneDeferredCommandPerLocale(): void
    {
        $preventionPlanId = PreventionPlanId::generate();
        $versionNumber = 2;

        $sut = $this->createSUT('es|en');
        $sut->handle(new PreventionPlanVersionDraftApproved($preventionPlanId, $versionNumber));

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(2, $commands);

        $locales = array_map(
            static fn(array $dispatched) => $dispatched['command']->command()->locale,
            $commands,
        );

        $this->assertContains(Locale::Spanish, $locales);
        $this->assertContains(Locale::English, $locales);

        foreach ($commands as $dispatched) {
            $command = $dispatched['command'];
            $this->assertInstanceOf(DeferredCommand::class, $command);

            $innerCommand = $command->command();
            $this->assertInstanceOf(GeneratePdfPreventionPlanRequest::class, $innerCommand);
            $this->assertEquals($preventionPlanId, $innerCommand->preventionPlanId);
            $this->assertSame($versionNumber, $innerCommand->versionNumber);
        }
    }

    public function testHandleIgnoresUnknownLocaleValues(): void
    {
        $preventionPlanId = PreventionPlanId::generate();

        $sut = $this->createSUT('es|unknown|en');
        $sut->handle(new PreventionPlanVersionDraftApproved($preventionPlanId, 1));

        $this->assertCount(2, $this->spyCommandBus->commands());
    }

    private function createSUT(string $localeList = 'es'): GeneratePdfOnPreventionPlanVersionDraftApprovedSubscriber
    {
        return new GeneratePdfOnPreventionPlanVersionDraftApprovedSubscriber(
            new FakeLoggedUser(UserMother::random()),
            $localeList,
        );
    }
}
