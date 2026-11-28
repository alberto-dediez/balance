<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\Publish\PublishContestRequest;
use App\CarbonFootprint\Application\Contest\Publish\PublishContestService;
use App\CarbonFootprint\Application\Contest\View\ViewContestRequest;
use App\CarbonFootprint\Application\Contest\View\ViewContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostAlreadyInUseException;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotPublishableException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\PublishContestType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/carbon-footprint/contests/{contestId}/publish', name: 'contests_publish', methods: ['GET', 'POST'])]
class PublishController extends AbstractController
{
    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        ViewContestService $viewContest,
        PublishContestService $publishContest,
        ContestCountries $countries,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            $view = $viewContest(new ViewContestRequest(new ContestId($contestId)), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $contest = $view->contest;

        if (!$contest->isDraft()) {
            return $this->redirectToRoute('contests_edit', ['contestId' => $contestId]);
        }

        $form = $this->createForm(PublishContestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $publishContest(new PublishContestRequest($contest->id(), true === $form->get('responsibilityAccepted')->getData()), $user);

                $this->addFlash('success', new TranslatableMessage('Concurso publicado con éxito'));

                return $this->redirectToRoute('contests_edit', ['contestId' => $contestId]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (ContestHostAlreadyInUseException $e) {
                $form->addError(new FormError($translator->trans('La calculadora «{name}» ya tiene otro concurso publicado.', ['{name}' => $e->hostName()])));
            } catch (ContestNotPublishableException|\InvalidArgumentException $e) {
                $form->addError(new FormError($translator->trans($e->getMessage())));
            }
        }

        return $this->render('@carbon_footprint/contests/publish.html.twig', [
            'contest' => $contest,
            'hostNames' => $view->hostNames,
            'countries' => $countries->countries(),
            'form' => $form->createView(),
        ]);
    }
}
