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

#[Route('/compensate/{origin}/{originId}', name: 'owner_contribution_choose', methods: ['GET'])]
class ChooseController extends AbstractController
{
    use OwnerContributionTrait;

    public function __invoke(
        string $origin,
        string $originId,
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

        $covers = [];

        foreach ($options->options as $option) {
            $gallery = $this->publicGallery($option->project->gallery(), $mediaRepository);
            $covers[(string) $option->project->id()] = $gallery[0] ?? null;
        }

        return $this->render('@carbon_footprint/owner_contribution/choose.html.twig', [
            'originName' => $context->originName,
            'co2eqKg' => $context->suggestedCo2eqKg,
            'routes' => $this->contributionRoutes($context),
            'options' => $options->options,
            'covers' => $covers,
        ]);
    }
}
