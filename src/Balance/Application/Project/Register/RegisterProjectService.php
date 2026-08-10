<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Register;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterProjectService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProjectRepository $projectRepository,
        private ProjectAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterProjectRequest $request, User $user): RegisterProjectResponse
    {
        $id = ProjectId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Project(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $project = new Project($id);
        $project->register(
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

        return new RegisterProjectResponse(
            projectId: $project->id(),
            name: $project->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterProjectRequest;
    }
}
