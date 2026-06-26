<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialCategoryCodeType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitCodeType;
use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * The DataExchange import translation/assign-values steps bind the chosen value to
 * ColumnValueTranslation::$translation, which is a (?string). These field types must
 * therefore produce a plain string, not an enum/value-object instance.
 */
class MaterialImportFieldTypesTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FakeLocaleSetter::reset();
    }

    public function testCategoryFieldBindsToString(): void
    {
        $data = $this->submitTranslationField(MaterialCategoryCodeType::class, 'metal');

        $this->assertSame('metal', $data->translation);
    }

    public function testUnitFieldBindsToString(): void
    {
        $data = $this->submitTranslationField(UnitCodeType::class, 'kg');

        $this->assertSame('kg', $data->translation);
    }

    public function testMaterialFieldBindsToStringId(): void
    {
        $material = MaterialMother::fromName('Acero');
        $this->getContainer()->get(MaterialRepository::class)->add($material);

        $data = $this->submitTranslationField(MaterialChoiceType::class, (string) $material->id());

        $this->assertSame((string) $material->id(), $data->translation);
    }

    /** @param class-string<FormTypeInterface<mixed>> $fieldType */
    private function submitTranslationField(string $fieldType, string $value): ColumnValueTranslation
    {
        $factory = $this->getContainer()->get(FormFactoryInterface::class);

        $form = $factory
            ->createBuilder(FormType::class, new ColumnValueTranslation(value: 'raw value', translation: null))
            ->add('translation', $fieldType)
            ->getForm()
        ;

        $form->submit(['translation' => $value]);

        $this->assertTrue($form->isSynchronized(), 'The form failed to bind the submitted value.');

        $data = $form->getData();
        $this->assertInstanceOf(ColumnValueTranslation::class, $data);

        return $data;
    }
}
