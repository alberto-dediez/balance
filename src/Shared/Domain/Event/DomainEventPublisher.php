<?php

namespace App\Shared\Domain\Event;

use BadMethodCallException;

final class DomainEventPublisher
{
    protected static $instance;

    protected array $subscribers; 

    protected array $queue = [];

    protected bool $isLock = false;
    
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
        if ($this->isLock) {
            $this->queue[] = $event;

            return ;
        }

        if (count($this->subscribers) > 0)  {
            foreach ($this->subscribers as $subscriber) {
                if ($subscriber->isSubscribedTo($event)) {
                    $subscriber->handle($event);
                }
            }
        }
    }

    public function lock()
    {
        $this->isLock = true;
    }

    public function unlock()
    {
        $this->isLock = false;

        if (count($this->queue) > 0) {
            foreach ($this->queue as $i => $event) {
                $this->publish($event);

                unset($this->queue[$i]);
            }
        }
    }
}