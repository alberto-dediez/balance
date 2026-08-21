<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form\Type;

use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly EventRepository $eventRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $loggedAccount = $this->loggedAccount;

        $account = $loggedAccount();

        $events = $this->eventRepository->findByFilter(
            new EventFilter(
                accountId: null == $account ? null : $account->id(),
                limit: 0,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($events),
            'class' => EventId::class,
        ]);
    }
}
