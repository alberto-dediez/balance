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
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateAllSectionsPrompt;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Domain\Model\JobProgress\JobProgressTracker;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\AI\Gemini\GeminiClient;
use Symfony\Component\Translation\TranslatableMessage;

class GeminiSectionsGenerator implements SectionsGenerator
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly CriticalPointRepository $criticalPointRepository,
        private readonly ActionRepository $actionRepository,
        private readonly ReasonRepository $reasonRepository,
        private readonly SurplusCauseRepository $surplusCauseRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly GeminiClient $geminiClient,
        private readonly EmployeeRepository $employeeRepository,
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
        /** @var array<string, SurplusCause> $surplusCauseMap */
        $surplusCauseMap = [];
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

            foreach ($cp->surplusCauseIds() as $surplusCauseId) {
                $key = (string) $surplusCauseId;
                if (!isset($surplusCauseMap[$key])) {
                    $surplusCause = $this->surplusCauseRepository->find($surplusCauseId);
                    if (null !== $surplusCause) {
                        $surplusCauseMap[$key] = $surplusCause;
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

        $locale = LocaleGetter::locale();

        $company = $this->companyRepository->find($plan->companyId());

        if (!$company instanceof Company) {
            throw new PlanWithoutCompanyException($plan->id());
        }

        $responsible = null !== $plan->responsible()
            ? $this->employeeRepository->find($plan->responsible())
            : null;

        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Generando secciones'));

        $response = $this->geminiClient->send(new GenerateAllSectionsPrompt(
            plan: $plan,
            company: $company,
            properties: array_values($propertyMap),
            createdAt: $version->createdAt(),
            propertyMap: $propertyMap,
            criticalPointsByProperty: $criticalPointsByProperty,
            proposedActions: $proposedActions,
            actionMap: $actionMap,
            criticalPointMap: $criticalPointMap,
            reasonMap: $reasonMap,
            surplusCauseMap: $surplusCauseMap,
            locale: $locale,
            responsible: $responsible instanceof Employee ? $responsible : null,
        ));

        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Revisando contenidos'));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Revisando contenidos'));
        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Revisando contenidos'));

        foreach ($response as $order => $section) {
            $title = isset($section['title']) && is_string($section['title']) ? $section['title'] : '';
            $content = isset($section['content']) && is_string($section['content']) ? $section['content'] : '';
            $content = $this->cleanMarkdownContent($content);
            $plan->addSection($title, $content, $order);
        }

        JobProgressTracker::instance()->advance((string) new TranslatableMessage('Guardando contenidos'));
    }

    private function cleanMarkdownContent(string $content): string
    {
        $content = str_replace('\\n', "\n", $content);
        $content = str_replace('\\r', "\r", $content);
        $content = str_replace('\\t', "\t", $content);
        $content = str_replace('\\\\', '\\', $content);
        $content = trim($content);

        return $content;
    }
}
