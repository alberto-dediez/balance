<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RecordOrganizationalProviderContributionManualEmissionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
        private OrganizationalProviderContributionAuthorizer $authorizer,
        private OrganizationalCarbonFootprintLocator $locator,
        private StageAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(RecordOrganizationalProviderContributionManualEmissionsRequest $request, User $user): RecordOrganizationalProviderContributionManualEmissionsResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::Edit, $user);

        $located = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $providerId = $contribution->providerId();

        if (!$providerId instanceof ProviderId) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution has no provider');
        }

        if (!$contribution->isManual()) {
            throw new \InvalidArgumentException('Only manual provider contributions accept emissions from the owner');
        }

        $sectionsByKey = [];
        foreach ($contribution->sources() as $source) {
            $section = Scope::Scope3->reportedSectionFor($source);
            $sectionsByKey[$section->key] = $section;
        }

        $sectionAnswers = [];
        foreach ($request->sectionAnswers as $sectionAnswer) {
            $definition = $sectionsByKey[$sectionAnswer->definition->key]
                ?? throw new \InvalidArgumentException(sprintf('Section "%s" is not covered by the provider contribution.', $sectionAnswer->definition->key));

            foreach ($sectionAnswer->answers as $answer) {
                if ($answer->type() !== $definition->activityType) {
                    throw new \InvalidArgumentException(sprintf('Answer of type "%s" does not match section "%s" type.', $answer->type()->value, $definition->key));
                }
            }

            $sectionAnswers[] = new SectionAnswer($definition, $sectionAnswer->answers);
        }

        $this->answersSynchronizer->sync(
            $located->carbonFootprint,
            Scope::Scope3,
            $sectionAnswers,
            $user,
            Scope::Scope3->providerSectionsFor($contribution->sources()),
            $providerId,
            false,
            $contribution->propertyId(),
        );

        $this->locator->save($located);

        return new RecordOrganizationalProviderContributionManualEmissionsResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RecordOrganizationalProviderContributionManualEmissionsRequest;
    }
}
