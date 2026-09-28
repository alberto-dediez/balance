<?php

declare(strict_types=1);

namespace App\Materiality\Application\Seed;

use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionService;
use App\Materiality\Application\MaterialityQuestion\Translate\TranslateMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Translate\TranslateMaterialityQuestionService;
use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicService;
use App\Materiality\Application\MaterialityTopic\Translate\TranslateMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Translate\TranslateMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityQuestion\StandardMaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class SeedStandardMaterialityMastersService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private MaterialityTopicAuthorizer $authorizer,
        private RegisterMaterialityTopicService $registerMaterialityTopic,
        private TranslateMaterialityTopicService $translateMaterialityTopic,
        private RegisterMaterialityQuestionService $registerMaterialityQuestion,
        private TranslateMaterialityQuestionService $translateMaterialityQuestion,
        private string $localeList,
    ) {}

    public function __invoke(SeedStandardMaterialityMastersRequest $request, User $user): SeedStandardMaterialityMastersResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new MaterialityTopic(id: MaterialityTopicId::generate()),
            SecurityAction::Admin,
            $user,
        );

        $default = LocaleGetter::default();
        $locales = array_values(array_filter(
            Locale::listFrom($this->localeList),
            static fn(Locale $locale): bool => $locale !== $default,
        ));

        $registeredTopics = [];
        $skippedTopics = 0;

        foreach (StandardScope3Category::cases() as $category) {
            if (null !== $this->materialityTopicRepository->findByNumber($category->value)) {
                ++$skippedTopics;

                continue;
            }

            $registeredTopics[] = $this->seedTopic($category, $default, $locales, $user);
        }

        $registeredQuestions = [];
        $questionsSkipped = $this->materialityQuestionRepository->totalResultsByFilter(new MaterialityQuestionFilter()) > 0;

        if (!$questionsSkipped) {
            foreach (StandardMaterialityQuestion::cases() as $standardQuestion) {
                $registeredQuestions[] = $this->seedQuestion($standardQuestion, $default, $locales, $user);
            }
        }

        return new SeedStandardMaterialityMastersResponse(
            registeredTopics: $registeredTopics,
            skippedTopics: $skippedTopics,
            registeredQuestions: $registeredQuestions,
            questionsSkipped: $questionsSkipped,
        );
    }

    /** @param list<Locale> $locales */
    private function seedTopic(StandardScope3Category $category, Locale $default, array $locales, User $user): MaterialityTopic
    {
        $response = ($this->registerMaterialityTopic)(
            new RegisterMaterialityTopicRequest(
                number: $category->value,
                scope: MaterialityScope::Scope3,
                direction: $category->direction(),
                name: $category->name($default) ?? (string) $category->name(Locale::English),
                description: $category->description($default) ?? (string) $category->description(Locale::English),
                position: $category->value,
                standard: true,
            ),
            $user,
        );

        foreach ($locales as $locale) {
            $name = $category->name($locale);

            if (null === $name) {
                continue;
            }

            ($this->translateMaterialityTopic)(
                new TranslateMaterialityTopicRequest(
                    materialityTopicId: $response->materialityTopicId,
                    name: $name,
                    description: $category->description($locale) ?? '',
                    locale: $locale,
                ),
                $user,
            );
        }

        $materialityTopic = $this->materialityTopicRepository->find($response->materialityTopicId);
        assert($materialityTopic instanceof MaterialityTopic);

        return $materialityTopic;
    }

    /** @param list<Locale> $locales */
    private function seedQuestion(StandardMaterialityQuestion $standardQuestion, Locale $default, array $locales, User $user): MaterialityQuestion
    {
        $response = ($this->registerMaterialityQuestion)(
            new RegisterMaterialityQuestionRequest(
                criterion: $standardQuestion->criterion(),
                topicId: null,
                text: $standardQuestion->text($default) ?? (string) $standardQuestion->text(Locale::English),
                help: $standardQuestion->help($default) ?? '',
                answerScale: $standardQuestion->answerScale(),
                weight: $standardQuestion->weight(),
                position: $standardQuestion->position(),
            ),
            $user,
        );

        foreach ($locales as $locale) {
            $text = $standardQuestion->text($locale);

            if (null === $text) {
                continue;
            }

            ($this->translateMaterialityQuestion)(
                new TranslateMaterialityQuestionRequest(
                    materialityQuestionId: $response->materialityQuestionId,
                    text: $text,
                    help: $standardQuestion->help($locale) ?? '',
                    locale: $locale,
                ),
                $user,
            );
        }

        $materialityQuestion = $this->materialityQuestionRepository->find($response->materialityQuestionId);
        assert($materialityQuestion instanceof MaterialityQuestion);

        return $materialityQuestion;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SeedStandardMaterialityMastersRequest;
    }
}
