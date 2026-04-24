<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PlanWithoutCompanyException;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedAction;
use App\FoodLoss\Domain\Model\PreventionPlan\SectionsGenerator;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateActionPlanSectionPrompt;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateCriticalPointsSectionPrompt;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateDonationsSectionPrompt;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateIntroductionSectionPrompt;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateMeasurementSectionPrompt;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Domain\Model\JobProgress\JobProgressTracker;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\AI\Gemini\GeminiClient;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;
use Symfony\Component\Translation\TranslatableMessage;

class GeminiSectionsGenerator implements SectionsGenerator
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly CriticalPointRepository $criticalPointRepository,
        private readonly ActionRepository $actionRepository,
        private readonly ReasonRepository $reasonRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly GeminiClient $geminiClient,
    ) {}

    public function generate(PreventionPlan $plan): void
    {
        $version = $plan->currentPlanVersion();

        $account = null !== $plan->accountId()
            ? $this->accountRepository->find($plan->accountId())
            : null;

        /** @var array<string, Property> $propertyMap */
        $propertyMap = [];
        foreach ($plan->propertyIds() as $propertyId) {
            $property = $this->propertyRepository->find($propertyId);
            if (null !== $property) {
                $propertyMap[(string) $propertyId] = $property;
            }
        }

        /** @var array<string, CriticalPoint[]> $criticalPointsByProperty */
        $criticalPointsByProperty = [];
        /** @var array<string, CriticalPoint> $criticalPointMap */
        $criticalPointMap = [];
        foreach ($plan->propertyIds() as $propertyId) {
            $criticalPoints = $this->criticalPointRepository->findByFilter(
                new CriticalPointFilter(propertyId: $propertyId, limit: 1000),
            );
            $criticalPointsByProperty[(string) $propertyId] = $criticalPoints;
            foreach ($criticalPoints as $cp) {
                $criticalPointMap[(string) $cp->id()] = $cp;
            }
        }

        /** @var array<string, Reason> $reasonMap */
        $reasonMap = [];
        foreach ($criticalPointMap as $cp) {
            foreach ($cp->reasonIds() as $reasonId) {
                $key = (string) $reasonId;
                if (!isset($reasonMap[$key])) {
                    $reason = $this->reasonRepository->find($reasonId);
                    if (null !== $reason) {
                        $reasonMap[$key] = $reason;
                    }
                }
            }
        }

        /** @var ProposedAction[] $proposedActions */
        $proposedActions = $version->proposedActions()->toArray();
        /** @var array<string, Action> $actionMap */
        $actionMap = [];
        foreach ($proposedActions as $pa) {
            $key = (string) $pa->actionId();
            if (!isset($actionMap[$key])) {
                $action = $this->actionRepository->find($pa->actionId());
                if (null !== $action) {
                    $actionMap[$key] = $action;
                }
            }
        }

        $plan->clearSections();

        $locale = LocaleGetter::locale();

        $company = $this->companyRepository->find($plan->companyId());

        if (!$company instanceof Company) {
            throw new PlanWithoutCompanyException($plan->id());
        }

        $this->generateSection($plan, new GenerateIntroductionSectionPrompt(
            plan: $plan,
            company: $company,
            properties: array_values($propertyMap),
            createdAt: $version->createdAt(),
            locale: $locale,
        ));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Introducción'));

        $this->generateSection($plan, new GenerateCriticalPointsSectionPrompt(
            propertyMap: $propertyMap,
            criticalPointsByProperty: $criticalPointsByProperty,
            proposedActions: $proposedActions,
            actionMap: $actionMap,
            reasonMap: $reasonMap,
            locale: $locale,
        ));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Puntos críticos'));

        $this->generateSection($plan, new GenerateActionPlanSectionPrompt(
            proposedActions: $proposedActions,
            propertyMap: $propertyMap,
            actionMap: $actionMap,
            criticalPointMap: $criticalPointMap,
            locale: $locale,
        ));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Plan de acción'));

        $this->generateSection($plan, new GenerateDonationsSectionPrompt(
            proposedActions: $proposedActions,
            propertyMap: $propertyMap,
            actionMap: $actionMap,
            criticalPointMap: $criticalPointMap,
            locale: $locale,
        ));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Donaciones'));

        $this->generateSection($plan, new GenerateMeasurementSectionPrompt(
            company: $company,
            propertyMap: $propertyMap,
            criticalPointsByProperty: $criticalPointsByProperty,
            locale: $locale,
        ));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Medición'));
    }

    private function generateSection(PreventionPlan $plan, GeminiPrompt $prompt): void
    {
        $response = $this->geminiClient->send($prompt);

        $title = isset($response['title']) && is_string($response['title']) ? $response['title'] : '';
        $content = isset($response['content']) && is_string($response['content']) ? $response['content'] : '';

        $plan->addSection($title, $content);
    }
}
