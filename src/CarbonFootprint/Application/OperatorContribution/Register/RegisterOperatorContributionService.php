<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Register;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Application\Property\UpdateOperatorContact\UpdatePropertyOperatorContactRequest;
use App\Property\Application\Property\UpdateOperatorContact\UpdatePropertyOperatorContactService;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\Property;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Uuid\UuidGenerator;

class RegisterOperatorContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
        private OperatorContributionAuthorizer $authorizer,
        private OperatorContributionRepository $contributionRepository,
        private CompanyProperties $companyProperties,
        private UpdatePropertyOperatorContactService $updatePropertyOperatorContact,
        private RegisterUserService $registerUser,
        private GrantRoleService $grantRole,
        private UserRepository $userRepository,
        private SystemActorResolver $systemActor,
    ) {}

    public function __invoke(RegisterOperatorContributionRequest $request, User $user): RegisterOperatorContributionResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint || $companyCarbonFootprint->isRemoved()) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->companyCarbonFootprintAuthorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);
        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Register, $user);

        if ($companyCarbonFootprint->isCompleted()) {
            throw new OperatorContributionClosedException();
        }

        $accountId = $companyCarbonFootprint->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint has no account');
        }

        $property = $this->nonConsolidatedProperty($companyCarbonFootprint->companyId(), $request);
        $source = Scope::sourceForOperatingRegime($property->operatingRegime());

        if (null === $source) {
            throw new \InvalidArgumentException('Property is not a non-consolidated property of the company');
        }

        if (null !== $this->contributionRepository->findActiveByCompanyCarbonFootprintAndProperty($companyCarbonFootprint->id(), $property->id())) {
            throw new OperatorContributionAlreadyExistsException();
        }

        $contact = OperatorContact::from($request->contactEmail, $request->contactName, $request->contactLocale);

        if (!$contact->equals($property->operatorContact())) {
            ($this->updatePropertyOperatorContact)(new UpdatePropertyOperatorContactRequest($property->id(), $contact), $user);
        }

        $systemActor = ($this->systemActor)();

        $registerResponse = ($this->registerUser)(
            new RegisterUserRequest(
                username: $this->generateUsername($property->name()),
                password: UuidGenerator::v4(),
                accountId: $accountId,
                welcome: false,
            ),
            $systemActor,
        );

        ($this->grantRole)(new GrantRoleRequest($registerResponse->userId, Role::Provider), $systemActor);

        $contribution = new OperatorContribution(OperatorContributionId::generate());
        $contribution->register(
            companyCarbonFootprintId: $companyCarbonFootprint->id(),
            accountId: $accountId,
            propertyId: $property->id(),
            source: $source,
            year: $companyCarbonFootprint->year(),
            emissionFactorGroupId: $companyCarbonFootprint->emissionFactorGroupId(),
            contactEmail: $request->contactEmail,
            contactName: $request->contactName,
            contactLocale: $request->contactLocale,
            token: OperatorContribution::generateToken(),
        );
        $contribution->linkUser($registerResponse->userId);

        $this->contributionRepository->add($contribution);

        return new RegisterOperatorContributionResponse($contribution->id());
    }

    private function nonConsolidatedProperty(?CompanyId $companyId, RegisterOperatorContributionRequest $request): Property
    {
        foreach ($this->companyProperties->nonConsolidated($companyId) as $property) {
            if ($property->id()->equals($request->propertyId)) {
                return $property;
            }
        }

        throw new \InvalidArgumentException('Property is not a non-consolidated property of the company');
    }

    private function generateUsername(string $propertyName): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) \Transliterator::create('Any-Latin; Latin-ASCII')?->transliterate($propertyName))), '-');
        $base = ('' === $slug ? 'property' : $slug) . '-operator';

        $username = $base;
        $suffix = 1;

        while ($this->userRepository->findByUsername($username) instanceof User) {
            ++$suffix;
            $username = sprintf('%s-%d', $base, $suffix);
        }

        return $username;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterOperatorContributionRequest;
    }
}
