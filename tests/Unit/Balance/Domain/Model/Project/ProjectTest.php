<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\Project;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $project = $this->registeredProject();

        $this->assertCount(0, $project->translations());
        $project->translate('translated-name', 'translated-description', Locale::English);
        $this->assertCount(1, $project->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $project = $this->registeredProject();

        $project->translate('translated-name-en', 'translated-description-en', Locale::English);
        $project->translate('translated-name-it', 'translated-description-it', Locale::Italian);
        $project->translate('translated-name-ge', 'translated-description-ge', Locale::German);

        $this->assertEquals('project-name', $project->name());
        $this->assertEquals('project-description', $project->description());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $project = $this->registeredProject();

        $project->translate('translated-name-en', 'translated-description-en', Locale::English);
        $project->translate('translated-name-it', 'translated-description-it', Locale::Italian);
        $project->translate('translated-name-ge', 'translated-description-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $project->name());
        $this->assertEquals('translated-description-it', $project->description());
    }

    public function testRegisterKeepsThePricePerTonne(): void
    {
        $project = $this->registeredProject(pricePerTonne: 20.0);

        $this->assertSame(20.0, $project->pricePerTonne());
    }

    public function testUpdateChangesThePricePerTonne(): void
    {
        $project = $this->registeredProject(pricePerTonne: 20.0);

        $project->update(
            name: 'project-name',
            description: 'project-description',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::EmissionCompensation,
            projectStatus: ProjectStatus::Pending,
            mitecoId: null,
            pricePerTonne: 25.0,
        );

        $this->assertSame(25.0, $project->pricePerTonne());
    }

    private function registeredProject(?float $pricePerTonne = null): Project
    {
        $project = new Project(id: ProjectId::generate());

        $project->register(
            name: 'project-name',
            description: 'project-description',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::EmissionCompensation,
            projectStatus: ProjectStatus::Pending,
            mitecoId: null,
            pricePerTonne: $pricePerTonne,
        );

        return $project;
    }
}
