<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\MitigationAction;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionEffectData;
use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Application\MitigationAction\Update\UpdateMitigationActionRequest;
use App\CarbonFootprint\Application\MitigationAction\Update\UpdateMitigationActionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMitigationActionType;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/mitigation-actions/{mitigationActionId}/edit', name: 'mitigation_actions_edit')]
class EditController extends AbstractController
{
    use GetMitigationActionFromIdTrait;

    public function __invoke(
        string $mitigationActionId,
        MitigationActionRepository $mitigationActionRepository,
        MitigationPlanFootprints $footprints,
        MitigationEmissionSourcesBuilder $emissionSourcesBuilder,
        UpdateMitigationActionService $updateMitigationAction,
        MitigationPlanStepRoute $stepRoute,
        TranslatorInterface $translator,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $mitigationAction = $this->getMitigationActionFromId($mitigationActionId, $mitigationActionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $mitigationAction);

        $carbonFootprint = $footprints->find($mitigationAction->subjectType(), $mitigationAction->carbonFootprintId());
        $step = $stepRoute->resolve($mitigationAction->subjectType(), $mitigationAction->carbonFootprintId());

        if ($carbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute($step['route'], $step['params']);
        }

        $emissionSources = [];
        foreach ($emissionSourcesBuilder->build($carbonFootprint) as $emissionSource) {
            $emissionSources[$emissionSource->key()] = $emissionSource;
        }

        $form = $this->createForm(UpdateMitigationActionType::class, $this->formData($mitigationAction), [
            'year' => $carbonFootprint->calculationYear(),
            'base_year' => $mitigationAction->baseYear(),
            'manual' => $mitigationAction->isManual(),
            'emission_sources' => $this->emissionSourceChoices($emissionSources, $translator),
            'ranges' => $this->ranges($mitigationAction),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{name: string, description: string, targetYear: int, effects: array<int, array{emissionSource: string, reduction: float}>} $data */
            $data = $form->getData();

            try {
                $updateMitigationAction(
                    new UpdateMitigationActionRequest(
                        mitigationActionId: $mitigationAction->id(),
                        name: $data['name'],
                        description: $data['description'],
                        targetYear: $data['targetYear'],
                        effects: $this->effects($data['effects']),
                    ),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Medida actualizada con éxito'));

                assert($form instanceof Form);
                $clickedButton = $form->getClickedButton();

                if ($clickedButton instanceof FormInterface && 'saveAndReturn' === $clickedButton->getName()) {
                    return $this->redirectToRoute($step['route'], $step['params']);
                }

                return $this->redirectToRoute('mitigation_actions_edit', ['mitigationActionId' => $mitigationActionId]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (MitigationPlanNotEditableException) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute($step['route'], $step['params']);
            } catch (\DomainException|\InvalidArgumentException) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha podido actualizar correctamente.'));
            }
        }

        return $this->render('@carbon_footprint/mitigation_actions/edit.html.twig', [
            'form' => $form->createView(),
            'mitigationAction' => $mitigationAction,
            'carbonFootprint' => $carbonFootprint,
            'subjectType' => $mitigationAction->subjectType()->value,
            'stepPath' => $this->generateUrl($step['route'], $step['params']),
            'effectRows' => $this->effectRows($mitigationAction, $emissionSources),
            'hasEmissionSources' => [] !== $emissionSources,
        ]);
    }

    /** @return array{name: string, description: string, targetYear: int, effects: list<array{emissionSource: string, reduction: float}>} */
    private function formData(MitigationAction $mitigationAction): array
    {
        $effects = [];
        foreach ($mitigationAction->effects() as $effect) {
            $effects[] = [
                'emissionSource' => $effect->source()->value . '|' . $effect->itemId(),
                'reduction' => $effect->reduction(),
            ];
        }

        return [
            'name' => $mitigationAction->name(),
            'description' => $mitigationAction->description(),
            'targetYear' => $mitigationAction->targetYear(),
            'effects' => $effects,
        ];
    }

    /**
     * @param array<string, MitigationEmissionSource> $emissionSources
     *
     * @return array<string, string>
     */
    private function emissionSourceChoices(array $emissionSources, TranslatorInterface $translator): array
    {
        $choices = [];

        foreach ($emissionSources as $key => $emissionSource) {
            $choices[sprintf('%s — %s', $translator->trans($emissionSource->source->value, [], 'source'), $emissionSource->itemName)] = $key;
        }

        return $choices;
    }

    /** @return array<string, array{min: float, max: float}> */
    private function ranges(MitigationAction $mitigationAction): array
    {
        $ranges = [];

        foreach ($mitigationAction->effects() as $effect) {
            $ranges[$effect->source()->value . '|' . $effect->itemId()] = [
                'min' => $effect->minimumReduction(),
                'max' => $effect->maximumReduction(),
            ];
        }

        return $ranges;
    }

    /**
     * @param array<string, MitigationEmissionSource> $emissionSources
     *
     * @return list<array{source: string, itemName: string, emissions: float|null, minimumReduction: float, maximumReduction: float}>
     */
    private function effectRows(MitigationAction $mitigationAction, array $emissionSources): array
    {
        $rows = [];

        foreach ($mitigationAction->effects() as $effect) {
            $emissionSource = $emissionSources[$effect->source()->value . '|' . $effect->itemId()] ?? null;

            $rows[] = [
                'source' => $effect->source()->value,
                'itemName' => null !== $emissionSource ? $emissionSource->itemName : $effect->itemName(),
                'emissions' => $emissionSource?->emissions,
                'minimumReduction' => $effect->minimumReduction(),
                'maximumReduction' => $effect->maximumReduction(),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array{emissionSource: string, reduction: float}> $rows
     *
     * @return list<MitigationActionEffectData>
     */
    private function effects(array $rows): array
    {
        $effects = [];

        foreach ($rows as $row) {
            [$source, $itemId] = explode('|', $row['emissionSource'], 2) + [1 => ''];

            $effects[] = new MitigationActionEffectData(Source::from($source), $itemId, (float) $row['reduction']);
        }

        return $effects;
    }
}
