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

use Arikaim\Core\Interfaces\Job\JobInterface;
use Arikaim\Core\Queue\Jobs\Job;

/**
 * Event dispatch job
 */
class EventDispatchJob extends Job implements JobInterface 
{
    /**
     * Event manager ref
     *
     * @var object
     */
    protected $eventManager;

    /**
     * Constructor
     *
     * @param array $params
     * @param object $eventManager
     */
    public function __construct(array $params, object &$eventManager)
    {
        parent::__construct(null,$params);

        $this->eventManager = $eventManager;
    }
    
     /**
     * Init job
     *
     * @return void
     */
    public function init(): void
    {        
        $this->setName('event.dispatch');
    }

    /**
     * Job code
     *
     * @return mixed
     */
    public function execute()
    {
        $eventName = $this->getParam('event_name');
        $eventParams = $this->getParam('event_name',[]);
        if (empty($eventName) == true) {
            return false;
        }

        $this->eventManager->dispatch($eventName,$eventParams);
    }

     /**
     * Init descriptor properties 
     *
     * @return void
     */
    protected function initDescriptor(): void
    {
        $this->descriptor->set('title','Event dispatch');
        $this->descriptor->set('description','Event dispatch job.');

        $this->descriptor->set('allow.admin.config',false);
        $this->descriptor->set('allow.console.push',true);

        // properties
        $this->descriptor->collection('parameters')->property('event_name',function($property) {
            $property
                ->title('Event Name')
                ->type('text')   
                ->required(true)                    
                ->value('');                         
        });
        $this->descriptor->collection('parameters')->property('event_params',function($property) {
            $property
                ->title('Event Params')
                ->type('list')   
                ->required(false)                    
                ->value(null);                         
        });
      
    }
}
