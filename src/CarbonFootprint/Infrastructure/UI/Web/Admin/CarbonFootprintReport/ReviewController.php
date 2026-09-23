<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\Approve\ApproveCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Approve\ApproveCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\GetForReview\GetCarbonFootprintReportForReviewRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\GetForReview\GetCarbonFootprintReportForReviewService;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reject\RejectCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reject\RejectCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts\UpdateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts\UpdateCarbonFootprintReportTextsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsIncompleteException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ReviewCarbonFootprintReportType;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport\GetCarbonFootprintReportFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/carbon-footprint-reports/{reportId}/review', name: 'admin_carbon_footprint_reports_review', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
class ReviewController extends AbstractController
{
    use GetCarbonFootprintReportFromIdTrait;

    public function __invoke(
        string $reportId,
        CarbonFootprintReportRepository $reportRepository,
        GetCarbonFootprintReportForReviewService $getForReview,
        UpdateCarbonFootprintReportTextsService $updateTexts,
        ApproveCarbonFootprintReportService $approveReport,
        RejectCarbonFootprintReportService $rejectReport,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $report = $this->getCarbonFootprintReportFromId($reportId, $reportRepository);

        if (!$report->isReviewable()) {
            $this->addFlash('warning', new TranslatableMessage('Este informe ya no está pendiente de revisión.'));

            return $this->redirectToRoute('admin_carbon_footprint_reports');
        }

        $user = $loggedUser();

        try {
            $review = $getForReview(new GetCarbonFootprintReportForReviewRequest($report->id()), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $keys = [...CarbonFootprintReportSectionKey::requiredFor($report->subjectType()), ...CarbonFootprintReportSectionKey::optionalFor($report->subjectType())];

        $form = $this->createForm(ReviewCarbonFootprintReportType::class, $report->texts()->sections + ['comment' => ''], ['keys' => $keys]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            assert($form instanceof Form);

            $data = $form->getData();
            $comment = trim((string) ($data['comment'] ?? ''));
            unset($data['comment']);

            /** @var array<string, string> $sections */
            $sections = array_map(static fn(?string $content): string => (string) $content, $data);
            $clickedButton = $form->getClickedButton();
            $action = $clickedButton instanceof FormInterface ? $clickedButton->getName() : 'approve';

            try {
                if ('reject' === $action) {
                    if ('' === $comment) {
                        $form->get('comment')->addError(new FormError('Indica el motivo del rechazo.'));
                    } else {
                        $updateTexts(new UpdateCarbonFootprintReportTextsRequest($report->id(), $sections), $user);
                        $rejectReport(new RejectCarbonFootprintReportRequest($report->id(), $comment), $user);
                        $this->addFlash('success', new TranslatableMessage('Informe rechazado. La huella se ha reabierto.'));

                        return $this->redirectToRoute('admin_carbon_footprint_reports');
                    }
                } else {
                    $approveReport(new ApproveCarbonFootprintReportRequest($report->id(), $sections), $user);
                    $this->addFlash('success', new TranslatableMessage('Informe aprobado. Se está generando el PDF.'));

                    return $this->redirectToRoute('admin_carbon_footprint_reports');
                }
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (CarbonFootprintReportTextsIncompleteException) {
                $form->addError(new FormError('Todas las secciones obligatorias deben tener contenido.'));
            }
        }

        return $this->render('@carbon_footprint/admin/carbon_footprint_reports/review.html.twig', [
            'report' => $report,
            'review' => $review,
            'form' => $form->createView(),
        ]);
    }
}
