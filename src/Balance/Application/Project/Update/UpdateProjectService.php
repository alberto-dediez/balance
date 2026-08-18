<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Update;

use App\Balance\Application\Project\Credits\ProjectCreditAvailability;
use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectHasUnconsumedCreditsException;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateProjectService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProjectRepository $projectRepository,
        private ProjectCreditAvailability $projectCreditAvailability,
        private ProjectAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateProjectRequest $request, User $user): UpdateProjectResponse
    {
        $project = $this->projectRepository->find($request->projectId);

        if (null == $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        $this->authorizer->denyUnlessCanDoThat($project, SecurityAction::Edit, $user);

        $this->denyCostChangeWithUnconsumedCredits($project, $request->costPerTonne);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $project->update(
            name: $request->name,
            description: $request->description ?? '',
            location: $request->location,
            gallery: $request->gallery,
            projectType: $request->projectType,
            projectStatus: $request->projectStatus,
            mitecoId: $request->mitecoId,
            pricePerTonne: $request->pricePerTonne,
            costPerTonne: $request->costPerTonne,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->projectRepository->add($project);

        return new UpdateProjectResponse(
            projectId: $project->id(),
            name: $project->name(),
        );
    }

    private function denyCostChangeWithUnconsumedCredits(Project $project, ?float $costPerTonne): void
    {
        if (round((float) $project->costPerTonne(), 2) === round((float) $costPerTonne, 2)) {
            return;
        }

        $unconsumedTonnes = $this->projectCreditAvailability->unconsumedTonnes($project->id());

        if ($unconsumedTonnes > ProjectCreditAvailability::TONNE_TOLERANCE) {
            throw new ProjectHasUnconsumedCreditsException($project->id(), $unconsumedTonnes);
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateProjectRequest;
    }
}
