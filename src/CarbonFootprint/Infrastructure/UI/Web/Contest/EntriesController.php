<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Application\Contest\View\ViewContestRequest;
use App\CarbonFootprint\Application\Contest\View\ViewContestService;
use App\CarbonFootprint\Application\ContestEntry\List\ListContestEntriesRequest;
use App\CarbonFootprint\Application\ContestEntry\List\ListContestEntriesService;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListContestEntriesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/contests/{contestId}/entries', name: 'contests_entries')]
class EntriesController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        ViewContestService $viewContest,
        ListContestEntriesService $listContestEntries,
        ContestCountries $countries,
        ContestHostLocator $hostLocator,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $id = new ContestId($contestId);

        try {
            $view = $viewContest(new ViewContestRequest($id), $user);

            [$filter, $form] = $this->filter($request, fn(): ListContestEntriesRequest => new ListContestEntriesRequest(contestId: $id), ListContestEntriesType::class);
            assert($filter instanceof ListContestEntriesRequest);
            $filter->contestId = $id;

            $response = $listContestEntries($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $hostNames = $view->hostNames;

        foreach ($response->entries as $entry) {
            $hostNames[$entry->host()->key()] ??= $hostLocator->name($entry->host());
        }

        return $this->render('@carbon_footprint/contests/entries.html.twig', [
            'contest' => $view->contest,
            'hostNames' => $hostNames,
            'countries' => $countries->countries(),
            'entries' => $response->entries,
            'disqualified' => $response->disqualified,
            'winners' => $response->winners,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
