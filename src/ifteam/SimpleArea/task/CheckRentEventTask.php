<?php

namespace ifteam\SimpleArea\task;

use ifteam\SimpleArea\database\user\UserProperties;
use pocketmine\scheduler\Task;

class CheckRentEventTask extends Task {
    private $owner;
    private $event;

    public function __construct(UserProperties $owner, $event) {
        $this->owner = $owner;
        $this->event = $event;
    }

    public function onRun(int $currentTick) {
        if (!$this->event->isCancelled())
            $this->owner->applyRentEvent($this->event);
    }
}

?>