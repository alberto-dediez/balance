<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Domain\Model\Locale\Locale;

class ServiceCategoryTest extends TestCase
{
    public function testTranslateAddTranslation()
    {
        $serviceCategory = new ServiceCategory(
            id: ServiceCategoryId::generate(),
            name: 'service-category-name'
        );

        $this->assertCount(0, $serviceCategory->translations());

        $serviceCategory->translate('translated-name', Locale::English);

        $this->assertCount(1, $serviceCategory->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSetted()
    {
        $serviceCategory = new ServiceCategory(
            id: ServiceCategoryId::generate(),
            name: 'service-category-name'
        );
        $serviceCategory->translate('translated-name-en', Locale::English);
        $serviceCategory->translate('translated-name-it', Locale::Italian);
        $serviceCategory->translate('translated-name-ge', Locale::German);

        $this->assertEquals('service-category-name', $serviceCategory->name());
    }

    public function testTranslateReturnTranslatedName()
    {
        $serviceCategory = new ServiceCategory(
            id: ServiceCategoryId::generate(),
            name: 'serviceCategory-name'
        );
        $serviceCategory->translate('translated-name-en', Locale::English);
        $serviceCategory->translate('translated-name-it', Locale::Italian);
        $serviceCategory->translate('translated-name-ge', Locale::German);

        $serviceCategory->translations()->setLocale(Locale::Italian);

        $this->assertEquals('translated-name-it', $serviceCategory->name());
    }
}