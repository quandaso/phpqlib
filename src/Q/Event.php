<?php
/**
 * @author quantm.tb@gmail.com
 * @date: 2/13/2017 10:06 AM
 */

namespace Q;


class Event
{
    private $eventHandlers = [];
    private $name;
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getEventName() {
        return $this->name;
    }



    /**
     * @param $event
     * @param $args
     * @return bool
     */
    public function emit($event, $args = []) {
        if (!empty ($this->eventHandlers[$event])) {
            foreach ($this->eventHandlers[$event] as $handler) {
                call_user_func_array($handler, $args);
            }
            return true;
        }

        return false;
    }

    /**
     * @param $event string exception|error
     * @param $handler
     */
    public function on($event, $handler) {
        $this->eventHandlers[$event][] = $handler;
    }
}