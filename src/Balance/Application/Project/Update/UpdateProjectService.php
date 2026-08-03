<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Update;

use App\Balance\Application\Project\ProjectAuthorizer;
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
        private ProjectAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateProjectRequest $request, User $user): UpdateProjectResponse
    {
        $project = $this->projectRepository->find($request->projectId);

        if (null == $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        $this->authorizer->denyUnlessCanDoThat($project, SecurityAction::Edit, $user);

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
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->projectRepository->add($project);

        return new UpdateProjectResponse(
            projectId: $project->id(),
            name: $project->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateProjectRequest;
    }
}
