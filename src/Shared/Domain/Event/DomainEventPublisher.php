<?php

namespace App\Shared\Domain\Event;

use BadMethodCallException;

final class DomainEventPublisher
{
    protected static $instance;

    protected array $subscribers; 
    
    private function __construct()
    {
        $this->subscribers = [];
    }

    public static function instance(): DomainEventPublisher
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }
        
        return static::$instance;
    }

    public function __clone()
    {
        throw new BadMethodCallException("Clone is not supported");
    }

    public function subscribe(DomainEventSubscriber $subscriber) 
    {
        if (!isset($this->subscribers[get_class($subscriber)])) {
            $this->subscribers[get_class($subscriber)] = $subscriber;    
        }        
    }

    public function reset()
    {
        $this->subscribers = [];
    }

    public function publish(DomainEvent $event)
    { 
        if (count($this->subscribers) > 0)  {
            foreach ($this->subscribers as $subscriber) {
                if ($subscriber->isSubscribedTo($event)) {
                    $subscriber->handle($event);
                }
            }
        }
    }
}