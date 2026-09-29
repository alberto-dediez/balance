<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateCarbonFootprintReportTextsType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class UpdateCarbonFootprintReportTextsTypeTest extends KernelTestCase
{
    public function testTheMitigationPlanSectionIsNotRequired(): void
    {
        $keys = [
            ...CarbonFootprintReportSectionKey::requiredFor(CarbonFootprintReportSubjectType::Property),
            ...CarbonFootprintReportSectionKey::optionalFor(CarbonFootprintReportSubjectType::Property),
        ];

        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $form = $formFactory->create(UpdateCarbonFootprintReportTextsType::class, null, ['keys' => $keys]);

        $this->assertFalse($form->get('mitigationPlan')->getConfig()->getRequired());
        $this->assertTrue($form->get('conclusion')->getConfig()->getRequired());
    }
}
