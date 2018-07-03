<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/23
 * Time: 17:59
 */

namespace choate\yii2\components\db;


use yii\base\Event;

abstract class AgentEvent extends Event
{
    public function handler() {
        Event::trigger(get_class($this), $this->getAgentName(), $this);
    }

    abstract public function getAgentName();
}