<?php

namespace ifteam\SimpleArea\task;

use ifteam\SimpleArea\database\user\UserProperties;
use pocketmine\scheduler\Task;

class CheckAreaEventTask extends Task {
    public $event;
    private $owner;

    public function __construct(UserProperties $owner, $event) {
        $this->owner = $owner;
        $this->event = $event;
    }

    public function onRun(int $currentTick) {
        if (!$this->event->isCancelled())
            $this->owner->applyAreaEvent($this->event);
    }
}

?>