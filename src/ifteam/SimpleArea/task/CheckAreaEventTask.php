<?php

namespace ifteam\SimpleArea\task;

use pocketmine\scheduler\Task;
use ifteam\SimpleArea\database\user\UserProperties;

class CheckAreaEventTask extends Task {
	public $event;
	private $owner;
	public function __construct(UserProperties $owner, $event) {
		$this->owner = $owner;
		$this->event = $event;
	}
	public function onRun($currentTick) {
		if (! $this->event->isCancelled ())
			$this->owner->applyAreaEvent ( $this->event );
	}
}

?>