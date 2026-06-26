<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Register;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterRoadTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RoadTransportRepository $roadTransportRepository,
        private RoadTransportAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterRoadTransportRequest $request, User $user): RegisterRoadTransportResponse
    {
        $id = RoadTransportId::generate();

        $this->authorizer->denyUnlessCanDoThat(new RoadTransport(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $roadTransport = new RoadTransport($id);
        $roadTransport->register(
            $request->name,
            $request->unit,
            $request->vehicle,
            $isDefaultLocale ? null : $locale,
        );

        $this->roadTransportRepository->add($roadTransport);

        return new RegisterRoadTransportResponse(
            roadTransportId: $roadTransport->id(),
            name: $roadTransport->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterRoadTransportRequest;
    }
}
