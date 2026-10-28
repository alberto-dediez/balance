<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources\ChangeOrganizationalProviderContributionSourcesRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources\ChangeOrganizationalProviderContributionSourcesService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions\RecordOrganizationalProviderContributionManualEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions\RecordOrganizationalProviderContributionManualEmissionsService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Show\GetOrganizationalProviderContributionDetailRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Show\GetOrganizationalProviderContributionDetailService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ChangeOrganizationalProviderContributionSourcesType;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\ReportedEmissionsAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ReportedEmissionsRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{carbonFootprintId}/providers/{contributionId}/edit', name: 'property_carbon_footprints_providers_edit', defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/providers/{contributionId}/edit', name: 'company_carbon_footprints_providers_edit', defaults: ['subjectType' => 'company'])]
class EditController extends AbstractController
{
    use GetOrganizationalProviderContributionFromIdTrait;

    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        string $contributionId,
        OrganizationalProviderContributionRepository $contributionRepository,
        GetOrganizationalProviderContributionDetailService $getDetail,
        ChangeOrganizationalProviderContributionSourcesService $changeSources,
        RecordOrganizationalProviderContributionManualEmissionsService $recordManualEmissions,
        StageAnswerHttpMapper $answerMapper,
        ReportedEmissionsAnswerHttpMapper $reportedAnswerMapper,
        ReportedEmissionsRowViewFactory $reportedRowViewFactory,
        ScopeStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $contribution = $this->getOrganizationalProviderContributionFromId($contributionId, $type, $carbonFootprintId, $contributionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $contribution);

        try {
            $detail = $getDetail(new GetOrganizationalProviderContributionDetailRequest($contribution->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $stepRoute = OrganizationalProviderContributionRoutes::step($type);
        $stepParameters = OrganizationalProviderContributionRoutes::stepParameters($type, $carbonFootprintId);
        $editParameters = ['carbonFootprintId' => $carbonFootprintId, 'contributionId' => $contributionId];
        $readOnly = $detail->carbonFootprint->isCompleted();

        $form = $this->createForm(ChangeOrganizationalProviderContributionSourcesType::class, ['sources' => $contribution->sources()], [
            'disabled' => $readOnly,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $readOnly) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute($stepRoute, $stepParameters);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{sources: list<Source>} $data */
            $data = $form->getData();

            try {
                $sectionAnswers = [];

                if ($contribution->isManual()) {
                    $sectionAnswers = $answerMapper->map($this->reportedData($request), $this->reportedSections($data['sources']));
                    self::ensureValidRows($sectionAnswers, $data['sources']);
                }

                $changeSources(new ChangeOrganizationalProviderContributionSourcesRequest($contribution->id(), $data['sources']), $loggedUser());

                if ($contribution->isManual()) {
                    $recordManualEmissions(
                        new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), $sectionAnswers),
                        $loggedUser(),
                    );
                }

                $this->addFlash('success', new TranslatableMessage('Aportación de proveedor actualizada con éxito'));

                assert($form instanceof Form);
                $clickedButton = $form->getClickedButton();

                if ($clickedButton instanceof FormInterface && 'saveAndReturn' === $clickedButton->getName()) {
                    return $this->redirectToRoute($stepRoute, $stepParameters);
                }

                return $this->redirectToRoute(OrganizationalProviderContributionRoutes::action($type, 'edit'), $editParameters);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (OrganizationalProviderContributionClosedException) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute($stepRoute, $stepParameters);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));
            }
        }

        $reportedRows = [];
        if ($form->isSubmitted() && $contribution->isManual()) {
            $reportedRows = $this->submittedReportedRows($request, $reportedAnswerMapper, $reportedRowViewFactory);
        } else {
            foreach ($detail->sectionsBySource as $source => $sections) {
                $key = Scope::Scope3->reportedSectionFor(Source::from($source))->key;
                $reportedRows[$source] = $stepView->build($sections)['sections'][$key]['rows'] ?? [];
            }
        }

        return $this->render('@carbon_footprint/organizational_carbon_footprints/provider_contributions/edit.html.twig', [
            'form' => $form->createView(),
            'contribution' => $contribution,
            'provider' => $detail->provider,
            'property' => $detail->property,
            'carbonFootprint' => $detail->carbonFootprint,
            'subjectType' => $subjectType,
            'reportedRows' => $reportedRows,
            'readOnly' => $readOnly,
            'stepPath' => $this->generateUrl($stepRoute, $stepParameters),
        ]);
    }

    /** @return array<string, list<ActivityRowView>> */
    private function submittedReportedRows(Request $request, ReportedEmissionsAnswerHttpMapper $answerMapper, ReportedEmissionsRowViewFactory $rowViewFactory): array
    {
        $reportedRows = [];

        foreach ($request->request->all('reported') as $source => $rows) {
            $reportedRows[$source] = [];

            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $answer = is_array($row) ? $answerMapper->map($row) : null;

                if ($answer instanceof ReportedEmissionsAnswer) {
                    $reportedRows[$source][] = $rowViewFactory->createFromAnswer($answer);
                }
            }
        }

        return $reportedRows;
    }

    /** @return array<string, mixed> */
    private function reportedData(Request $request): array
    {
        $data = [];

        foreach ($request->request->all('reported') as $source => $rows) {
            $data[$source . '_reported'] = $rows;
        }

        return $data;
    }

    /**
     * @param list<Source> $sources
     *
     * @return list<SectionDefinition>
     */
    private function reportedSections(array $sources): array
    {
        return array_map(
            static fn(Source $source): SectionDefinition => Scope::Scope3->reportedSectionFor($source),
            $sources,
        );
    }

    /**
     * @param list<SectionAnswer> $sectionAnswers
     * @param list<Source>        $sources
     */
    private static function ensureValidRows(array $sectionAnswers, array $sources): void
    {
        $sectionsByKey = [];
        foreach (Scope::Scope3->providerSectionsFor($sources) as $section) {
            $sectionsByKey[$section->key] = $section;
        }

        foreach ($sectionAnswers as $sectionAnswer) {
            $definition = $sectionsByKey[$sectionAnswer->definition->key]
                ?? throw new \InvalidArgumentException(sprintf('Section "%s" is not covered by the provider contribution.', $sectionAnswer->definition->key));

            foreach ($sectionAnswer->answers as $answer) {
                if ($answer->type() !== $definition->activityType) {
                    throw new \InvalidArgumentException(sprintf('Answer of type "%s" does not match section "%s" type.', $answer->type()->value, $definition->key));
                }

                if ($answer instanceof ReportedEmissionsAnswer) {
                    ReportedEmissionsActivity::ensureValidDescription($answer->description);
                }
            }
        }
    }
}
