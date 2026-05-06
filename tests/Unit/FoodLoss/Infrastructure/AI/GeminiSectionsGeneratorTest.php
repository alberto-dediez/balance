<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateAllSectionsPrompt;
use App\FoodLoss\Infrastructure\AI\GeminiSectionsGenerator;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryActionRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryCriticalPointRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryReasonRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemorySurplusCauseRepository;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Infrastructure\AI\Gemini\InMemoryGeminiClient;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class GeminiSectionsGeneratorTest extends TestCase
{
    private const GEMINI_TITLE = 'Generated Section Title';
    private const GEMINI_CONTENT = '## Introduction\n\nThis is the generated content.';

    private GeminiSectionsGenerator $sut;
    private AccountRepository $accountRepository;
    private CompanyRepository $companyRepository;
    private PropertyRepository $propertyRepository;
    private CriticalPointRepository $criticalPointRepository;
    private ActionRepository $actionRepository;
    private ReasonRepository $reasonRepository;
    private InMemoryGeminiClient $geminiClient;

    private CompanyId $companyId;

    public function setUp(): void
    {
        $this->accountRepository = new InMemoryAccountRepository();
        $this->companyRepository = new InMemoryCompanyRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->criticalPointRepository = new InMemoryCriticalPointRepository();
        $this->actionRepository = new InMemoryActionRepository();
        $this->reasonRepository = new InMemoryReasonRepository();

        $sections = [
            ['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT],
            ['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT],
            ['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT],
            ['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT],
            ['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT],
        ];

        $this->geminiClient = new InMemoryGeminiClient($sections);

        $this->sut = new GeminiSectionsGenerator(
            accountRepository: $this->accountRepository,
            propertyRepository: $this->propertyRepository,
            criticalPointRepository: $this->criticalPointRepository,
            actionRepository: $this->actionRepository,
            reasonRepository: $this->reasonRepository,
            surplusCauseRepository: new InMemorySurplusCauseRepository(),
            companyRepository: $this->companyRepository,
            geminiClient: $this->geminiClient,
            employeeRepository: new InMemoryEmployeeRepository(),
        );

        $this->companyId = CompanyId::generate();
        $this->companyRepository->add(CompanyMother::fromNameAndId('ACME Hotels', (string) $this->companyId));
    }

    public function testGeneratesFiveSectionsForEmptyPlan(): void
    {
        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: []);

        $this->sut->generate($plan);

        $this->assertCount(5, $plan->sections()->toArray());
    }

    public function testGeneratesFiveSectionsForPlanWithPropertyAndCriticalPoints(): void
    {
        $accountId = (string) AccountId::generate();
        $propertyId = PropertyId::generate();
        $criticalPointId = (string) CriticalPointId::generate();
        $actionId = (string) ActionId::generate();

        $this->accountRepository->add(AccountMother::fromNameAndId('ACME Hotels', $accountId));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndId('Hotel Central', $this->companyId, $propertyId));
        $this->criticalPointRepository->add(
            CriticalPointMother::fromNameAndAccountAndProperty('Kitchen waste', AccountId::fromString($accountId), $propertyId),
        );
        $this->actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId));

        $plan = $this->buildPlan(accountId: $accountId, propertyIds: [$propertyId]);
        $plan->addProposedAction($propertyId, new ActionId($actionId), new CriticalPointId($criticalPointId));

        $this->sut->generate($plan);

        $this->assertCount(5, $plan->sections()->toArray());
    }

    public function testSectionsContainTitleAndContentFromGeminiResponse(): void
    {
        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: []);
        $this->sut->generate($plan);

        foreach ($plan->sections()->toArray() as $section) {
            $this->assertSame(self::GEMINI_TITLE, $section->title());
            $this->assertStringContainsString('Introduction', $section->content());
            $this->assertStringContainsString('generated content', $section->content());
        }
    }

    public function testUsesConsolidatedPrompt(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndId('Test Hotel', $this->companyId, $propertyId));

        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: [$propertyId]);
        $this->sut->generate($plan);

        $this->assertInstanceOf(GenerateAllSectionsPrompt::class, $this->geminiClient->lastPrompt());
    }

    public function testClearsExistingSectionsBeforeGenerating(): void
    {
        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: []);
        $this->sut->generate($plan);
        $this->assertCount(5, $plan->sections()->toArray());

        $this->sut->generate($plan);
        $this->assertCount(5, $plan->sections()->toArray());
    }

    public function testHandlesMissingGeminiFieldsGracefully(): void
    {
        $sections = [
            [],
            [],
            [],
            [],
            [],
        ];
        $this->geminiClient->setResponse($sections);

        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: []);
        $this->sut->generate($plan);

        $sections = $plan->sections()->toArray();
        $this->assertCount(5, $sections);
        foreach ($sections as $section) {
            $this->assertSame('', $section->title());
            $this->assertSame('', $section->content());
        }
    }

    public function testCleansEscapedMarkdownContent(): void
    {
        $cleanedContent = "## Title\n\nThis is content with proper newlines.";
        $sections = array_fill(0, 5, [
            'title' => 'Title',
            'content' => '## Title\\n\\nThis is content with proper newlines.',
        ]);
        $this->geminiClient->setResponse($sections);

        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: []);
        $this->sut->generate($plan);

        foreach ($plan->sections()->toArray() as $section) {
            $this->assertSame($cleanedContent, $section->content());
        }
    }

    /** @param PropertyId[] $propertyIds */
    private function buildPlan(string $accountId, array $propertyIds): PreventionPlan
    {
        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Test Prevention Plan',
            propertyIds: $propertyIds,
            companyId: $this->companyId,
            accountId: new AccountId($accountId),
            registeredBy: new UserId('user-id'),
        );

        return $plan;
    }
}
