<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Application\Contest\Update\UpdateContestRequest;
use App\CarbonFootprint\Application\Contest\Update\UpdateContestService;
use App\CarbonFootprint\Application\Contest\View\ViewContestRequest;
use App\CarbonFootprint\Application\Contest\View\ViewContestService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostAlreadyInUseException;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ContestType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/carbon-footprint/contests/{contestId}/edit', name: 'contests_edit', methods: ['GET', 'POST'])]
class EditController extends AbstractController
{
    public function __invoke(
        string $contestId,
        LoggedUser $loggedUser,
        ViewContestService $viewContest,
        UpdateContestService $updateContest,
        TripCalculationLinkRepository $tripCalculationLinkRepository,
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
        $form = $this->createForm(ContestType::class, ContestFormData::fromContest($contest), ['mode' => self::modeFor($contest)]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $accountId = $contest->accountId();
            $tripLinkId = null !== $accountId ? $tripCalculationLinkRepository->findForAccount($accountId)?->id() : null;

            try {
                $updateContest(new UpdateContestRequest(
                    contestId: $contest->id(),
                    participationMode: $data->participationMode,
                    awardMode: $data->awardMode,
                    winnersCount: $data->winnersCount,
                    platformDraw: $data->platformDraw,
                    adultsOnly: $data->adultsOnly,
                    name: $data->name,
                    description: $data->description,
                    prizeDescription: $data->prizeDescription,
                    terms: $data->terms,
                    organizerName: $data->organizerName,
                    organizerTaxId: $data->organizerTaxId,
                    organizerContactEmail: $data->organizerContactEmail,
                    privacyPolicyUrl: $data->privacyPolicyUrl,
                    startsAt: $data->startsAt(),
                    endsAt: $data->endsAt(),
                    hosts: $data->hosts($tripLinkId),
                    allowedCountries: $data->allowedCountries,
                ), $user);

                $this->addFlash('success', new TranslatableMessage('Concurso actualizado con éxito'));

                return $this->redirectToRoute($this->clickedSaveAndReturn($form) ? 'contests' : 'contests_edit', ['contestId' => $contestId]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (ContestHostAlreadyInUseException $e) {
                $form->addError(new FormError($translator->trans('La calculadora «{name}» ya tiene otro concurso publicado.', ['{name}' => $e->hostName()])));
            } catch (\InvalidArgumentException|\RuntimeException $e) {
                $form->addError(new FormError($translator->trans($e->getMessage())));
            }
        }

        return $this->render('@carbon_footprint/contests/edit.html.twig', [
            'contest' => $contest,
            'hostNames' => $view->hostNames,
            'countries' => $countries->countries(),
            'form' => $form->createView(),
        ]);
    }

    public static function modeFor(Contest $contest): string
    {
        return match (true) {
            $contest->isDraft() => ContestType::MODE_FULL,
            $contest->isPublished() => ContestType::MODE_PUBLISHED,
            default => ContestType::MODE_READONLY,
        };
    }

    /** @param FormInterface<ContestFormData> $form */
    private function clickedSaveAndReturn(FormInterface $form): bool
    {
        assert($form instanceof Form);
        $button = $form->getClickedButton();

        return $button instanceof FormInterface && 'saveAndReturn' === $button->getName();
    }
}
