<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ContestEntry\Export\ContestEntriesExportFilter;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestFilter;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<ContestEntriesExportFilter> */
class ContestEntriesExportType extends AbstractType
{
    public function __construct(
        private readonly ContestRepository $contestRepository,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contestId', ChoiceType::class, [
                'label' => new TranslatableMessage('Concurso'),
                'required' => true,
                'choices' => $this->contests(),
                'choice_value' => static fn(?ContestId $contestId): ?string => null === $contestId ? null : (string) $contestId,
                'choice_label' => fn(ContestId $contestId): string => $this->contestRepository->find($contestId)?->name() ?? (string) $contestId,
                'choice_translation_domain' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContestEntriesExportFilter::class,
        ]);
    }

    /** @return list<ContestId> */
    private function contests(): array
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        if (!$accountId instanceof AccountId) {
            return [];
        }

        return array_map(
            static fn(Contest $contest): ContestId => $contest->id(),
            $this->contestRepository->findByFilter(new ContestFilter(accountId: $accountId, limit: 0)),
        );
    }
}
