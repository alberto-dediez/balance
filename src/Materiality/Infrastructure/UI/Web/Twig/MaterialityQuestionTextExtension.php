<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Twig;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MaterialityQuestionTextExtension extends AbstractExtension
{
    /** @var array<string, ?MaterialityQuestion> */
    private array $materialityQuestions = [];

    public function __construct(private readonly MaterialityQuestionRepository $materialityQuestionRepository) {}

    /** @return TwigFilter[] */
    public function getFilters(): array
    {
        return [
            new TwigFilter('materiality_question_text', $this->materialityQuestionText(...)),
        ];
    }

    public function materialityQuestionText(MaterialityQuestionId $materialityQuestionId): ?MaterialityQuestion
    {
        if (!array_key_exists((string) $materialityQuestionId, $this->materialityQuestions)) {
            $this->materialityQuestions[(string) $materialityQuestionId] = $this->materialityQuestionRepository->find($materialityQuestionId);
        }

        return $this->materialityQuestions[(string) $materialityQuestionId];
    }
}
