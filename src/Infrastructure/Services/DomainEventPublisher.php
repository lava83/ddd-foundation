<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Services;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Lava83\DddFoundation\Domain\Shared\Contracts\DomainEvent;

class DomainEventPublisher
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @param  Collection<int, DomainEvent>  $events
     */
    public function publishEvents(Collection $events): void
    {
        $events->each(fn (DomainEvent $event) => $this->publishEvent($event));
    }

    public function publishEvent(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
