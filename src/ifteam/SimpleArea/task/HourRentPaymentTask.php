<?php

namespace ifteam\SimpleArea\task;

use ifteam\SimpleArea\api\RentPayment;
use pocketmine\scheduler\Task;

class HourRentPaymentTask extends Task {
	/**
	 *
	 * @var RentPayment
	 */
	private $owner;

	public function __construct(RentPayment $owner) {
		$this->owner = $owner;
	}

	public function onRun(int $currentTick) {
		$this->owner->payment();
	}
}

?>