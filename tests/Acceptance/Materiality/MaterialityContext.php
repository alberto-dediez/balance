<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Materiality;

use App\Materiality\Application\Seed\SeedStandardMaterialityMastersRequest;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorTranslation;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicTranslation;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class MaterialityContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(KernelBrowser $kernelBrowser)
    {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    private function getTopicRepository(): MaterialityTopicRepository
    {
        return $this->client->getContainer()->get(MaterialityTopicRepository::class);
    }

    private function getQuestionRepository(): MaterialityQuestionRepository
    {
        return $this->client->getContainer()->get(MaterialityQuestionRepository::class);
    }

    private function getSectorRepository(): MaterialitySectorRepository
    {
        return $this->client->getContainer()->get(MaterialitySectorRepository::class);
    }

    #[Given('materiality topic :name with id :id exists')]
    public function materialityTopicExists(string $name, string $id): void
    {
        $this->getTopicRepository()->add(MaterialityTopicMother::fromNameAndId($name, $id));
    }

    #[Given('materiality topic :name with id :id in scope :scope exists')]
    public function materialityTopicInScopeExists(string $name, string $id, string $scope): void
    {
        $materialityScope = MaterialityScope::from($scope);

        $this->getTopicRepository()->add(MaterialityTopicMother::from(
            name: $name,
            id: $id,
            scope: $materialityScope,
            direction: MaterialityScope::Scope3 === $materialityScope ? MaterialityTopicDirection::Upstream : null,
        ));
    }

    #[Given('standard materiality topic :number with id :id exists')]
    public function standardMaterialityTopicExists(string $number, string $id): void
    {
        $this->getTopicRepository()->add(MaterialityTopicMother::standard(StandardScope3Category::from((int) $number), $id));
    }

    #[Given('materiality topic :id is inactive')]
    public function materialityTopicIsInactive(string $id): void
    {
        $materialityTopic = $this->topic($id);

        $materialityTopic->update(
            scope: $materialityTopic->scope(),
            direction: $materialityTopic->direction(),
            name: $materialityTopic->defaultName(),
            description: $materialityTopic->defaultDescription(),
            position: $materialityTopic->position(),
            active: false,
        );

        $this->getTopicRepository()->add($materialityTopic);
    }

    #[Given('materiality question :text with id :id for criterion :criterion exists')]
    public function materialityQuestionExists(string $text, string $id, string $criterion): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion and topic :topicId exists')]
    public function materialityQuestionForTopicExists(string $text, string $id, string $criterion, string $topicId): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            topicId: new MaterialityTopicId($topicId),
        ));
    }

    #[Given('materiality question :text with id :id for criterion :criterion and sectors :sectorIds exists')]
    public function materialityQuestionForSectorsExists(string $text, string $id, string $criterion, string $sectorIds): void
    {
        $this->getQuestionRepository()->add(MaterialityQuestionMother::from(
            text: $text,
            id: $id,
            criterion: MaterialityCriterion::from($criterion),
            sectorIds: array_map(
                static fn(string $sectorId): MaterialitySectorId => new MaterialitySectorId(trim($sectorId)),
                explode(',', $sectorIds),
            ),
        ));
    }

    #[Given('materiality sector :name with id :id exists')]
    public function materialitySectorExists(string $name, string $id): void
    {
        $this->getSectorRepository()->add(MaterialitySectorMother::fromNameAndId($name, $id));
    }

    #[Given('standard materiality masters are seeded')]
    public function standardMaterialityMastersAreSeeded(): void
    {
        $service = $this->client->getContainer()->get(SeedStandardMaterialityMastersService::class);

        $service(new SeedStandardMaterialityMastersRequest(), UserMother::adminUser());
    }

    #[Then('materiality topic :id should be removed')]
    public function materialityTopicShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->topic($id, includeRemoved: true)->isRemoved());
    }

    #[Then('materiality topic :id should have name :name in locale :locale')]
    public function materialityTopicShouldHaveNameInLocale(string $id, string $name, string $locale): void
    {
        $materialityTopic = $this->topic($id);
        $expectedLocale = Locale::from($locale);

        if (LocaleGetter::default() === $expectedLocale) {
            Assert::assertSame($name, $materialityTopic->defaultName());

            return;
        }

        $translation = $materialityTopic->translations()->getTranslationFor($expectedLocale);

        Assert::assertInstanceOf(MaterialityTopicTranslation::class, $translation);
        Assert::assertSame($name, $translation->name());
    }

    #[Then('materiality sector :id should be removed')]
    public function materialitySectorShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->sector($id, includeRemoved: true)->isRemoved());
    }

    #[Then('materiality sector :id should have name :name in locale :locale')]
    public function materialitySectorShouldHaveNameInLocale(string $id, string $name, string $locale): void
    {
        $materialitySector = $this->sector($id);
        $expectedLocale = Locale::from($locale);

        if (LocaleGetter::default() === $expectedLocale) {
            Assert::assertSame($name, $materialitySector->defaultName());

            return;
        }

        $translation = $materialitySector->translations()->getTranslationFor($expectedLocale);

        Assert::assertInstanceOf(MaterialitySectorTranslation::class, $translation);
        Assert::assertSame($name, $translation->name());
    }

    #[Then('there should be :count materiality sectors')]
    public function thereShouldBeMaterialitySectors(int $count): void
    {
        Assert::assertSame($count, $this->getSectorRepository()->totalResultsByFilter(new MaterialitySectorFilter()));
    }

    #[Then('materiality question :id should be removed')]
    public function materialityQuestionShouldBeRemoved(string $id): void
    {
        Assert::assertTrue($this->question($id)->isRemoved());
    }

    #[Then('materiality question :id should have topic :topicId')]
    public function materialityQuestionShouldHaveTopic(string $id, string $topicId): void
    {
        Assert::assertSame($topicId, (string) $this->question($id)->topicId());
    }

    #[Then('materiality question :id should have sectors :sectorIds')]
    public function materialityQuestionShouldHaveSectors(string $id, string $sectorIds): void
    {
        $expected = array_map('trim', explode(',', $sectorIds));
        $actual = array_map(
            static fn(MaterialitySectorId $sectorId): string => (string) $sectorId,
            $this->question($id)->sectorIds(),
        );

        sort($expected);
        sort($actual);

        Assert::assertSame($expected, $actual);
    }

    #[Then('materiality question :id should apply to all sectors')]
    public function materialityQuestionShouldApplyToAllSectors(string $id): void
    {
        Assert::assertTrue($this->question($id)->appliesToAllSectors());
    }

    #[Then('there should be :count standard materiality topics')]
    public function thereShouldBeStandardMaterialityTopics(int $count): void
    {
        Assert::assertSame($count, $this->getTopicRepository()->totalResultsByFilter(new MaterialityTopicFilter(standard: true)));
    }

    #[Then('there should be :count materiality questions')]
    public function thereShouldBeMaterialityQuestions(int $count): void
    {
        Assert::assertSame($count, $this->getQuestionRepository()->totalResultsByFilter(new MaterialityQuestionFilter()));
    }

    private function topic(string $id, bool $includeRemoved = false): MaterialityTopic
    {
        foreach ($this->getTopicRepository()->findByFilter(new MaterialityTopicFilter(includeRemoved: $includeRemoved, limit: 0)) as $materialityTopic) {
            if ((string) $materialityTopic->id() === $id) {
                return $materialityTopic;
            }
        }

        throw new \RuntimeException(sprintf('Materiality topic "%s" not found', $id));
    }

    private function sector(string $id, bool $includeRemoved = false): MaterialitySector
    {
        foreach ($this->getSectorRepository()->findByFilter(new MaterialitySectorFilter(includeRemoved: $includeRemoved, limit: 0)) as $materialitySector) {
            if ((string) $materialitySector->id() === $id) {
                return $materialitySector;
            }
        }

        throw new \RuntimeException(sprintf('Materiality sector "%s" not found', $id));
    }

    private function question(string $id): MaterialityQuestion
    {
        foreach ($this->getQuestionRepository()->findByFilter(new MaterialityQuestionFilter(includeRemoved: true, limit: 0)) as $materialityQuestion) {
            if ((string) $materialityQuestion->id() === $id) {
                return $materialityQuestion;
            }
        }

        throw new \RuntimeException(sprintf('Materiality question "%s" not found', $id));
    }
}
