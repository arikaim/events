<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Events;

use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Events\Event;
use Arikaim\Core\Interfaces\Events\EventInterface;
use Arikaim\Core\Interfaces\Events\EventSubscriberInterface;
use Arikaim\Core\Interfaces\EventDispatcherInterface;
use Arikaim\Core\Interfaces\EventRegistryInterface;
use Arikaim\Core\Interfaces\SubscriberRegistryInterface;

/**
 * Dispatch and manage events and event subscribers.
*/
class EventsManager implements EventDispatcherInterface
{
    /**
     * Subscribers
     *
     * @var array
     */
    protected $subscribers;

    /**
     * Event Registry
     *
     * @var EventRegistryInterface
     */
    protected $eventRegistry;

    /**
     * Subscriber Registry
     *
     * @var SubscriberRegistryInterface
     */
    protected $subscriberRegistry;

    /**
     * Constructor
     */
    public function __construct(EventRegistryInterface $eventRegistry, SubscriberRegistryInterface $subscriberRegistry)
    {
        $this->subscribers = [];
        $this->eventRegistry = $eventRegistry;
        $this->subscriberRegistry = $subscriberRegistry;
    }
    
    /**
     * Unregister events for extension (removes events from db table)
     *
     * @param string $extension
     * @return void
     */
    public function unregisterEvents($extension)
    {
        return $this->eventRegistry->deleteEvents($extension);       
    }

    /**
     * Unregister event
     *
     * @param string $eventName
     * @return bool
     */
    public function unregisterEvent($eventName)
    {
        return $this->eventRegistry->deleteEvent($eventName);
    }

    /**
     * Add event to events db table.
     *
     * @param string $name
     * @param string $title
     * @param string $extension
     * @param string $description
     * @return bool
     */
    public function registerEvent($name, $title, $extension = null, $description = null)
    {
        if (($this->isCoreEvent($name) == true) && ($extension != null)) {
            // core events can't be registered from extension
            return false;
        }
        
        return $this->eventRegistry->registerEvent($name,$title,$extension,$description);
    }

    /**
     * Check if event name is core event name.
     *
     * @param string $name
     * @return boolean
     */
    public function isCoreEvent($name)
    {
        return (substr($name,0,4) == "core") ? true : false;          
    }

    /**
     * Register event subscriber.
     *
     * @param string $class
     * @param string $extension
     * @return bool
     */
    public function registerSubscriber($class, $extension)
    {
        $subscriber = Factory::createEventSubscriber($class,$extension);
        if ($subscriber != false) {
            $events = $subscriber->getSubscribedEvents();
            foreach ($events as $event) {
                $this->subscribe($event['event_name'],$class,$extension,$event['priority'],$event['handler_method']);
            }
            return true;
        }

        return false;
    }

    /**
     * Save subscriber info to db table. 
     *
     * @param string $eventName
     * @param string $class
     * @param string|null $extension
     * @param integer $priority
     * @return bool
     */
    public function subscribe($eventName, $class, $extension = null, $priority = 0, $hadnlerMethod = null)
    {
        return $this->subscriberRegistry->addSubscriber($eventName,$class,$extension,$priority,$hadnlerMethod);
    }

    /**
     * Subscribe callback
     *
     * @param string $eventName
     * @param Closure $callback
     * @param boolean $single
     * @return void
     */
    public function subscribeCallback($eventName, $callback, $single = false)
    {        
        if (isset($this->subscribers[$eventName]) == false) {
            $this->subscribers[$eventName] = [];
        }
        if ($single == true) {
            $this->subscribers[$eventName] = [$callback];
        } else {
            array_push($this->subscribers[$eventName],$callback);
        }
    }

    /**
     * Remove event subscribers
     *
     * @param string $eventName
     * @param string $extension
     * @return bool
     */
    public function unsubscribe($eventName, $extension)
    {
        return $this->subscriberRegistry->deleteSubscribers($eventName,$extension);
    }

    /**
     * Fire event, dispatch event data to all subscribers
     *
     * @param string $eventName
     * @param array|EventInterface $event
     * @param boolean $callbackOnly
     * @param string|null $extension
     * @return array
     */
    public function dispatch($eventName, $event = [], $callbackOnly = false, $extension = null)
    {       
        if (is_object($event) == false) {
            $event = new Event($event);   
        }
        if (($event instanceof EventInterface) == false) {
            throw new \Exception("Not valid event object.", 1);
        }

        $event->setName($eventName);          
        $result = [];

        if ($callbackOnly != true) {
            // get all subscribers for event
            if (empty($extension) == false) {
                $subscribers = $this->subscriberRegistry->getExtensionSubscribers($extension,1,$eventName);   
            } else {
                $subscribers = $this->subscriberRegistry->getSubscribers($eventName,1);       
            }            
            $result = $this->executeEventHandlers($subscribers,$event);  
        }

        // run subscribed callback
        $callbackResult = $this->runCallback($eventName,$event);

        return array_merge($result,$callbackResult);
    }

    /**
     * Execute closure subscribers
     *
     * @param string $eventName
     * @param EventInterface $event
     * @return array
     */
    private function runCallback($eventName, $event)
    {
        if (isset($this->subscribers[$eventName]) == false) {
            return [];
        }
        $result = [];
        foreach ($this->subscribers[$eventName] as $callback) {
            if (Utils::isClosure($callback) == true) {
                $callbackResult = $callback($event);
                array_push($result,$callbackResult);
            }                  
        }

        return $result;
    }

    /**
     * Run event handlers
     *
     * @param array $eventSubscribers
     * @param EventInterface $event
     * @return array 
     */
    private function executeEventHandlers(array $eventSubscribers, Event $event)
    {       
        if (empty($eventSubscribers) == true) {
            return [];
        }
        $result = [];
        foreach ($eventSubscribers as $item) {
            $subscriber = Factory::createInstance($item['handler_class']);
            $handlerMethod = (empty($item['handler_method']) == true) ? 'execute' : $item['handler_method'];
           
            if (is_object($subscriber) == true && $subscriber instanceof EventSubscriberInterface) {
                $eventResult = $subscriber->{$handlerMethod}($event);
                if (empty($eventResult) == false) {
                    $result[] = $eventResult;
                }              
            }
        }

        return $result;
    }
}
