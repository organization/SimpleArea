<?php

namespace ifteam\SimpleArea\task;

use pocketmine\scheduler\PluginTask;
use ifteam\SimpleArea\SimpleArea;

class AutoSaveTask extends PluginTask {
	public function __construct(SimpleArea $owner) {
		parent::__construct ( $owner );
	}
	public function onRun(int $currentTick) {
		$this->getOwner ()->autoSave ();
	}
}

?>