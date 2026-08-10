<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Project;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesRequest;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/projects/{projectId}', name: 'projects_view')]
class ViewController extends AbstractController
{
    use GetProjectFromIdTrait;

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        MediaRepository $mediaRepository,
        AccountProjectRepository $accountProjectRepository,
        CreditAvailability $creditAvailability,
        ProjectContributionRepository $projectContributionRepository,
        ListCreditPurchasesService $listCreditPurchases,
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
    ): Response {
        $project = $this->getProjectFromId($projectId, $projectRepository);

        $this->denyAccessUnlessGranted(Action::View->value, $project);

        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $gallery = array_filter(array_map(
            static fn($mediaId) => $mediaRepository->find($mediaId),
            $project->gallery(),
        ));

        $accountProjects = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $account->id(), projectId: $project->id(), limit: 1),
        );

        $accountProject = $accountProjects[0] ?? null;

        $now = new \DateTimeImmutable('NOW');
        $creditBalance = $creditAvailability->for($account->id(), $project->id(), $now);
        $reservedTonnes = $projectContributionRepository->reservedTonnes($account->id(), $project->id(), $now);

        $creditPurchases = [];

        if (ProjectType::EmissionCompensation === $project->projectType()) {
            $creditPurchasesResponse = $listCreditPurchases(
                new ListCreditPurchasesRequest(accountId: $account->id(), projectId: $project->id(), limit: 0),
                $loggedUser(),
            );

            $creditPurchases = $creditPurchasesResponse->creditPurchases;
        }

        return $this->render('@balance/projects/view.html.twig', [
            'project' => $project,
            'gallery' => $gallery,
            'accountProject' => $accountProject,
            'creditBalance' => $creditBalance,
            'reservedTonnes' => $reservedTonnes,
            'creditPurchases' => $creditPurchases,
        ]);
    }
}
