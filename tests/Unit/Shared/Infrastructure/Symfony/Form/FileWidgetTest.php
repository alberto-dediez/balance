<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Form;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;

class FileWidgetTest extends KernelTestCase
{
    public function testAFileTypeRendersAsADropzone(): void
    {
        $html = $this->renderWidget($this->createView('form', []));

        $this->assertStringContainsString('data-controller="file-input"', $html);
        $this->assertStringContainsString('dropzone__input', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('Arrastra aquí el archivo o pulsa para elegirlo', $html);
    }

    public function testAMultipleFileTypeKeepsTheArrayName(): void
    {
        $html = $this->renderWidget($this->createView('upload', ['multiple' => true]));

        $this->assertStringContainsString('name="upload[file][]"', $html);
        $this->assertStringContainsString('multiple="multiple"', $html);
        $this->assertStringContainsString('Arrastra aquí los archivos o pulsa para elegirlos', $html);
    }

    public function testTheAcceptAttributeIsKept(): void
    {
        $html = $this->renderWidget($this->createView('form', ['attr' => ['accept' => 'application/pdf']]));

        $this->assertStringContainsString('accept="application/pdf"', $html);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createView(string $name, array $options): FormView
    {
        $factory = self::getContainer()->get('form.factory');

        return $factory
            ->createNamedBuilder($name, FormType::class, null, ['csrf_protection' => false])
            ->add('file', FileType::class, $options)
            ->getForm()
            ->createView()
        ;
    }

    private function renderWidget(FormView $view): string
    {
        $twig = self::getContainer()->get('twig');

        return $twig
            ->createTemplate('{{ form_widget(form.file) }}')
            ->render(['form' => $view])
        ;
    }
}
