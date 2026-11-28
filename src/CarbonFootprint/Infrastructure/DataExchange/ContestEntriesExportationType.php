<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Application\ContestEntry\Export\ContestEntriesExportFilter;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ContestEntriesExportType;
use App\CarbonFootprint\Infrastructure\UI\Web\Contest\ContestCountries;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContestEntriesExportationType implements FilterAuthorizedExportationType
{
    public function __construct(
        private readonly ContestRepository $contestRepository,
        private readonly ContestEntryRepository $contestEntryRepository,
        private readonly ContestAuthorizer $authorizer,
        private readonly ContestHostLocator $hostLocator,
        private readonly ContestCountries $contestCountries,
        private readonly TranslatorInterface $translator,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'contest_entries';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Apuntados del concurso');
    }

    public function returnRoute(): string
    {
        return 'contests';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('entryNumber', new TranslatableMessage('Nº')),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('email', new TranslatableMessage('Email')),
            new ExportColumnDefinition('phone', new TranslatableMessage('Teléfono')),
            new ExportColumnDefinition('country', new TranslatableMessage('País')),
            new ExportColumnDefinition('locale', new TranslatableMessage('Idioma')),
            new ExportColumnDefinition('host', new TranslatableMessage('Calculadora')),
            new ExportColumnDefinition('status', new TranslatableMessage('Estado')),
            new ExportColumnDefinition('winner', new TranslatableMessage('Ganador')),
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha de inscripción')),
            new ExportColumnDefinition('confirmedAt', new TranslatableMessage('Fecha de confirmación')),
            new ExportColumnDefinition('disqualifiedAt', new TranslatableMessage('Fecha de descalificación')),
            new ExportColumnDefinition('disqualificationReason', new TranslatableMessage('Motivo de descalificación')),
            new ExportColumnDefinition('consentedAt', new TranslatableMessage('Fecha del consentimiento')),
            new ExportColumnDefinition('termsVersion', new TranslatableMessage('Versión de bases aceptada')),
            new ExportColumnDefinition('ip', new TranslatableMessage('IP')),
        ];
    }

    public function filterFormClass(): string
    {
        return ContestEntriesExportType::class;
    }

    public function createFilter(): object
    {
        $contestId = $this->string($this->requestStack->getCurrentRequest()?->query->get('contestId'));

        return new ContestEntriesExportFilter(null === $contestId ? null : new ContestId($contestId));
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof ContestEntriesExportFilter) {
            return [];
        }

        return [
            'contestId' => $filter->contestId instanceof ContestId ? (string) $filter->contestId : null,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $contestId = $this->string($criteria['contestId'] ?? null);

        return new ContestEntriesExportFilter(null === $contestId ? null : new ContestId($contestId));
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof ContestEntriesExportFilter || !$filter->contestId instanceof ContestId) {
            return;
        }

        $contest = $this->contestRepository->find($filter->contestId);

        if (!$contest instanceof Contest) {
            return;
        }

        $countries = $this->contestCountries->countries();
        $hostNames = [];

        foreach ($contest->hostReferences() as $host) {
            $hostNames[$host->key()] = $this->hostLocator->name($host);
        }

        $entryFilter = new ContestEntryFilter(contestId: $contest->id(), limit: 0);
        $entryFilter->orderWay = 'ASC';

        foreach ($this->contestEntryRepository->findByFilter($entryFilter) as $entry) {
            if ($entry->isPendingPayment()) {
                continue;
            }

            $hostNames[$entry->host()->key()] ??= $this->hostLocator->name($entry->host());

            yield $this->row($entry, $countries, $hostNames);
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new Contest(
            id: ContestId::generate(),
            accountId: $this->currentAccountIdProvider->currentAccountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        if (!$filter instanceof ContestEntriesExportFilter || !$filter->contestId instanceof ContestId) {
            return false;
        }

        $contest = $this->contestRepository->find($filter->contestId);

        if (!$contest instanceof Contest) {
            return false;
        }

        return $this->authorizer->canDoThat($contest, Action::Export, $user);
    }

    /**
     * @param array<string, string> $countries
     * @param array<string, string> $hostNames
     *
     * @return array<string, int|string>
     */
    private function row(ContestEntry $entry, array $countries, array $hostNames): array
    {
        $country = $entry->country();
        $consent = $entry->consentProof();

        return [
            'entryNumber' => $entry->entryNumber() ?? '',
            'name' => $entry->name(),
            'email' => $entry->email() ?? '',
            'phone' => $entry->phone() ?? '',
            'country' => null === $country ? '' : ($countries[$country] ?? $country),
            'locale' => $entry->locale()->value,
            'host' => $this->translator->trans($hostNames[$entry->host()->key()] ?? 'Calculadora retirada del concurso'),
            'status' => $this->translator->trans($entry->status()->value, [], 'contestEntryStatus'),
            'winner' => $entry->isWinner() ? $this->translator->trans('Sí') : $this->translator->trans('No'),
            'registeredAt' => $entry->registeredAt()->format('d/m/Y H:i'),
            'confirmedAt' => $entry->confirmedAt()?->format('d/m/Y H:i') ?? '',
            'disqualifiedAt' => $entry->disqualifiedAt()?->format('d/m/Y H:i') ?? '',
            'disqualificationReason' => $entry->disqualificationReason() ?? '',
            'consentedAt' => $consent?->consentedAt()?->format('d/m/Y H:i') ?? '',
            'termsVersion' => $consent?->policyVersion() ?? '',
            'ip' => $consent?->ip() ?? '',
        ];
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return trim($value);
    }
}
