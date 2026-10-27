<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\Issue\IssueCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Issue\IssueCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\SubmitForReview\SubmitCarbonFootprintReportForReviewRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\SubmitForReview\SubmitCarbonFootprintReportForReviewService;
use App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts\UpdateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts\UpdateCarbonFootprintReportTextsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsIncompleteException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateCarbonFootprintReportTextsType;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Application\Action as SecurityAction;
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

#[Route('/carbon-footprint-reports/{reportId}/texts', name: 'carbon_footprint_reports_texts', methods: ['GET', 'POST'])]
class TextsController extends AbstractController
{
    use GetCarbonFootprintReportFromIdTrait;

    public function __invoke(
        string $reportId,
        CarbonFootprintReportRepository $reportRepository,
        ReportPermissionsPolicy $reportPermissionsPolicy,
        CarbonFootprintReportResultsRoute $resultsRoute,
        UpdateCarbonFootprintReportTextsService $updateTexts,
        SubmitCarbonFootprintReportForReviewService $submitForReview,
        IssueCarbonFootprintReportService $issueReport,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $report = $this->getCarbonFootprintReportFromId($reportId, $reportRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $report);

        $user = $loggedUser();

        $results = $resultsRoute->resolve($report);
        $permissions = $reportPermissionsPolicy->permissionsFor($report->accountId(), $user);

        if (!$report->isEditableByOwner() || !$permissions->canEditTexts()) {
            $this->addFlash('warning', new TranslatableMessage('El informe no tiene textos pendientes de revisar.'));

            return $this->redirectToRoute($results['route'], $results['params']);
        }

        $keys = [...CarbonFootprintReportSectionKey::requiredFor($report->subjectType()), ...CarbonFootprintReportSectionKey::optionalFor($report->subjectType())];

        $form = $this->createForm(UpdateCarbonFootprintReportTextsType::class, $report->texts()->sections, [
            'keys' => $keys,
            'autoReview' => $permissions->autoReview(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            assert($form instanceof Form);

            /** @var array<string, string> $sections */
            $sections = array_map(static fn(?string $content): string => (string) $content, $form->getData());
            $clickedButton = $form->getClickedButton();
            $action = $clickedButton instanceof FormInterface ? $clickedButton->getName() : 'save';

            try {
                if ('submitForReview' === $action) {
                    $submitForReview(new SubmitCarbonFootprintReportForReviewRequest($report->id(), $sections), $user);
                    $this->addFlash('success', new TranslatableMessage('Informe enviado a revisión.'));

                    return $this->redirectToRoute($results['route'], $results['params']);
                }

                if ('issue' === $action) {
                    $issueReport(new IssueCarbonFootprintReportRequest($report->id(), $sections), $user);
                    $this->addFlash('success', new TranslatableMessage('Se está generando el informe, te notificaremos cuando esté disponible.'));

                    return $this->redirectToRoute($results['route'], $results['params']);
                }

                $updateTexts(new UpdateCarbonFootprintReportTextsRequest($report->id(), $sections), $user);
                $this->addFlash('success', new TranslatableMessage('Textos guardados.'));

                return $this->redirectToRoute('carbon_footprint_reports_texts', ['reportId' => $reportId]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (CarbonFootprintReportTextsIncompleteException) {
                $form->addError(new FormError('Todas las secciones obligatorias deben tener contenido.'));
            } catch (CarbonFootprintReportTransitionNotAllowedException) {
                $this->addFlash('warning', new TranslatableMessage('El informe no tiene textos pendientes de revisar.'));

                return $this->redirectToRoute($results['route'], $results['params']);
            }
        }

        return $this->render('@carbon_footprint/carbon_footprint_reports/texts.html.twig', [
            'report' => $report,
            'form' => $form->createView(),
            'resultsRoute' => $results['route'],
            'resultsParams' => $results['params'],
            'autoReview' => $permissions->autoReview(),
        ]);
    }
}
