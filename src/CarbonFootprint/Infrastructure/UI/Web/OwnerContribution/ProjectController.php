<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compensate/{origin}/{originId}/{projectId}/detail', name: 'owner_contribution_project', methods: ['GET'])]
class ProjectController extends AbstractController
{
    use OwnerContributionTrait;

    public function __invoke(
        string $origin,
        string $originId,
        string $projectId,
        OwnerContributionContextResolver $contextResolver,
        ListContributionOptionsService $listContributionOptions,
        MediaRepository $mediaRepository,
        LoggedUser $loggedUser,
    ): Response {
        $context = $this->resolveContext($origin, $originId, $contextResolver, $loggedUser());

        $options = $listContributionOptions(new ListContributionOptionsRequest(
            accountId: $context->accountId,
            co2eqKg: $context->suggestedCo2eqKg,
            ownerMode: true,
        ));

        $option = $options->find($projectId);

        if (null === $option) {
            throw $this->createNotFoundException('Proyecto no disponible para esta cuenta.');
        }

        return $this->render('@carbon_footprint/owner_contribution/project.html.twig', [
            'originName' => $context->originName,
            'routes' => $this->contributionRoutes($context),
            'project' => $option->project,
            'option' => $option,
            'gallery' => $this->publicGallery($option->project->gallery(), $mediaRepository),
        ]);
    }
}
