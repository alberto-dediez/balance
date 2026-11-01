<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class EventCarbonFootprintChoiceType extends AbstractType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private readonly EventRepository $eventRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new AggregateRootIdTransformer(EventCarbonFootprintId::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $loggedAccount = $this->loggedAccount;
        $account = $loggedAccount();

        if (null === $account) {
            $resolver->setDefaults([
                'choices' => [],
            ]);

            return;
        }

        $footprints = array_filter(
            $this->eventCarbonFootprintRepository->findByFilter(new EventCarbonFootprintFilter(
                accountId: $account->id(),
                limit: 0,
            )),
            static fn(EventCarbonFootprint $footprint): bool => $footprint->isCompleted(),
        );

        $choices = [];

        foreach ($footprints as $footprint) {
            $choices[$this->label($footprint, $choices)] = (string) $footprint->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }

    /** @param array<string, string> $choices */
    private function label(EventCarbonFootprint $footprint, array $choices): string
    {
        $eventId = $footprint->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;

        $label = null !== $event ? sprintf('%s — %d', $event->name(), $footprint->calculationYear()) : (string) $footprint->id();

        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }
}
