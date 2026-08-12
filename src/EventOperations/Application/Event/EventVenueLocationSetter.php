<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event;

use App\EventOperations\Domain\Model\Event\Event;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class EventVenueLocationSetter
{
    public function __construct(private readonly PlaceFinder $placeFinder) {}

    public function resolve(Event $event, ?string $placeReference): EventVenueLocationChange
    {
        $reference = null !== $placeReference ? trim($placeReference) : '';

        if ('' === $reference) {
            if (null === $event->venuePlaceReference()) {
                return EventVenueLocationChange::none();
            }

            return EventVenueLocationChange::clear();
        }

        if ($reference === $event->venuePlaceReference()) {
            return EventVenueLocationChange::none();
        }

        $place = $this->placeFinder->resolve($reference, LocaleGetter::locale());

        if (null === $place) {
            throw new \InvalidArgumentException('The venue location could not be resolved.');
        }

        return EventVenueLocationChange::to($place);
    }

    public function apply(Event $event, EventVenueLocationChange $change): void
    {
        if (!$change->changed) {
            return;
        }

        $event->setVenueLocation(
            $change->reference,
            $change->label,
            $change->latitude,
            $change->longitude,
        );
    }
}
