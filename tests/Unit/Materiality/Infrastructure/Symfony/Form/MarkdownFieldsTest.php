<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Application\MaterialityQuestion\Register\RegisterMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityTopic\Register\RegisterMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Update\UpdateMaterialityTopicRequest;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Infrastructure\Symfony\Form\RegisterMaterialityQuestionType;
use App\Materiality\Infrastructure\Symfony\Form\RegisterMaterialityTopicType;
use App\Materiality\Infrastructure\Symfony\Form\UpdateMaterialityQuestionType;
use App\Materiality\Infrastructure\Symfony\Form\UpdateMaterialityTopicType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;

class MarkdownFieldsTest extends KernelTestCase
{
    public function testRegisterQuestionFormRendersHelpAsMarkdownEditor(): void
    {
        $html = $this->renderWidget(
            $this->createView(RegisterMaterialityQuestionType::class, new RegisterMaterialityQuestionRequest()),
            'help',
        );

        $this->assertMarkdownEditor($html, 'register_materiality_question[help]');
    }

    public function testUpdateQuestionFormRendersHelpWithItsValue(): void
    {
        $request = new UpdateMaterialityQuestionRequest(
            materialityQuestionId: MaterialityQuestionId::generate(),
            criterion: MaterialityCriterion::Magnitude,
            topicId: null,
            text: '¿Pregunta?',
            help: '**Alto** significa mayor relevancia',
            answerScale: MaterialityAnswerScale::Level,
            weight: 1,
            position: 1,
            active: true,
        );

        $html = $this->renderWidget($this->createView(UpdateMaterialityQuestionType::class, $request), 'help');

        $this->assertMarkdownEditor($html, 'update_materiality_question[help]');
        $this->assertStringContainsString('**Alto** significa mayor relevancia</textarea>', $html);
    }

    public function testRegisterTopicFormRendersDescriptionAsMarkdownEditor(): void
    {
        $html = $this->renderWidget(
            $this->createView(RegisterMaterialityTopicType::class, new RegisterMaterialityTopicRequest()),
            'description',
        );

        $this->assertMarkdownEditor($html, 'register_materiality_topic[description]');
    }

    public function testUpdateTopicFormRendersDescriptionWithItsValue(): void
    {
        $request = new UpdateMaterialityTopicRequest(
            materialityTopicId: MaterialityTopicId::generate(),
            scope: MaterialityScope::Scope3,
            direction: null,
            name: 'Lavandería externa',
            description: '**Incluye** transporte subcontratado',
            position: 1,
            active: true,
        );

        $html = $this->renderWidget($this->createView(UpdateMaterialityTopicType::class, $request), 'description');

        $this->assertMarkdownEditor($html, 'update_materiality_topic[description]');
        $this->assertStringContainsString('**Incluye** transporte subcontratado</textarea>', $html);
    }

    private function assertMarkdownEditor(string $html, string $name): void
    {
        $this->assertStringContainsString('data-controller="markdown-editor"', $html);
        $this->assertStringContainsString('data-markdown-editor-height-value="220px"', $html);
        $this->assertStringContainsString('data-markdown-editor-hide-link-value="false"', $html);
        $this->assertStringContainsString(sprintf('name="%s"', $name), $html);
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     */
    private function createView(string $type, object $data): FormView
    {
        $factory = self::getContainer()->get('form.factory');

        return $factory
            ->create($type, $data, ['csrf_protection' => false])
            ->createView()
        ;
    }

    private function renderWidget(FormView $view, string $field): string
    {
        $twig = self::getContainer()->get('twig');

        return $twig
            ->createTemplate(sprintf('{{ form_widget(form.%s) }}', $field))
            ->render(['form' => $view])
        ;
    }
}
