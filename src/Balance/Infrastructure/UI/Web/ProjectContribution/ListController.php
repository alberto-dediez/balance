<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\ProjectContribution;

use App\Balance\Application\AccountProject\List\ListAccountProjectsRequest;
use App\Balance\Application\AccountProject\List\ListAccountProjectsService;
use App\Balance\Application\ProjectContribution\List\ListProjectContributionsRequest;
use App\Balance\Application\ProjectContribution\List\ListProjectContributionsService;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\Symfony\Form\ListProjectContributionsType;
use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesService;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/credits/contributions', name: 'project_contributions')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListProjectContributionsService $listProjectContributions,
        ListAccountProjectsService $listAccountProjects,
        ProjectRepository $projectRepository,
        ListPropertiesService $listProperties,
        ProjectContributionAuthorizer $projectContributionAuthorizer,
        AccountFeaturesPolicy $accountFeaturesPolicy,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            $projectChoices = [];

            if ($accountFeaturesPolicy->isEnabled(AccountFeature::Compensation, $account->id())) {
                $accountProjectsResponse = $listAccountProjects(
                    new ListAccountProjectsRequest(accountId: $account->id(), limit: 0),
                    $loggedUser(),
                );

                foreach ($accountProjectsResponse->accountProjects as $accountProject) {
                    $projectId = $accountProject->projectId();
                    $project = null !== $projectId ? $projectRepository->find($projectId) : null;

                    if (null !== $project) {
                        $projectChoices[$project->name()] = $project->id();
                    }
                }
            }

            $isDistributor = $projectContributionAuthorizer->isActiveDistributorOwner($loggedUser(), $account->id());
            $propertyChoices = [];

            if ($isDistributor) {
                $properties = $listProperties(new ListPropertiesRequest(accountId: $account->id(), limit: 0), $loggedUser())->properties;

                foreach ($properties as $property) {
                    $propertyChoices[$property->name()] = $property->id();
                }
            }

            [$filter, $form] = $this->filter(
                $request,
                fn(): ListProjectContributionsRequest => new ListProjectContributionsRequest(accountId: $account->id()),
                ListProjectContributionsType::class,
                ['projects' => $projectChoices, 'properties' => $propertyChoices],
            );

            assert($filter instanceof ListProjectContributionsRequest);

            $filter->accountId = $account->id();

            $response = $listProjectContributions($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/contributions/list.html.twig', [
            'projectContributions' => $response->projectContributions,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'isDistributor' => $isDistributor,
        ]);
    }
}
