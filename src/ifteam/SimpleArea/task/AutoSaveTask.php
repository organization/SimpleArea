<?php

namespace ifteam\SimpleArea\task;

use ifteam\SimpleArea\SimpleArea;
use pocketmine\scheduler\Task;

class AutoSaveTask extends Task {
	private $owner;

	public function __construct(SimpleArea $owner) {
		$this->owner = $owner;
	}

	public function onRun(int $currentTick) {
		$this->owner->autoSave();
	}
}

?>
