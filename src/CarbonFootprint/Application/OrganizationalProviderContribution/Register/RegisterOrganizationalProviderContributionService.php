<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Register;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\LocatedOrganizationalCarbonFootprint;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterOrganizationalProviderContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalCarbonFootprintLocator $locator,
        private ProviderRepository $providerRepository,
        private OrganizationalProviderContributionRepository $contributionRepository,
        private CompanyProperties $companyProperties,
    ) {}

    public function __invoke(RegisterOrganizationalProviderContributionRequest $request, User $user): RegisterOrganizationalProviderContributionResponse
    {
        $located = $this->locator->locate($request->subjectType, $request->carbonFootprintId);

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $accountId = $located->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('CarbonFootprint has no account');
        }

        $provider = $this->providerRepository->find($request->providerId);

        if (null === $provider || $provider->isRemoved() || null === $provider->accountId() || !$accountId->equals($provider->accountId())) {
            throw new \InvalidArgumentException('Provider not found');
        }

        if ($request->mode->isRequested() && null === $provider->userId()) {
            throw new \InvalidArgumentException('Provider has no linked user');
        }

        $this->ensureValidProperty($located, $request->propertyId);

        $existing = $this->contributionRepository->findActiveByFootprintAndProvider($request->subjectType, $request->carbonFootprintId, $request->providerId);

        if (null !== $existing) {
            throw new OrganizationalProviderContributionAlreadyExistsException();
        }

        $contribution = new OrganizationalProviderContribution(OrganizationalProviderContributionId::generate());
        $contribution->register(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            accountId: $accountId,
            providerId: $request->providerId,
            propertyId: $request->propertyId,
            mode: $request->mode,
            token: $request->mode->isRequested() ? OrganizationalProviderContribution::generateToken() : '',
            sources: $request->sources,
            companyName: $provider->name(),
        );

        $this->contributionRepository->add($contribution);

        return new RegisterOrganizationalProviderContributionResponse($contribution->id(), $contribution->mode());
    }

    private function ensureValidProperty(LocatedOrganizationalCarbonFootprint $located, ?PropertyId $propertyId): void
    {
        if (null === $propertyId) {
            return;
        }

        $carbonFootprint = $located->carbonFootprint;

        if (!$carbonFootprint instanceof CompanyCarbonFootprint) {
            throw new \InvalidArgumentException('A property carbon footprint contribution cannot be assigned to a property');
        }

        foreach ($this->companyProperties->consolidated($carbonFootprint->companyId()) as $property) {
            if ($property->id()->equals($propertyId)) {
                return;
            }
        }

        throw new \InvalidArgumentException('Property is not consolidated in the company');
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterOrganizationalProviderContributionRequest;
    }
}
