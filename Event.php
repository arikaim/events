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

use Arikaim\Core\Interfaces\Events\EventInterface;
use Arikaim\Core\Collection\Collection;

use Arikaim\Core\Collection\Traits\Descriptor;

/**
 * Base event class
*/
class Event implements EventInterface
{
    use Descriptor;

    /**
     * Event name
     *
     * @var string
     */
    protected $name;

    /**
     * Event title
     *
     * @var string|null
     */
    protected $title;

    /**
     * Event description
     *
     * @var string|null
     */
    protected $description;

    /**
     * Event parameters
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Event propagation
     *
     * @var boolean
     */
    protected $propagation = false;

    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct(array $params = []) 
    {
        $this->parameters = $params;   
        $this->setDescriptorClass('Arikaim\\Core\\Events\\EventDescriptor');
        $this->init();         
    }

    /**
     * Init event
     *
     * @return void
     */
    protected function init(): void
    {
    }

    /**
     * Init descriptor properties 
     *
     * @return void
     */
    protected function initDescriptor(): void
    {
    }

    /**
     * Set event name
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {   
        $this->name = $name;
    }

    /**
     * Set event title
     *
     * @param string $name
     * @return void
     */
    public function setTitle(string $title): void
    {   
        $this->title = $title;
    }

    /**
     * Set event description
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {   
        $this->description = $description;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get event description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get event title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Setop event propagation
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagation = true;
    }

    /**
     * Return true if event propagation is disabled
     *
     * @return boolean
     */
    public function isStopped(): bool
    {
        return $this->propagation;
    }

    /**
     * Set event parameter
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setParameter(string $name, $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Return event parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Return params array
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = $this->parameters;
        $result['event_name'] = $this->getName();
        
        return $result;
    }

    /**
     * Return collection object with event params
     *
     * @return \Collection
     */
    public function toCollection()
    {
        return new Collection($this->parameters);
    }

    /**
     * Return parameter
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getParameter(string $name, $default = null) 
    {
        return $this->parameters[$name] ?? $default;         
    }

    /**
     * Return true if parameter exist
     *
     * @param string $name
     * @return boolean
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);      
    }
}
