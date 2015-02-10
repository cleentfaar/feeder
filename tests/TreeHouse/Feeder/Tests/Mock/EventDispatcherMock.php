<?php

namespace TreeHouse\Feeder\Tests\Mock;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherMock extends EventDispatcher
{
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @inheritdoc
     */
    public function dispatch($eventName, Event $event = null)
    {
        $this->events[$eventName][] = $event;
    }

    /**
     * @return array
     */
    public function getDispatchedEvents()
    {
        return $this->events;
    }
}
