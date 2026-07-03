<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Import;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportOvernightStayService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OvernightStayRepository $overnightStayRepository,
        private OvernightStayAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportOvernightStayRequest $request, User $user): ImportOvernightStayResponse
    {
        $year = $this->resolveYear($request->year);

        $overnightStay = $this->resolveOvernightStay($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $overnightStay->setEmissionFactor($this->buildEmissionFactor($request, $year, $group));

        $this->overnightStayRepository->add($overnightStay);

        return new ImportOvernightStayResponse(
            overnightStayId: $overnightStay->id(),
        );
    }

    private function resolveOvernightStay(ImportOvernightStayRequest $request, User $user): OvernightStay
    {
        if (!empty($request->overnightStay)) {
            $overnightStay = $this->overnightStayRepository->find(new OvernightStayId($request->overnightStay));

            if (null === $overnightStay) {
                throw new \InvalidArgumentException('OvernightStay not found');
            }

            $this->authorizer->denyUnlessCanDoThat($overnightStay, SecurityAction::Edit, $user);

            return $overnightStay;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('An overnight stay or a name is required');
        }

        return $this->registerOvernightStay($name, $request, $user);
    }

    private function registerOvernightStay(string $name, ImportOvernightStayRequest $request, User $user): OvernightStay
    {
        $id = OvernightStayId::generate();

        $this->authorizer->denyUnlessCanDoThat(new OvernightStay(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $overnightStay = new OvernightStay($id);
        $overnightStay->register(
            $name,
            CategoryOvernight::tryFrom((string) $request->category) ?? CategoryOvernight::Attendees,
            $isDefaultLocale ? null : $locale,
        );

        return $overnightStay;
    }

    private function buildEmissionFactor(ImportOvernightStayRequest $request, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, Unit::Pernoctacion, $source);
    }

    private function resolveEmissionFactorGroup(?string $value): EmissionFactorGroup
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('An emission factor group is required');
        }

        $group = $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($value));

        if (null === $group) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $group;
    }

    private function resolveYear(?string $year): int
    {
        $parsed = (int) $year;

        if ($parsed <= 0) {
            throw new \InvalidArgumentException('A valid year is required');
        }

        return $parsed;
    }

    private function decimal(?string $value): string
    {
        $value = trim((string) $value);

        return '' === $value ? '0' : str_replace(',', '.', $value);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportOvernightStayRequest;
    }
}
