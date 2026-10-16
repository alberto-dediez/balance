<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Register;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TransportRepository $transportRepository,
        private TransportAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterTransportRequest $request, User $user): RegisterTransportResponse
    {
        $id = TransportId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Transport(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $transport = new Transport($id);
        $transport->register(
            $request->name,
            $request->unit,
            $request->vehicle,
            $isDefaultLocale ? null : $locale,
        );

        $this->transportRepository->add($transport);

        return new RegisterTransportResponse(
            transportId: $transport->id(),
            name: $transport->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterTransportRequest;
    }
}
