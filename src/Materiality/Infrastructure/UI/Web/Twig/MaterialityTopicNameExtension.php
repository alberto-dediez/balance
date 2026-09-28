<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Twig;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MaterialityTopicNameExtension extends AbstractExtension
{
    /** @var array<string, ?MaterialityTopic> */
    private array $materialityTopics = [];

    public function __construct(private readonly MaterialityTopicRepository $materialityTopicRepository) {}

    /** @return TwigFilter[] */
    public function getFilters(): array
    {
        return [
            new TwigFilter('materiality_topic_name', $this->materialityTopicName(...)),
        ];
    }

    public function materialityTopicName(MaterialityTopicId $materialityTopicId): ?MaterialityTopic
    {
        if (!array_key_exists((string) $materialityTopicId, $this->materialityTopics)) {
            $this->materialityTopics[(string) $materialityTopicId] = $this->materialityTopicRepository->find($materialityTopicId);
        }

        return $this->materialityTopics[(string) $materialityTopicId];
    }
}
