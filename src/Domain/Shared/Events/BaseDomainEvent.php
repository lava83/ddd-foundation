<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Domain\Shared\Events;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Lava83\DddFoundation\Domain\Shared\Contracts\DomainEvent;
use Lava83\DddFoundation\Domain\Shared\ValueObjects\Id;

abstract class BaseDomainEvent implements DomainEvent
{
    private CarbonImmutable $occurredOn;

    private int $eventVersion;

    /**
     * @param  Collection<string, mixed>  $eventData
     */
    public function __construct(
        private Id $aggregateId,
        private Collection $eventData = new Collection,
        int $eventVersion = 1
    ) {
        $this->eventVersion = $eventVersion;
        $this->occurredOn = CarbonImmutable::now();
    }

    public function aggregateId(): Id
    {
        return $this->aggregateId;
    }

    public function occurredOn(): CarbonImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return Collection<string, mixed>
     */
    public function eventData(): Collection
    {
        return $this->eventData;
    }

    public function eventVersion(): int
    {
        return $this->eventVersion;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'aggregate_id' => $this->aggregateId,
            'event_data' => $this->eventData->toArray(),
            'event_version' => $this->eventVersion,
            'occurred_on' => $this->occurredOn->format(DateTimeImmutable::ATOM),
        ];
    }
}
