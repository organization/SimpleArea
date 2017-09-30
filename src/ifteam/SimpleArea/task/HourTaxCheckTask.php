<?php

namespace ifteam\SimpleArea\task;

use pocketmine\scheduler\Task;
use ifteam\SimpleArea\api\AreaTax;

class HourTaxCheckTask extends Task {
	/**
	 *
	 * @var AreaTax
	 */
	private $owner;
	public function __construct(AreaTax $owner) {
		$this->owner = $owner;
	}
	public function onRun(int $currentTick) {
		$this->owner->payment ();
	}
}
?>